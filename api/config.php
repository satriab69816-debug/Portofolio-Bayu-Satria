<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'bayu_portfolio';
const DB_USER = 'root';
const DB_PASS = '';

// Password admin: 110724 (disimpan sebagai hash, bukan plaintext).
const ADMIN_PASSWORD_HASH = '$2y$12$RUckY.TtbI2oaDSsamH4Q.OCZdes11Wsm5Sh8Gk5LnOo8Zg3xGhoW';

const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
const MAX_IMAGES_PER_PROJECT = 30;
const ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

function start_app_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('bayu_admin');
        session_start();
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_admin(): void {
    start_app_session();
    if (empty($_SESSION['admin_authenticated'])) {
        json_response(['ok' => false, 'message' => 'Unauthorized'], 401);
    }
}

function slugify(string $text): string {
    $text = trim($text);
    $text = preg_replace('/[^a-zA-Z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return strtolower($text) ?: 'project';
}

function safe_upload_extension(string $tmp): string {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset(ALLOWED_MIMES[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WebP.');
    }
    return ALLOWED_MIMES[$mime];
}
