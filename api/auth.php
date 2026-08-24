<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
start_app_session();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    json_response(['ok' => true, 'authenticated' => !empty($_SESSION['admin_authenticated'])]);
}

$action = $_GET['action'] ?? 'login';
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    json_response(['ok' => true]);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$password = (string)($input['password'] ?? '');
if (password_verify($password, ADMIN_PASSWORD_HASH)) {
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    json_response(['ok' => true, 'authenticated' => true]);
}
json_response(['ok' => false, 'message' => 'Password salah.'], 401);
