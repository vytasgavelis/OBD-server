<?php

define("DB_SERVER", "localhost");
$dbServer = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'obd';

$dsn = 'mysql:host=' . $dbServer . ';dbname=' . $dbName;
$connection = new PDO($dsn, $dbUser, $dbPass);

$output = [];
try {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'login') {
            if (isset($_POST['email'], $_POST['password'])) {
                $output['success'] = false;
                $password = substr(hash('sha256', $_POST['password']), 5, 32);
                if (login($connection, $_POST['email'], $password)) {
                    $output['success'] = true;
                }
            }
        }

        if ($_GET['action'] === 'upload_speed_test') {
            if (isset($_POST['user_id'], $_POST['speed_test_data'])) {
                $output['success'] = false;
                if (insertSpeedData($connection, $_POST['user_id'], $_POST['speed_test_data'])) {
                    $output['success'] = true;
                }
            }
        }

        if ($_GET['action'] === 'get_tests') {
            // Fetch tests by user id
            if (isset($_GET['user_id'])) {
                $tests = fetchUserSpeedTests($connection, $_GET['user_id']);
            }
            // Fetch all tests
            else {
                $tests = fetchSpeedTests($connection);
            }
            foreach ($tests as $test) {
                $tests[] = [
                    'test_id' => $test['id'],
                    'user_id' => $test['user_id'],
                    'test_data' => $test['data'],
                ];
            }
            $output['success'] = true;
            $output['speed_tests'] = $tests;
        }

    }
} catch(Exception $e) {
    $output['success'] = false;
    $output['message'] = 'Unexpected error.';
}

echo json_encode($output);

function login($connection, string $email, string $password): bool
{
    $stmt = $connection->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        return true;
    }

    return false;
}

function insertSpeedData($connection, int $userId, string $speedTestData): bool
{
    $stmt = $connection->prepare('INSERT INTO speed_test (user_id, data) VALUES(?, ?)');
    $stmt->execute([$userId, $speedTestData]);

    return true;
}

function fetchSpeedTests($connection): array
{
    $stmt = $connection->prepare('SELECT * FROM speed_test');
    $stmt->execute([]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUserSpeedTests($connection, int $userId): array
{
    $stmt = $connection->prepare('SELECT * FROM speed_test WHERE user_id=?');
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
