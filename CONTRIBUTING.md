# Contributing to guardrate

Thanks for considering a contribution. This document covers how to get set up, what a good
change looks like, and how to get it merged.

## Ground rules

- Be civil. The [Code of Conduct](CODE_OF_CONDUCT.md) applies everywhere in this project.
- **Never open a public issue for a security vulnerability** — see [SECURITY.md](SECURITY.md).
- **Never commit real data.** No customer records, no live wage tables, no client documents,
  no `.env`. Only `.env.example` belongs in git. Anything committed to a public repository must
  be treated as permanently public, even if a later commit removes it.

## Getting set up

```bash
git clone https://github.com/<your-fork>/guardrate.git
cd guardrate

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate
php artisan migrate --seed

php artisan serve
```

Running the tests:

```bash
php artisan test
```

SQLite in memory is used for tests — no database setup needed. See `phpunit.xml`.

## Before you start work

- **Open an issue first** for anything beyond a small fix. It saves you building something that
  turns out to be heading the wrong way.
- Check the issue isn't already taken. Comment to claim it.
- Issues labelled `good first issue` are deliberately self-contained.

## Making a change

Branch from `main`:

```bash
git switch -c fix/short-description
```

**Match the surrounding code.** This codebase has consistent conventions — follow the file
you're editing rather than importing a different house style.

- PSR-12 for PHP; Laravel conventions for structure
- Explain *why*, not *what*, in comments — the code already says what it does
- Keep changes focused. One concern per pull request
- Add tests for behaviour you change or add
- Don't reformat unrelated lines; it buries the actual change in noise

### Database changes

- Always add a migration; never edit an existing one that has shipped
- Provide a working `down()`
- Guard against missing tables/columns where a migration touches data

### Anything user-facing

- Check it works signed out as well as signed in
- Check it works on a phone-width screen
- Don't break the no-JavaScript path where one currently exists

## Commit messages

Explain the reasoning, not just the change:

```
Fix relief factor ignoring paid holidays

The coverage calculation divided annual post hours by 2080, which assumes an
officer is available every working day. Holidays and PTO are paid but not
worked, so the required headcount came out roughly 8% low on 24/7 posts.
```

## Pull requests

1. Make sure `php artisan test` passes
2. Fill in the pull request template
3. Link the issue it closes (`Closes #123`)
4. Include before/after screenshots for visual changes

Expect review comments — they're about the code, not about you. Maintainers may ask for changes
before merging.

## What gets rejected

To save you the effort:

- Wholesale reformatting or style-only rewrites
- Dependency bumps with no explanation of why
- Features with no issue and no discussion behind them
- Anything that commits real data, credentials, or a third party's proprietary material
- Changes that break the signed-out experience

## Questions

Open a [discussion](../../discussions) or ask in the issue you're working on.
