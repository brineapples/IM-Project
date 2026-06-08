<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdminArea();
requireSuperAdmin();

$errors = [];
$roleName = trim($_POST['role_name'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($roleName === '') {
        $errors[] = 'Role name is required.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO ROLE (role_name) VALUES (?)');
            $stmt->execute([$roleName]);
            logCurrentUserActivity('CREATE_ROLE', 'User created role ' . $roleName . '.');
            flash('success', 'Role created.');
            redirect('roles/index.php');
        } catch (PDOException $exception) {
            $errors[] = 'Role name already exists.';
        }
    }
}

$roles = db()->query(
    'SELECT r.role_id, r.role_name, COUNT(ua.user_id) AS user_count
     FROM ROLE r
     LEFT JOIN USER_ACCOUNT ua ON ua.role_id = r.role_id
     GROUP BY r.role_id, r.role_name
     ORDER BY r.role_name'
)->fetchAll();

renderAdminHeader('Roles', 'roles');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel mb-4">
            <div class="app-heading">
                <div>
                    <h1>Role <em>Management</em></h1>
                    <p class="app-muted mb-0">Review roles before creating or changing permissions.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Users</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= e(displayRoleName($role['role_name'])) ?></td>
                                <td><?= e((string) $role['user_count']) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('roles/permissions.php?id=' . $role['role_id'])) ?>">Permissions</a>
                                        <?php if ($role['role_name'] !== 'Super Admin'): ?>
                                            <form method="post" action="<?= e(url('roles/delete.php')) ?>" onsubmit="return confirm('Delete this role?');">
                                                <input type="hidden" name="id" value="<?= e((string) $role['role_id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h2>Create <em>Role</em></h2>
                    <p class="app-muted mb-0">Add a new role after checking the current list.</p>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="role_name">New Role Name</label>
                        <input class="form-control" id="role_name" name="role_name" required>
                    </div>
                    <div class="form-group col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary" type="submit">Create Role</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
