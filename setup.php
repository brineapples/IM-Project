<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (userCount() > 0) {
    redirect('login.php');
}

$errors = [];
$username = trim($_POST['username'] ?? 'superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare("SELECT role_id FROM ROLE WHERE role_name = 'Super Admin'");
        $stmt->execute();
        $roleId = $stmt->fetchColumn();

        if (!$roleId) {
            $errors[] = 'Super Admin role is missing from the database.';
        } else {
            $stmt = db()->prepare('INSERT INTO USER_ACCOUNT (username, password, role_id) VALUES (?, ?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $roleId]);
            $userId = (int) db()->lastInsertId();

            logActivity($userId, 'CREATE_INITIAL_SUPERADMIN', 'Initial Super Admin account ' . $username . ' was created.');

            flash('success', 'Initial Super Admin account created. You can log in now.');
            redirect('login.php');
        }
    }
}

renderHeader('First Setup', 'login');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel login-panel">
            <div class="app-heading">
                <h1>ZEST <em>Setup</em></h1>
            </div>

            <p class="app-muted">Create the first Super Admin account for Zumba Event Scheduling Tracker. This is needed because the SQL schema has roles and permissions but no user accounts.</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input class="form-control" id="username" name="username" value="<?= e($username) ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input class="form-control" id="password" type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input class="form-control" id="confirm_password" type="password" name="confirm_password" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Create Super Admin</button>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
