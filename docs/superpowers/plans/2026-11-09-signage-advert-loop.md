# Gyros City advert loop implementation plan

> **For agentic workers:** Use superpowers:executing-plans inline. The user has approved autonomous implementation.

**Goal:** Turn the local TV preview into a continuously running advert with active food motion, automatic menu pages and individual pizza slides.

**Architecture:** Extend the existing shared renderer with a disposable slideshow and cycling grid focus. A local demo-program builder supplies current catalogue bindings; the demo and admin preview automatically play scenes using the existing renderer. No database schema or production changes.

**Tech Stack:** Laravel 9, Blade, vanilla JavaScript/CSS, PHPUnit and Node tests, existing Playwright.

**Spec:** User request in this session and `docs/signage/implementation-plan.md`; exact copies require the original videos, still unavailable.

## Global constraints

- Keep live price expiry, unavailable-product filtering and private media handling.
- Local only; no credentials, production changes, dependency installation or migration.
- Preserve stable product/photo/video nodes on price refresh.
- Maintain 1920x1080 and 1280x720 readability and reduced-motion support.

## Review focus

- Removed active pizza must advance to an available product without stale prices.
- Scene replacement, expiry and fallback must clear all old animation timers.
- Price refresh must preserve the current pizza and slide clock.
- Slideshow duration must include every configured card before the playlist advances.
- Demo catalogue refresh must not restart the advert; preview must loop without clicks.

### Task 1: Shared animated scenes

- [x] Add failing DOM tests for slideshow advance/wrap, availability, price stability and disposal.
- [x] Implement stage lifecycle, timed slides and grid focus in `public/signage/renderer.js`.
- [x] Add advert typography, background movement, photo motion and slide choreography in `public/signage/player.css`.
- [x] Run renderer Node tests and browser geometry/motion checks.

### Task 2: Configurable pizza scenes

- [x] Add validator tests for slideshow limits, timing and derived full-cycle duration.
- [x] Extend `SignageSceneDefinition`, controller allowlist and existing admin form with slideshow/slide_seconds.
- [x] Run focused PHP tests and Blade syntax checks.

### Task 3: Automatically playing demo and preview

- [x] Add `SignageDemoPrograms` for burgers, available menu pages and all valid pizzas.
- [x] Make the local demo automatically cycle scenes with program selection and pause/next/fullscreen controls.
- [x] Make admin playlist preview autoplay with video-end handling and pause.
- [x] Test actual HTTP catalogue, all pizza slides, program loops, live price updates, expiry, reduced motion and both TV sizes.
- [x] Update design/operations/work log; run full relevant suites and review the final diff.
