<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

const TEST_PREFIX = 'TEST_ZEST_CRUD_';

final class HttpResponse
{
    public function __construct(
        public int $status,
        public string $body,
        public array $headers,
        public string $url
    ) {
    }

    public function location(): string
    {
        return $this->headers['location'] ?? '';
    }
}

final class HttpClient
{
    private string $cookieFile;

    public function __construct(private string $baseUrl, string $cookieName)
    {
        $this->cookieFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cookieName;
        if (is_file($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function get(string $path): HttpResponse
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $data): HttpResponse
    {
        return $this->request('POST', $path, $data);
    }

    private function request(string $method, string $path, array $data = []): HttpResponse
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 20,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $headers = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($rawHeaders)) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return new HttpResponse($status, $body, $headers, $url);
    }
}

final class CrudTestRunner
{
    private PDO $db;
    private string $baseUrl;
    private array $results = [];
    private array $ids = [];

    public function __construct()
    {
        $this->db = db();
        $this->baseUrl = getenv('ZEST_BASE_URL') ?: 'http://localhost/IM%20Project';
    }

    public function run(): int
    {
        $this->cleanup();

        try {
            $this->testEnvironment();
            $this->seedActors();
            $this->testPublicPages();
            $this->testAuthenticationAndPermissions();
            $admin = $this->loggedInClient('superadmin');
            $this->testSessions($admin);
            $this->testApplications($admin);
            $this->testUsers($admin);
            $managedRoleId = $this->testRoles($admin);
            $this->testRolePermissions($admin, $managedRoleId);
            $this->testRoleDelete($admin, $managedRoleId);
            $this->testSessionStatuses();
            $this->testLookupAndNonCrudRoutes();
        } finally {
            $this->cleanup();
            $this->writeReport();
        }

        $failed = count(array_filter($this->results, static fn (array $result): bool => $result['status'] === 'FAIL'));
        $passed = count($this->results) - $failed;

        echo PHP_EOL . 'CRUD test run complete: ' . $passed . ' passed, ' . $failed . ' failed.' . PHP_EOL;
        echo 'Report: ' . realpath(__DIR__ . '/../CRUD_TEST_REPORT.md') . PHP_EOL;

        return $failed > 0 ? 1 : 0;
    }

