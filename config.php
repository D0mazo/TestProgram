<?php

define('DB_HOST',           'localhost');
define('DB_USER',           'root');
define('DB_PASS',           '');
define('DB_NAME',           'quiz_db');

define('ADMIN_PASSWORD',    'admin123');
define('QUESTIONS_PER_TEST', 10);

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_NAME
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            die(renderFatal('Database Connection Failed', $e->getMessage()));
        }
    }
    return $pdo;
}

function getUserIP(): string
{
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
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
        die(renderFatal('Forbidden', 'CSRF token mismatch. Please go back and try again.'));
    }
}

function renderFatal(string $title, string $detail = ''): string
{
    $t = e($title);
    $d = e($detail);
    return <<<HTML
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Error – {$t}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow p-5 text-center" style="max-width:500px;width:100%">
  <div style="font-size:3rem">⚠️</div>
  <h3 class="mt-2 fw-bold text-danger">{$t}</h3>
  <p class="text-muted mt-2">{$d}</p>
  <a href="index.php" class="btn btn-primary mt-3">← Go Home</a>
</div>
</body></html>
HTML;
}

function pageHeader(string $title): void
{
    $t = e($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$t}</title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 2rem 1rem 3rem;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .quiz-card {
      background: #fff;
      border-radius: 1.25rem;
      padding: 2.5rem;
      max-width: 680px;
      width: 100%;
      box-shadow: 0 25px 60px rgba(0,0,0,.18);
      margin-top: 1.5rem;
    }
    .option-btn {
      display: block;
      width: 100%;
      text-align: left;
      padding: .85rem 1.2rem;
      margin-bottom: .6rem;
      border: 2px solid #e2e8f0;
      border-radius: .75rem;
      background: #f8fafc;
      cursor: pointer;
      font-size: 1rem;
      transition: border-color .15s, background .15s;
    }
    .option-btn:hover { border-color: #4f46e5; background: #eef2ff; }
    .option-btn.selected { border-color: #4f46e5; background: #eef2ff; font-weight:600; }
    .option-label {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2rem; height: 2rem;
      border-radius: 50%;
      background: #4f46e5;
      color: #fff;
      font-weight: 700;
      margin-right: .75rem;
      font-size: .85rem;
      flex-shrink: 0;
    }
    .progress-bar-custom {
      height: 8px;
      border-radius: 4px;
      background: #e2e8f0;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #4f46e5, #7c3aed);
      transition: width .4s ease;
      border-radius: 4px;
    }
    .badge-correct   { background:#dcfce7; color:#16a34a; }
    .badge-incorrect { background:#fee2e2; color:#dc2626; }
    .answer-row { border-radius:.75rem; padding:1rem; margin-bottom:.75rem; border:1px solid #e2e8f0; }
    .answer-row.correct   { border-color:#86efac; background:#f0fdf4; }
    .answer-row.incorrect { border-color:#fca5a5; background:#fff1f2; }
  </style>
</head>
<body>
<div class="quiz-card">
HTML;
}

function pageFooter(): void
{
    echo <<<HTML
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
}