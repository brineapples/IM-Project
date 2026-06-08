<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requirePermission('USER_ACCOUNT', 'VIEW');

$errors = [];
$username = trim($_POST['username'] ?? '');
$roleId = (int) ($_POST['role_id'] ?? 0);

$roles = db()->query('SELECT role_id, role_name FROM ROLE ORDER BY role_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('USER_ACCOUNT', 'CREATE');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($roleId <= 0) {
        $errors[] = 'Role is required.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO USER_ACCOUNT (username, password, role_id) VALUES (?, ?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $roleId]);

            logCurrentUserActivity('CREATE_USER_ACCOUNT', 'User created account ' . $username . '.');
            flash('success', 'User account created.');
            redirect('users/index.php');
        } catch (PDOException $exception) {
            $errors[] = 'Username already exists.';
        }
    }
}

$stmt = db()->query(
    'SELECT ua.user_id, ua.username, ua.role_id, r.role_name
     FROM USER_ACCOUNT ua
     INNER JOIN ROLE r ON r.role_id = ua.role_id
     ORDER BY ua.username'
);
$users = $stmt->fetchAll();

renderHeader('Users', 'users');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel mb-4">
            <div class="app-heading">
                <div>
                    <h1>User <em>Accounts</em></h1>
                    <p class="app-muted mb-0">Create accounts and assign their role.</p>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (hasPermission('USER_ACCOUNT', 'CREATE')): ?>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="username">Username</label>
                            <input class="form-control" id="username" name="username" value="<?= e($username) ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password</label>
                            <input class="form-control" id="password" type="password" name="password" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="role_id">Role</label>
                            <select class="form-control" id="role_id" name="role_id" required>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= e((string) $role['role_id']) ?>" <?= $roleId === (int) $role['role_id'] ? 'selected' : '' ?>>
                                        <?= e(displayRoleName($role['role_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Create User</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="app-panel">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Change Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $account): ?>
                            <tr>
                                <td><?= e($account['username']) ?></td>
                                <td><?= e(displayRoleName($account['role_name'])) ?></td>
                                <td>
                                    <?php if (hasPermission('USER_ACCOUNT', 'UPDATE')): ?>
                                        <form class="form-inline" method="post" action="<?= e(url('users/update_role.php')) ?>">
                                            <input type="hidden" name="user_id" value="<?= e((string) $account['user_id']) ?>">
                                            <select class="form-control form-control-sm mr-2" name="role_id">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= e((string) $role['role_id']) ?>" <?= (int) $account['role_id'] === (int) $role['role_id'] ? 'selected' : '' ?>>
                                                        <?= e(displayRoleName($role['role_name'])) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="app-muted">No permission</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
