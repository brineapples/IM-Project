const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');

const BASE_URL = process.env.ZEST_BASE_URL || 'http://localhost/IM%20Project';
const ROOT = path.resolve(__dirname, '..');
const PHP = process.env.ZEST_PHP || 'C:\\wamp64\\bin\\php\\php8.5.0\\php.exe';
const PREFIX = 'QA_BROWSER_';
const PASSWORD = 'QaBrowser123!';

test.setTimeout(60000);

function runPhp(code) {
  return execFileSync(PHP, ['-r', code], {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function dbSetup() {
  runPhp(`
    require 'config/database.php';
    $db = db();
    $prefix = '${PREFIX}';

    function qa_scalar(PDO $db, string $sql, array $params = []) {
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchColumn();
    }
    function qa_role(PDO $db, string $name): int {
      return (int) qa_scalar($db, 'SELECT role_id FROM ROLE WHERE role_name = ?', [$name]);
    }
    function qa_permission(PDO $db, string $module, string $action): int {
      return (int) qa_scalar($db, 'SELECT p.permission_id FROM PERMISSION p INNER JOIN MODULE m ON m.module_id = p.module_id INNER JOIN ACTION_TYPE a ON a.action_type_id = p.action_type_id WHERE m.module_name = ? AND a.action_name = ?', [$module, $action]);
    }
    function qa_cleanup(PDO $db, string $prefix): void {
      $userIds = $db->prepare('SELECT user_id FROM USER_ACCOUNT WHERE username LIKE ?');
      $userIds->execute([$prefix . '%']);
      $userIds = $userIds->fetchAll(PDO::FETCH_COLUMN);
      $roleIds = $db->prepare('SELECT role_id FROM ROLE WHERE role_name LIKE ?');
      $roleIds->execute([$prefix . '%']);
      $roleIds = $roleIds->fetchAll(PDO::FETCH_COLUMN);

      $db->prepare('DELETE FROM ACTIVITY_LOG WHERE action LIKE ? OR description LIKE ?')->execute(['%' . $prefix . '%', '%' . $prefix . '%']);
      if ($userIds) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $db->prepare('DELETE FROM ACTIVITY_LOG WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
        $db->prepare('DELETE FROM MEMBER WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
      }
      $db->prepare('DELETE FROM SESSION WHERE session_title LIKE ? OR location LIKE ?')->execute([$prefix . '%', $prefix . '%']);
      $db->prepare('DELETE FROM MEMBERSHIP_APPLICATION WHERE desired_username LIKE ? OR first_name LIKE ? OR address LIKE ?')->execute([$prefix . '%', $prefix . '%', $prefix . '%']);
      if ($userIds) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $db->prepare('DELETE FROM USER_ACCOUNT WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
      }
      if ($roleIds) {
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $db->prepare('DELETE FROM ROLE_PERMISSION WHERE role_id IN (' . $placeholders . ')')->execute($roleIds);
        $db->prepare('DELETE FROM ROLE WHERE role_id IN (' . $placeholders . ')')->execute($roleIds);
      }
    }

    qa_cleanup($db, $prefix);
    $hash = password_hash('${PASSWORD}', PASSWORD_DEFAULT);
    $insertUser = $db->prepare('INSERT INTO USER_ACCOUNT (username, password, role_id) VALUES (?, ?, ?)');
    $insertUser->execute([$prefix . 'SUPERADMIN', $hash, qa_role($db, 'Super Admin')]);
    $insertUser->execute([$prefix . 'MEMBER', $hash, qa_role($db, 'Member')]);
    $insertUser->execute([$prefix . 'COACH', $hash, qa_role($db, 'Instructor')]);

    $db->prepare('INSERT INTO ROLE (role_name) VALUES (?)')->execute([$prefix . 'LIMITED']);
    $limitedRole = (int) $db->lastInsertId();
    foreach ([['APPLICATION', 'READ'], ['APPLICATION', 'UPDATE']] as $item) {
      $db->prepare('INSERT INTO ROLE_PERMISSION (role_id, permission_id) VALUES (?, ?)')->execute([$limitedRole, qa_permission($db, $item[0], $item[1])]);
    }
    $insertUser->execute([$prefix . 'LIMITED', $hash, $limitedRole]);
  `);
}

function dbCleanup() {
  runPhp(`
    require 'config/database.php';
    $db = db();
    $prefix = '${PREFIX}';
    $userIdsStmt = $db->prepare('SELECT user_id FROM USER_ACCOUNT WHERE username LIKE ?');
    $userIdsStmt->execute([$prefix . '%']);
    $userIds = $userIdsStmt->fetchAll(PDO::FETCH_COLUMN);
    $roleIdsStmt = $db->prepare('SELECT role_id FROM ROLE WHERE role_name LIKE ?');
    $roleIdsStmt->execute([$prefix . '%']);
    $roleIds = $roleIdsStmt->fetchAll(PDO::FETCH_COLUMN);
    $db->prepare('DELETE FROM ACTIVITY_LOG WHERE action LIKE ? OR description LIKE ?')->execute(['%' . $prefix . '%', '%' . $prefix . '%']);
    if ($userIds) {
      $placeholders = implode(',', array_fill(0, count($userIds), '?'));
      $db->prepare('DELETE FROM ACTIVITY_LOG WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
      $db->prepare('DELETE FROM MEMBER WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
    }
    $db->prepare('DELETE FROM SESSION WHERE session_title LIKE ? OR location LIKE ?')->execute([$prefix . '%', $prefix . '%']);
    $db->prepare('DELETE FROM MEMBERSHIP_APPLICATION WHERE desired_username LIKE ? OR first_name LIKE ? OR address LIKE ?')->execute([$prefix . '%', $prefix . '%', $prefix . '%']);
    if ($userIds) {
      $placeholders = implode(',', array_fill(0, count($userIds), '?'));
      $db->prepare('DELETE FROM USER_ACCOUNT WHERE user_id IN (' . $placeholders . ')')->execute($userIds);
    }
    if ($roleIds) {
      $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
      $db->prepare('DELETE FROM ROLE_PERMISSION WHERE role_id IN (' . $placeholders . ')')->execute($roleIds);
      $db->prepare('DELETE FROM ROLE WHERE role_id IN (' . $placeholders . ')')->execute($roleIds);
    }
  `);
}

function dbScalar(sql, params = []) {
  const encoded = Buffer.from(JSON.stringify({ sql, params }), 'utf8').toString('base64');
  return runPhp(`
    require 'config/database.php';
    $payload = json_decode(base64_decode('${encoded}'), true);
    $stmt = db()->prepare($payload['sql']);
    $stmt->execute($payload['params']);
    echo (string) $stmt->fetchColumn();
  `).trim();
}

async function expectCleanPage(page, label) {
  await expect(page.locator('body')).not.toContainText('Fatal error');
  await expect(page.locator('body')).not.toContainText('Parse error');
  await expect(page.locator('body')).not.toContainText('SQLSTATE');
  await expect(page.locator('body')).not.toContainText('PDOException');
  await expect(page.locator('body')).not.toContainText('Warning:');
  await expect(page.locator('body')).not.toContainText('Notice:');
  await page.screenshot({ path: `playwright-screenshots/${label}.png`, fullPage: true });
}

async function login(page, username) {
  await page.goto(`${BASE_URL}/login.php`);
  await page.getByLabel('Username').fill(username);
  await page.locator('#password').fill(PASSWORD);
  await page.locator('#password').press('Enter');
  await page.waitForLoadState('domcontentloaded');
}

async function scrollAndAcceptPolicyPage(page) {
  for (const type of ['terms_of_use', 'terms_of_service', 'privacy_statement']) {
    await page.locator(`[data-policy-scroll="${type}"]`).evaluate((element) => {
      element.scrollTop = element.scrollHeight;
      element.dispatchEvent(new Event('scroll'));
    });
  }
  await expect(page.locator('[data-policy-submit]')).toBeEnabled();
  await page.locator('[data-policy-submit]').click();
  await page.waitForLoadState('domcontentloaded');
}

async function loginAndAccept(page, username) {
  await login(page, username);
  if (page.url().includes('accept-policies.php')) {
    await scrollAndAcceptPolicyPage(page);
  }
}

async function completeApplicationPolicyPopups(page) {
  for (const type of ['terms_of_use', 'terms_of_service', 'privacy_statement', 'fitness_risk']) {
    await expect(page.locator(`[data-policy-modal="${type}"]`)).toBeVisible();
    const button = page.locator(`[data-policy-modal-read="${type}"]`);
    await expect(button).toBeDisabled();
    await page.locator(`[data-policy-modal-scroll="${type}"]`).evaluate((element) => {
      element.scrollTop = element.scrollHeight;
      element.dispatchEvent(new Event('scroll'));
    });
    await expect(button).toBeEnabled();
    await button.click();
  }
}

async function fillApplication(page, username, firstName = `${PREFIX}First`) {
  await page.goto(`${BASE_URL}/apply.php`);
  await page.getByLabel('Username').fill(username);
  await page.locator('#password').fill(PASSWORD);
  await page.locator('#confirm_password').fill(PASSWORD);
  await page.getByLabel('First Name').fill(firstName);
  await page.getByLabel('Middle Name').fill('Browser');
  await page.getByLabel('Last Name').fill('Applicant');
  await page.getByLabel('Address').fill(`${PREFIX} Address ñ`);
  await page.getByLabel('Birthday').fill('1995-05-15');
  await page.locator('#barangay_id').selectOption('28');
  await page.locator('#chapter_id').selectOption({ index: 1 });
  await page.getByLabel('Phone Number').fill('09999999999');
  await page.locator('#gender_id').selectOption({ index: 1 });
  await page.locator('#member_status_id').selectOption({ index: 1 });
  await page.locator('#educational_attainment_id').selectOption({ index: 1 });
  await page.getByLabel('Primary School').fill(`${PREFIX} Primary`);
  await page.getByLabel('Primary Year Graduated').fill('2007');
  await page.getByLabel('Secondary School').fill(`${PREFIX} Secondary`);
  await page.getByLabel('Secondary Year Graduated').fill('2011');
  await page.getByLabel('College School').fill(`${PREFIX} College`);
  await page.getByLabel('College Year Graduated').fill('2015');
}

test.beforeAll(() => {
  dbSetup();
});

test.afterAll(() => {
  dbCleanup();
});

test.beforeEach(async ({ page }) => {
  page.on('dialog', (dialog) => dialog.accept());
});

test('public smoke, legal pages, responsive screenshots, and application policy popups', async ({ page }) => {
  const consoleErrors = [];
  const failedRequests = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  page.on('requestfailed', (request) => failedRequests.push(request.url()));

  for (const [label, viewport] of Object.entries({
    mobile: { width: 360, height: 800 },
    tablet: { width: 768, height: 1024 },
    desktop: { width: 1366, height: 768 },
  })) {
    await page.setViewportSize(viewport);
    await page.goto(`${BASE_URL}/index.php`);
    await expectCleanPage(page, `qa-${label}-landing`);
    await expect(page.getByText('Join the Movement')).toBeVisible();
  }

  await page.goto(`${BASE_URL}/legal.php?document=terms_of_use`);
  await expect(page.getByRole('heading', { name: 'Legal Documents' })).toBeVisible();
  await expect(page.locator('#terms_of_use')).toContainText('Effective Date: June 15, 2026');
  await expect(page.locator('#terms_of_use')).not.toContainText('read our Terms of Use below');
  await expectCleanPage(page, 'qa-legal-terms');

  await fillApplication(page, `${PREFIX}APPLY_POLICY`);
  await page.getByRole('button', { name: 'Submit Application' }).click();
  await completeApplicationPolicyPopups(page);
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('.alert-success')).toContainText('Application submitted');
  expect(Number(dbScalar('SELECT COUNT(*) FROM MEMBERSHIP_APPLICATION WHERE desired_username = ?', [`${PREFIX}APPLY_POLICY`]))).toBe(1);

  expect(consoleErrors).toEqual([]);
  expect(failedRequests).toEqual([]);
});

test('authentication, first-login policy gate, logout, and access control', async ({ page }) => {
  await page.goto(`${BASE_URL}/admin/dashboard.php`);
  await expect(page).toHaveURL(/login\.php/);

  await page.goto(`${BASE_URL}/login.php`);
  await page.getByLabel('Username').fill(`${PREFIX}SUPERADMIN`);
  await page.locator('#password').fill('wrong-password');
  await page.locator('#password').press('Enter');
  await expect(page.locator('.alert-danger')).toContainText('Invalid username or password');

  await login(page, `${PREFIX}SUPERADMIN`);
  await expect(page).toHaveURL(/accept-policies\.php/);
  await expect(page.locator('[data-policy-submit]')).toBeDisabled();
  await scrollAndAcceptPolicyPage(page);
  await expect(page).toHaveURL(/admin\/dashboard\.php/);
  await expectCleanPage(page, 'qa-admin-dashboard');

  await page.goto(`${BASE_URL}/logout.php`);
  await expect(page).toHaveURL(/index\.php/);

  await loginAndAccept(page, `${PREFIX}MEMBER`);
  await page.goto(`${BASE_URL}/admin/dashboard.php`);
  await expect(page).toHaveURL(/sessions\.php/);
  await page.goto(`${BASE_URL}/index.php`);
  await expect(page.locator('body')).not.toContainText('Users');
  await expect(page.locator('body')).not.toContainText('Roles');
});

test('sessions CRUD through admin UI', async ({ page }) => {
  await loginAndAccept(page, `${PREFIX}SUPERADMIN`);
  await page.goto(`${BASE_URL}/sessions/index.php`);
  await expect(page.getByRole('heading', { name: 'Manage ZEST Sessions' })).toBeVisible();

  await page.getByRole('link', { name: 'Create Session' }).click();
  await page.getByLabel('Title').fill(`${PREFIX}SESSION`);
  await page.getByLabel('Date').fill('2026-07-15');
  await page.getByLabel('Start Time').fill('08:00');
  await page.getByLabel('End Time').fill('09:00');
  await page.locator('#coach_user_id').selectOption({ index: 1 });
  await page.getByLabel('Location').fill(`${PREFIX}LOCATION`);
  await page.getByRole('button', { name: 'Save Session' }).click();
  await expect(page).toHaveURL(/sessions\/index\.php/);
  await expect(page.locator('.alert-success')).toContainText('Session created');

  await page.goto(`${BASE_URL}/sessions/index.php?status_id=&sort=desc`);
  await page.getByText(`${PREFIX}SESSION`).click();
  await expect(page.getByRole('heading', { name: 'Session Details' })).toBeVisible();
  await expect(page.locator('body')).toContainText(`${PREFIX}LOCATION`);

  await page.getByRole('link', { name: 'Edit' }).click();
  await page.getByLabel('Title').fill(`${PREFIX}SESSION_UPDATED`);
  await page.getByLabel('End Time').fill('09:30');
  await page.getByRole('button', { name: 'Save Session' }).click();
  await expect(page.locator('.alert-success')).toContainText('Session updated');
  await page.goto(`${BASE_URL}/sessions/index.php?status_id=&sort=desc`);
  await page.getByText(`${PREFIX}SESSION_UPDATED`).click();
  await expect(page.locator('body')).toContainText('9:30 AM');

  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page).toHaveURL(/sessions\/index\.php/);
  await expect(page.locator('.alert-success')).toContainText('Session deleted');
  expect(Number(dbScalar('SELECT COUNT(*) FROM SESSION WHERE session_title = ?', [`${PREFIX}SESSION_UPDATED`]))).toBe(0);
});

test('applications approve/reject and derived age display', async ({ page }) => {
  await fillApplication(page, `${PREFIX}APPROVE`, `${PREFIX}Approve`);
  await page.getByRole('button', { name: 'Submit Application' }).click();
  await completeApplicationPolicyPopups(page);
  await page.waitForLoadState('domcontentloaded');

  await fillApplication(page, `${PREFIX}REJECT`, `${PREFIX}Reject`);
  await page.getByRole('button', { name: 'Submit Application' }).click();
  await completeApplicationPolicyPopups(page);
  await page.waitForLoadState('domcontentloaded');

  await loginAndAccept(page, `${PREFIX}SUPERADMIN`);
  await page.goto(`${BASE_URL}/applications/index.php`);
  await page.locator('.application-card').filter({ hasText: `${PREFIX}Approve` }).click();
  await expect(page.locator('body')).toContainText('Age');
  await page.getByRole('button', { name: 'Approve' }).click();
  await expect(page.locator('.alert-success')).toContainText('Application approved');
  expect(Number(dbScalar('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?', [`${PREFIX}APPROVE`]))).toBe(1);
  expect(Number(dbScalar('SELECT COUNT(*) FROM MEMBER m INNER JOIN USER_ACCOUNT ua ON ua.user_id = m.user_id WHERE ua.username = ?', [`${PREFIX}APPROVE`]))).toBe(1);

  await page.goto(`${BASE_URL}/applications/index.php`);
  await page.locator('.application-card').filter({ hasText: `${PREFIX}Reject` }).click();
  await page.getByRole('button', { name: 'Reject' }).click();
  await expect(page.locator('.alert-success')).toContainText('Application rejected');
  expect(Number(dbScalar('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?', [`${PREFIX}REJECT`]))).toBe(0);
});

test('users, roles, permissions, logs, and validation states', async ({ page }) => {
  await loginAndAccept(page, `${PREFIX}SUPERADMIN`);

  await page.goto(`${BASE_URL}/users/index.php`);
  await page.getByLabel('Username').fill(`${PREFIX}USER`);
  await page.getByLabel('Password').fill('123');
  await page.locator('#role_id').selectOption({ index: 1 });
  await page.getByRole('button', { name: 'Create User' }).click();
  await expect(page.locator('.alert-danger')).toContainText('Password must be at least 6 characters');

  await page.getByLabel('Password').fill(PASSWORD);
  await page.getByRole('button', { name: 'Create User' }).click();
  await expect(page.locator('.alert-success')).toContainText('User account created');
  expect(Number(dbScalar('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?', [`${PREFIX}USER`]))).toBe(1);
  await expect(page.locator('body')).not.toContainText(PASSWORD);

  await page.goto(`${BASE_URL}/roles/index.php`);
  await page.getByLabel('New Role Name').fill(`${PREFIX}ROLE`);
  await page.getByRole('button', { name: 'Create Role' }).click();
  await expect(page.locator('.alert-success')).toContainText('Role created');
  await page.getByText(`${PREFIX}ROLE`).locator('xpath=ancestor::tr').getByRole('link', { name: 'Permissions' }).click();
  await expect(page.locator('h1')).toContainText('Role Permissions');
  await page.locator('.permission-feature-card').filter({ hasText: 'Sessions' }).locator('input[type="checkbox"]').first().check();
  await page.getByRole('button', { name: 'Save Permissions' }).click();
  await expect(page.locator('.alert-success')).toContainText('Permissions updated');

  await page.goto(`${BASE_URL}/roles/index.php`);
  await page.getByText(`${PREFIX}ROLE`).locator('xpath=ancestor::tr').getByRole('button', { name: 'Delete' }).click();
  await expect(page.locator('.alert-success')).toContainText('Role deleted');

  await page.goto(`${BASE_URL}/activity_logs.php`);
  await expect(page.locator('body')).toContainText('CREATE_USER_ACCOUNT');
  await expect(page.locator('body')).toContainText('UPDATE_ROLE_PERMISSION');
  await expectCleanPage(page, 'qa-logs');
});
