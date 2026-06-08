<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (userCount() === 0) {
    redirect('setup.php');
}

refreshSessionStatuses();

$statuses = getSessionStatuses();
$incomingStatusId = 0;
foreach ($statuses as $status) {
    if ($status['status_name'] === 'Incoming') {
        $incomingStatusId = (int) $status['status_id'];
        break;
    }
}

$statusId = array_key_exists('status_id', $_GET) ? (int) $_GET['status_id'] : $incomingStatusId;
$sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$where = [];
$params = [];

if ($statusId > 0) {
    $where[] = 's.status_id = ?';
    $params[] = $statusId;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$orderDirection = strtoupper($sort);

$stmt = db()->prepare(
    'SELECT s.session_title, s.session_date, s.start_time, s.end_time, s.location,
            ua.username AS coach_username,
            ss.status_name
     FROM `SESSION` s
     INNER JOIN USER_ACCOUNT ua ON ua.user_id = s.instructor_user_id
     INNER JOIN SESSION_STATUS ss ON ss.status_id = s.status_id' .
     $whereSql .
     ' ORDER BY s.session_date ' . $orderDirection . ', s.start_time ' . $orderDirection
);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

renderPublicHeader('Sessions', 'sessions');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Zumba <em>Sessions</em></h1>
                    <p class="app-muted mb-0">View scheduled Zumba sessions for ILHF Santo Nino Chapter.</p>
                </div>
            </div>

            <form class="session-filter" id="public-session-filter-form" method="get">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="status_id">Status</label>
                        <select class="form-control js-auto-submit" id="status_id" name="status_id">
                            <option value="">All statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e((string) $status['status_id']) ?>" <?= $statusId === (int) $status['status_id'] ? 'selected' : '' ?>>
                                    <?= e($status['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sort">Date Sort</label>
                        <select class="form-control js-auto-submit" id="sort" name="sort">
                            <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Oldest to newest</option>
                            <option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Newest to oldest</option>
                        </select>
                    </div>
                </div>
            </form>

            <?php if (!$sessions): ?>
                <p class="app-muted mb-0">No sessions have been scheduled yet.</p>
            <?php else: ?>
                <div class="session-card-grid">
                    <?php foreach ($sessions as $session): ?>
                        <article class="session-card session-card-<?= e(strtolower(str_replace(' ', '-', $session['status_name']))) ?>">
                            <div class="session-card-date">
                                <span><?= e(date('M', strtotime($session['session_date']))) ?></span>
                                <strong><?= e(date('d', strtotime($session['session_date']))) ?></strong>
                            </div>
                            <div class="session-card-body">
                                <span class="status-badge status-<?= e(strtolower(str_replace(' ', '-', $session['status_name']))) ?>">
                                    <?= e($session['status_name']) ?>
                                </span>
                                <h2><?= e($session['session_title']) ?></h2>
                                <p class="session-card-time"><?= e(date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time']))) ?></p>
                                <div class="session-card-meta">
                                    <span><?= e($session['location']) ?></span>
                                    <span>Coach: <?= e($session['coach_username']) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
document.querySelectorAll('.js-auto-submit').forEach(function (select) {
    select.addEventListener('change', function () {
        document.getElementById('public-session-filter-form').submit();
    });
});
</script>
<?php renderFooter(); ?>
