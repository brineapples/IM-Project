<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireAdminArea();

// -----------------------------
// Dashboard Counts
// -----------------------------

$counts = [
    'sessions' => (int) db()->query('SELECT COUNT(*) FROM `SESSION`')->fetchColumn(),
    'applications' => (int) db()->query('SELECT COUNT(*) FROM MEMBERSHIP_APPLICATION')->fetchColumn(),
    'users' => (int) db()->query('SELECT COUNT(*) FROM USER_ACCOUNT')->fetchColumn(),
    'roles' => (int) db()->query('SELECT COUNT(*) FROM ROLE')->fetchColumn(),
    'logs' => (int) db()->query('SELECT COUNT(*) FROM ACTIVITY_LOG')->fetchColumn(),
];

// -----------------------------
// HTML Output
// -----------------------------

renderAdminHeader('Dashboard', 'dashboard');
?>
<section class="section app-section">
    <div class="app-panel">
        <div class="app-heading">
            <div>
                <h1>ILHF <em>ZEST Dashboard</em></h1>
                <p class="app-muted mb-0">Management area for staff and administrators.</p>
            </div>
        </div>

        <div class="admin-dashboard-grid">
            <?php if (hasPermission('SESSION', 'READ')): ?>
                <a class="admin-dashboard-card" href="<?= e(url('admin/sessions.php')) ?>">
                    <i class="fa fa-calendar admin-dashboard-card-icon"></i>
                    <span><?= e((string) $counts['sessions']) ?></span>
                    <strong>Manage Sessions</strong>
                </a>
            <?php endif; ?>
            <?php if (hasPermission('APPLICATION', 'READ')): ?>
                <a class="admin-dashboard-card" href="<?= e(url('admin/applications.php')) ?>">
                    <i class="fa fa-file-text-o admin-dashboard-card-icon"></i>
                    <span><?= e((string) $counts['applications']) ?></span>
                    <strong>Applications</strong>
                </a>
            <?php endif; ?>
            <?php if (hasPermission('USER_ACCOUNT', 'READ')): ?>
                <a class="admin-dashboard-card" href="<?= e(url('admin/users.php')) ?>">
                    <i class="fa fa-users admin-dashboard-card-icon"></i>
                    <span><?= e((string) $counts['users']) ?></span>
                    <strong>Users</strong>
                </a>
            <?php endif; ?>
            <?php if (isSuperAdmin()): ?>
                <a class="admin-dashboard-card" href="<?= e(url('admin/roles.php')) ?>">
                    <i class="fa fa-key admin-dashboard-card-icon"></i>
                    <span><?= e((string) $counts['roles']) ?></span>
                    <strong>Roles</strong>
                </a>
            <?php endif; ?>
            <?php if (hasPermission('ACTIVITY_LOG', 'READ')): ?>
                <a class="admin-dashboard-card" href="<?= e(url('admin/logs.php')) ?>">
                    <i class="fa fa-list-alt admin-dashboard-card-icon"></i>
                    <span><?= e((string) $counts['logs']) ?></span>
                    <strong>Logs</strong>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
