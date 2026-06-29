<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['session_db_id']) || empty($_SESSION['quiz_token'])) {
    header('Location: index.php'); exit;
}

$db        = getDB();
$sessionId = (int) $_SESSION['session_db_id'];
$token     = $_SESSION['quiz_token'];

$stmt = $db->prepare('SELECT * FROM test_sessions WHERE id = ? AND session_token = ? LIMIT 1');
$stmt->execute([$sessionId, $token]);
$sess = $stmt->fetch();

if (!$sess) { session_destroy(); header('Location: index.php'); exit; }
if ((int) $sess['is_completed']) { header('Location: results.php'); exit; }

$questionOrder = json_decode($sess['question_order'], true);
$currentIndex  = (int) $sess['current_question_index'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $answer     = strtoupper(trim($_POST['answer'] ?? ''));
    $questionId = (int) $questionOrder[$currentIndex];

    if (!in_array($answer, ['A','B','C','D'], true)) {
        header('Location: quiz.php?err=bad'); exit;
    }

    $stmt = $db->prepare('SELECT id FROM answers WHERE session_id = ? AND question_id = ? LIMIT 1');
    $stmt->execute([$sessionId, $questionId]);

    if (!$stmt->fetch()) {
        $stmt = $db->prepare('SELECT correct_answer FROM questions WHERE id = ?');
        $stmt->execute([$questionId]);
        $isCorrect = ($answer === $stmt->fetchColumn()) ? 1 : 0;

        $stmt = $db->prepare('INSERT INTO answers (session_id, question_id, selected_answer, is_correct) VALUES (?,?,?,?)');
        $stmt->execute([$sessionId, $questionId, $answer, $isCorrect]);

        $nextIndex = $currentIndex + 1;

        if ($nextIndex >= QUESTIONS_PER_TEST) {
            $stmt = $db->prepare('SELECT COALESCE(SUM(is_correct),0) FROM answers WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $score = (int) $stmt->fetchColumn();

            $stmt = $db->prepare('UPDATE test_sessions SET current_question_index=?,is_completed=1,completed_at=NOW(),score=? WHERE id=?');
            $stmt->execute([$nextIndex, $score, $sessionId]);
            header('Location: results.php'); exit;
        }

        $stmt = $db->prepare('UPDATE test_sessions SET current_question_index=? WHERE id=?');
        $stmt->execute([$nextIndex, $sessionId]);
    }

    header('Location: quiz.php'); exit;
}

$questionId  = (int) $questionOrder[$currentIndex];
$stmt        = $db->prepare('SELECT * FROM questions WHERE id = ?');
$stmt->execute([$questionId]);
$question    = $stmt->fetch();

if (!$question) die(renderFatal('Question not found', "ID {$questionId} missing from DB."));

$questionNum = $currentIndex + 1;
$totalQ      = QUESTIONS_PER_TEST;
$progressPct = round(($currentIndex / $totalQ) * 100);
$isLast      = ($questionNum === $totalQ);
$csrf        = csrfToken();
$options     = ['A'=>$question['option_a'],'B'=>$question['option_b'],'C'=>$question['option_c'],'D'=>$question['option_d']];

pageHeader("Question {$questionNum} of {$totalQ}");
?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted small">👤 <?= e($sess['first_name']) ?> <?= e($sess['last_name']) ?></span>
        <span class="badge bg-light text-secondary fw-semibold px-3 py-2 border">Q <?= $questionNum ?> / <?= $totalQ ?></span>
    </div>

    <div class="progress-bar-custom">
        <div class="progress-bar-fill" style="width:<?= $progressPct ?>%"></div>
    </div>

<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-warning rounded-3 py-2 small mb-3">⚠ Please select an answer.</div>
<?php endif; ?>

    <h5 class="fw-bold mb-4 lh-base"><?= e($question['question_text']) ?></h5>

    <form method="post" action="quiz.php" id="quizForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <?php foreach ($options as $letter => $text): ?>
            <label class="option-btn">
                <input type="radio" name="answer" value="<?= $letter ?>"
                       class="d-none" onchange="selectOption(this)" required>
                <span class="option-label"><?= $letter ?></span>
                <?= e($text) ?>
            </label>
        <?php endforeach; ?>
        <button type="submit" id="submitBtn" class="btn-quiz" disabled>
            <?= $isLast ? '✔ Finish Quiz' : 'Next Question →' ?>
        </button>
    </form>

    <div class="dot-nav">
        <?php for ($i = 1; $i <= $totalQ; $i++): ?>
            <div class="dot <?= $i < $questionNum ? 'done' : ($i === $questionNum ? 'current' : '') ?>"></div>
        <?php endfor; ?>
    </div>

<?php pageFooter(['js/quiz.js']); ?>