<?php


use App\Database;
use App\DateFormat;
use App\Helper;
use App\Response;
use telesign\sdk\messaging\MessagingClient;

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
        'name' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
    ];

    $errors = [];
    foreach ($fields as $key => $field) {
        if (empty($field)) {
            $errors['errors'][] = "Field <strong>{$key}</strong> cannot be blank";
        }
    }

    if (!empty($errors)) {
        Response::json($errors, 400);
        exit;
    }

    $roomId = $fields['room_id'];
    $dateFrom = DateFormat::asDbFormat($fields['dateFrom']);
    $dateTo = DateFormat::asDbFormat($fields['dateTo']);
    $userName = $fields['name'];
    $userPhone = $fields['phone'];
    $userEmail = $fields['email'];

    $query = "SELECT *, u.name AS user_name FROM bookings b" .
        " JOIN user u ON b.user_id = u.id" .
        " WHERE (('{$dateFrom}' BETWEEN date_from AND date_to) OR ('{$dateTo}' BETWEEN date_from AND date_to))" .
        " AND room_id={$roomId}";

    $bookings = $db->dbConnection()->query($query)->fetchAll();

    if (!empty($bookings)) {
        $busyDatePeriods = array_map(function ($item) {
            return "from <strong>{$item['date_from']}</strong>"
            . " to <strong>{$item['date_to']}</strong> by <strong>{$item['user_name']}</strong>";
        }, $bookings);

        $busyPeriodString = implode(", ", $busyDatePeriods);

        $response = ['status' => 'no', 'message' => "The Room #{$roomId} is busy {$busyPeriodString}"];
        Response::json($response);
        exit;
    } else {
//        $response = ['status' => 'yes', 'message' => "The Room #{$roomId} is available for this period"];

        $connection = $db->dbConnection();
        try {
            $connection->beginTransaction();
            $userId = null;
            $sql = "SELECT id FROM user WHERE name='{$userName}' AND phone='{$userPhone}' AND email='{$userEmail}'";
            if ($fetchedUser = $connection->query($sql)->fetch()) {
                $userId = $fetchedUser['id'];
            } else {
                $sql = "INSERT INTO user (name, phone, email) VALUES ('{$userName}', '{$userPhone}', '{$userEmail}')";
                $connection->exec($sql);
                $userId = $connection->lastInsertId();
            }

            $bookingSql = "INSERT INTO bookings (room_id, user_id, date_from, date_to) VALUES ({$roomId}, {$userId}, '{$dateFrom}', '{$dateTo}')";
            $connection->exec($bookingSql);
            $connection->commit();

            $mailJet = new \Mailjet\Client($_ENV['EMAIL_API_KEY'], $_ENV['EMAIL_API_SECRET_KEY'], true,['version' => 'v3.1']);
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => $_ENV['EMAIL_SENDER'],
                            'Name' => 'Alif Tech Test',
                        ],
                        'To' => [
                            [
                                'Email' => $userEmail,
                                'Name' => $userName,
                            ]
                        ],
                        'Subject' => 'AlifTech Test Message',
                        'TextPart' => "The Room #{$roomId} has been successfully booked from {$dateFrom} to {$dateTo}",
                        'HTMLPart' => "",
                    ]
                ]
            ];

            $emailResponse = $mailJet->post(\Mailjet\Resources::$Email, ['body' => $body]);
            $emailStatus = null;
            if ($emailResponse->success()) {
                $emailStatus = ['status' => 'yes', 'message' => 'Email notification sent successfully!', 'data' => $emailResponse->getData()];
            } else {
                $emailStatus = ['status' => 'no', 'message' => 'Email notification was not sent!', 'data' => $emailResponse->getData()];
            }

            $smsCustomerId = $_ENV['SMS_CUSTOMER_ID'];
            $smsApiKey = $_ENV['SMS_API_KEY'];
            $smsPhoneNumber = Helper::cleanString($userPhone);
            $smsMessage = "The Room #{$roomId} was booked";
            $smsMessageType = 'ARN';
            $smsGateway = new MessagingClient($smsCustomerId, $smsApiKey);
            $smsResponse = $smsGateway->message($smsPhoneNumber, $smsMessage, $smsMessageType);

            $smsStatus = null;
            if ($smsResponse->ok) {
                $smsStatus = ['status' => 'yes', 'message' => 'SMS notification was sent successfully!'];
            } else {
                $smsStatus = ['status' => 'no', 'message' => 'SMS notification was not sent!'];
            }

            Response::json([
                'status' => 'yes',
                'message' => "The Room #{$roomId} has been successfully booked!",
                'emailStatus' => $emailStatus,
                'smsStatus' => $smsStatus,
            ]);

        } catch (Exception $e) {
            $connection->rollBack();
            Response::json($e->getMessage());
            exit;
        }
    }
}

if ($route == 'getRooms') {
    $rooms = $db->dbConnection()
        ->query("SELECT * FROM room")
        ->fetchAll();

    Response::json($rooms);
}