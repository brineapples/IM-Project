<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function renderHeader(string $title, string $active = ''): void
{
    $user = currentUser();
    $messages = consumeFlash();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?= e($title) ?> | ZEST</title>
        <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/font-awesome.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/templatemo-training-studio.css')) ?>">
        <link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
    </head>
    <body class="app-body">
        <header class="header-area header-sticky background-header">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <nav class="main-nav">
                            <a href="<?= e(url('index.php')) ?>" class="logo app-brand">
                                <img class="app-brand-logo" src="<?= e(url('assets/SNC%20Logo.png')) ?>" alt="Santo Nino Chapter logo">
                                <span class="app-brand-copy">
                                    <span class="app-brand-title">ILHF <em>ZEST</em></span>
                                    <span class="app-brand-subtitle">Zumba Event Scheduling Tracker</span>
                                </span>
                            </a>
                            <ul class="nav">
                                <?php if ($user): ?>
                                    <li><a class="<?= $active === 'sessions' ? 'active' : '' ?>" href="<?= e(url('sessions/index.php')) ?>">Sessions</a></li>
                                    <li><a href="<?= e(url('index.php#trainer-profiles')) ?>">Contact</a></li>
                                    <?php if (hasPermission('APPLICATION', 'VIEW')): ?>
                                        <li><a class="<?= $active === 'applications' ? 'active' : '' ?>" href="<?= e(url('applications/index.php')) ?>">Applications</a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('USER_ACCOUNT', 'VIEW')): ?>
                                        <li><a class="<?= $active === 'users' ? 'active' : '' ?>" href="<?= e(url('users/index.php')) ?>">Users</a></li>
                                    <?php endif; ?>
                                    <?php if (isSuperAdmin()): ?>
                                        <li><a class="<?= $active === 'roles' ? 'active' : '' ?>" href="<?= e(url('roles/index.php')) ?>">Roles</a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('ACTIVITY_LOG', 'VIEW')): ?>
                                        <li><a class="<?= $active === 'logs' ? 'active' : '' ?>" href="<?= e(url('activity_logs.php')) ?>">Logs</a></li>
                                    <?php endif; ?>
                                    <li><a href="<?= e(url('logout.php')) ?>">Logout</a></li>
                                <?php else: ?>
                                    <li><a href="<?= e(url('index.php#trainer-profiles')) ?>">Contact</a></li>
                                    <li><a class="<?= $active === 'apply' ? 'active' : '' ?>" href="<?= e(url('apply.php')) ?>">Apply</a></li>
                                    <li><a class="<?= $active === 'login' ? 'active' : '' ?>" href="<?= e(url('login.php')) ?>">Login</a></li>
                                <?php endif; ?>
                            </ul>
                            <a class="menu-trigger"><span>Menu</span></a>
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main">
            <?php if ($messages): ?>
                <div class="container app-flashes">
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= e($message['type']) ?>">
                            <?= e($message['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    <?php
}

function renderFooter(): void
{
    ?>
        </main>
        <footer>
            <div class="container">
                <p>
                    &copy; ZEST, Zumba Event Scheduling Tracker. Theme base:
                    <a href="https://templatemo.com/tm-548-training-studio" target="_blank" rel="noopener">TemplateMo 548 Training Studio</a>.
                </p>
            </div>
        </footer>
        <script src="<?= e(asset('js/jquery-2.1.0.min.js')) ?>"></script>
        <script src="<?= e(asset('js/popper.js')) ?>"></script>
        <script src="<?= e(asset('js/bootstrap.min.js')) ?>"></script>
        <script src="<?= e(asset('js/custom.js')) ?>"></script>
        <script>
        document.querySelectorAll('a[href$="#trainer-profiles"]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var target = document.getElementById('trainer-profiles');
                if (!target || new URL(link.href).pathname !== window.location.pathname) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', '#trainer-profiles');
            });
        });
        </script>
    </body>
    </html>
    <?php
}
