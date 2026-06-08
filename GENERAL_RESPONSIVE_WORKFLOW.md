# General Responsive Workflow

This is a reusable workflow for doing a responsive UI pass on an existing web app without redesigning it.

## 1. Inspect before editing

- Identify the major routes and page types: landing, auth, forms, list views, detail views, admin pages.
- Find the shared layout files, shared CSS, shared JS, and any template assets that affect navigation or layout.
- Check whether the project already has browser automation, screenshot tooling, or a test harness you can reuse.

## 2. Define the viewport matrix

Use a fixed viewport set so comparisons stay consistent:

- `1920x1080`
- `1366x768`
- `1024x768`
- `768x1024`
- `430x932`
- `390x844`
- `360x800`

If the app has a known audience or device profile, add those after the baseline matrix, not instead of it.

## 3. Set up safe test access

- Prefer isolated test accounts over real user credentials.
- Keep test data clearly prefixed.
- Use cleanup scripts or teardown steps from the start, not at the end.

## 4. Run the first screenshot sweep

For each major page and viewport:

- open the page
- capture screenshots
- record console errors
- record page errors
- measure horizontal overflow
- record obvious DOM elements that exceed the viewport

Do not start patching CSS until you know whether the failure is:

- a real layout issue
- a runtime JS error
- a bad selector in the test harness
- a state leak between screenshots

## 5. Separate harness bugs from UI bugs

Before changing the app, verify:

- URL building is correct for subfolder deployments
- mobile-only controls are tested only at the breakpoints where they are actually visible
- menus, drawers, and toggles are reset between screenshots
- the automation is not reusing stale open/closed UI state across pages

## 6. Fix shared layout first

Start with the most central files:

- shared header/navbar
- shared sidebar/admin shell
- shared card/grid utilities
- shared form spacing
- shared table wrappers

Prefer improvements that solve multiple pages at once:

- flexible widths
- wrapping actions
- grid/flex cleanup
- max-width and minmax rules
- media-query corrections

## 7. Fix real runtime errors early

If screenshot runs show repeated console or page errors:

- guard optional template/plugin calls
- avoid leaving broken JS in place while doing CSS work
- rerun the browser checks after fixing the error so the later screenshots reflect the real UI state

## 8. Handle tables deliberately

For table-heavy pages on small screens, choose one approach intentionally:

- horizontal scroll wrapper when the data must stay tabular
- stacked card rows when readability matters more than strict column layout
- selective column reduction only if the hidden data is genuinely low priority

Do not assume “no page overflow” means the table is usable.

## 9. Watch for transform conflicts

Off-canvas nav, drawers, floating bars, and animated panels often use `transform`.

When adding reveal animations or motion utilities:

- check whether they also write to `transform`
- exclude off-canvas components if needed
- verify open and closed states visually after the animation change

## 10. Re-test after each meaningful patch set

After a patch round:

- rerun screenshots
- compare the worst pages first
- recheck console/page errors
- recheck overflow metrics
- confirm the fixes did not break auth, CRUD pages, or navigation

## 11. Document while working

Keep two kinds of notes:

- project-specific notes: exact bugs, selectors, breakpoints, and quirks
- general workflow notes: reusable lessons and process guardrails

This prevents repeating the same mistakes in later responsive passes.

## 12. Clean up temporary tooling

At the end:

- remove temporary seed scripts
- remove temporary screenshot helpers if they are not meant to live in the project
- keep only the documentation or reusable workflow files that are intentionally part of the deliverable

## 13. Final verification checklist

- no unexpected console/page errors
- no horizontal page overflow on target pages
- nav remains usable on mobile
- forms stack cleanly
- buttons do not overflow
- card grids wrap properly
- admin shell behaves on tablet and phone widths
- table-heavy pages are readable and operable
- no unrelated business logic or database behavior was changed
