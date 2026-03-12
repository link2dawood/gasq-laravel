# GASQ Laravel — Documentation

This directory contains project documentation for the GASQ Laravel application (security services marketplace and calculators).

## Contents

| Document | Description |
|----------|-------------|
| [Architecture](architecture.md) | High-level architecture, stack, and data flow |
| [Setup](setup.md) | Local setup (native and Docker) |
| [Modules](modules.md) | Feature modules and implementation status |
| [Development](development.md) | Development workflow, commands, and conventions |

## Quick links

- **Local run (Docker)**: See [Setup — Docker](setup.md#docker-local-only).
- **After clone**: `composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`.
- **Plan**: Feature build is driven by the Laravel Bootstrap Rebuild Plan (extend auth, data model, marketplace, calculators, admin, reports).
