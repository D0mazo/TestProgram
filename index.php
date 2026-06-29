<?php
session_start();
require_once 'config.php';

$ip = getUserIP();
$db = getDB();

$stmt = $db->prepare(
        'SELECT id, session_token, question_order, is_completed
     FROM test_sessions WHERE ip_address = ? LIMIT 1'
);
$stmt->execute([$ip]);
$existing = $stmt->fetch();

if ($existing) {
    if ((int) $existing['is_completed'] === 1) {
        pageHeader('Access Denied');
        ?>
        <div class="text-center py-3">
            <div class="center-icon">🚫</div>
            <h2 class="fw-bold mt-3">Already Completed</h2>
            <p class="text-muted mt-2">A quiz has already been submitted from<br><code><?= e($ip) ?></code></p>
            <p class="text-muted">Only <strong>one attempt per IP</strong> is allowed.</p>
        </div>
        <?php
        pageFooter();
        exit;
    }
    $_SESSION['session_db_id']  = $existing['id'];
    $_SESSION['quiz_token']     = $existing['session_token'];
    $_SESSION['question_order'] = json_decode($existing['question_order'], true);
    header('Location: quiz.php');
    exit;
}

$errors = []; $firstName = ''; $lastName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');

    if ($firstName === '')               $errors[] = 'First name is required.';
    elseif (mb_strlen($firstName) > 100) $errors[] = 'First name must be under 100 characters.';
    if ($lastName === '')                $errors[] = 'Last name is required.';
    elseif (mb_strlen($lastName) > 100)  $errors[] = 'Last name must be under 100 characters.';

    if (empty($errors)) {
        $stmt = $db->query('SELECT id FROM questions ORDER BY RAND() LIMIT ' . QUESTIONS_PER_TEST);
        $qIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($qIds) < QUESTIONS_PER_TEST) {
            $errors[] = 'Not enough questions in the database.';
        } else {
            $token = bin2hex(random_bytes(32));
            try {
                $stmt = $db->prepare(
                        'INSERT INTO test_sessions (ip_address, first_name, last_name, session_token, question_order)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$ip, $firstName, $lastName, $token, json_encode($qIds)]);
                $_SESSION['session_db_id']  = (int) $db->lastInsertId();
                $_SESSION['quiz_token']     = $token;
                $_SESSION['question_order'] = $qIds;
                header('Location: quiz.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'This IP was just registered. Please refresh.';
            }
        }
    }
}

pageHeader('Online Quiz — Start');
?>

    <div class="text-center mb-4">
        <div class="center-icon">📝</div>
        <h2 class="fw-bold mt-3">Online Quiz</h2>
        <p class="text-muted"><?= QUESTIONS_PER_TEST ?> questions &nbsp;·&nbsp; one at a time &nbsp;·&nbsp; one attempt per IP</p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3">
        <?php foreach ($errors as $err): ?><div>⚠ <?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post" action="index.php" novalidate autocomplete="off">
        <div class="mb-3">
            <label for="first_name" class="form-label fw-semibold">First Name</label>
            <input type="text" id="first_name" name="first_name"
                   class="form-control form-control-lg"
                   value="<?= e($firstName) ?>" placeholder="e.g. Jonas"
                   maxlength="100" autofocus required>
        </div>
        <div class="mb-4">
            <label for="last_name" class="form-label fw-semibold">Last Name</label>
            <input type="text" id="last_name" name="last_name"
                   class="form-control form-control-lg"
                   value="<?= e($lastName) ?>" placeholder="e.g. Petraitis"
                   maxlength="100" required>
        </div>
        <button type="submit" class="btn-quiz">Start Quiz →</button>
    </form>

    <hr class="my-4">
    <p class="text-center text-muted small">
        ⚠ You can attempt this quiz <strong>only once</strong> from <code><?= e($ip) ?></code>
    </p>

<?php pageFooter(); ?>