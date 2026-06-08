<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireSuperAdmin();

$roleId = (int) ($_GET['id'] ?? $_POST['role_id'] ?? 0);
$stmt = db()->prepare('SELECT role_id, role_name FROM ROLE WHERE role_id = ?');
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    flash('danger', 'Role not found.');
    redirect('roles/index.php');
}

$modules = db()->query('SELECT module_id, module_name FROM MODULE ORDER BY module_name')->fetchAll();
$actions = db()->query('SELECT action_type_id, action_name FROM ACTION_TYPE ORDER BY FIELD(action_name, "CREATE", "READ", "UPDATE", "DELETE", "VIEW"), action_name')->fetchAll();
$permissions = db()->query(
    'SELECT p.permission_id, p.module_id, p.action_type_id
     FROM PERMISSION p'
)->fetchAll();

$permissionLookup = [];
foreach ($permissions as $permission) {
    $permissionLookup[$permission['module_id']][$permission['action_type_id']] = (int) $permission['permission_id'];
}

$stmt = db()->prepare('SELECT permission_id FROM ROLE_PERMISSION WHERE role_id = ?');
$stmt->execute([$roleId]);
$currentPermissionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPermissionIds = array_map('intval', $_POST['permissions'] ?? []);
    $toAdd = array_values(array_diff($selectedPermissionIds, $currentPermissionIds));
    $toRemove = array_values(array_diff($currentPermissionIds, $selectedPermissionIds));

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

renderHeader('Role Permissions', 'roles');
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

            <form method="post">
                <input type="hidden" name="role_id" value="<?= e((string) $roleId) ?>">
                <div class="table-responsive">
                    <table class="table table-bordered permission-grid">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <?php foreach ($actions as $action): ?>
                                    <th><?= e($action['action_name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                                <tr>
                                    <td><?= e($module['module_name']) ?></td>
                                    <?php foreach ($actions as $action): ?>
                                        <?php $permissionId = $permissionLookup[$module['module_id']][$action['action_type_id']] ?? null; ?>
                                        <td>
                                            <?php if ($permissionId): ?>
                                                <input type="checkbox" name="permissions[]" value="<?= e((string) $permissionId) ?>" <?= in_array($permissionId, $currentPermissionIds, true) ? 'checked="checked"' : '' ?>>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-primary" type="submit">Save Permissions</button>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
