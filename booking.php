<?php
require_once 'config.php';

$client = getClient();

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['access_token']) || !$_SESSION['access_token']) {
    header('Location: index.php');
    exit;
}

if (!isset($_POST['slot'])) {
    echo "Слот не выбран.";
    exit;
}

$slotStart = $_POST['slot'];
$startTime = new DateTime($slotStart);
$endTime = clone $startTime;
$endTime->modify('+1 hour');

$service = new Google_Service_Calendar($client);
$calendarId = 'sanzhar.kirgizbaev@gmail.com'; 


$event = new Google_Service_Calendar_Event([
    'summary' => 'Booked slot',
    'start' => [
        'dateTime' => $startTime->format(DateTime::ATOM),
        'timeZone' => 'Turkey' 
    ],
    'end' => [
        'dateTime' => $endTime->format(DateTime::ATOM),
        'timeZone' => 'Turkey'
    ]
]);

try {
    $createdEvent = $service->events->insert($calendarId, $event);
    echo "<div style='marginTop:40vh; text-align: center; font-size: 24px;'>Слот успешно забронирован. <a href='index.php'>Вернуться к списку</a></div>";
} catch (Exception $e) {
    echo "Ошибка при бронировании: " . $e->getMessage();
}
?>
