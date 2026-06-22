<?php
session_start();
require_once 'config.php';

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_ok']);
    header('Location: admin.php'); exit;
}

$loginError = '';
if (!empty($_SESSION['admin_ok'])) {
    $loggedIn = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_ok'] = true;
        header('Location: admin.php'); exit;
    }
    $loginError = 'Incorrect password.';
    $loggedIn   = false;
} else {
    $loggedIn = false;
}

if (!$loggedIn) {
    pageHeader('Admin Login');
    ?>
    <div class="text-center mb-4">
        <div class="center-icon">🔐</div>
        <h2 class="fw-bold mt-3">Admin Panel</h2>
        <p class="text-muted">Enter the admin password</p>
    </div>
    <?php if ($loginError): ?>
        <div class="alert alert-danger rounded-3"><?= e($loginError) ?></div>
    <?php endif; ?>
    <form method="post" action="admin.php">
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" autofocus required>
        </div>
        <button type="submit" class="btn-quiz">Log In →</button>
    </form>
    <?php
    pageFooter(); exit;
}

$db   = getDB();
$stat = $db->query(
    'SELECT COUNT(*) AS total, SUM(is_completed) AS completed,
            ROUND(AVG(CASE WHEN is_completed=1 THEN score END),1) AS avg_score,
            MAX(score) AS top_score
     FROM test_sessions'
)->fetch();

$sessions = $db->query(
    'SELECT id, first_name, last_name, ip_address, score,
            current_question_index, is_completed, started_at, completed_at
     FROM test_sessions ORDER BY started_at DESC'
)->fetchAll();

$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$viewSess = null; $viewAnswers = [];

if ($viewId > 0) {
    $s = $db->prepare('SELECT * FROM test_sessions WHERE id = ? LIMIT 1');
    $s->execute([$viewId]); $viewSess = $s->fetch();
    if ($viewSess) {
        $s = $db->prepare(
            'SELECT a.selected_answer, a.is_correct, q.question_text,
                    q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer
             FROM answers a JOIN questions q ON q.id = a.question_id
             WHERE a.session_id = ? ORDER BY a.id ASC'
        );
        $s->execute([$viewId]); $viewAnswers = $s->fetchAll();
    }
}

pageHeader('Admin Dashboard', 'quiz-card wide');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">📊 Quiz Dashboard</h4>
    <form method="post" action="admin.php">
        <button name="logout" class="btn btn-sm btn-outline-secondary">Log out</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
                       ['👤','Registered', (int)($stat['total']??0),            'text-primary'],
                       ['✅','Completed',  (int)($stat['completed']??0),         'text-success'],
                       ['📈','Average',    ($stat['avg_score']??0).'/'.QUESTIONS_PER_TEST,'text-info'],
                       ['🏆','Top Score',  (int)($stat['top_score']??0).'/'.QUESTIONS_PER_TEST,'text-warning'],
                   ] as [$icon,$label,$val,$cls]): ?>
        <div class="col-6 col-sm-3">
            <div class="stat-card">
                <div class="stat-icon"><?= $icon ?></div>
                <div class="stat-value <?= $cls ?>"><?= $val ?></div>
                <div class="stat-label"><?= $label ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($viewSess): ?>
    <div class="card border-primary shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Detail — <?= e($viewSess['first_name']) ?> <?= e($viewSess['last_name']) ?></strong>
            <a href="admin.php" class="btn btn-sm btn-outline-secondary">✕ Close</a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                IP: <code><?= e($viewSess['ip_address']) ?></code> &nbsp;|&nbsp;
                Score: <strong><?= $viewSess['score'] ?>/<?= QUESTIONS_PER_TEST ?></strong> &nbsp;|&nbsp;
                Started: <?= e(substr($viewSess['started_at'],0,16)) ?> &nbsp;|&nbsp;
                Finished: <?= $viewSess['completed_at'] ? e(substr($viewSess['completed_at'],0,16)) : '—' ?>
            </p>
            <?php foreach ($viewAnswers as $i => $row):
                $ok = (int)$row['is_correct'];
                $opts = ['A'=>$row['option_a'],'B'=>$row['option_b'],'C'=>$row['option_c'],'D'=>$row['option_d']];
                ?>
                <div class="answer-row <?= $ok ? 'correct' : 'incorrect' ?>">
                    <div class="d-flex justify-content-between">
                        <small class="fw-semibold text-muted">Q<?= $i+1 ?></small>
                        <span><?= $ok ? '✅' : '❌' ?></span>
                    </div>
                    <p class="mb-1 fw-semibold small"><?= e($row['question_text']) ?></p>
                    <p class="mb-0 small">
                        Selected: <strong><?= e($row['selected_answer'].'. '.$opts[$row['selected_answer']]) ?></strong>
                        <?php if (!$ok): ?> &nbsp;|&nbsp;
                            Correct: <strong class="text-success"><?= e($row['correct_answer'].'. '.$opts[$row['correct_answer']]) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="fw-bold mb-0">All Sessions</h5>
    <input type="text" id="sessionSearch" class="form-control form-control-sm"
           style="max-width:240px" placeholder="🔍 Filter by name or IP…">
</div>

<?php if (empty($sessions)): ?>
    <div class="text-center py-5 text-muted">No sessions yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover sessions-table">
            <thead class="table-light">
            <tr><th>#</th><th>Name</th><th>IP</th><th>Score</th><th>Progress</th><th>Status</th><th>Started</th><th></th></tr>
            </thead>
            <tbody id="sessionsBody">
            <?php foreach ($sessions as $s):
                $done = (int)$s['is_completed'];
                $pct  = $done ? round(($s['score']/QUESTIONS_PER_TEST)*100) : 0;
                $col  = $pct>=70 ? '#16a34a' : ($pct>=50 ? '#ca8a04' : '#dc2626');
                ?>
                <tr data-session-id="<?= (int)$s['id'] ?>">
                    <td class="text-muted"><?= (int)$s['id'] ?></td>
                    <td class="fw-semibold"><?= e($s['first_name']) ?> <?= e($s['last_name']) ?></td>
                    <td><code><?= e($s['ip_address']) ?></code></td>
                    <td>
                        <?php if ($done): ?>
                            <span class="fw-bold" style="color:<?= $col ?>"><?= (int)$s['score'] ?>/<?= QUESTIONS_PER_TEST ?></span>
                            <span class="text-muted small">(<?= $pct ?>%)</span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td style="min-width:110px">
                        <?php $prog = min(100,round(($s['current_question_index']/QUESTIONS_PER_TEST)*100)); ?>
                        <div class="progress mb-1" style="height:5px">
                            <div class="progress-bar" style="width:<?= $prog ?>%;background:#4f46e5"></div>
                        </div>
                        <small class="text-muted"><?= (int)$s['current_question_index'] ?>/<?= QUESTIONS_PER_TEST ?></small>
                    </td>
                    <td>
                        <?php if ($done): ?>
                            <span class="badge" style="background:#dcfce7;color:#16a34a">Completed</span>
                        <?php else: ?>
                            <span class="badge" style="background:#fef9c3;color:#854d0e">In Progress</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= e(substr($s['started_at'],0,16)) ?></td>
                    <td><a href="admin.php?view=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            <?php endforeach; ?>
            <tr id="noResultRow" style="display:none">
                <td colspan="8" class="text-center text-muted py-3">No sessions match your search.</td>
            </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
pageFooter(['js/admin.js']);
echo '<script>document.body.dataset.viewId="'.$viewId.'";</script>';
?>
