# Case study: GASQ — Marketplace and decision-support for security services

**GASQ** is a Laravel-based platform that combines a **security services marketplace** with **decision-support tools**. This case study summarizes the business problem, the product response, and how Laravel’s ecosystem supported reliable, maintainable delivery of complex workflows—notably calculator engines, credits, payments, and trust controls.

---

## 1. Project overview (what we built)

GASQ is an **online marketplace for the security services sector**. It connects **buyers** (organizations that need security) with **vendors** (service providers) and replaces opaque procurement with **transparent, numbers-driven conversations**.

Instead of relying on rough estimates alone, users can **post jobs**, **submit and manage bids**, and run **advanced calculators** that turn real-world inputs—hours, posts, bill rates, patrol patterns, contract structures—into **breakdowns, totals, and comparisons** useful for budgeting and negotiation.

The platform’s identity is therefore **dual**: it is both a **transactional marketplace** and a **decision-support** product that helps both sides align on **cost and scope** with a shared vocabulary.

---

## 2. The problem and the solution (why it matters)

**The problem.** Security contracting often suffers from **low transparency**: line-item economics, burdened labor, and patrol or multi-post scenarios are hard to compare across bids. Buyers and vendors may talk past each other when “scope” and “price” are not grounded in the same assumptions.

**The solution.** GASQ provides a **single hub** where:

- Buyers **publish needs** and vendors **respond with structured bids**.
- Both parties use **shared calculators** to benchmark staffing, rates, and operating models so decisions rest on **data**, not guesswork.

**Why Laravel.** A single codebase must serve **three audiences**—buyers, vendors, and administrators—each with different permissions, onboarding steps, and monetization rules. **Laravel** gives a coherent foundation for **authentication**, **database-backed domain models**, **HTTP routing**, **middleware-based policy enforcement**, and **integration points** (payments, SMS, mail, queues) without fragmenting business logic across unrelated services. That made it a practical choice for a product where **marketplace flows** and **calculation-heavy features** live side by side.

---

## 3. Key technical pillars

### 3.1 Complex logic engines (calculators)

The calculators are not simple form calculators; they encode **security and labor economics**: burden stacks, patrol economics, contract summaries, TCO-style comparisons, and tool-specific scenarios aligned with product requirements (including spreadsheet- or spec-driven parity where defined).

Technically:

- Domain logic is organized in **PHP service classes** (including versioned “V24” compute paths) so Blade views stay thin and **rules stay testable**.
- **JSON-shaped scenarios** travel from the browser to dedicated **compute endpoints**; results return structured KPIs for rendering tables, tabs, and summaries.
- **Automated tests** and **fixture-driven parity checks** help prevent regressions when formulas or defaults change—critical when stakeholders treat numbers as contractual references.

**Configurable economics for operators:** The **credit cost per calculator run** is not hard-coded in scattered places. It is driven by **environment-backed configuration** so deployments can tune monetization (**e.g. credits per run**) without redeploying business logic throughout the app—supporting ops and product iteration.

### 3.2 Credit and payment ecosystem

GASQ uses a **credit-based** model for sustained access to compute-heavy features:

- Users hold a **wallet balance**; **ledger entries** record purchases and usage for support and auditing.
- **Stripe Checkout** (with webhook confirmation) tops up balances after successful payment, fitting a familiar “buy credits online” flow.

Laravel’s **service container**, **configuration layer**, and standard **HTTP + webhook** handling keep payment side effects **traceable** and **easier to secure** (signature verification, idempotent grant patterns—implementation-specific details live in the codebase).

### 3.3 Security and accountability

Sensitive capabilities (paid tools, rich analytics) need **accountability**:

- **Mandatory phone verification** after registration (**SMS** delivery via provider integration) ties accounts to a reachable identity and reduces casual abuse.
- Verification is enforced with **middleware** so protected routes share a consistent **trust bar** without duplicating checks in every controller.

This pattern maps cleanly onto Laravel’s **pipeline** model: declare who may access a route group once, enforce it everywhere in that group.

### 3.4 Role-aware access (buyers vs vendors vs admins)

Not all users follow the same path:

- **Buyers** follow a deliberate **onboarding sequence** for full calculator access: verified phone → **sufficient credits** → **at least one job posted** (vendors are not held to the job-posting gate for calculators).
- **Administrators** operate **settings**, **analytics**, and **wallet administration** through separate, privileged routes.

Laravel **roles** (backed by the user model and middleware aliases) make these differences **explicit and maintainable**, so product rules can evolve (for example, tuning credit thresholds or buyer gates) without rewriting the entire surface area.

---

## 4. Technical impact and summary

The outcome is a platform that **does two jobs at once**: it **facilitates procurement** (jobs, bids, vendor discovery) and **elevates the quality of decisions** through calculators that expose contract economics clearly.

| Capability | Business impact |
|------------|-----------------|
| **Bidding & jobs** | Keeps **procurement and vendor selection** in one workflow instead of splitting discussions across email and spreadsheets. |
| **Credits & payments** | Aligns **heavy computational usage** with sustainable operations and clear **commercial rules** for the operator. |
| **Calculators** | Gives buyers and vendors a **shared language for cost and scope**, improving fairness and speed to agreement. |
| **Verification & middleware** | Improves **trust and abuse resistance** around paid, data-rich features. |

From an engineering perspective, **Laravel** provided the **structure** (Auth, Eloquent relationships for marketplace entities, queue-ready architecture for notifications, clear boundaries between HTTP and services) to grow a **complex domain**—marketplace plus quantitative engines—without sacrificing **clarity** or **operability**.

---

## 5. Audience note

- **Business and investor readers:** See also **[OVERVIEW_FOR_STAKEHOLDERS.md](OVERVIEW_FOR_STAKEHOLDERS.md)** (non-technical product overview).
- **Engineers and implementers:** See **[README.md](README.md)** in this folder for consolidated technical documentation, setup, and operational detail.