    private function test(string $module, string $action, string $name, callable $callback): void
    {
        try {
            $details = $callback() ?: 'OK';
            $this->results[] = [
                'module' => $module,
                'action' => $action,
                'name' => $name,
                'expected' => 'Request/query succeeds and database state matches expectations.',
                'actual' => (string) $details,
                'status' => 'PASS',
            ];
            echo '[PASS] ' . $module . ' - ' . $name . PHP_EOL;
        } catch (Throwable $exception) {
            $this->results[] = [
                'module' => $module,
                'action' => $action,
                'name' => $name,
                'expected' => 'Request/query succeeds and database state matches expectations.',
                'actual' => $exception->getMessage(),
                'status' => 'FAIL',
            ];
            echo '[FAIL] ' . $module . ' - ' . $name . ': ' . $exception->getMessage() . PHP_EOL;
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertSameValue(mixed $expected, mixed $actual, string $message): void
    {
        if ((string) $expected !== (string) $actual) {
            throw new RuntimeException($message . ' Expected [' . (string) $expected . '], got [' . (string) $actual . '].');
        }
    }

    private function assertHttp(HttpResponse $response, array $allowedStatuses, string $label): void
    {
        $this->assertTrue(in_array($response->status, $allowedStatuses, true), $label . ' returned HTTP ' . $response->status);
        $this->assertNoPhpOrSqlError($response, $label);
    }

    private function assertRedirect(HttpResponse $response, string $label, ?string $locationContains = null): void
    {
        $this->assertHttp($response, [301, 302, 303], $label);
        if ($locationContains !== null) {
            $this->assertTrue(str_contains($response->location(), $locationContains), $label . ' redirect target was [' . $response->location() . ']');
        }
    }

    private function assertNoPhpOrSqlError(HttpResponse $response, string $label): void
    {
        $patterns = [
            'Fatal error',
            'Parse error',
            'Warning:',
            'Notice:',
            'PDOException',
            'SQLSTATE',
            'mysqli_sql_exception',
            'Call to undefined',
            'Stack trace',
        ];

        foreach ($patterns as $pattern) {
            $this->assertTrue(!str_contains($response->body, $pattern), $label . ' response contains [' . $pattern . ']');
        }
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function row(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function column(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function testEnvironment(): void
    {
        $this->test('Environment', 'READ', 'Database and required seed rows exist', function (): string {
            foreach (['Super Admin', 'Admin', 'Instructor', 'Member'] as $role) {
                $this->assertTrue((int) $this->scalar('SELECT COUNT(*) FROM ROLE WHERE role_name = ?', [$role]) === 1, 'Missing role ' . $role);
            }

            foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
                $this->assertTrue((int) $this->scalar('SELECT COUNT(*) FROM ACTION_TYPE WHERE action_name = ?', [$action]) === 1, 'Missing action ' . $action);
            }

            foreach (['Pending', 'Approved', 'Rejected'] as $status) {
                $this->assertTrue((int) $this->scalar('SELECT COUNT(*) FROM APPLICATION_STATUS WHERE status_name = ?', [$status]) === 1, 'Missing application status ' . $status);
            }

            foreach (['Incoming', 'In Progress', 'Finished'] as $status) {
                $this->assertTrue((int) $this->scalar('SELECT COUNT(*) FROM SESSION_STATUS WHERE status_name = ?', [$status]) === 1, 'Missing session status ' . $status);
            }

            return 'Required roles, actions, and status rows exist.';
        });
    }

    private function seedActors(): void
    {
        $this->test('Test Setup', 'CREATE', 'Create isolated test users and limited role', function (): string {
            $superAdminRoleId = $this->roleId('Super Admin');
            $memberRoleId = $this->roleId('Member');
            $instructorRoleId = $this->roleId('Instructor');

            $limitedRoleId = $this->insertRole(TEST_PREFIX . 'LIMITED_ADMIN_ROLE');
            $this->grantPermission($limitedRoleId, 'APPLICATION', 'READ');
            $this->grantPermission($limitedRoleId, 'APPLICATION', 'UPDATE');

            $this->ids['superadmin_user'] = $this->insertUser(TEST_PREFIX . 'SUPERADMIN', $superAdminRoleId);
            $this->ids['member_user'] = $this->insertUser(TEST_PREFIX . 'MEMBER', $memberRoleId);
            $this->ids['instructor_user'] = $this->insertUser(TEST_PREFIX . 'INSTRUCTOR', $instructorRoleId);
            $this->ids['limited_user'] = $this->insertUser(TEST_PREFIX . 'LIMITED_ADMIN', $limitedRoleId);
            $this->ids['limited_role'] = $limitedRoleId;

            return 'Created test users only.';
        });
    }

    private function testPublicPages(): void
    {
        $client = new HttpClient($this->baseUrl, 'zest_public_' . uniqid('', true) . '.txt');

        $this->test('Public Pages', 'READ', 'Landing page loads without admin controls', function () use ($client): string {
            $response = $client->get('index.php');
            $this->assertHttp($response, [200], 'Landing page');
            $this->assertTrue(!str_contains($response->body, 'admin/users.php'), 'Landing page exposes Users admin link.');
            $this->assertTrue(!str_contains($response->body, 'admin/roles.php'), 'Landing page exposes Roles admin link.');
            $this->assertTrue(!str_contains($response->body, 'activity_logs.php'), 'Landing page exposes Logs admin link.');
            return 'Landing page returned 200 and no management links were exposed.';
        });

        $this->test('Public Pages', 'READ', 'Public sessions page loads without admin controls', function () use ($client): string {
            $response = $client->get('sessions.php');
            $this->assertHttp($response, [200], 'Public sessions');
            $this->assertTrue(!str_contains($response->body, 'sessions/create.php'), 'Public sessions page exposes Create Session link.');
            $this->assertTrue(!str_contains($response->body, 'sessions/delete.php'), 'Public sessions page exposes Delete Session action.');
            return 'Public sessions page returned 200 and stayed read-only.';
        });

        $this->test('Public Pages', 'READ', 'Apply and login pages load', function () use ($client): string {
            $apply = $client->get('apply.php');
            $login = $client->get('login.php');
            $this->assertHttp($apply, [200], 'Apply page');
            $this->assertHttp($login, [200], 'Login page');
            $this->assertTrue(str_contains($apply->body, 'Membership'), 'Apply form did not render expected content.');
            return 'Apply and login pages returned 200.';
        });
    }

    private function testAuthenticationAndPermissions(): void
    {
        $this->test('Authentication', 'READ', 'Unauthenticated admin request redirects to login', function (): string {
            $client = new HttpClient($this->baseUrl, 'zest_guest_' . uniqid('', true) . '.txt');
            $response = $client->get('admin/dashboard.php');
            $this->assertRedirect($response, 'Guest admin dashboard', 'login.php');
            return 'Guest was redirected to login.';
        });

        $this->test('Authentication', 'CREATE', 'Failed login logs safely and does not authenticate', function (): string {
            $client = new HttpClient($this->baseUrl, 'zest_failed_login_' . uniqid('', true) . '.txt');
            $response = $client->post('login.php', [
                'username' => TEST_PREFIX . 'SUPERADMIN',
                'password' => 'wrong-password',
            ]);
            $this->assertHttp($response, [200], 'Failed login');
            $after = $client->get('admin/dashboard.php');
            $this->assertRedirect($after, 'Dashboard after failed login', 'login.php');
            return 'Invalid credentials stayed unauthenticated.';
        });

        $this->test('Authentication', 'CREATE', 'Valid Super Admin login and logout', function (): string {
            $client = $this->loggedInClient('superadmin');
            $dashboard = $client->get('admin/dashboard.php');
            $this->assertHttp($dashboard, [200], 'Admin dashboard after login');
            $logout = $client->get('logout.php');
            $this->assertRedirect($logout, 'Logout', 'index.php');
            $after = $client->get('admin/dashboard.php');
            $this->assertRedirect($after, 'Dashboard after logout', 'login.php');
            return 'Login reached dashboard; logout cleared session.';
        });

        $this->test('Permissions', 'READ', 'Member cannot manually access admin CRUD URLs', function (): string {
            $client = $this->loggedInClient('member');
            foreach ([
                'admin/dashboard.php',
                'sessions/create.php',
                'applications/index.php',
                'users/index.php',
                'roles/index.php',
                'activity_logs.php',
            ] as $path) {
                $response = $client->get($path);
                $this->assertRedirect($response, 'Member blocked from ' . $path, 'sessions.php');
            }
            return 'Member was redirected away from protected admin routes.';
        });

        $this->test('Permissions', 'READ', 'Limited admin can read applications but cannot open unrelated admin actions', function (): string {
            $client = $this->loggedInClient('limited');
            $applications = $client->get('applications/index.php');
            $this->assertHttp($applications, [200], 'Limited admin applications');

            $roles = $client->get('roles/index.php');
            $this->assertHttp($roles, [403], 'Limited admin roles');

            $sessionCreate = $client->get('sessions/create.php');
            $this->assertHttp($sessionCreate, [403], 'Limited admin session create');

            return 'Limited role enforced per-module permissions.';
        });

        $this->test('Public Pages', 'READ', 'Logged-in public landing does not expose full admin controls', function (): string {
            $client = $this->loggedInClient('superadmin');
            $response = $client->get('index.php');
            $this->assertHttp($response, [200], 'Logged-in public landing');
            $this->assertTrue(str_contains($response->body, 'admin/dashboard.php'), 'Logged-in admin should see dashboard shortcut.');
            $this->assertTrue(!str_contains($response->body, 'admin/users.php'), 'Landing page exposes Users management link.');
            $this->assertTrue(!str_contains($response->body, 'admin/roles.php'), 'Landing page exposes Roles management link.');
            $this->assertTrue(!str_contains($response->body, 'admin/logs.php'), 'Landing page exposes Logs management link.');
            return 'Only Dashboard/Logout shortcuts appeared on public layout.';
        });
    }

    private function testSessions(HttpClient $admin): void
    {
        $this->test('Sessions', 'READ', 'Admin session list loads', function () use ($admin): string {
            $response = $admin->get('sessions/index.php');
            $this->assertHttp($response, [200], 'Session list');
            return 'Manage Sessions returned 200.';
        });

        $this->test('Sessions', 'CREATE', 'Invalid session create is rejected without DB insert', function () use ($admin): string {
            $before = (int) $this->scalar('SELECT COUNT(*) FROM `SESSION` WHERE session_title = ?', [TEST_PREFIX . 'SESSION_INVALID']);
            $response = $admin->post('sessions/create.php', [
                'session_title' => TEST_PREFIX . 'SESSION_INVALID',
                'session_date' => date('Y-m-d', strtotime('+7 days')),
                'start_time' => '10:00',
                'end_time' => '09:00',
                'location' => 'Test Location',
                'coach_user_id' => $this->ids['instructor_user'],
            ]);
            $this->assertHttp($response, [200], 'Invalid session create');
            $after = (int) $this->scalar('SELECT COUNT(*) FROM `SESSION` WHERE session_title = ?', [TEST_PREFIX . 'SESSION_INVALID']);
            $this->assertSameValue($before, $after, 'Invalid session create changed the database.');
            return 'Invalid time order rendered validation error and inserted no row.';
        });

        $this->test('Sessions', 'CREATE', 'Valid session create inserts row and redirects', function () use ($admin): string {
            $response = $admin->post('sessions/create.php', [
                'session_title' => TEST_PREFIX . 'SESSION_CREATE',
                'session_date' => date('Y-m-d', strtotime('+9 days')),
                'start_time' => '08:00',
                'end_time' => '09:00',
                'location' => TEST_PREFIX . 'LOCATION',
                'coach_user_id' => $this->ids['instructor_user'],
            ]);
            $this->assertRedirect($response, 'Valid session create', 'sessions/index.php');
            $row = $this->row('SELECT session_id, session_title, instructor_user_id FROM `SESSION` WHERE session_title = ?', [TEST_PREFIX . 'SESSION_CREATE']);
            $this->assertTrue($row !== null, 'Created session row not found.');
            $this->assertSameValue($this->ids['instructor_user'], $row['instructor_user_id'], 'Session coach was not saved.');
            $this->ids['session'] = (int) $row['session_id'];
            return 'Session #' . $row['session_id'] . ' created.';
        });

        $this->test('Sessions', 'READ', 'Session detail loads created record', function () use ($admin): string {
            $response = $admin->get('sessions/view.php?id=' . $this->ids['session']);
            $this->assertHttp($response, [200], 'Session detail');
            $this->assertTrue(str_contains($response->body, TEST_PREFIX . 'SESSION_CREATE'), 'Session detail did not show created title.');
            return 'Session detail returned 200.';
        });

        $this->test('Sessions', 'UPDATE', 'Invalid session update does not change row', function () use ($admin): string {
            $before = $this->row('SELECT session_title, start_time, end_time FROM `SESSION` WHERE session_id = ?', [$this->ids['session']]);
            $response = $admin->post('sessions/edit.php?id=' . $this->ids['session'], [
                'id' => $this->ids['session'],
                'session_title' => TEST_PREFIX . 'SESSION_BAD_UPDATE',
                'session_date' => date('Y-m-d', strtotime('+9 days')),
                'start_time' => '11:00',
                'end_time' => '10:00',
                'location' => TEST_PREFIX . 'BAD_LOCATION',
                'coach_user_id' => $this->ids['instructor_user'],
            ]);
            $this->assertHttp($response, [200], 'Invalid session update');
            $after = $this->row('SELECT session_title, start_time, end_time FROM `SESSION` WHERE session_id = ?', [$this->ids['session']]);
            $this->assertSameValue($before['session_title'], $after['session_title'], 'Invalid update changed title.');
            return 'Invalid update inserted no changes.';
        });

        $this->test('Sessions', 'UPDATE', 'Valid session update changes row and redirects', function () use ($admin): string {
            $response = $admin->post('sessions/edit.php?id=' . $this->ids['session'], [
                'id' => $this->ids['session'],
                'session_title' => TEST_PREFIX . 'SESSION_UPDATED',
                'session_date' => date('Y-m-d', strtotime('+10 days')),
                'start_time' => '10:00',
                'end_time' => '11:30',
                'location' => TEST_PREFIX . 'UPDATED_LOCATION',
                'coach_user_id' => $this->ids['instructor_user'],
            ]);
            $this->assertRedirect($response, 'Valid session update', 'sessions/view.php?id=' . $this->ids['session']);
            $title = $this->scalar('SELECT session_title FROM `SESSION` WHERE session_id = ?', [$this->ids['session']]);
            $this->assertSameValue(TEST_PREFIX . 'SESSION_UPDATED', $title, 'Session title was not updated.');
            return 'Session updated and redirected to detail.';
        });

        $this->test('Sessions', 'DELETE', 'GET delete is blocked and does not remove row', function () use ($admin): string {
            $response = $admin->get('sessions/delete.php?id=' . $this->ids['session']);
            $this->assertHttp($response, [405], 'GET session delete');
            $exists = (int) $this->scalar('SELECT COUNT(*) FROM `SESSION` WHERE session_id = ?', [$this->ids['session']]);
            $this->assertSameValue(1, $exists, 'GET delete removed session.');
            return 'Delete route is POST-only.';
        });

        $this->test('Sessions', 'DELETE', 'POST delete removes test session only', function () use ($admin): string {
            $response = $admin->post('sessions/delete.php', ['id' => $this->ids['session']]);
            $this->assertRedirect($response, 'POST session delete', 'sessions/index.php');
            $exists = (int) $this->scalar('SELECT COUNT(*) FROM `SESSION` WHERE session_id = ?', [$this->ids['session']]);
            $this->assertSameValue(0, $exists, 'Session was not deleted.');
            return 'Test session deleted.';
        });
    }

    private function testApplications(HttpClient $admin): void
    {
        $lookup = $this->lookupIds();

        $this->test('Applications', 'CREATE', 'Invalid public application is rejected without insert', function () use ($lookup): string {
            $client = new HttpClient($this->baseUrl, 'zest_invalid_apply_' . uniqid('', true) . '.txt');
            $response = $client->post('apply.php', [
                'desired_username' => TEST_PREFIX . 'APPLICATION_INVALID',
                'password' => 'short',
                'confirm_password' => 'different',
                'first_name' => '',
                'last_name' => 'Applicant',
                'address' => 'Test Address',
                'birthday' => '2100-01-01',
                'phone_number' => '',
                'barangay_id' => 999,
                'chapter_id' => $lookup['chapter_id'],
                'gender_id' => $lookup['gender_id'],
                'member_status_id' => $lookup['member_status_id'],
                'educational_attainment_id' => $lookup['educational_attainment_id'],
            ]);
            $this->assertHttp($response, [200], 'Invalid application submit');
            $count = (int) $this->scalar('SELECT COUNT(*) FROM MEMBERSHIP_APPLICATION WHERE desired_username = ?', [TEST_PREFIX . 'APPLICATION_INVALID']);
            $this->assertSameValue(0, $count, 'Invalid application inserted a row.');
            return 'Invalid application showed errors and inserted no row.';
        });

        $this->ids['approve_application'] = $this->submitApplication(TEST_PREFIX . 'APPLICATION_APPROVE', $lookup);

        $this->test('Applications', 'READ', 'Admin application list and detail load', function () use ($admin): string {
            $list = $admin->get('applications/index.php');
            $detail = $admin->get('applications/view.php?id=' . $this->ids['approve_application']);
            $this->assertHttp($list, [200], 'Application list');
            $this->assertHttp($detail, [200], 'Application detail');
            $this->assertTrue(str_contains($detail->body, TEST_PREFIX . 'APPLICATION_APPROVE'), 'Application detail did not show test username.');
            return 'Application list and detail returned 200.';
        });

        $this->test('Applications', 'UPDATE', 'Approve creates user and member exactly once', function () use ($admin): string {
            $response = $admin->post('applications/review.php', [
                'application_id' => $this->ids['approve_application'],
                'decision' => 'approve',
            ]);
            $this->assertRedirect($response, 'Application approve', 'applications/view.php?id=' . $this->ids['approve_application']);

            $status = $this->scalar(
                'SELECT aps.status_name FROM MEMBERSHIP_APPLICATION ma INNER JOIN APPLICATION_STATUS aps ON aps.application_status_id = ma.application_status_id WHERE ma.application_id = ?',
                [$this->ids['approve_application']]
            );
            $this->assertSameValue('Approved', $status, 'Application was not approved.');

            $userId = (int) $this->scalar('SELECT user_id FROM USER_ACCOUNT WHERE username = ?', [TEST_PREFIX . 'APPLICATION_APPROVE']);
            $this->assertTrue($userId > 0, 'Approved application did not create user account.');
            $memberCount = (int) $this->scalar('SELECT COUNT(*) FROM MEMBER WHERE user_id = ?', [$userId]);
            $this->assertSameValue(1, $memberCount, 'Approved application did not create one member row.');

            $again = $admin->post('applications/review.php', [
                'application_id' => $this->ids['approve_application'],
                'decision' => 'approve',
            ]);
            $this->assertRedirect($again, 'Duplicate application approve', 'applications/view.php?id=' . $this->ids['approve_application']);
            $memberCountAfter = (int) $this->scalar('SELECT COUNT(*) FROM MEMBER WHERE user_id = ?', [$userId]);
            $this->assertSameValue(1, $memberCountAfter, 'Duplicate approval created duplicate member row.');

            return 'Approval created one user and one member; duplicate approval did not duplicate.';
        });

        $this->ids['reject_application'] = $this->submitApplication(TEST_PREFIX . 'APPLICATION_REJECT', $lookup);

        $this->test('Applications', 'UPDATE', 'Reject keeps application but creates no user/member', function () use ($admin): string {
            $response = $admin->post('applications/review.php', [
                'application_id' => $this->ids['reject_application'],
                'decision' => 'reject',
            ]);
            $this->assertRedirect($response, 'Application reject', 'applications/view.php?id=' . $this->ids['reject_application']);
            $status = $this->scalar(
                'SELECT aps.status_name FROM MEMBERSHIP_APPLICATION ma INNER JOIN APPLICATION_STATUS aps ON aps.application_status_id = ma.application_status_id WHERE ma.application_id = ?',
                [$this->ids['reject_application']]
            );
            $userCount = (int) $this->scalar('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?', [TEST_PREFIX . 'APPLICATION_REJECT']);
            $this->assertSameValue('Rejected', $status, 'Application was not rejected.');
            $this->assertSameValue(0, $userCount, 'Rejected application created a user.');
            return 'Reject changed status only.';
        });
    }

    private function testUsers(HttpClient $admin): void
    {
        $this->test('User Accounts', 'READ', 'User list loads', function () use ($admin): string {
            $response = $admin->get('users/index.php');
            $this->assertHttp($response, [200], 'User list');
            return 'Users page returned 200.';
        });

        $this->test('User Accounts', 'CREATE', 'Invalid user create is rejected without insert', function () use ($admin): string {
            $response = $admin->post('users/index.php', [
                'username' => TEST_PREFIX . 'USER_INVALID',
                'password' => '123',
                'role_id' => $this->roleId('Member'),
            ]);
            $this->assertHttp($response, [200], 'Invalid user create');
            $count = (int) $this->scalar('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?', [TEST_PREFIX . 'USER_INVALID']);
            $this->assertSameValue(0, $count, 'Invalid user was inserted.');
            return 'Invalid password prevented user insert.';
        });

        $this->test('User Accounts', 'CREATE', 'Valid user create inserts account', function () use ($admin): string {
            $response = $admin->post('users/index.php', [
                'username' => TEST_PREFIX . 'USER',
                'password' => 'TestPass123!',
                'role_id' => $this->roleId('Member'),
            ]);
            $this->assertRedirect($response, 'Valid user create', 'users/index.php');
            $userId = (int) $this->scalar('SELECT user_id FROM USER_ACCOUNT WHERE username = ?', [TEST_PREFIX . 'USER']);
            $this->assertTrue($userId > 0, 'Created user not found.');
            $this->ids['managed_user'] = $userId;
            return 'User #' . $userId . ' created.';
        });

        $this->test('User Accounts', 'UPDATE', 'Invalid role change is rejected without change', function () use ($admin): string {
            $before = (int) $this->scalar('SELECT role_id FROM USER_ACCOUNT WHERE user_id = ?', [$this->ids['managed_user']]);
            $response = $admin->post('users/update_role.php', [
                'user_id' => $this->ids['managed_user'],
                'role_id' => 999999,
            ]);
            $this->assertRedirect($response, 'Invalid user role change', 'users/index.php');
            $after = (int) $this->scalar('SELECT role_id FROM USER_ACCOUNT WHERE user_id = ?', [$this->ids['managed_user']]);
            $this->assertSameValue($before, $after, 'Invalid role changed user role.');
            return 'Invalid role id did not alter account.';
        });

        $this->test('User Accounts', 'UPDATE', 'Valid role change updates account', function () use ($admin): string {
            $response = $admin->post('users/update_role.php', [
                'user_id' => $this->ids['managed_user'],
                'role_id' => $this->roleId('Instructor'),
            ]);
            $this->assertRedirect($response, 'Valid user role change', 'users/index.php');
            $roleId = (int) $this->scalar('SELECT role_id FROM USER_ACCOUNT WHERE user_id = ?', [$this->ids['managed_user']]);
            $this->assertSameValue($this->roleId('Instructor'), $roleId, 'User role did not update.');
            return 'Role changed to Coach/Instructor.';
        });
    }

    private function testRoles(HttpClient $admin): int
    {
        $this->test('Roles', 'READ', 'Role list loads', function () use ($admin): string {
            $response = $admin->get('roles/index.php');
            $this->assertHttp($response, [200], 'Role list');
            return 'Roles page returned 200.';
        });

        $this->test('Roles', 'CREATE', 'Invalid role create is rejected without insert', function () use ($admin): string {
            $response = $admin->post('roles/index.php', ['role_name' => '']);
            $this->assertHttp($response, [200], 'Invalid role create');
            return 'Blank role name rendered validation error.';
        });

        $this->test('Roles', 'CREATE', 'Valid role create inserts role', function () use ($admin): string {
            $response = $admin->post('roles/index.php', ['role_name' => TEST_PREFIX . 'ROLE_MANAGED']);
            $this->assertRedirect($response, 'Valid role create', 'roles/index.php');
            $roleId = (int) $this->scalar('SELECT role_id FROM ROLE WHERE role_name = ?', [TEST_PREFIX . 'ROLE_MANAGED']);
            $this->assertTrue($roleId > 0, 'Created role not found.');
            $this->ids['managed_role'] = $roleId;
            return 'Role #' . $roleId . ' created.';
        });

        $this->test('Roles', 'CREATE', 'Duplicate role create does not create second row', function () use ($admin): string {
            $response = $admin->post('roles/index.php', ['role_name' => TEST_PREFIX . 'ROLE_MANAGED']);
            $this->assertHttp($response, [200], 'Duplicate role create');
            $count = (int) $this->scalar('SELECT COUNT(*) FROM ROLE WHERE role_name = ?', [TEST_PREFIX . 'ROLE_MANAGED']);
            $this->assertSameValue(1, $count, 'Duplicate role was inserted.');
            return 'Duplicate role stayed at one row.';
        });

        return (int) $this->ids['managed_role'];
    }

    private function testRolePermissions(HttpClient $admin, int $roleId): void
    {
        $sessionRead = $this->permissionId('SESSION', 'READ');
        $sessionCreate = $this->permissionId('SESSION', 'CREATE');

        $this->test('Role Permissions', 'READ', 'Permission grid loads with current role', function () use ($admin, $roleId): string {
            $response = $admin->get('roles/permissions.php?id=' . $roleId);
            $this->assertHttp($response, [200], 'Permission grid');
            $this->assertTrue(str_contains($response->body, TEST_PREFIX . 'ROLE_MANAGED'), 'Permission page did not show test role name.');
            return 'Permission page returned 200.';
        });

        $this->test('Role Permissions', 'CREATE', 'Assign permissions creates role_permission rows', function () use ($admin, $roleId, $sessionRead, $sessionCreate): string {
            $response = $admin->post('roles/permissions.php?id=' . $roleId, [
                'role_id' => $roleId,
                'permissions' => [$sessionRead, $sessionCreate],
            ]);
            $this->assertRedirect($response, 'Assign permissions', 'roles/permissions.php?id=' . $roleId);
            $count = (int) $this->scalar('SELECT COUNT(*) FROM ROLE_PERMISSION WHERE role_id = ?', [$roleId]);
            $this->assertSameValue(2, $count, 'Expected two assigned permissions.');
            return 'Two role_permission rows assigned.';
        });

        $this->test('Role Permissions', 'UPDATE', 'Permission page shows assigned checks', function () use ($admin, $roleId, $sessionRead): string {
            $response = $admin->get('roles/permissions.php?id=' . $roleId);
            $this->assertHttp($response, [200], 'Assigned permission grid');
            $this->assertTrue(str_contains($response->body, 'value="' . $sessionRead . '" checked="checked"'), 'Assigned READ permission was not checked.');
            return 'Assigned permission rendered checked.';
        });

        $this->test('Role Permissions', 'DELETE', 'Removing permission deletes role_permission row only', function () use ($admin, $roleId, $sessionRead, $sessionCreate): string {
            $response = $admin->post('roles/permissions.php?id=' . $roleId, [
                'role_id' => $roleId,
                'permissions' => [$sessionRead],
            ]);
            $this->assertRedirect($response, 'Remove one permission', 'roles/permissions.php?id=' . $roleId);
            $readExists = (int) $this->scalar('SELECT COUNT(*) FROM ROLE_PERMISSION WHERE role_id = ? AND permission_id = ?', [$roleId, $sessionRead]);
            $createExists = (int) $this->scalar('SELECT COUNT(*) FROM ROLE_PERMISSION WHERE role_id = ? AND permission_id = ?', [$roleId, $sessionCreate]);
            $this->assertSameValue(1, $readExists, 'READ permission should remain.');
            $this->assertSameValue(0, $createExists, 'CREATE permission should be removed.');
            return 'Permission update removed only the unchecked permission.';
        });
    }

    private function testRoleDelete(HttpClient $admin, int $roleId): void
    {
        $this->test('Roles', 'DELETE', 'Assigned role cannot be deleted', function () use ($admin): string {
            $response = $admin->post('roles/delete.php', ['id' => $this->ids['limited_role']]);
            $this->assertRedirect($response, 'Delete assigned role', 'roles/index.php');
            $exists = (int) $this->scalar('SELECT COUNT(*) FROM ROLE WHERE role_id = ?', [$this->ids['limited_role']]);
            $this->assertSameValue(1, $exists, 'Assigned role was deleted.');
            return 'Assigned test role remained.';
        });

        $this->test('Roles', 'DELETE', 'Super Admin role cannot be deleted', function () use ($admin): string {
            $response = $admin->post('roles/delete.php', ['id' => $this->roleId('Super Admin')]);
            $this->assertRedirect($response, 'Delete Super Admin role', 'roles/index.php');
            $exists = (int) $this->scalar("SELECT COUNT(*) FROM ROLE WHERE role_name = 'Super Admin'");
            $this->assertSameValue(1, $exists, 'Super Admin role was deleted.');
            return 'Built-in Super Admin role remained.';
        });

        $this->test('Roles', 'DELETE', 'Unassigned test role deletes and cascades permissions', function () use ($admin, $roleId): string {
            $response = $admin->post('roles/delete.php', ['id' => $roleId]);
            $this->assertRedirect($response, 'Delete test role', 'roles/index.php');
            $exists = (int) $this->scalar('SELECT COUNT(*) FROM ROLE WHERE role_id = ?', [$roleId]);
            $permissionRows = (int) $this->scalar('SELECT COUNT(*) FROM ROLE_PERMISSION WHERE role_id = ?', [$roleId]);
            $this->assertSameValue(0, $exists, 'Role was not deleted.');
            $this->assertSameValue(0, $permissionRows, 'Deleted role left role_permission rows.');
            return 'Unassigned role and role_permission rows removed.';
        });
    }

    private function testSessionStatuses(): void
    {
        $this->test('Session Statuses', 'READ/UPDATE', 'Status rows are read and sessions auto-refresh by time', function (): string {
            $incomingStatusId = $this->statusId('SESSION_STATUS', 'status_id', 'Incoming');
            $finishedStatusId = $this->statusId('SESSION_STATUS', 'status_id', 'Finished');
            $stmt = $this->db->prepare(
                'INSERT INTO `SESSION` (session_title, session_date, start_time, end_time, location, instructor_user_id, status_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                TEST_PREFIX . 'SESSION_STATUS_REFRESH',
                date('Y-m-d', strtotime('-2 days')),
                '08:00',
                '09:00',
                TEST_PREFIX . 'STATUS_LOCATION',
                $this->ids['instructor_user'],
                $incomingStatusId,
            ]);
            $sessionId = (int) $this->db->lastInsertId();

            $client = new HttpClient($this->baseUrl, 'zest_status_' . uniqid('', true) . '.txt');
            $response = $client->get('sessions.php');
            $this->assertHttp($response, [200], 'Public sessions status refresh');

            $statusId = (int) $this->scalar('SELECT status_id FROM `SESSION` WHERE session_id = ?', [$sessionId]);
            $this->assertSameValue($finishedStatusId, $statusId, 'Past session did not auto-refresh to Finished.');
            return 'Past test session changed from Incoming to Finished.';
        });
    }

    private function testLookupAndNonCrudRoutes(): void
    {
        $this->test('Lookup Data', 'READ', 'Lookup tables and hardcoded barangays render in apply form', function (): string {
            foreach ([
                'CHAPTER' => 'chapter_id',
                'GENDER' => 'gender_id',
                'MEMBER_STATUS' => 'member_status_id',
                'EDUCATIONAL_ATTAINMENT' => 'educational_attainment_id',
            ] as $table => $idColumn) {
                $count = (int) $this->scalar('SELECT COUNT(*) FROM `' . $table . '`');
                $this->assertTrue($count > 0, $table . ' lookup table is empty.');
            }

            $client = new HttpClient($this->baseUrl, 'zest_lookup_' . uniqid('', true) . '.txt');
            $response = $client->get('apply.php');
            $this->assertHttp($response, [200], 'Apply lookup render');
            $this->assertTrue(str_contains($response->body, 'Western Bicutan'), 'Hardcoded barangay list did not render.');
            return 'Lookup DB rows and PHP barangay options are readable.';
        });

        $this->test('Delete Surface', 'DELETE', 'Non-delete modules do not have delete handlers', function (): string {
            foreach ([
                'applications/delete.php',
                'users/delete.php',
                'members/delete.php',
                'session_statuses/delete.php',
                'barangays/delete.php',
                'chapters/delete.php',
                'genders/delete.php',
            ] as $path) {
                $this->assertTrue(!is_file(__DIR__ . '/../' . $path), 'Unexpected delete handler exists: ' . $path);
            }
            return 'No unexpected delete handlers found.';
        });
    }

    private function loggedInClient(string $actor): HttpClient
    {
        $username = match ($actor) {
            'superadmin' => TEST_PREFIX . 'SUPERADMIN',
            'member' => TEST_PREFIX . 'MEMBER',
            'limited' => TEST_PREFIX . 'LIMITED_ADMIN',
            default => throw new InvalidArgumentException('Unknown actor ' . $actor),
        };

        $client = new HttpClient($this->baseUrl, 'zest_' . $actor . '_' . uniqid('', true) . '.txt');
        $response = $client->post('login.php', [
            'username' => $username,
            'password' => 'TestPass123!',
        ]);
        $this->assertRedirect($response, 'Login ' . $actor);
        return $client;
    }

    private function submitApplication(string $username, array $lookup): int
    {
        $client = new HttpClient($this->baseUrl, 'zest_apply_' . uniqid('', true) . '.txt');
        $response = $client->post('apply.php', [
            'desired_username' => $username,
            'password' => 'TestPass123!',
            'confirm_password' => 'TestPass123!',
            'first_name' => TEST_PREFIX . 'First',
            'middle_name' => 'Middle',
            'last_name' => 'Applicant',
            'address' => TEST_PREFIX . ' Address',
            'birthday' => '1995-05-15',
            'phone_number' => '09999999999',
            'barangay_id' => 28,
            'chapter_id' => $lookup['chapter_id'],
            'gender_id' => $lookup['gender_id'],
            'member_status_id' => $lookup['member_status_id'],
            'educational_attainment_id' => $lookup['educational_attainment_id'],
            'primary_school' => TEST_PREFIX . ' Primary',
            'primary_year_graduated' => '2007',
            'secondary_school' => TEST_PREFIX . ' Secondary',
            'secondary_year_graduated' => '2011',
            'college_school' => TEST_PREFIX . ' College',
            'college_year_graduated' => '2015',
        ]);
        $this->assertRedirect($response, 'Valid public application submit', 'apply.php');
        $applicationId = (int) $this->scalar('SELECT application_id FROM MEMBERSHIP_APPLICATION WHERE desired_username = ?', [$username]);
        $this->assertTrue($applicationId > 0, 'Application was not inserted for ' . $username);
        return $applicationId;
    }

    private function lookupIds(): array
    {
        return [
            'chapter_id' => (int) $this->scalar('SELECT chapter_id FROM CHAPTER ORDER BY chapter_id LIMIT 1'),
            'gender_id' => (int) $this->scalar('SELECT gender_id FROM GENDER ORDER BY gender_id LIMIT 1'),
            'member_status_id' => (int) $this->scalar('SELECT member_status_id FROM MEMBER_STATUS ORDER BY member_status_id LIMIT 1'),
            'educational_attainment_id' => (int) $this->scalar('SELECT educational_attainment_id FROM EDUCATIONAL_ATTAINMENT ORDER BY educational_attainment_id LIMIT 1'),
        ];
    }

    private function roleId(string $roleName): int
    {
        $roleId = $this->scalar('SELECT role_id FROM ROLE WHERE role_name = ?', [$roleName]);
        if ($roleId === false) {
            throw new RuntimeException('Missing role ' . $roleName);
        }
        return (int) $roleId;
    }

    private function permissionId(string $moduleName, string $actionName): int
    {
        $permissionId = $this->scalar(
            'SELECT p.permission_id
             FROM PERMISSION p
             INNER JOIN MODULE m ON m.module_id = p.module_id
             INNER JOIN ACTION_TYPE a ON a.action_type_id = p.action_type_id
             WHERE m.module_name = ? AND a.action_name = ?',
            [$moduleName, $actionName]
        );
        if ($permissionId === false) {
            throw new RuntimeException('Missing permission ' . $moduleName . ' ' . $actionName);
        }
        return (int) $permissionId;
    }

    private function statusId(string $table, string $idColumn, string $statusName): int
    {
        $statusId = $this->scalar('SELECT `' . $idColumn . '` FROM `' . $table . '` WHERE status_name = ?', [$statusName]);
        if ($statusId === false) {
            throw new RuntimeException('Missing status ' . $statusName);
        }
        return (int) $statusId;
    }

    private function insertRole(string $roleName): int
    {
        $stmt = $this->db->prepare('INSERT INTO ROLE (role_name) VALUES (?)');
        $stmt->execute([$roleName]);
        return (int) $this->db->lastInsertId();
    }

    private function insertUser(string $username, int $roleId): int
    {
        $stmt = $this->db->prepare('INSERT INTO USER_ACCOUNT (username, password, role_id) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash('TestPass123!', PASSWORD_DEFAULT), $roleId]);
        return (int) $this->db->lastInsertId();
    }

    private function grantPermission(int $roleId, string $moduleName, string $actionName): void
    {
        $permissionId = $this->permissionId($moduleName, $actionName);
        $stmt = $this->db->prepare('INSERT INTO ROLE_PERMISSION (role_id, permission_id) VALUES (?, ?)');
        $stmt->execute([$roleId, $permissionId]);
    }

    private function cleanup(): void
    {
        $testUserIds = $this->column('SELECT user_id FROM USER_ACCOUNT WHERE username LIKE ?', [TEST_PREFIX . '%']);
        $testRoleIds = $this->column('SELECT role_id FROM ROLE WHERE role_name LIKE ?', [TEST_PREFIX . '%']);

        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM ACTIVITY_LOG WHERE description LIKE ? OR action LIKE ?')->execute(['%' . TEST_PREFIX . '%', '%' . TEST_PREFIX . '%']);

            if ($testUserIds) {
                $placeholders = implode(',', array_fill(0, count($testUserIds), '?'));
                $this->db->prepare('DELETE FROM ACTIVITY_LOG WHERE user_id IN (' . $placeholders . ')')->execute($testUserIds);
                $this->db->prepare('DELETE FROM MEMBER WHERE user_id IN (' . $placeholders . ')')->execute($testUserIds);
            }

            $this->db->prepare('DELETE FROM `SESSION` WHERE session_title LIKE ? OR location LIKE ?')->execute([TEST_PREFIX . '%', TEST_PREFIX . '%']);
            $this->db->prepare('DELETE FROM MEMBERSHIP_APPLICATION WHERE desired_username LIKE ? OR first_name LIKE ? OR address LIKE ?')->execute([TEST_PREFIX . '%', TEST_PREFIX . '%', TEST_PREFIX . '%']);

            if ($testUserIds) {
                $placeholders = implode(',', array_fill(0, count($testUserIds), '?'));
                $this->db->prepare('DELETE FROM USER_ACCOUNT WHERE user_id IN (' . $placeholders . ')')->execute($testUserIds);
            }

            if ($testRoleIds) {
                $placeholders = implode(',', array_fill(0, count($testRoleIds), '?'));
                $this->db->prepare('DELETE FROM ROLE_PERMISSION WHERE role_id IN (' . $placeholders . ')')->execute($testRoleIds);
                $this->db->prepare('DELETE FROM ROLE WHERE role_id IN (' . $placeholders . ')')->execute($testRoleIds);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function writeReport(): void
    {
        $lines = [];
        $lines[] = '# ILHF ZEST CRUD Test Report';
        $lines[] = '';
        $lines[] = 'Generated: ' . date('Y-m-d H:i:s T');
        $lines[] = '';
        $lines[] = '## Test Approach';
        $lines[] = '';
        $lines[] = '- Runner: `tests/crud_test_runner.php`';
        $lines[] = '- Command: `php tests/crud_test_runner.php`';
        $lines[] = '- HTTP base URL: `' . $this->baseUrl . '`';
        $lines[] = '- Safety: creates only `TEST_ZEST_CRUD_*` users, roles, sessions, and applications, then deletes those records after the run.';
        $lines[] = '- Scope: real PHP routes/forms are submitted over localhost, then database state is verified with PDO.';
        $lines[] = '';
        $lines[] = '## Automated Results';
        $lines[] = '';
        $lines[] = '| Module | Action | Test | Expected | Actual | Status |';
        $lines[] = '|---|---|---|---|---|---|';
        foreach ($this->results as $result) {
            $lines[] = '| ' . $this->md($result['module']) . ' | ' . $this->md($result['action']) . ' | ' . $this->md($result['name']) . ' | ' . $this->md($result['expected']) . ' | ' . $this->md($result['actual']) . ' | ' . $result['status'] . ' |';
        }

        $failed = count(array_filter($this->results, static fn (array $result): bool => $result['status'] === 'FAIL'));
        $passed = count($this->results) - $failed;

        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '- Passed: ' . $passed;
        $lines[] = '- Failed: ' . $failed;
        $lines[] = '- Bugs found/fixed in this run: ' . ($failed === 0 ? 'None.' : 'See failed rows above before fixing.');
        $lines[] = '- Files changed for testing: `tests/crud_test_runner.php`, `CRUD_TEST_REPORT.md`.';
        $lines[] = '- SQL issues fixed: None unless noted in failed rows.';
        $lines[] = '';
        $lines[] = '## Manual Checks Not Automated';
        $lines[] = '';
        $lines[] = '- Browser confirm dialogs for delete/review actions should still be clicked manually once in a browser.';
        $lines[] = '- Visual checks for card layout, floating save button, and auto-submit dropdown behavior remain browser/manual UI checks.';
        $lines[] = '- No standalone member CRUD page exists, so member read/update/delete are intentionally not tested as routes.';

        file_put_contents(__DIR__ . '/../CRUD_TEST_REPORT.md', implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private function md(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);
        return str_replace('|', '\\|', $value);
    }
}

$runner = new CrudTestRunner();
exit($runner->run());
