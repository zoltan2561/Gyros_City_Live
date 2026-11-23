# Production signage export implementation plan

> **For agentic workers:** Use superpowers:executing-plans in this session. The user subsequently explicitly requested the PROD push; commit and push only the reviewed production feature.

**Goal:** Let every authorized signage administrator start and download an MP4 from the live admin.

**Architecture:** Private filesystem job records, a single host-wide render lock, a detached bounded PHP worker, and authenticated start/status/download endpoints. Reuse the existing renderer and saved playlist snapshot.

**Tech Stack:** Laravel 9 / PHP 8.2, Node 22, Playwright Chromium, FFmpeg, existing Blade/JavaScript admin.

**Spec:** ../specs/2026-11-23-signage-production-export-design.md

## Global constraints

- Preserve user edits; no migration, credential or .env changes. Commit and PROD push were subsequently requested explicitly.
- Runtime files stay outside webroot; export files stay in private Laravel storage.
- One render at a time; job ownership and existing module permission protect every endpoint.
- Minimum ten minutes of complete cycles, Full HD silent H.264 output.
- Production changes are authorized by the user's approval; back up and read back every deployed file.

## Review focus

- Background launch fails or exits before recording status: report a bounded failure and allow retry.
- Two admins start together: atomic lock admits exactly one worker.
- Other users guess identifiers: return 404 and never expose output paths or job payload.
- Page reload or network failure: restore latest own playlist job without duplicating render.
- Partial output, runaway render or stale lock: download only completed outputs and bound the worker lifetime.

## Tasks

- [x] Runtime feasibility: pinned Node 22.23.5, Playwright 1.65.1 / Chromium 159.0.8154.6 and H.264 FFmpeg installed privately; actual 600-second 1920x1080 H.264/yuv420p export succeeded.
- [x] Added failing service/controller and frontend tests, then verified green.
- [x] Implemented private jobs, bounded background CLI worker, authenticated actions and explicit permission mappings.
- [x] Updated existing form and JavaScript with start/poll/restore/download.
- [x] Full PHP suite passed: 422 tests, 2310 assertions; Node suite 108 tests. Independent review findings fixed. Real host timeout returned 124 and left zero scoped browser/recorder processes.
- [x] Repeated checks on the production-baseline release worktree: 422 PHP tests / 2310 assertions and 106 Node tests passed. Code and database backups were verified outside webroot before deployment.
- [ ] Back up production code/database state outside webroot, deploy only this feature's approved files, clear relevant generated caches, verify readback and real production export. Record runtime versions and any remaining physical-TV limitation.

## Execution notes

- The user switched the original checkout to PROD during execution. Feature work moved to an isolated worktree. The release started from the production commit a4f6dd7; PROD was advanced through that already-live baseline. The user subsequently explicitly requested all existing main commits too, so main through 53f14ba is merged into the release, retaining the newer production export implementation where the old local exporter conflicts.
- The approved architecture is implemented directly; the user has twice approved proceeding to production, so there is no additional plan approval gate.
