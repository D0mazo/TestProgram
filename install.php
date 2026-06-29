<?php
// ============================================================
//  install.php — Paleisk VIENĄ kartą naršyklėje
//  Po to IŠTRINK šį failą!
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = 'root';
$db   = 'quiz_db';

$steps = [];

try {
    // Connect without selecting DB
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $steps[] = ['ok', 'Prisijungta prie MySQL'];

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $steps[] = ['ok', "Duombazė '$db' sukurta"];

    $pdo->exec("USE `$db`");

    // Drop old tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DROP TABLE IF EXISTS answers");
    $pdo->exec("DROP TABLE IF EXISTS test_sessions");
    $pdo->exec("DROP TABLE IF EXISTS questions");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $steps[] = ['ok', 'Senos lentelės išvalytos'];

    // Create questions table
    $pdo->exec("CREATE TABLE questions (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        question_text  TEXT    NOT NULL,
        option_a       TEXT    NOT NULL,
        option_b       TEXT    NOT NULL,
        option_c       TEXT    NOT NULL,
        option_d       TEXT    NOT NULL,
        correct_answer CHAR(1) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $steps[] = ['ok', 'Lentelė: questions ✓'];

    // Create test_sessions table
    $pdo->exec("CREATE TABLE test_sessions (
        id                     INT AUTO_INCREMENT PRIMARY KEY,
        ip_address             VARCHAR(45)  NOT NULL,
        first_name             VARCHAR(100) NOT NULL,
        last_name              VARCHAR(100) NOT NULL,
        session_token          VARCHAR(64)  NOT NULL,
        question_order         TEXT         NOT NULL,
        current_question_index TINYINT UNSIGNED DEFAULT 0,
        is_completed           TINYINT(1)   DEFAULT 0,
        score                  TINYINT UNSIGNED DEFAULT 0,
        started_at             DATETIME     DEFAULT CURRENT_TIMESTAMP,
        completed_at           DATETIME     DEFAULT NULL,
        UNIQUE KEY uq_ip (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $steps[] = ['ok', 'Lentelė: test_sessions ✓'];

    // Create answers table
    $pdo->exec("CREATE TABLE answers (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        session_id      INT       NOT NULL,
        question_id     INT       NOT NULL,
        selected_answer CHAR(1)   NOT NULL,
        is_correct      TINYINT(1) NOT NULL DEFAULT 0,
        answered_at     DATETIME  DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (session_id)  REFERENCES test_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $steps[] = ['ok', 'Lentelė: answers ✓'];

    // Insert questions
    $questions = [
        ['What does HTML stand for?', 'HyperText Markup Language', 'HighText Machine Language', 'Hyperlink and Text Markup Language', 'Home Tool Markup Language', 'A'],
        ['Which language runs natively in the browser?', 'PHP', 'Python', 'JavaScript', 'Ruby', 'C'],
        ['What does SQL stand for?', 'Sequential Query Logic', 'Strong Question Language', 'Structured Query Language', 'Standard Query Library', 'C'],
        ['Which HTTP method retrieves data without a body?', 'POST', 'PUT', 'DELETE', 'GET', 'D'],
        ['What does PHP stand for (official recursive meaning)?', 'Personal Home Page', 'PHP: Hypertext Preprocessor', 'Professional Hypertext Protocol', 'Private Host Platform', 'B'],
        ['What is the default HTTP port?', '21', '443', '80', '8080', 'C'],
        ['Which data structure follows LIFO?', 'Queue', 'Linked List', 'Array', 'Stack', 'D'],
        ['What does API stand for?', 'Application Protocol Interface', 'Applied Programming Integration', 'Application Programming Interface', 'Automated Process Interface', 'C'],
        ['Which of the following is a NoSQL database?', 'PostgreSQL', 'MySQL', 'SQLite', 'MongoDB', 'D'],
        ['What is the time complexity of binary search?', 'O(1)', 'O(n)', 'O(log n)', 'O(n²)', 'C'],
        ['In OOP, what does encapsulation mean?', 'Inheriting properties from a parent class', 'Bundling data and its methods inside one class', 'Creating multiple instances of a class', 'Overloading operators', 'B'],
        ['Which CSS property changes text colour?', 'font-color', 'text-color', 'foreground', 'color', 'D'],
        ['What is a foreign key in a relational database?', 'An encrypted primary key', 'A key imported from another database', 'A field referencing the primary key of another table', 'The second unique identifier in a table', 'C'],
        ['What does CRUD stand for?', 'Create, Read, Update, Delete', 'Copy, Retrieve, Upload, Download', 'Clone, Read, Update, Deploy', 'Create, Retrieve, Upload, Delete', 'A'],
        ['Which PHP function starts a session?', 'start_session()', 'begin_session()', 'session_start()', 'init_session()', 'C'],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($questions as $q) {
        $stmt->execute($q);
    }
    $steps[] = ['ok', count($questions) . ' klausimų įkelti ✓'];

    $success = true;

} catch (PDOException $e) {
    $steps[] = ['err', 'KLAIDA: ' . $e->getMessage()];
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installiacija</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: linear-gradient(135deg,#4f46e5,#7c3aed); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
        .card { border-radius:1rem; padding:2rem; max-width:520px; width:100%; }
    </style>
</head>
<body>
<div class="card shadow">
    <h3 class="fw-bold mb-4">⚙️ Installiacija</h3>

    <?php foreach ($steps as [$type, $msg]): ?>
        <div class="d-flex align-items-center mb-2">
            <span class="me-2"><?= $type === 'ok' ? '✅' : '❌' ?></span>
            <span class="<?= $type === 'err' ? 'text-danger fw-bold' : '' ?>"><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endforeach; ?>

    <hr class="my-3">

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Viskas padaryta!</strong> Duombazė sukurta sėkmingai.
        </div>
        <a href="index.php" class="btn btn-primary w-100 mb-2">🚀 Atidaryti Quiz</a>
        <a href="admin.php" class="btn btn-outline-secondary w-100">📊 Admin Panel</a>
        <div class="alert alert-warning mt-3 small">
            ⚠️ <strong>IŠTRINK</strong> šį <code>install.php</code> failą dabar!
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            Klaida. Patikrink MySQL user/password <code>config.php</code> faile.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
