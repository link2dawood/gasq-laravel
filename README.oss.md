# guardrate

**Know what a security contract should cost — before you ask anyone to price it.**

guardrate is an open-source platform for **security-services procurement**. It models what
guarding a site actually costs to deliver — wages, relief coverage, turnover, hours paid but
not worked, overhead — and turns that into a defensible benchmark a buyer can hold a quote
against.

Most procurement tooling helps you *compare quotes*. guardrate answers the question that comes
before that one: **what should this service realistically cost?**

Built on Laravel 12, server-rendered, with a relational database.

---

## Why this exists

Security buyers are usually comparing vendors against each other rather than against the real
cost of doing the work properly. That is how contracts end up understaffed, with high turnover
and poor service — the low bid was never deliverable in the first place.

Model the cost first, and every quote can be measured against the same independent number.

## What it does

- **Cost modelling** — build up a fully-loaded hourly cost from wage, burden, relief factor,
  coverage hours and overhead
- **Coverage & staffing** — work out how many officers a post schedule genuinely requires
- **In-house vs contracted** — compare running it yourself against buying it in
- **Quote validation** — measure a vendor's price against the modelled cost
- **Job posting & bids** — post a requirement, receive structured vendor responses
- **Vendor qualification** — licensing, insurance, references and financial-sustainment checks
- **Reporting** — generate PDF cost reports and email them

## Getting started

```bash
git clone https://github.com/<org>/guardrate.git
cd guardrate

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate

# configure your database in .env, then:
php artisan migrate --seed

php artisan serve
```

Visit http://localhost:8000.

### Requirements

| | |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node | 20+ |
| Database | MySQL 8 / MariaDB / PostgreSQL / SQLite |

### Optional integrations

All are opt-in and the app runs without them. Configure in `.env`:

- **Stripe** — payments and credit purchases
- **Twilio** — SMS phone verification
- **Google Maps** — location autocomplete
- **SMTP** — outbound report email

## Documentation

| Document | Covers |
|---|---|
| [docs/architecture.md](docs/architecture.md) | How the application is put together |
| [docs/modules.md](docs/modules.md) | Feature modules |
| [docs/setup.md](docs/setup.md) | Full environment setup |
| [docs/development.md](docs/development.md) | Local development workflow |

## Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Good first issues are
labelled [`good first issue`](../../issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22).

Please read the [Code of Conduct](CODE_OF_CONDUCT.md) before taking part, and
[SECURITY.md](SECURITY.md) if you have found a vulnerability.

## Licence

[MIT](LICENSE).

## A note on scope

This project ships the **cost model and the procurement workflow**. It does not ship wage data,
rate cards, or any commercial party's pricing methodology — bring your own inputs. Cost
assumptions belong to whoever runs the instance.
