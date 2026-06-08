<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireAdminArea();
requirePermission('ACTIVITY_LOG', 'READ');

// -----------------------------
// Database Queries
// -----------------------------

$stmt = db()->query(
    'SELECT al.log_id, al.action, al.description, al.log_date, ua.username
     FROM ACTIVITY_LOG al
     LEFT JOIN USER_ACCOUNT ua ON ua.user_id = al.user_id
     ORDER BY al.log_date DESC, al.log_id DESC'
);
$logs = $stmt->fetchAll();

// -----------------------------
// HTML Output
// -----------------------------

renderAdminHeader('Activity Logs', 'logs');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Activity <em>Logs</em></h1>
                    <p class="app-muted mb-0">All recorded system actions.</p>
                </div>
            </div>

            <div class="table-responsive responsive-table-stack">
                <table class="table table-hover responsive-stack-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td data-label="Date"><?= e(date('M d, Y g:i A', strtotime($log['log_date']))) ?></td>
                                <td data-label="User"><?= e($log['username'] ?? 'Anonymous/System') ?></td>
                                <td data-label="Action"><?= e($log['action']) ?></td>
                                <td data-label="Description"><?= e($log['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
