<?php


use App\Database;
use App\DateFormat;
use App\Response;

require __DIR__ . './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s',
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NAME']
);
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

$db = new Database($dsn, $user, $password);

$route = $_GET['r'];
if ($route == 'checkRoom') {
    $fields = [
        'room_id' => $_POST['room_id'] ?? '',
        'dateFrom' => $_POST['date_from'] ?? '',
        'dateTo' => $_POST['date_to'] ?? '',
    ];

    $errors = [];
    foreach ($fields as $key => $field) {
        if (empty($field)) {
            $errors['errors'][] = "field {$key} cannot be blank";
        }
    }

    if (!empty($errors)) {
        Response::json($errors, 400);
        exit;
    }

    $roomId = $fields['room_id'];
    $dateFrom = DateFormat::asDbFormat($fields['dateFrom']);
    $dateTo = DateFormat::asDbFormat($fields['dateTo']);

    $query = "SELECT * FROM bookings" .
        " WHERE (('{$dateFrom}' BETWEEN date_from AND date_to) OR ('{$dateTo}' BETWEEN date_from AND date_to))" .
        " AND room_id={$roomId}";

    $bookings = $db->dbConnection()->query($query)->fetchAll();
    if (!empty($bookings)) {
        $response = ['status' => 'no', 'message' => "The Room #{$roomId} is busy for this period"];
        Response::json($response);
        exit;
    } else {
        $response = ['status' => 'yes', 'message' => "The Room #{$roomId} is available for this period"];
    }
}

if ($route == 'getRooms') {
    $rooms = $db->dbConnection()
        ->query("SELECT * FROM room")
        ->fetchAll();

    Response::json($rooms);
}