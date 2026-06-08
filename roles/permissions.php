<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdminArea();
requireSuperAdmin();

$roleId = (int) ($_GET['id'] ?? $_POST['role_id'] ?? 0);
$stmt = db()->prepare('SELECT role_id, role_name FROM ROLE WHERE role_id = ?');
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    flash('danger', 'Role not found.');
    redirect('roles/index.php');
}

$featureDefinitions = [
    'SESSION' => [
        'label' => 'Sessions',
        'description' => 'Create, read, edit, and delete scheduled Zumba sessions.',
        'actions' => ['CREATE', 'READ', 'UPDATE', 'DELETE'],
    ],
    'APPLICATION' => [
        'label' => 'Applications',
        'description' => 'Review membership applications and approve or reject applicants.',
        'actions' => ['READ', 'UPDATE'],
    ],
    'USER_ACCOUNT' => [
        'label' => 'User Accounts',
        'description' => 'Create accounts and assign user roles.',
        'actions' => ['CREATE', 'READ', 'UPDATE'],
    ],
    'ROLE' => [
        'label' => 'Roles',
        'description' => 'Create and delete system roles.',
        'actions' => ['CREATE', 'READ', 'DELETE'],
    ],
    'PERMISSION' => [
        'label' => 'Role Permissions',
        'description' => 'Assign allowed actions to each role.',
        'actions' => ['READ', 'UPDATE'],
    ],
    'ACTIVITY_LOG' => [
        'label' => 'Activity Logs',
        'description' => 'View the central audit trail.',
        'actions' => ['READ'],
    ],
];

$allModules = db()->query('SELECT module_id, module_name FROM MODULE ORDER BY module_name')->fetchAll();
$moduleLookup = [];
foreach ($allModules as $module) {
    $moduleLookup[$module['module_name']] = $module;
}

$modules = [];
foreach (array_keys($featureDefinitions) as $moduleName) {
    if (isset($moduleLookup[$moduleName])) {
        $modules[] = $moduleLookup[$moduleName];
    }
}

$actions = db()->query('SELECT action_type_id, action_name FROM ACTION_TYPE ORDER BY FIELD(action_name, "CREATE", "READ", "UPDATE", "DELETE"), action_name')->fetchAll();
$permissions = db()->query(
    'SELECT p.permission_id, p.module_id, p.action_type_id
     FROM PERMISSION p'
)->fetchAll();

$permissionLookup = [];
foreach ($permissions as $permission) {
    $permissionLookup[$permission['module_id']][$permission['action_type_id']] = (int) $permission['permission_id'];
}

$visiblePermissionIds = [];
foreach ($modules as $module) {
    $featureActions = $featureDefinitions[$module['module_name']]['actions'];
    foreach ($actions as $action) {
        if (!in_array($action['action_name'], $featureActions, true)) {
            continue;
        }

        $permissionId = $permissionLookup[$module['module_id']][$action['action_type_id']] ?? null;
        if ($permissionId) {
            $visiblePermissionIds[] = $permissionId;
        }
    }
}

$stmt = db()->prepare('SELECT permission_id FROM ROLE_PERMISSION WHERE role_id = ?');
$stmt->execute([$roleId]);
$currentPermissionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $selectedPermissionIds = array_map('intval', $_POST['permissions'] ?? []);
    $selectedPermissionIds = array_values(array_intersect($selectedPermissionIds, $visiblePermissionIds));
    $currentVisiblePermissionIds = array_values(array_intersect($currentPermissionIds, $visiblePermissionIds));
    $toAdd = array_values(array_diff($selectedPermissionIds, $currentPermissionIds));
    $toRemove = array_values(array_diff($currentVisiblePermissionIds, $selectedPermissionIds));

    db()->beginTransaction();
    try {
        foreach ($toAdd as $permissionId) {
            $stmt = db()->prepare('INSERT INTO ROLE_PERMISSION (role_id, permission_id) VALUES (?, ?)');
            $stmt->execute([$roleId, $permissionId]);
        }

        foreach ($toRemove as $permissionId) {
            $stmt = db()->prepare('DELETE FROM ROLE_PERMISSION WHERE role_id = ? AND permission_id = ?');
            $stmt->execute([$roleId, $permissionId]);
        }

        db()->commit();

        if ($toAdd || $toRemove) {
            logCurrentUserActivity('UPDATE_ROLE_PERMISSION', 'User updated permissions for role ' . displayRoleName($role['role_name']) . '. Added ' . count($toAdd) . ', removed ' . count($toRemove) . '.');
        }

        flash('success', 'Permissions updated.');
        redirect('roles/permissions.php?id=' . $roleId);
    } catch (Throwable $exception) {
        db()->rollBack();
        flash('danger', 'Could not update permissions.');
        redirect('roles/permissions.php?id=' . $roleId);
    }
}

renderAdminHeader('Role Permissions', 'roles');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Role <em>Permissions</em></h1>
                    <p class="app-muted mb-0"><?= e(displayRoleName($role['role_name'])) ?></p>
                </div>
                <a class="btn btn-outline-secondary" href="<?= e(url('roles/index.php')) ?>">Back</a>
            </div>

            <form id="role-permissions-form" method="post">
                <input type="hidden" name="role_id" value="<?= e((string) $roleId) ?>">
                <div class="permission-feature-grid">
                    <?php foreach ($modules as $module): ?>
                        <?php $feature = $featureDefinitions[$module['module_name']]; ?>
                        <section class="permission-feature-card">
                            <div class="permission-feature-heading">
                                <div>
                                    <h2><?= e($feature['label']) ?></h2>
                                    <p><?= e($feature['description']) ?></p>
                                </div>
                            </div>

                            <div class="permission-action-grid">
                                <?php foreach ($actions as $action): ?>
                                    <?php if (!in_array($action['action_name'], $feature['actions'], true)) { continue; } ?>
                                    <?php $permissionId = $permissionLookup[$module['module_id']][$action['action_type_id']] ?? null; ?>
                                    <?php if ($permissionId): ?>
                                        <label class="permission-action-option">
                                            <input type="checkbox" name="permissions[]" value="<?= e((string) $permissionId) ?>" <?= in_array($permissionId, $currentPermissionIds, true) ? 'checked="checked"' : '' ?>>
                                            <span><?= e($action['action_name']) ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <div class="permission-save-bar">
            <button class="btn btn-primary" type="submit" form="role-permissions-form">Save Permissions</button>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
