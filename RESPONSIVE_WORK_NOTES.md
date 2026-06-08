# Responsive Work Notes

These are implementation notes for the ILHF ZEST responsive pass so the same setup mistakes are not repeated.

## What went wrong first

- The first Playwright URL builder used leading `/` paths, which dropped the project subfolder and opened `http://localhost/login.php` instead of `http://localhost/IM%20Project/login.php`.
- The screenshot runner tried to click the public mobile menu at `768px`, but that trigger is only visible below the app's `max-width: 767px` breakpoint.
- The runner also assumed a menu trigger should always be clickable if it exists in the DOM. In this project, some triggers remain in the markup while CSS hides them.

## Rules for this project

- Always build localhost URLs relative to `http://localhost/IM%20Project/`, never with a leading slash.
- Before clicking any responsive nav trigger, check visibility, not just existence.
- Match the real CSS breakpoints in `assets/app.css` before deciding which mobile interactions to test.
- Use isolated `TEST_ZEST_RESP_*` accounts for admin responsive checks instead of touching real user accounts.
- Separate harness bugs from UI bugs. Fix the test script first when the failure is clearly in the automation path.
- Recheck any off-canvas sidebar that uses `transform` after adding reveal animations. A generic reveal class can accidentally force the menu visible on mobile.
- When a page has no full-page overflow but still feels cramped, inspect table-heavy admin pages next. Internal table wrappers can hide usability problems that overflow metrics alone will not catch.

## Current helper files

- `zest_responsive_seed.php`: creates and cleans up isolated responsive test users.
- `zest_playwright_check.js`: captures viewport screenshots and layout metrics.

## Responsive findings from this pass

- The repeated Playwright `pageErrors` were caused by the template calling `$("#tabs").tabs()` even on pages where jQuery UI Tabs was not present.
- The most meaningful small-screen layout weakness was concentrated in the three table pages: Users, Roles, and Logs.
- Converting those table pages into stacked mobile rows was more effective than relying on a horizontal scroll wrapper alone.
- The admin mobile sidebar looked open by default because the shared reveal animation overrode its off-canvas `transform`, not because the toggle logic was wrong.
