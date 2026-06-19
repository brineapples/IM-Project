<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// -----------------------------
// Document Setup
// -----------------------------

function renderDocumentStart(string $title, string $bodyClass = 'app-body'): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?= e($title) ?> | ILHF</title>
        <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/font-awesome.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset('css/templatemo-training-studio.css')) ?>">
        <link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
    </head>
    <body class="<?= e($bodyClass) ?>">
    <?php
}

// -----------------------------
// Shared Header Pieces
// -----------------------------

function renderBrand(): void
{
    ?>
    <a href="<?= e(url('index.php')) ?>" class="logo app-brand">
        <img class="app-brand-logo" src="<?= e(url('assets/SNC%20Logo.png')) ?>" alt="Santo Nino Chapter logo">
        <span class="app-brand-copy">
            <span class="app-brand-title">
                <span class="app-brand-title-love">I Love</span>
                <span class="app-brand-title-health">Health</span>
                <span class="app-brand-title-fitness">&amp; Fitness</span>
            </span>
            <span class="app-brand-subtitle">Santo Nino Chapter</span>
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

// -----------------------------
// Public Layout
// -----------------------------

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
                                    <li><a href="<?= e(url('settings.php')) ?>">Settings</a></li>
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

// -----------------------------
// Admin Layout
// -----------------------------

function renderAdminHeader(string $title, string $active = ''): void
{
    $GLOBALS['layout_is_admin'] = true;
    renderDocumentStart($title, 'app-body admin-body');
    ?>
        <header class="admin-topbar">
            <div class="admin-topbar-inner">
                <?php renderBrand(); ?>
                <div class="admin-topbar-actions">
                    <a class="admin-link-titlecase" href="<?= e(url('index.php')) ?>">Landing Page</a>
                    <a class="admin-link-titlecase" href="<?= e(url('settings.php')) ?>">Settings</a>
                    <a href="<?= e(url('logout.php')) ?>">Logout</a>
                </div>
                <button class="admin-menu-trigger" type="button" aria-label="Toggle admin menu" aria-expanded="false">
                    <span></span>
                </button>
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
                        <div class="admin-sidebar-divider"></div>
                        <a class="admin-sidebar-utility" href="<?= e(url('settings.php')) ?>"><i class="fa fa-user"></i> Settings</a>
                        <a class="admin-sidebar-utility" href="<?= e(url('logout.php')) ?>"><i class="fa fa-sign-out"></i> Logout</a>
                    </nav>
                </aside>
                <button class="admin-sidebar-backdrop" type="button" aria-label="Close admin menu"></button>
                <div class="admin-content">
    <?php
}

function renderHeader(string $title, string $active = ''): void
{
    renderPublicHeader($title, $active);
}

// -----------------------------
// Footer and Shared Scripts
// -----------------------------

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
                    &copy; ILHF, Santo Nino Chapter. Theme base:
                    <a href="https://templatemo.com/tm-548-training-studio" target="_blank" rel="noopener">TemplateMo 548 Training Studio</a>.
                    <a href="<?= e(url('legal.php?document=terms_of_use')) ?>">Terms of Use</a>
                    <a href="<?= e(url('legal.php?document=terms_of_service')) ?>">Terms of Service</a>
                    <a href="<?= e(url('legal.php?document=privacy_statement')) ?>">Privacy Statement</a>
                </p>
            </div>
        </footer>
        <script src="<?= e(asset('js/jquery-2.1.0.min.js')) ?>"></script>
        <script src="<?= e(asset('js/popper.js')) ?>"></script>
        <script src="<?= e(asset('js/bootstrap.min.js')) ?>"></script>
        <script src="<?= e(asset('js/scrollreveal.min.js')) ?>"></script>
        <script src="<?= e(asset('js/custom.js')) ?>"></script>
        <script>
        // Admin sidebar toggle for tablet and mobile screens.
        (function () {
            var body = document.body;
            var trigger = document.querySelector('.admin-menu-trigger');
            var sidebar = document.querySelector('.admin-sidebar');
            var backdrop = document.querySelector('.admin-sidebar-backdrop');
            var mobileQuery = window.matchMedia('(max-width: 1024px)');

            if (!trigger || !sidebar || !backdrop) {
                return;
            }

            function setSidebarState(isOpen) {
                body.classList.toggle('admin-sidebar-open', isOpen);
                trigger.classList.toggle('active', isOpen);
                trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            function closeSidebar() {
                setSidebarState(false);
            }

            trigger.addEventListener('click', function () {
                if (!mobileQuery.matches) {
                    return;
                }

                setSidebarState(!body.classList.contains('admin-sidebar-open'));
            });

            backdrop.addEventListener('click', closeSidebar);

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (mobileQuery.matches) {
                        closeSidebar();
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (!mobileQuery.matches) {
                    closeSidebar();
                }
            });
        })();

        // Public mobile menu toggle.
        (function () {
            var trigger = document.querySelector('.header-area .menu-trigger');
            var nav = document.querySelector('.header-area .main-nav .nav');
            var mobileQuery = window.matchMedia('(max-width: 767px)');

            if (!trigger || !nav) {
                return;
            }

            function closeMobileMenu() {
                trigger.classList.remove('active');
                nav.classList.remove('mobile-open');
                nav.style.display = '';
            }

            trigger.addEventListener('click', function (event) {
                if (!mobileQuery.matches) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                trigger.classList.toggle('active');
                nav.classList.toggle('mobile-open');
                nav.style.display = nav.classList.contains('mobile-open') ? 'block' : '';
            }, true);

            nav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMobileMenu);
            });

            window.addEventListener('resize', function () {
                if (!mobileQuery.matches) {
                    closeMobileMenu();
                }
            });
        })();

        // Smooth scroll for the public contact link.
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

        // Hide the public header while scrolling down.
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

        // Hide the admin topbar while scrolling down.
        (function () {
            var topbar = document.querySelector('.admin-topbar');
            var lastScrollY = window.pageYOffset;
            var ticking = false;

            if (!topbar) {
                return;
            }

            function updateTopbar() {
                var currentScrollY = window.pageYOffset;

                if (currentScrollY > lastScrollY && currentScrollY > 140) {
                    topbar.classList.add('admin-topbar-hidden');
                } else {
                    topbar.classList.remove('admin-topbar-hidden');
                }

                lastScrollY = Math.max(currentScrollY, 0);
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(updateTopbar);
                    ticking = true;
                }
            });
        })();

        // Lightweight reveal animation for major UI surfaces.
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

        // Legal policy scroll gates for acceptance pages and application submit popups.
        (function () {
            var readDocuments = {};
            var threshold = 10;
            var applicationForm = document.querySelector('[data-application-policy-form]');
            var pendingApplicationSubmit = false;
            var applicationPolicySequence = ['terms_of_use', 'terms_of_service', 'privacy_statement', 'fitness_risk'];

            function isScrolledToBottom(element) {
                return element.scrollTop + element.clientHeight >= element.scrollHeight - threshold;
            }

            function markStatusComplete(status) {
                if (!status) {
                    return;
                }

                status.textContent = 'Read complete';
                status.classList.add('complete');
            }

            function bindScrollBox(box, onComplete, checkImmediately) {
                var completed = false;

                function check() {
                    if (box.offsetParent === null && !box.closest('.policy-modal.is-open')) {
                        return;
                    }

                    if (completed || !isScrolledToBottom(box)) {
                        return;
                    }

                    completed = true;
                    onComplete();
                }

                box.policyCheckScrollComplete = check;
                box.addEventListener('scroll', check);
                if (checkImmediately) {
                    window.setTimeout(check, 50);
                }
            }

            function openPolicyModal(type) {
                var modal = document.querySelector('[data-policy-modal="' + type + '"]');
                if (!modal) {
                    return;
                }

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');

                var box = modal.querySelector('[data-policy-modal-scroll]');
                if (box && typeof box.policyCheckScrollComplete === 'function') {
                    window.setTimeout(box.policyCheckScrollComplete, 50);
                }
            }

            function closePolicyModal(modal) {
                if (!modal) {
                    return;
                }

                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('[data-policy-scroll]').forEach(function (box) {
                var type = box.getAttribute('data-policy-scroll');
                var status = document.querySelector('[data-policy-status="' + type + '"]');
                var submit = document.querySelector('[data-policy-submit]');
                var summary = document.querySelector('[data-policy-summary]');

                bindScrollBox(box, function () {
                    readDocuments[type] = true;
                    markStatusComplete(status);

                    var required = Array.prototype.slice.call(document.querySelectorAll('[data-policy-scroll]'));
                    var allRead = required.every(function (requiredBox) {
                        return readDocuments[requiredBox.getAttribute('data-policy-scroll')];
                    });

                    if (allRead && submit) {
                        submit.disabled = false;
                        if (summary) {
                            summary.textContent = 'All policy documents are complete.';
                        }
                    }
                }, true);
            });

            function updateApplicationPolicyFields() {
                var termsInput = document.querySelector('[data-policy-hidden="terms"]');
                var privacyInput = document.querySelector('[data-policy-hidden="privacy"]');
                var fitnessInput = document.querySelector('[data-policy-hidden="fitness_risk"]');

                if (termsInput && readDocuments.terms_of_use && readDocuments.terms_of_service) {
                    termsInput.value = '1';
                }

                if (privacyInput && readDocuments.privacy_statement) {
                    privacyInput.value = '1';
                }

                if (fitnessInput && readDocuments.fitness_risk) {
                    fitnessInput.value = '1';
                }
            }

            function applicationPoliciesComplete() {
                var termsInput = document.querySelector('[data-policy-hidden="terms"]');
                var privacyInput = document.querySelector('[data-policy-hidden="privacy"]');
                var fitnessInput = document.querySelector('[data-policy-hidden="fitness_risk"]');

                return (!termsInput || termsInput.value === '1')
                    && (!privacyInput || privacyInput.value === '1')
                    && (!fitnessInput || fitnessInput.value === '1');
            }

            function openNextApplicationPolicy() {
                var nextType = applicationPolicySequence.find(function (type) {
                    return !readDocuments[type];
                });

                if (nextType) {
                    openPolicyModal(nextType);
                    return;
                }

                updateApplicationPolicyFields();
                if (pendingApplicationSubmit && applicationForm && applicationPoliciesComplete()) {
                    pendingApplicationSubmit = false;
                    applicationForm.setAttribute('data-policy-complete', 'true');

                    if (applicationForm.requestSubmit) {
                        applicationForm.requestSubmit();
                    } else {
                        applicationForm.submit();
                    }
                }
            }

            document.querySelectorAll('[data-policy-modal-scroll]').forEach(function (box) {
                var type = box.getAttribute('data-policy-modal-scroll');
                var status = document.querySelector('[data-policy-modal-status="' + type + '"]');
                var button = document.querySelector('[data-policy-modal-read="' + type + '"]');

                bindScrollBox(box, function () {
                    markStatusComplete(status);
                    if (button) {
                        button.disabled = false;
                    }
                }, false);
            });

            document.querySelectorAll('[data-policy-modal-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var type = button.getAttribute('data-policy-modal-open');
                    openPolicyModal(type);
                });
            });

            document.querySelectorAll('[data-policy-modal-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    closePolicyModal(button.closest('[data-policy-modal]'));
                });
            });

            document.querySelectorAll('[data-policy-modal-read]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var type = button.getAttribute('data-policy-modal-read');
                    var modal = button.closest('[data-policy-modal]');
                    readDocuments[type] = true;
                    updateApplicationPolicyFields();
                    closePolicyModal(modal);

                    if (pendingApplicationSubmit) {
                        openNextApplicationPolicy();
                    }
                });
            });

            if (applicationForm) {
                applicationForm.addEventListener('submit', function (event) {
                    if (applicationForm.getAttribute('data-policy-complete') === 'true' || applicationPoliciesComplete()) {
                        applicationForm.setAttribute('data-policy-complete', 'true');
                        return;
                    }

                    event.preventDefault();
                    pendingApplicationSubmit = true;
                    openNextApplicationPolicy();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.policy-modal.is-open').forEach(function (modal) {
                    closePolicyModal(modal);
                });
            });
        })();
        </script>
    </body>
    </html>
    <?php
}

