---
name: ui-ux-pro-max
description: "UI/UX design intelligence for web and mobile. Provides design systems, color palettes, typography, accessibility and UX rules via searchable CLI. Use when designing or building UI (plan, build, create, design, implement, review, fix, improve), choosing colors/typography, building landing pages or dashboards, or implementing accessibility. Supports Tailwind, React, Vue, Next.js, Svelte, Flutter, shadcn/ui."
---

# UI/UX Pro Max

Design guide with priority-based rules, design-system generation, and stack-specific guidance. When the user requests UI/UX work, follow the workflow below.

## When to Apply

- Designing new UI components or pages
- Choosing color palettes and typography
- Reviewing code for UX issues
- Building landing pages or dashboards
- Implementing accessibility requirements

## Rule Priorities (Quick Reference)

| Priority | Category              | Domain        |
|----------|------------------------|---------------|
| 1        | Accessibility (CRITICAL) | `ux`        |
| 2        | Touch & Interaction (CRITICAL) | `ux` |
| 3        | Performance (HIGH)     | `ux`        |
| 4        | Layout & Responsive (HIGH) | `ux`   |
| 5        | Typography & Color (MEDIUM) | `typography`, `color` |
| 6        | Animation (MEDIUM)     | `ux`        |
| 7        | Style Selection (MEDIUM) | `style`, `product` |
| 8        | Charts & Data (LOW)    | `chart`     |

## Workflow

### Step 1: Analyze Requirements

Extract from the user request:
- **Product type**: SaaS, e-commerce, portfolio, dashboard, landing page, etc.
- **Style keywords**: minimal, playful, professional, elegant, dark mode, etc.
- **Industry**: healthcare, fintech, gaming, education, etc.
- **Stack**: React, Vue, Next.js, or default `html-tailwind`

### Step 2: Generate Design System (REQUIRED)

Run from **project root** (script path may be `skills/ui-ux-pro-max/scripts/search.py` or `.cursor/skills/ui-ux-pro-max/scripts/search.py` depending on setup):

```bash
python3 skills/ui-ux-pro-max/scripts/search.py "<product_type> <industry> <keywords>" --design-system [-p "Project Name"]
```

- Searches product, style, color, landing, typography and returns pattern, style, colors, typography, effects, anti-patterns.
- **Optional persist:** Add `--persist` to write `design-system/MASTER.md` and use `--page "pagename"` for page overrides in `design-system/pages/`.

### Step 3: Supplement (as needed)

```bash
python3 skills/ui-ux-pro-max/scripts/search.py "<keyword>" --domain <domain>
```

| Need           | Domain       | Example |
|----------------|--------------|---------|
| More styles    | `style`      | `--domain style "glassmorphism dark"` |
| Charts         | `chart`      | `--domain chart "real-time dashboard"` |
| UX / a11y      | `ux`         | `--domain ux "animation accessibility"` |
| Fonts          | `typography` | `--domain typography "elegant luxury"` |
| Landing structure | `landing` | `--domain landing "hero social-proof"` |

### Step 4: Stack Guidelines

Default stack: `html-tailwind`. Get implementation practices:

```bash
python3 skills/ui-ux-pro-max/scripts/search.py "<keyword>" --stack html-tailwind
```

Stacks: `html-tailwind`, `react`, `nextjs`, `vue`, `svelte`, `swiftui`, `react-native`, `flutter`, `shadcn`, `jetpack-compose`

Then synthesize design system + searches and implement.

## Pre-Delivery Checklist (Summary)

- **Visual:** SVG icons (no emojis), consistent icon set, no layout shift on hover
- **Interaction:** `cursor-pointer` on clickable elements, visible focus states, transitions 150–300ms
- **Contrast:** 4.5:1 minimum for text; glass/borders visible in light mode
- **Layout:** Responsive 375px–1440px, no horizontal scroll, content not hidden under fixed nav
- **Accessibility:** Alt text, form labels, `prefers-reduced-motion` respected

For full domain/stack tables, prerequisites (Python install), persist pattern, common rules tables, and the complete checklist, see [reference.md](reference.md).
