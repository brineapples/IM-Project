<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (userCount() === 0) {
    redirect('setup.php');
}

if (isLoggedIn()) {
    redirect('sessions/index.php');
}

$errors = [];
$username = trim($_POST['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare(
        'SELECT ua.user_id, ua.username, ua.password, ua.role_id, r.role_name
         FROM USER_ACCOUNT ua
         INNER JOIN ROLE r ON r.role_id = ua.role_id
         WHERE ua.username = ?'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        logActivity((int) $user['user_id'], 'LOGIN_SUCCESS', 'User ' . $user['username'] . ' logged in successfully.');
        redirect('sessions/index.php');
    }

    logFailedLogin($username, $user ? (int) $user['user_id'] : null);
    $errors[] = 'Invalid username or password.';
}

renderHeader('Login', 'login');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel login-panel">
            <div class="app-heading">
                <h1>ZEST <em>Login</em></h1>
            </div>

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
                    <input class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input class="form-control" id="password" type="password" name="password" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Login</button>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
