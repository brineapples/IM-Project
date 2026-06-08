<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requirePermission('SESSION', 'VIEW');
refreshSessionStatuses();

$sessionId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT s.session_id, s.session_title, s.session_date, s.start_time, s.end_time, s.location,
            ua.username AS coach_username,
            ss.status_name
     FROM `SESSION` s
     INNER JOIN USER_ACCOUNT ua ON ua.user_id = s.instructor_user_id
     INNER JOIN SESSION_STATUS ss ON ss.status_id = s.status_id
     WHERE s.session_id = ?'
);
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    flash('danger', 'Session not found.');
    redirect('sessions/index.php');
}

renderHeader('View Session', 'sessions');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Session <em>Details</em></h1>
                    <p class="app-muted mb-0"><?= e($session['session_title']) ?></p>
                </div>
                <div class="actions">
                    <a class="btn btn-outline-secondary" href="<?= e(url('sessions/index.php')) ?>">Back</a>
                    <?php if (hasPermission('SESSION', 'UPDATE')): ?>
                        <a class="btn btn-primary" href="<?= e(url('sessions/edit.php?id=' . $session['session_id'])) ?>">Edit</a>
                    <?php endif; ?>
                    <?php if (hasPermission('SESSION', 'DELETE')): ?>
                        <form method="post" action="<?= e(url('sessions/delete.php')) ?>" onsubmit="return confirm('Delete this session?');">
                            <input type="hidden" name="id" value="<?= e((string) $session['session_id']) ?>">
                            <button class="btn btn-outline-danger" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="row">
                <dt class="col-sm-3">Title</dt>
                <dd class="col-sm-9"><?= e($session['session_title']) ?></dd>
                <dt class="col-sm-3">Date</dt>
                <dd class="col-sm-9"><?= e(date('F d, Y', strtotime($session['session_date']))) ?></dd>
                <dt class="col-sm-3">Time</dt>
                <dd class="col-sm-9"><?= e(date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time']))) ?></dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="status-badge status-<?= e(strtolower(str_replace(' ', '-', $session['status_name']))) ?>">
                        <?= e($session['status_name']) ?>
                    </span>
                </dd>
                <dt class="col-sm-3">Location</dt>
                <dd class="col-sm-9"><?= e($session['location']) ?></dd>
                <dt class="col-sm-3">Coach</dt>
                <dd class="col-sm-9"><?= e($session['coach_username']) ?></dd>
            </dl>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
