# Simple TV playlists implementation plan

> Execute inline using the existing Laravel/Blade and shared TV renderer. Keep changes uncommitted for review.

**Goal:** Keep food images inside the TV frame and make each playlist easy to edit and repeat continuously.

**Architecture:** Retain immutable downloaded releases. One playlist form saves the list and creates a new release for explicitly selected screens in one transaction. The continuous manifest ignores legacy schedules; prices keep their independent expiry safeguards.

**Tech stack:** Laravel 9, Blade, Bootstrap, vanilla JS/CSS, PHPUnit, Node tests, local browser.

**Spec:** User request of 2026-11-11: fix cropped pictures; editable individual playlists; simplest practical admin; no scheduling; infinite playback while the TV runs.

## Constraints and review focus

- Work in the existing clean `feature/gctv-v1-local` checkout served by Apache.
- No credentials, production changes, schema changes, commits, pushes or deployment.
- Saving must affect only selected TVs and roll back completely if a release cannot be built.
- Detect stale names as well as stale scene order; preserve duplicate scenes and draft-only saves.
- Keep existing release downloads atomic and price expiry intact.
- Check all reference scenes at 1920×1080 and 1280×720, including animation extremes.

## Tasks

- [x] Add failing PHP tests for selected-screen updates, atomic failure and stale edits; implement transactional playlist saving and version checking.
- [x] Add continuous-playback regression tests; remove the schedule tab and schedule selection from new manifests without deleting stored schedules.
- [x] Simplify the playlist form and JS: list selection, add/reorder/remove, scene content links, selected screens, clear save action and empty state.
- [x] Correct reference image sizing and margins; verify image bounds in the actual browser and keep the existing visual identity.
- [x] Run relevant PHP/Node suites, syntax and diff checks; inspect desktop/narrow admin rendering; update operations and design guidance.

Ruling: periodic polling of an unchanged package reset the scene index and prevented later scenes from playing. Added a failing two-cycle regression and preserved the current position when the layout revision is unchanged.

Independent read-only review found no material issue. Browser admin checks use the real rendered Blade/CSS and admin script with form submission intercepted; backend writes are exercised only against in-memory SQLite fixtures.

Final verification: full PHP suite 340 tests / 1465 assertions; all 89 frontend Node tests; 507 browser bounds samples per TV size; desktop/narrow editor interaction and save payload checks; PHP/JS syntax and diff checks passed. See `docs/signage/acceptance.md` for the local evidence and hardware limitations.
