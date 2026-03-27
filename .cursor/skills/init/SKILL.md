---
name: init
description: Understand a project quickly by reading key files and mapping architecture, routes, data flow, and setup steps. Use when the user says init, onboarding, understand the codebase, or asks for a project overview.
---

# Init

## Purpose

Create a fast, reliable codebase understanding pass for a new or existing project.

## When To Use

Use this skill when the user asks to:
- initialize context
- understand the project
- read the codebase
- onboard quickly
- summarize architecture or setup

## Workflow

1. Identify project type and stack:
   - Read top-level files: `README.md`, `package.json`, `composer.json`, `pyproject.toml`, `go.mod`, `Dockerfile`, `docker-compose.yml`.
   - Infer runtime, framework, package manager, and entry points.

2. Map execution flow:
   - Locate route definitions and primary controllers/handlers.
   - Locate startup/bootstrap files and config loading path.
   - Note where auth, middleware, and environment variables are applied.

3. Map data layer:
   - Identify models/entities and migrations/schema files.
   - Identify service/repository layer (if present).
   - Capture important relationships and key business tables.

4. Map frontend layer (if present):
   - Identify page routes, shared layouts, and major components.
   - Note state/data fetching patterns and API integration points.

5. Extract run/build/test commands:
   - Dev server command(s)
   - Build command(s)
   - Test and lint command(s)
   - Required services (DB, Redis, queues, containers)

6. Produce output:
   - Keep concise and structured.
   - Focus on what helps a new contributor start working immediately.

## Output Format

Use this format:

```markdown
## Project Snapshot
- Stack:
- App type:
- Entry points:

## Architecture Map
- Routing:
- Core modules:
- Data layer:
- Frontend layer:

## Runbook
- Install:
- Dev:
- Build:
- Test/Lint:

## High-Value Files
- `path/to/file` - why it matters

## Open Questions / Risks
- ...
```

## Rules

- Prefer concrete paths and symbols over generic statements.
- Do not dump large file contents.
- If information is missing, explicitly mark it as unknown.
- Keep findings factual; avoid speculative design commentary.

