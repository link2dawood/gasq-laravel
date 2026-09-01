# Security Policy

## Reporting a vulnerability

**Please do not open a public issue for a security vulnerability.**

Report it privately through
[GitHub Security Advisories](../../security/advisories/new), or email
**<SECURITY CONTACT EMAIL>**.

Please include:

- What the issue is and why it matters
- Steps to reproduce, or a proof of concept
- Affected version or commit
- Any suggested fix, if you have one

### What to expect

| | |
|---|---|
| Acknowledgement | Within 3 working days |
| Initial assessment | Within 10 working days |
| Fix or mitigation plan | Communicated once assessed |

We will keep you updated while we work on it, and credit you in the advisory unless you would
rather stay anonymous.

Please give us reasonable time to ship a fix before disclosing publicly.

## Supported versions

Security fixes land on `main`. If you are running a fork or a pinned release, rebase onto the
patched commit.

## Scope

In scope:

- Authentication and session handling
- Authorisation — one account reaching another's data
- SQL injection, XSS, CSRF, SSRF
- Credit, wallet, or payment logic that can be manipulated
- File upload handling
- Secret or credential exposure

Out of scope:

- Anything requiring physical access to a user's machine
- Social engineering
- Vulnerabilities only reachable with a misconfigured deployment (for example `APP_DEBUG=true`
  in production — that is a deployment error, and it is documented as such)
- Automated scanner output with no demonstrated impact
- Missing hardening headers with no exploitable consequence

## For operators

If you deploy this software, you own its security in production. At minimum:

- Set `APP_DEBUG=false` and a strong unique `APP_KEY`
- Serve over HTTPS only
- Keep `.env` out of version control and unreadable by the web server
- Keep PHP, Composer and npm dependencies patched
- Restrict database access to the application host
- Take backups and test restoring them

This project ships no credentials and no production data. Anything you add is yours to protect.
