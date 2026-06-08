<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

// -----------------------------
// Admin Route Redirect
// -----------------------------

requireAdminArea();
redirect('roles/index.php');
