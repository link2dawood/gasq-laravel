# GASQ Questionnaire Flows

Two questionnaires now live in the platform — one for **buyers** (front of the funnel, when they post a job) and one for **vendors** (back of the funnel, when they accept a buyer's offer). Together they form the GASQ Responsive & Responsible system: every job that reaches a vendor has been qualified, and every vendor who reaches a buyer has been verified.

---

## 1. Buyer Questionnaire Flow

### Trigger
A buyer signs in and starts a new job posting through the **Post a Job** flow.

### Step 1 — Buyer fills out the questionnaire
The buyer completes a guided form that captures the full picture of what they need. Key sections include:

- **Contact & decision-maker info** — who's making the call, what's their authority
- **Property / site details** — location, type, square footage, hours of operation, special risks
- **Service needs** — armed/unarmed, patrol vs static, hours per day / days per week / weeks per year
- **Schedule & start timeline** — when they want service to start
- **Budget** — hourly, monthly, or annual budget; or a range
- **Approval & readiness** — confirmation they're ready to move forward, will interview vendors, and have funds approved
- **Compliance, technology, and special requirements**

### Step 2 — Buyer reviews and publishes
After the form, the buyer sees a preview of the generated job announcement, can edit, and clicks **Publish**.

### Step 3 — Automatic qualification
The moment the job is published, GASQ runs the **Buyer Qualification Service** behind the scenes. It evaluates five core criteria:

| Criterion | Pass condition |
|---|---|
| Decision-maker authority | Buyer confirmed they (or their representative) are authorized |
| Budget confirmed | Budget is documented and approved |
| Scope completed | Hours, days, weeks, location, service type all filled in |
| Timeline ready | Start date is within ~60 days |
| Move-forward commitment | Buyer agreed to engage if a qualified vendor accepts |

Each job is then assigned a **Lead Tier**:

- **Tier A** — all 5 criteria pass → released immediately to **up to 5 vendors**
- **Tier B** — 4 of 5 pass → flagged for admin review → released to **up to 3 vendors**
- **Tier C** — ≤3 pass → held; not released to any vendor

### Step 4 — Buyer receives an email
Two outcomes:

**✅ Approved** *(Tier A or B)* — Subject: *"Qualification Approved – Your Security Request Is Now Live"*
The buyer learns their submission is active, sees the GASQ commitments and protections (Vendor Replacement Guarantee, Price Lock Guarantee), and gets a **"Track Vendor Activity"** button that takes them to their job dashboard, where vendor acceptances and interview phases update in real time.

**❌ Not Qualified** *(Tier C)* — Subject: *"Status Update – Service Request Qualification"*
The buyer learns their request is on Pending Qualification Status, sees which categories they need to strengthen, and gets an **"Update My Questionnaire"** button to revise and resubmit. As soon as the resubmission qualifies, the job is released.

### Step 5 — Real-time vendor activity
Once approved, the buyer's dashboard shows vendor acceptances, declines, interview-phase counts, and incoming bids — all updating live as vendors respond.

---

## 2. Vendor Questionnaire Flow

### Trigger
A vendor receives an email/SMS notification: *"A buyer has made you a price offer."* They open the offer in their GASQ portal.

### Step 1 — Vendor's initial decision
Two paths:
- **Decline** — The offer is closed. The buyer is notified. No further action.
- **Accept** — The vendor is taken straight into the qualification questionnaire. *No buyer notification yet — the buyer will only hear back once the vendor's full qualification packet is ready.*

### Step 2 — Vendor completes the qualification
A guided 6-step wizard:

1. **Submission Compliance** — company info, licensing confirmations, document uploads (state security license, certificate of insurance, W-9, capability statement, workers' comp, general liability, business license)
2. **Pricing Responsiveness** — accepts buyer's price, schedule, and confirms pricing is sustainable
3. **Operational Capacity** — headcount, supervisors, accounts under management, weekly billable hours, dispatch capabilities
4. **Workforce Sustainment** — overtime reliance, turnover, retention, benefits, training, background checks, written SOPs
5. **Financial Responsibility** — payroll sustainability (30–45 days), payroll/contract history, insurance coverage, litigation, license history
6. **Performance & Integrity** — references, proof of past performance, plus a final review screen

**Vendor conveniences:**
- **Pre-filled documents** — anything already in the vendor's GASQ profile (insurance certs, W-9, etc.) auto-attaches; vendors only upload what's missing
- **Save & Exit** — vendors can leave at any point and resume from a "Resume Questionnaire" tile on their dashboard
- **Step progress bar** — clear indicator of where they are in the 6 steps

### Step 3 — Built-in quality gate
When the vendor clicks **Send Response to Buyer**, the system runs two automated checks:

**Responsive check** — blocks submission if the vendor:
- Skipped any mandatory item
- Failed to upload any required document
- Said "No" to any compliance question (start date, coverage hours, staffing, uniforms, technology, insurance minimums, wage, training, response time, scope, terms)

**Responsible check** — blocks submission if the vendor:
- Cannot sustain payroll for 30–45 days
- Has previously failed payroll
- Has lost a contract due to staffing failures
- Has been involved in negligent-security litigation
- Has had a security license suspended/revoked
- Does not carry both Workers' Comp and General Liability insurance
- Cannot provide 3 references or proof of past performance

If anything fails, the vendor stays on the review step with a clear list of what to fix. **Nothing reaches the buyer until both checks pass.**

### Step 4 — Submission and packet generation
Once the vendor passes both gates:
- The questionnaire **locks** (no further edits)
- GASQ generates a polished PDF of all responses
- All vendor documents are attached
- A secure, time-limited buyer review link is generated (valid 14 days)

### Step 5 — Buyer receives the packet
The buyer gets an email titled *"[Vendor Name] submitted vendor qualification for: [Job Title]"* containing:
- Vendor name with **Responsive** and **Responsible** status badges
- Full questionnaire response as a PDF attachment
- All supporting documents as separate attachments
- A **"Review vendor response"** button opening a clean buyer review page

### Step 6 — Buyer reviews and decides
The review page organizes every answer by section with direct links to each document. The buyer can confidently move to interview scheduling, knowing the vendor has cleared the full qualification gauntlet.

---

## How the Two Flows Connect

```
[Buyer posts job]
        │
        ▼
[Buyer Questionnaire submitted]
        │
        ▼
[Auto-qualification runs]
        │
   ┌────┴────┐
   ▼         ▼
Tier C    Tier A/B
   │         │
   ▼         ▼
[Not       [Approved
Qualified   email +
email]      job released
            to vendors]
                │
                ▼
        [Vendors invited]
                │
                ▼
        [Vendor accepts offer]
                │
                ▼
        [Vendor Questionnaire (6 steps)]
                │
                ▼
        [Responsive + Responsible
        quality gate]
                │
                ▼
        [PDF + docs packet emailed
        to buyer]
                │
                ▼
        [Buyer reviews → Interview phase]
```

---

## What This Means for the Customer

> *"GASQ does not simply identify vendors willing to accept a price. We identify vendors capable of sustaining the workforce necessary to successfully perform the contract at that price."*

- **Buyers** never waste time talking to vendors who can't actually deliver. Every response they see has cleared a structured 25-question qualification across licensing, insurance, financial sustainment, operational capacity, and workforce stability.
- **Vendors** never waste time chasing leads that aren't real opportunities. Every job they're invited to has cleared a 5-criteria buyer qualification — verified decision-maker, confirmed budget, completed scope, real timeline, committed to interviewing.
- **Admins** see the full picture in read-only oversight views and can confirm the system's determinations are working correctly.

The result: pre-vetted, decision-ready engagements on both sides — not raw price replies, not unqualified leads.
