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
if (!(int) $sess['is_completed']) { header('Location: quiz.php'); exit; }

$stmt = $db->prepare(
    'SELECT a.selected_answer, a.is_correct,
            q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer
     FROM answers a JOIN questions q ON q.id = a.question_id
     WHERE a.session_id = ? ORDER BY a.id ASC'
);
$stmt->execute([$sessionId]);
$answers = $stmt->fetchAll();

$score = (int) $sess['score'];
$total = count($answers);
$pct   = $total > 0 ? round(($score / $total) * 100) : 0;

[$gradeText, $gradeClass] = match(true) {
    $pct >= 90 => ['🏆 Excellent!',   'text-success'],
    $pct >= 70 => ['👍 Good job!',    'text-primary'],
    $pct >= 50 => ['📚 Almost there', 'text-warning'],
    default    => ['❌ Keep studying', 'text-danger'],
};

pageHeader('Quiz Results');
?>

<div class="text-center mb-4">
    <div class="score-circle"><?= $score ?>/<?= $total ?></div>
    <h2 class="fw-bold mt-3 <?= $gradeClass ?>"><?= $gradeText ?></h2>
    <p class="text-muted">
        <?= e($sess['first_name']) ?> <?= e($sess['last_name']) ?> &nbsp;·&nbsp;
        <strong><?= $pct ?>%</strong> &nbsp;·&nbsp;
        <?= e(substr($sess['completed_at'], 0, 16)) ?>
    </p>
    <div class="progress mx-auto mt-2" style="max-width:380px;height:12px;border-radius:6px">
        <div class="progress-bar"
             style="width:<?= $pct ?>%;background:linear-gradient(90deg,#4f46e5,#7c3aed)"
             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <small class="text-muted"><?= $score ?> correct out of <?= $total ?></small>
</div>

<hr class="my-4">
<h5 class="fw-bold mb-3">📋 Answer Review</h5>

<?php foreach ($answers as $i => $row):
    $correct = (int) $row['is_correct'];
    $opts    = ['A'=>$row['option_a'],'B'=>$row['option_b'],'C'=>$row['option_c'],'D'=>$row['option_d']];
    ?>
    <div class="answer-row <?= $correct ? 'correct' : 'incorrect' ?>">
        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted fw-semibold">Question <?= $i + 1 ?></small>
            <span><?= $correct ? '✅' : '❌' ?></span>
        </div>
        <p class="fw-semibold mb-2" style="font-size:.95rem"><?= e($row['question_text']) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($opts as $letter => $text):
                $badge = 'bg-secondary bg-opacity-10 text-secondary';
                if ($letter === $row['correct_answer'])                    $badge = 'text-success fw-bold';
                if ($letter === $row['selected_answer'] && !$correct)     $badge = 'text-danger fw-bold';
                ?>
                <span class="badge px-3 py-2 border <?= $badge ?>" style="font-size:.8rem;border-radius:.5rem;background:#f8fafc">
      <?= $letter ?>. <?= e($text) ?>
                    <?php if ($letter === $row['correct_answer']): ?> ✓<?php endif; ?>
                    <?php if ($letter === $row['selected_answer'] && !$correct): ?> ✗<?php endif; ?>
    </span>
            <?php endforeach; ?>
        </div>
        <?php if (!$correct): ?>
            <p class="mt-2 mb-0 small text-success">
                ✅ Correct: <strong><?= e($row['correct_answer'] . '. ' . $opts[$row['correct_answer']]) ?></strong>
            </p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<hr class="mt-4">
<p class="text-center text-muted small">🔒 Result saved. This IP cannot retake the quiz.</p>

<?php pageFooter(); ?>
