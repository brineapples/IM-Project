<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function renderDocumentStart(string $title, string $bodyClass = 'app-body'): void
{
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
    <body class="<?= e($bodyClass) ?>">
    <?php
}

function renderBrand(): void
{
    ?>
    <a href="<?= e(url('index.php')) ?>" class="logo app-brand">
        <img class="app-brand-logo" src="<?= e(url('assets/SNC%20Logo.png')) ?>" alt="Santo Nino Chapter logo">
        <span class="app-brand-copy">
            <span class="app-brand-title">ILHF <em>ZEST</em></span>
            <span class="app-brand-subtitle">Zumba Event Scheduling Tracker</span>
        </span>
    </a>
    <?php
}

function renderFlashes(): void
{
    $messages = consumeFlash();
    if (!$messages) {
        return;
    }
    ?>
    <div class="container app-flashes">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= e($message['type']) ?>">
                <?= e($message['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderPublicHeader(string $title, string $active = ''): void
{
    $user = currentUser();
    $GLOBALS['layout_is_admin'] = false;
    renderDocumentStart($title);
    ?>
        <header class="header-area header-sticky background-header">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <nav class="main-nav">
                            <?php renderBrand(); ?>
                            <ul class="nav">
                                <li><a class="<?= $active === 'home' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">Home</a></li>
                                <li><a class="<?= $active === 'sessions' ? 'active' : '' ?>" href="<?= e(url('sessions.php')) ?>">Sessions</a></li>
                                <li><a href="<?= e(url('index.php#trainer-profiles')) ?>">Contact</a></li>
                                <?php if (!$user): ?>
                                    <li><a class="<?= $active === 'apply' ? 'active' : '' ?>" href="<?= e(url('apply.php')) ?>">Apply</a></li>
                                    <li><a class="<?= $active === 'login' ? 'active' : '' ?>" href="<?= e(url('login.php')) ?>">Login</a></li>
                                <?php else: ?>
                                    <?php if (userCanAccessAdminArea()): ?>
                                        <li><a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a></li>
                                    <?php endif; ?>
                                    <li><a href="<?= e(url('logout.php')) ?>">Logout</a></li>
                                <?php endif; ?>
                            </ul>
                            <a class="menu-trigger"><span>Menu</span></a>
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main">
            <?php renderFlashes(); ?>
    <?php
}

function renderAdminHeader(string $title, string $active = ''): void
{
    $GLOBALS['layout_is_admin'] = true;
    renderDocumentStart($title, 'app-body admin-body');
    ?>
        <header class="admin-topbar">
            <div class="admin-topbar-inner">
                <?php renderBrand(); ?>
                <div class="admin-topbar-actions">
                    <a href="<?= e(url('index.php')) ?>">Public Site</a>
                    <a href="<?= e(url('logout.php')) ?>">Logout</a>
                </div>
            </div>
        </header>

        <main class="app-main admin-main">
            <?php renderFlashes(); ?>
            <div class="admin-shell">
                <aside class="admin-sidebar">
                    <nav>
                        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
                        <?php if (hasPermission('SESSION', 'READ')): ?>
                            <a class="<?= $active === 'sessions' ? 'active' : '' ?>" href="<?= e(url('admin/sessions.php')) ?>"><i class="fa fa-calendar"></i> Manage Sessions</a>
                        <?php endif; ?>
                        <?php if (hasPermission('APPLICATION', 'READ')): ?>
                            <a class="<?= $active === 'applications' ? 'active' : '' ?>" href="<?= e(url('admin/applications.php')) ?>"><i class="fa fa-file-text-o"></i> Applications</a>
                        <?php endif; ?>
                        <?php if (hasPermission('USER_ACCOUNT', 'READ')): ?>
                            <a class="<?= $active === 'users' ? 'active' : '' ?>" href="<?= e(url('admin/users.php')) ?>"><i class="fa fa-users"></i> Users</a>
                        <?php endif; ?>
                        <?php if (isSuperAdmin()): ?>
                            <a class="<?= $active === 'roles' ? 'active' : '' ?>" href="<?= e(url('admin/roles.php')) ?>"><i class="fa fa-key"></i> Roles</a>
                        <?php endif; ?>
                        <?php if (hasPermission('ACTIVITY_LOG', 'READ')): ?>
                            <a class="<?= $active === 'logs' ? 'active' : '' ?>" href="<?= e(url('admin/logs.php')) ?>"><i class="fa fa-list-alt"></i> Logs</a>
                        <?php endif; ?>
                    </nav>
                </aside>
                <div class="admin-content">
    <?php
}

function renderHeader(string $title, string $active = ''): void
{
    renderPublicHeader($title, $active);
}

function renderFooter(): void
{
    if (!empty($GLOBALS['layout_is_admin'])) {
        ?>
                </div>
            </div>
        <?php
    }
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

        (function () {
            var header = document.querySelector('.header-area');
            var lastScrollY = window.pageYOffset;
            var ticking = false;

            if (!header) {
                return;
            }

            function updateHeader() {
                var currentScrollY = window.pageYOffset;

                if (currentScrollY > lastScrollY && currentScrollY > 140) {
                    header.classList.add('header-hidden');
                } else {
                    header.classList.remove('header-hidden');
                }

                lastScrollY = Math.max(currentScrollY, 0);
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            });
        })();

        (function () {
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var revealSelector = [
                '.app-panel',
                '.session-card',
                '.application-card',
                '.permission-feature-card',
                '.landing-card',
                '.landing-band',
                '.landing-coach-card',
                '.admin-dashboard-card',
                '.admin-sidebar',
                '.table-responsive',
                '.session-filter',
                'form .form-group'
            ].join(',');
            var items = Array.prototype.slice.call(document.querySelectorAll(revealSelector));

            if (!items.length) {
                return;
            }

            if (reduceMotion || !('IntersectionObserver' in window)) {
                items.forEach(function (item) {
                    item.classList.add('is-visible');
                });
                return;
            }

            items.forEach(function (item, index) {
                item.classList.add('ui-reveal');
                item.style.setProperty('--reveal-delay', ((index % 6) * 45) + 'ms');
            });

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, {
                rootMargin: '0px 0px -30px',
                threshold: 0.12
            });

            items.forEach(function (item) {
                observer.observe(item);
            });
        })();
        </script>
    </body>
    </html>
    <?php
}
