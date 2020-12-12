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
                if ($data = login($connection, $_POST['email'], $password)) {
                    $output['success'] = true;
                    $output['user_id'] = $data['id'];
                }
            }
        }

        if ($_GET['action'] === 'register') {
            if (isset($_POST['email'], $_POST['password'])) {
                $output['success'] = false;
                $errors = [];
                if (strlen($_POST['password']) <= 8) {
                    $errors[] = 'Slaptazodis turi susidaryti bent is 8 simboliu.';
                }
                if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Neteisingas el. pasto formatas';
                }
                if (is_array(findUserByEmail($connection, $_POST['email']))) {
                    $errors[] = 'El. pastas jau uzimtas.';
                }
                if (count($errors) !== 0) {
                    $output['errors'] = $errors;
                } else {
                    $output['success'] = insertUser(
                        $connection, $_POST['email'],
                        substr(hash('sha256', $_POST['password']), 5, 32)
                    );
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
            $testsResponse = [];
            foreach ($tests as $test) {
                $testsResponse[] = [
                    'test_id' => $test['id'],
                    'user_id' => $test['user_id'],
                    'test_data' => $test['data'],
                ];
            }
            $output['success'] = true;
            $output['speed_tests'] = $testsResponse;
        }

        if ($_GET['action'] === 'get_user_settings') {
            $output['success'] = false;
            if (isset($_GET['user_id'])) {
                $settings = getUserSettings($connection, $_GET['user_id'])[0];
                $output['success'] = true;
                $output['settings']['setting_id'] = $settings['id'];
                $output['settings']['user_id'] = $settings['user_id'];
                $output['settings']['can_compare_tests'] = $settings['can_compare_tests'] == 1 ? true : false;
            }
        }

        if ($_GET['action'] === 'update_user_settings') {
            $output['success'] = false;
            if (isset($_POST['user_id'], $_POST['can_compare_tests'])) {
                $canCompareTests = $_POST['can_compare_tests'] == 'true' ? 1 : 0;
                updateUserSettings($connection, $_POST['user_id'], $canCompareTests);
                $output['success'] = true;
                $output['can_compare_tests'] = $canCompareTests;
            }
        }

    }
} catch(Exception $e) {
    $output['success'] = false;
    $output['message'] = 'Unexpected error.';
}

echo json_encode($output);

function login($connection, string $email, string $password): ?array
{
    $stmt = $connection->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        return $data;
    }

    return null;
}

function insertSpeedData($connection, int $userId, string $speedTestData): bool
{
    $stmt = $connection->prepare('INSERT INTO speed_test (user_id, data) VALUES(?, ?)');
    $stmt->execute([$userId, $speedTestData]);

    return true;
}

function fetchSpeedTests($connection): array
{
    $stmt = $connection->prepare('
        SELECT `speed_test`.`id`, `speed_test`.`user_id`, `speed_test`.`data` FROM `speed_test`
        JOIN settings ON speed_test.user_id=settings.user_id
        WHERE settings.can_compare_tests=1 
    ');
    $stmt->execute([]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUserSpeedTests($connection, int $userId): array
{
    $stmt = $connection->prepare('
        SELECT `speed_test`.`id`, `speed_test`.`user_id`, `speed_test`.`data` FROM `speed_test`
        JOIN settings ON speed_test.user_id=settings.user_id
        WHERE settings.can_compare_tests=1 AND settings.user_id=?
    ');
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserSettings($connection, int $userId)
{
    $stmt = $connection->prepare('SELECT * FROM settings WHERE user_id=?');
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateUserSettings($connection, int $userId, int $canCompareTests)
{
    $stmt = $connection->prepare('UPDATE `settings` SET `can_compare_tests` = ? WHERE user_id=?;');
    $stmt->execute([$canCompareTests, $userId]);

    return true;
}

function findUserByEmail($connection, string $email)
{
    $stmt = $connection->prepare('SELECT * FROM user WHERE email = ?');
    $stmt->execute([$email]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertUser($connection, string $email, string $password): bool
{
    $stmt = $connection->prepare('INSERT INTO user (email, password) VALUES(?, ?)');
    $stmt->execute([$email, $password]);

    return true;
}
