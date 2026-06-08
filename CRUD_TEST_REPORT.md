# ILHF ZEST CRUD Test Report

Generated: 2026-06-08 13:59:53 PST

## Test Approach

- Runner: `tests/crud_test_runner.php`
- Command: `php tests/crud_test_runner.php`
- HTTP base URL: `http://localhost/IM%20Project`
- Safety: creates only `TEST_ZEST_CRUD_*` users, roles, sessions, and applications, then deletes those records after the run.
- Scope: real PHP routes/forms are submitted over localhost, then database state is verified with PDO.

## Automated Results

| Module | Action | Test | Expected | Actual | Status |
|---|---|---|---|---|---|
| Environment | READ | Database and required seed rows exist | Request/query succeeds and database state matches expectations. | Required roles, actions, and status rows exist. | PASS |
| Test Setup | CREATE | Create isolated test users and limited role | Request/query succeeds and database state matches expectations. | Created test users only. | PASS |
| Public Pages | READ | Landing page loads without admin controls | Request/query succeeds and database state matches expectations. | Landing page returned 200 and no management links were exposed. | PASS |
| Public Pages | READ | Public sessions page loads without admin controls | Request/query succeeds and database state matches expectations. | Public sessions page returned 200 and stayed read-only. | PASS |
| Public Pages | READ | Apply and login pages load | Request/query succeeds and database state matches expectations. | Apply and login pages returned 200. | PASS |
| Authentication | READ | Unauthenticated admin request redirects to login | Request/query succeeds and database state matches expectations. | Guest was redirected to login. | PASS |
| Authentication | CREATE | Failed login logs safely and does not authenticate | Request/query succeeds and database state matches expectations. | Invalid credentials stayed unauthenticated. | PASS |
| Authentication | CREATE | Valid Super Admin login and logout | Request/query succeeds and database state matches expectations. | Login reached dashboard; logout cleared session. | PASS |
| Permissions | READ | Member cannot manually access admin CRUD URLs | Request/query succeeds and database state matches expectations. | Member was redirected away from protected admin routes. | PASS |
| Permissions | READ | Limited admin can read applications but cannot open unrelated admin actions | Request/query succeeds and database state matches expectations. | Limited role enforced per-module permissions. | PASS |
| Public Pages | READ | Logged-in public landing does not expose full admin controls | Request/query succeeds and database state matches expectations. | Only Dashboard/Logout shortcuts appeared on public layout. | PASS |
| Sessions | READ | Admin session list loads | Request/query succeeds and database state matches expectations. | Manage Sessions returned 200. | PASS |
| Sessions | CREATE | Invalid session create is rejected without DB insert | Request/query succeeds and database state matches expectations. | Invalid time order rendered validation error and inserted no row. | PASS |
| Sessions | CREATE | Valid session create inserts row and redirects | Request/query succeeds and database state matches expectations. | Session #29 created. | PASS |
| Sessions | READ | Session detail loads created record | Request/query succeeds and database state matches expectations. | Session detail returned 200. | PASS |
| Sessions | UPDATE | Invalid session update does not change row | Request/query succeeds and database state matches expectations. | Invalid update inserted no changes. | PASS |
| Sessions | UPDATE | Valid session update changes row and redirects | Request/query succeeds and database state matches expectations. | Session updated and redirected to detail. | PASS |
| Sessions | DELETE | GET delete is blocked and does not remove row | Request/query succeeds and database state matches expectations. | Delete route is POST-only. | PASS |
| Sessions | DELETE | POST delete removes test session only | Request/query succeeds and database state matches expectations. | Test session deleted. | PASS |
| Applications | CREATE | Invalid public application is rejected without insert | Request/query succeeds and database state matches expectations. | Invalid application showed errors and inserted no row. | PASS |
| Applications | READ | Admin application list and detail load | Request/query succeeds and database state matches expectations. | Application list and detail returned 200. | PASS |
| Applications | UPDATE | Approve creates user and member exactly once | Request/query succeeds and database state matches expectations. | Approval created one user and one member; duplicate approval did not duplicate. | PASS |
| Applications | UPDATE | Reject keeps application but creates no user/member | Request/query succeeds and database state matches expectations. | Reject changed status only. | PASS |
| User Accounts | READ | User list loads | Request/query succeeds and database state matches expectations. | Users page returned 200. | PASS |
| User Accounts | CREATE | Invalid user create is rejected without insert | Request/query succeeds and database state matches expectations. | Invalid password prevented user insert. | PASS |
| User Accounts | CREATE | Valid user create inserts account | Request/query succeeds and database state matches expectations. | User #22 created. | PASS |
| User Accounts | UPDATE | Invalid role change is rejected without change | Request/query succeeds and database state matches expectations. | Invalid role id did not alter account. | PASS |
| User Accounts | UPDATE | Valid role change updates account | Request/query succeeds and database state matches expectations. | Role changed to Coach/Instructor. | PASS |
| Roles | READ | Role list loads | Request/query succeeds and database state matches expectations. | Roles page returned 200. | PASS |
| Roles | CREATE | Invalid role create is rejected without insert | Request/query succeeds and database state matches expectations. | Blank role name rendered validation error. | PASS |
| Roles | CREATE | Valid role create inserts role | Request/query succeeds and database state matches expectations. | Role #12 created. | PASS |
| Roles | CREATE | Duplicate role create does not create second row | Request/query succeeds and database state matches expectations. | Duplicate role stayed at one row. | PASS |
| Role Permissions | READ | Permission grid loads with current role | Request/query succeeds and database state matches expectations. | Permission page returned 200. | PASS |
| Role Permissions | CREATE | Assign permissions creates role_permission rows | Request/query succeeds and database state matches expectations. | Two role_permission rows assigned. | PASS |
| Role Permissions | UPDATE | Permission page shows assigned checks | Request/query succeeds and database state matches expectations. | Assigned permission rendered checked. | PASS |
| Role Permissions | DELETE | Removing permission deletes role_permission row only | Request/query succeeds and database state matches expectations. | Permission update removed only the unchecked permission. | PASS |
| Roles | DELETE | Assigned role cannot be deleted | Request/query succeeds and database state matches expectations. | Assigned test role remained. | PASS |
| Roles | DELETE | Super Admin role cannot be deleted | Request/query succeeds and database state matches expectations. | Built-in Super Admin role remained. | PASS |
| Roles | DELETE | Unassigned test role deletes and cascades permissions | Request/query succeeds and database state matches expectations. | Unassigned role and role_permission rows removed. | PASS |
| Session Statuses | READ/UPDATE | Status rows are read and sessions auto-refresh by time | Request/query succeeds and database state matches expectations. | Past test session changed from Incoming to Finished. | PASS |
| Lookup Data | READ | Lookup tables and hardcoded barangays render in apply form | Request/query succeeds and database state matches expectations. | Lookup DB rows and PHP barangay options are readable. | PASS |
| Delete Surface | DELETE | Non-delete modules do not have delete handlers | Request/query succeeds and database state matches expectations. | No unexpected delete handlers found. | PASS |

## Summary

- Passed: 42
- Failed: 0
- Bugs found/fixed in this run: None.
- Files changed for testing: `tests/crud_test_runner.php`, `CRUD_TEST_REPORT.md`.
- SQL issues fixed: None unless noted in failed rows.

## Manual Checks Not Automated

- Browser confirm dialogs for delete/review actions should still be clicked manually once in a browser.
- Visual checks for card layout, floating save button, and auto-submit dropdown behavior remain browser/manual UI checks.
- No standalone member CRUD page exists, so member read/update/delete are intentionally not tested as routes.
