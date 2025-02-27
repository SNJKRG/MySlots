<?php
require_once 'config.php';

try {
    $client = getClient();

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (!isset($token['error'])) {
            $_SESSION['access_token'] = $token;
            if (isset($token['refresh_token'])) {
                $_SESSION['refresh_token'] = $token['refresh_token'];
            }
            header('Location: http://localhost:8000');
            exit;
        } else {
            throw new Exception('Error fetching access token: ' . $token['error']);
        }
    }

    if (!$client->getAccessToken()) {
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    $service = new Google_Service_Calendar($client);
    $calendarId = 'sanzhar.kirgizbaev@gmail.com'; 

    $optParams = array(
        'timeMin' => date('c'),
        'timeMax' => date('c', strtotime('+7 days')),
        'singleEvents' => true,
        'orderBy' => 'startTime'
    );

    $results = $service->events->listEvents($calendarId, $optParams);
    $events = $results->getItems();

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    unset($_SESSION['access_token']);
    unset($_SESSION['refresh_token']);
    header('Location: http://localhost:8000');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Запись на встречу</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
    <style>
        .fc-daygrid-day.fc-day-selected {
            background-color:rgb(255, 69, 0) !important;
            color: white; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Book an appointment</h1>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        <form id="main-form" action="" method="post">
            <div class="bio">
                <img class="bio-img" src="./me.jpeg"></img>
                <h2>Sanzhar Kirgizbaev</h2>
                <p>Math tutor</p>
                <p>Personal SAT score: 1500
                  <br>
                  Math:800
                  <br>
                  EBRW: 700
                </p>
            </div>
            <div class="selector">
                <div class="calendar-section">
                  <h3>Choose a date and time</h3>
                  <div class="calendar">
                    <input type="hidden" id="appointment_date" name="appointment_date">
                </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var calendarEl = document.querySelector('.calendar');
                        var calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth', 
                            selectable: true,
                            dateClick: function(info) {
                                document.getElementById('appointment_date').value = info.dateStr;
                                var allDays = document.querySelectorAll('.fc-daygrid-day');
                                allDays.forEach(function(day) {
                                    day.classList.remove('fc-day-selected');
                                });
                                info.dayEl.classList.add('fc-day-selected');
                                loadAvailableSlots(info.dateStr);
                            }
                        });
                        calendar.render();
                    });

                    function loadAvailableSlots(date) {
                        var events = <?php echo json_encode($events); ?>;
                        var slotsContainer = document.querySelector('.time');
                        slotsContainer.innerHTML = '';

                        var bookedSlots = new Set();
                        events.forEach(function(event) {
                            var start = event.start.dateTime || event.start.date;
                            var eventDate = new Date(start).toISOString().split('T')[0];
                            if (eventDate === date && event.summary.includes('Booked')) {
                                bookedSlots.add(start);
                            }
                        });

                        events.forEach(function(event) {
                            var start = event.start.dateTime || event.start.date;
                            var end = event.end.dateTime || event.end.date;
                            var eventDate = new Date(start).toISOString().split('T')[0];
                            if (eventDate === date && event.summary.includes('Available') && !bookedSlots.has(start)) {
                                var startTime = new Date(start);
                                var endTime = new Date(end);
                                while (startTime < endTime) {
                                    var slotEndTime = new Date(startTime);
                                    slotEndTime.setHours(slotEndTime.getHours() + 1);
                                    if (slotEndTime > endTime) {
                                        slotEndTime = endTime;
                                    }
                                    if (!event.summary.includes('Booked')) {
                                        var time = startTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                        var endTimeHour = new Date(startTime.getTime() + 60 * 60 * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                        var slotDiv = document.createElement('div');
                                        slotDiv.classList.add('event');
                                        slotDiv.innerHTML = `
                                            <form id="form-time" action="booking.php" method="post">
                                                <input type="hidden" name="slot" value="${startTime.toISOString()}">
                                                <button class="time-button" type="submit" class="book-button">${time} - ${endTimeHour}</button>
                                            </form>
                                        `;
                                        slotsContainer.appendChild(slotDiv);
                                        startTime.setHours(startTime.getHours() + 1);
                                    }
                                }
                            }
                        });
                    }
                </script>
                <div class="time">
                </div>
            </div>
        </form>
    </div>
</body>
</html>
