<?php
// ============================================================
//  config.php
// ============================================================

define('DB_HOST',            'localhost');
define('DB_USER',            'root');
define('DB_PASS',            'root');
define('DB_NAME',            'quiz_db');
define('ADMIN_PASSWORD',     'admin123');
define('QUESTIONS_PER_TEST',  10);

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(503);
        die(renderFatal('Database Connection Failed', $e->getMessage()));
    }
    return $pdo;
}

function getUserIP(): string
{
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        die(renderFatal('Forbidden', 'Invalid form token. Go back and try again.'));
    }
    unset($_SESSION['csrf_token']);
}

function renderFatal(string $title, string $detail = ''): string
{
    $t = e($title);
    $d = e($detail);
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="fatal-body">
  <div class="fatal-card">
    <div class="center-icon">⚠️</div>
    <h3 class="fw-bold text-danger mt-3">{$t}</h3>
    <p class="text-muted mt-2">{$d}</p>
    <a href="index.php" class="btn btn-primary mt-3">← Go Home</a>
  </div>
</body>
</html>
HTML;
}

function pageHeader(string $title, string $cardClass = 'quiz-card'): void
{
    $t = e($title);
    $c = e($cardClass);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$t}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="{$c}">
HTML;
}

function pageFooter(array $scripts = []): void
{
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>' . PHP_EOL;
    foreach ($scripts as $src) {
        echo '<script src="' . e($src) . '"></script>' . PHP_EOL;
    }
    echo '</div></body></html>';
}