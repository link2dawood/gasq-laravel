# GASQ Website Checklist — Implementation Roadmap (Phases 1–37)

Tracks the full "GASQ Website Developer Action-Item Checklist." Status as of the
current review pass. **Deploys are on hold** — nothing ships until the owner says
"push."

Priority: P0 = critical, P1 = high, P2 = optimization.

## Status legend
- ✅ **Live** — pushed and verified on beta.
- 🔒 **Built, held** — committed locally, awaiting "push."
- 🔍 **Audited** — reviewed, no code change needed.
- 🛠️ **Ready** — can build now, no owner input needed.
- 🏗️ **Build (draft)** — new pages/tools; I can draft content.
- 🧠 **Decision** — needs an owner business decision.
- ✍️ **Content** — needs real content from the owner (won't fabricate).

## Roadmap

| # | Phase | Prio | Status | Blocker / Note |
|---|---|---|---|---|
| 1 | Routing & access | P0 | ✅ Live | `/home` guests → `/` fixed. 404 page + link sweep 🛠️ ready |
| 2 | Homepage hero | P0 | ✅ Live | new H1 + CTAs, one `<h1>` |
| 3 | Correct copy | P0/P1 | ✅ Live (P0) | typo + pricing fixed; terminology sweep 🛠️ ready |
| 4 | Market position | P0 | ✅ Live | "not a quote-comparison site" band |
| 5 | Brand architecture | P1 | ✅ Live | `docs/brand-copy-standards.md` |
| 6 | Homepage IA reorder | P1 | 🏗️ partial | §3 built; §7 case study + §11 founder ✍️ |
| 7 | Traditional vs GASQ | P1 | ✅ Live | responsive comparison |
| 8 | Validate Before You Estimate workflow | P1 | 🔒 Held | 6-step stepper |
| 9 | Calculator funnel | P0 | 🧠 Decision | make Know-Before-You-Buy preview free? |
| 10 | Result transparency (assumptions) | P1 | 🛠️ Ready | from computed values |
| 11 | Results dashboard | P1 | 🛠️ Ready | data already computed |
| 12 | Pricing risk indicators | P2 | 🛠️ Ready | builds on price-realism |
| 13 | **Buyer vs Vendor pricing** | **P0** | 🧠 Decision | two contradictory pricing systems; pick canonical + final plans |
| 14 | Explain credits | P0 | 🛠️ Ready | cost table from `config/credits.php`; rollover depends on §13 |
| 15 | Free vs paid products | P1 | 🏗️ Build | needs §13 pricing for cells |
| 16 | GASQ Certified page | P1 | 🏗️/✍️ | scaffold from cert statement; owner defines reviewer/validity |
| 17 | Independence & disclosure | P1 | ✍️ Content | real fee/relationship info |
| 18 | Vendor qualification page | P1 | ✍️ Content | real qualification process |
| 19 | Trust & credibility | P1/P2 | ✍️ Content | founder bio/photo, testimonials |
| 20 | Replace CFO claim | P0 | 🔒 Held | done → "Built for CFO-Level Cost Analysis" |
| 21 | Capital recovery explanation | P1/P2 | 🛠️ Ready | disclaimer + payback |
| 22 | Workforce Maintenance terminology | P1 | 🛠️ Ready | standardize + formula-consistency check |
| 23 | **Protect proprietary calcs** | **P0** | 🔍 **Audited — PASS** | formula server-side; no client leak |
| 24 | Buyer onboarding | P1 | 🏗️ Build | objective-based routing |
| 25 | Vendor onboarding | P1 | 🏗️ Build | progressive qualification |
| 26 | Validate My Quote (`/validate-security-quote`) | P1 | 🏗️ Build | new tool |
| 27 | Build My Security Budget (`/security-budget-calculator`) | P2 | 🏗️ Build | new tool |
| 28 | Validate My RFP (`/security-rfp-validation`) | P2 | 🏗️ Build | new tool |
| 29 | Service landing pages (×8) | — | 🏗️ Build | I draft copy |
| 30 | Industry landing pages (×13) | — | 🏗️ Build | I draft copy |
| 31 | Navigation redesign | P1 | 🏗️ Build | buyer/vendor split, sticky CTA |
| 32 | Rename "Mobile Patrol Hit Calculator" | P2 | 🛠️ Ready | → "Mobile Patrol Visit Cost Calculator" |
| 33 | Contact page | P1/P2 | 🛠️/❓ | needs 2 facts: what do "P"/"A" mean; is 24/7 human support real? |
| 34 | About page | P1 | ✍️ Content | overlaps §19 founder |
| 35 | Pricing/procurement glossary (`/security-pricing-glossary`) | — | 🛠️ Ready | from brand-copy-standards |
| 36 | SEO technical | P1/P2 | 🛠️ Build | titles/meta/canonical/sitemap/robots/OG/schema |
| 37 | Homepage SEO | P1 | 🛠️ Ready | production title + buyer-intent keywords |

## Recommended execution order
1. **"push"** → deploy held commits (8, 20) + build the 🛠️ Ready bucket (404, terminology, 10, 11, 12, 14, 21, 22, 35, 37) and screenshot each.
2. **Decide §13 pricing** (unblocks 13, 14, 15, and CTA targets).
3. **SEO technical (§36)** — high ROI, mostly mechanical.
4. **Tool pages** — 26 → 27 → 28.
5. **Nav redesign (§31)**.
6. **SEO content pages** — 29 (services ×8) → 30 (industries ×13).
7. **Onboarding** — 24, 25.
8. **Content-dependent** — 6, 16, 17, 18, 19, 34 as material arrives.

## What the owner still needs to provide
- **Decisions:** §13 (pricing model + final plans), §9 (free preview y/n), /dashboard alias y/n.
- **Facts:** §33 — meaning of "P"/"A" phone labels; whether 24/7 human support is real.
- **Content:** §6 case study + founder, §17 fee disclosures, §18 qualification process, §19 founder bio/photo/testimonials.
- **The word "push"** to lift the deploy hold.
