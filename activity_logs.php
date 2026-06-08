<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

requireAdminArea();
requirePermission('ACTIVITY_LOG', 'READ');

$stmt = db()->query(
    'SELECT al.log_id, al.action, al.description, al.log_date, ua.username
     FROM ACTIVITY_LOG al
     LEFT JOIN USER_ACCOUNT ua ON ua.user_id = al.user_id
     ORDER BY al.log_date DESC, al.log_id DESC
     LIMIT 200'
);
$logs = $stmt->fetchAll();

renderAdminHeader('Activity Logs', 'logs');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Activity <em>Logs</em></h1>
                    <p class="app-muted mb-0">Latest 200 recorded system actions.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
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
                                <td><?= e(date('M d, Y g:i A', strtotime($log['log_date']))) ?></td>
                                <td><?= e($log['username'] ?? 'Anonymous/System') ?></td>
                                <td><?= e($log['action']) ?></td>
                                <td><?= e($log['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
