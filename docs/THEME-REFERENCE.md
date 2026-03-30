# GASQ Theme Reference (Lovable preview)

Reference for matching [Lovable preview](https://preview--gasq-calculator-project.lovable.app/) in the Laravel app. Design tokens and layout mirror the preview’s stylesheet and component structure.

---

## Design tokens (HSL)

| Token | Value | Usage |
|-------|--------|--------|
| `--background` | `240 10% 98%` | Page background |
| `--foreground` | `225 15% 15%` | Body text, headings |
| `--primary` | `220 90% 25%` | Buttons, links, accents (deep blue) |
| `--primary-hover` | `220 90% 30%` | Primary button hover |
| `--primary-foreground` | `0 0% 100%` | Text on primary |
| `--secondary` | `220 15% 95%` | Secondary surfaces |
| `--secondary-foreground` | `225 15% 25%` | Text on secondary |
| `--muted` | `220 15% 95%` | Muted surface |
| `--muted-foreground` | `225 10% 50%` | Muted text (nav links, descriptions) |
| `--card` | `0 0% 100%` | Card background |
| `--border` | `220 15% 90%` | Borders |
| `--destructive` | `0 84.2% 60.2%` | Errors, “Traditional RFP” list |
| `--radius` | `0.5rem` | Border radius (cards, buttons) |

Tailwind uses these as `hsl(var(--name))`; gasq-theme.css uses equivalent `--gasq-*` with full `hsl(...)` values.

---

## Navbar (header)

- **Container**: `container mx-auto px-4 py-4 flex justify-between items-center`
- **Classes**: `border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 sticky top-0 z-50`
- **Behavior**: Sticky, bottom border only, semi-transparent background; with backdrop-filter support use ~60% opacity, else ~95%.
- **Logo**: `h-12 w-auto` (48px height).
- **Nav links**: `text-muted-foreground hover:text-foreground transition-colors`, spacing `space-x-6`.
- **No** “Register” in the main nav on the preview—only “Login” when not authenticated.

---

## Sections (landing)

- **Hero**: `py-20 bg-gradient-to-br from-primary/5 via-background to-secondary/5`
- **Section (default)**: `py-20 bg-background`
- **Section (muted)**: `py-20 bg-muted/30` (e.g. For Sellers, Trusted by…)
- **ROI guarantee block**: `py-16 px-4 bg-gradient-to-br from-background to-muted/20`
- **Final CTA**: `py-20 bg-gradient-to-br from-primary/10 via-background to-secondary/10`
- **Footer**: `bg-muted/50 py-12 border-t`

---

## Cards

- **Base**: `rounded-lg border bg-card text-card-foreground shadow-sm`
- **Card header**: `flex flex-col space-y-1.5 p-6`
- **Card title**: `text-2xl font-semibold leading-none tracking-tight`
- **Card content**: `p-6 pt-0`

---

## Buttons

- **Primary**: `bg-primary text-primary-foreground hover:bg-primary/90`
- **Outline**: `border border-input bg-background hover:bg-accent hover:text-accent-foreground`
- **Secondary**: `bg-secondary text-secondary-foreground hover:bg-secondary/80`
- **Vendor CTA (sellers)**: On the preview, outline CTAs sometimes use a solid primary blue background; for Laravel theme consistency use `primary` or `outline-primary`.

---

## Typography

- **H1 (hero)**: `text-5xl md:text-7xl font-bold text-foreground`
- **H2 (section)**: `text-4xl md:text-5xl font-bold text-foreground mb-6`
- **Lead**: `text-xl md:text-2xl text-muted-foreground`
- **Body muted**: `text-muted-foreground` or `text-lg text-muted-foreground`
- **Small/labels**: `text-sm text-muted-foreground`

---

## CTA / marketing blocks

- **Gradient CTA box (auth)**: `bg-gradient-to-r from-primary/10 via-primary/5 to-primary/10 p-6 rounded-2xl border border-primary/20 shadow-lg`
- **Guest CTA box**: `bg-gradient-to-r from-secondary/10 via-secondary/5 to-secondary/10 ... border border-secondary/20`
- **Links in CTA**: `text-muted-foreground hover:text-foreground transition-colors`

---

## Lists

- **Pros (GASQ)**: Green check icon (`CheckCircle` / `text-green-500` or theme success).
- **Cons (Traditional)**: Red dot or `bg-destructive` circle; text `text-destructive`.
- **Step numbers**: `w-8 h-8 bg-primary text-primary-foreground rounded-full flex items-center justify-center font-bold`

---

## Footer

- **Layout**: `grid md:grid-cols-4 gap-8`, then `border-t pt-8` for copyright.
- **Links**: `text-muted-foreground hover:text-foreground transition-colors`
- **Logo**: `h-8 w-auto`

---

## Laravel mapping (gasq-theme.css)

- `--gasq-background` = `--background`
- `--gasq-foreground` = `--foreground`
- `--gasq-primary` / `--gasq-primary-hover` = `--primary` / `--primary-hover`
- `--gasq-muted` = `--muted-foreground`
- `--gasq-card` = `--card`
- `--gasq-border` = `--border`
- `.gasq-navbar` = header with sticky, border-b, backdrop blur, transparent inner nav
- `.gasq-card` = Card (rounded, border, shadow)
- `.gasq-hero-bg` = hero gradient
- `.gasq-section-muted` = `bg-muted/30`
- `.gasq-cta-bg` = CTA gradient
- `.gasq-footer` = footer (muted bg, border-top)
- `.gasq-list-check` / `.gasq-list-dot` = list styles
- `.gasq-step-num` = step number circle
