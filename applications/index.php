<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requirePermission('APPLICATION', 'VIEW');

$statuses = db()->query('SELECT application_status_id, status_name FROM APPLICATION_STATUS ORDER BY FIELD(status_name, "Pending", "Approved", "Rejected"), status_name')->fetchAll();
$pendingStatusId = getApplicationStatusId('Pending');
$statusId = array_key_exists('status_id', $_GET) ? (int) $_GET['status_id'] : $pendingStatusId;
$whereSql = '';
$params = [];

if ($statusId > 0) {
    $whereSql = ' WHERE ma.application_status_id = ?';
    $params[] = $statusId;
}

$stmt = db()->prepare(
    'SELECT ma.application_id, ma.desired_username, ma.first_name, ma.middle_name, ma.last_name,
            ma.birthday, ma.barangay_id, ma.created_at, aps.status_name
     FROM MEMBERSHIP_APPLICATION ma
     INNER JOIN APPLICATION_STATUS aps ON aps.application_status_id = ma.application_status_id' .
     $whereSql .
     ' ORDER BY ma.created_at DESC'
);
$stmt->execute($params);
$applications = $stmt->fetchAll();

renderHeader('Applications', 'applications');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Pending <em>Applications</em></h1>
                    <p class="app-muted mb-0">Review applicants before they become official members.</p>
                </div>
            </div>

            <form class="session-filter" id="application-filter-form" method="get">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="status_id">Status</label>
                        <select class="form-control js-auto-submit" id="status_id" name="status_id">
                            <option value="">All applications</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e((string) $status['application_status_id']) ?>" <?= $statusId === (int) $status['application_status_id'] ? 'selected' : '' ?>>
                                    <?= e($status['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if (!$applications): ?>
                <p class="app-muted mb-0">No applications found for this filter.</p>
            <?php else: ?>
                <div class="application-card-grid">
                    <?php foreach ($applications as $application): ?>
                        <a class="application-card" href="<?= e(url('applications/view.php?id=' . $application['application_id'])) ?>">
                            <div>
                                <span class="application-status application-status-<?= e(strtolower($application['status_name'])) ?>">
                                    <?= e($application['status_name']) ?>
                                </span>
                                <h2><?= e(trim($application['first_name'] . ' ' . ($application['middle_name'] ?? '') . ' ' . $application['last_name'])) ?></h2>
                                <p class="app-muted mb-0">Application #<?= e((string) $application['application_id']) ?> · <?= e($application['desired_username']) ?></p>
                            </div>
                            <div class="application-card-meta">
                                <span>Barangay: <?= e(barangayName((int) $application['barangay_id'])) ?></span>
                                <span>Age: <?= e((string) calculateAge($application['birthday'])) ?></span>
                                <span>Submitted: <?= e(date('M d, Y g:i A', strtotime($application['created_at']))) ?></span>
                            </div>
                            <span class="session-card-link-text">Open Application</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
document.querySelectorAll('.js-auto-submit').forEach(function (select) {
    select.addEventListener('change', function () {
        document.getElementById('application-filter-form').submit();
    });
});
</script>
<?php renderFooter(); ?>
