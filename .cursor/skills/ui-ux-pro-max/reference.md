# UI/UX Pro Max — Reference

## Prerequisites

Ensure Python is available:

```bash
python3 --version || python --version
```

**macOS:** `brew install python3`  
**Ubuntu/Debian:** `sudo apt update && sudo apt install python3`  
**Windows:** `winget install Python.Python.3.12`

---

## Available Domains

| Domain      | Use For                    | Example Keywords |
|-------------|----------------------------|------------------|
| `product`   | Product type recommendations | SaaS, e-commerce, portfolio, healthcare, beauty, service |
| `style`     | UI styles, colors, effects | glassmorphism, minimalism, dark mode, brutalism |
| `typography`| Font pairings, Google Fonts | elegant, playful, professional, modern |
| `color`     | Color palettes by product   | saas, ecommerce, healthcare, beauty, fintech, service |
| `landing`   | Page structure, CTA        | hero, hero-centric, testimonial, pricing, social-proof |
| `chart`     | Chart types, libraries     | trend, comparison, timeline, funnel, pie |
| `ux`        | Best practices, anti-patterns | animation, accessibility, z-index, loading |
| `react`     | React/Next.js performance  | waterfall, bundle, suspense, memo, rerender, cache |
| `web`       | Web interface guidelines    | aria, focus, keyboard, semantic, virtualize |
| `prompt`    | AI prompts, CSS keywords   | (style name) |

## Available Stacks

| Stack            | Focus |
|------------------|--------|
| `html-tailwind`  | Tailwind utilities, responsive, a11y (DEFAULT) |
| `react`          | State, hooks, performance, patterns |
| `nextjs`         | SSR, routing, images, API routes |
| `vue`            | Composition API, Pinia, Vue Router |
| `svelte`         | Runes, stores, SvelteKit |
| `swiftui`        | Views, State, Navigation, Animation |
| `react-native`   | Components, Navigation, Lists |
| `flutter`        | Widgets, State, Layout, Theming |
| `shadcn`         | shadcn/ui components, theming, forms, patterns |
| `jetpack-compose` | Composables, Modifiers, State Hoisting, Recomposition |

---

## Design System Persist (Master + Overrides)

**Persist to disk:**

```bash
python3 skills/ui-ux-pro-max/scripts/search.py "<query>" --design-system --persist -p "Project Name"
```

Creates:
- `design-system/MASTER.md` — global design rules
- `design-system/pages/` — folder for page overrides

**Page-specific override:**

```bash
python3 skills/ui-ux-pro-max/scripts/search.py "<query>" --design-system --persist -p "Project Name" --page "dashboard"
```

Creates `design-system/pages/dashboard.md`. When building a page, check `design-system/pages/[page-name].md` first; if it exists, its rules override MASTER. Otherwise use MASTER only.

**Context prompt for retrieval:**
"I am building the [Page Name] page. Please read design-system/MASTER.md. Also check if design-system/pages/[page-name].md exists. If the page file exists, prioritize its rules. If not, use the Master rules exclusively. Now, generate the code..."

---

## Example Workflow

**Request:** "Landing page for professional skin care service"

1. **Analyze:** Product = Beauty/Spa, style = elegant, professional, soft, stack = html-tailwind.
2. **Design system:**
   ```bash
   python3 skills/ui-ux-pro-max/scripts/search.py "beauty spa wellness service elegant" --design-system -p "Serenity Spa"
   ```
3. **Supplement (optional):**
   ```bash
   python3 skills/ui-ux-pro-max/scripts/search.py "animation accessibility" --domain ux
   python3 skills/ui-ux-pro-max/scripts/search.py "layout responsive form" --stack html-tailwind
   ```
4. Synthesize and implement.

---

## Output Formats

```bash
# ASCII (default) — terminal
python3 skills/ui-ux-pro-max/scripts/search.py "fintech crypto" --design-system

# Markdown — documentation
python3 skills/ui-ux-pro-max/scripts/search.py "fintech crypto" --design-system -f markdown
```

---

## Tips

1. Use specific keywords: "healthcare SaaS dashboard" > "app".
2. Run multiple searches with different keywords.
3. Combine domains (style + typography + color) for a full system.
4. Always check UX: search "animation", "z-index", "accessibility".
5. Use `--stack` for implementation-specific guidance.
6. Iterate if the first result doesn’t fit.

---

## Common Rules for Professional UI

### Icons & Visual

| Rule                 | Do                                      | Don't |
|----------------------|-----------------------------------------|--------|
| No emoji icons       | SVG (Heroicons, Lucide, Simple Icons)   | Emojis as UI icons |
| Stable hover         | Color/opacity transitions               | Scale that shifts layout |
| Brand logos          | Official SVG from Simple Icons          | Guess or wrong paths |
| Icon size            | Fixed viewBox 24x24, e.g. w-6 h-6        | Random sizes |

### Interaction & Cursor

| Rule           | Do                                | Don't |
|----------------|------------------------------------|--------|
| Cursor pointer | `cursor-pointer` on clickable      | Default cursor on interactive |
| Hover feedback | Color, shadow, or border change    | No feedback |
| Transitions    | `transition-colors duration-200`  | Instant or >500ms |

### Light/Dark Contrast

| Rule              | Do                     | Don't |
|-------------------|------------------------|--------|
| Glass light mode  | `bg-white/80` or higher| `bg-white/10` |
| Body text light   | e.g. slate-900         | slate-400 for body |
| Muted text light  | slate-600 minimum      | gray-400 or lighter |
| Borders light     | `border-gray-200`      | `border-white/10` |

### Layout & Spacing

| Rule            | Do                          | Don't |
|-----------------|-----------------------------|--------|
| Floating navbar | `top-4 left-4 right-4`      | Stick to top-0 |
| Content padding | Account for fixed nav height| Content under nav |
| Max-width       | Same e.g. max-w-6xl/7xl     | Mixed container widths |

---

## Pre-Delivery Checklist (Full)

### Visual Quality
- [ ] No emojis as icons (use SVG)
- [ ] Icons from one set (Heroicons/Lucide)
- [ ] Brand logos correct (Simple Icons)
- [ ] Hover states don’t cause layout shift
- [ ] Theme colors used directly (e.g. bg-primary), not only var() wrapper

### Interaction
- [ ] Clickable elements have `cursor-pointer`
- [ ] Hover gives clear visual feedback
- [ ] Transitions 150–300ms
- [ ] Focus states visible for keyboard

### Light/Dark Mode
- [ ] Text contrast ≥ 4.5:1
- [ ] Glass/transparent elements visible in light mode
- [ ] Borders visible in both modes
- [ ] Test both modes before delivery

### Layout
- [ ] Floating elements spaced from edges
- [ ] No content under fixed navbars
- [ ] Responsive at 375, 768, 1024, 1440px
- [ ] No horizontal scroll on mobile

### Accessibility
- [ ] Images have alt text
- [ ] Form inputs have labels
- [ ] Color not the only indicator
- [ ] `prefers-reduced-motion` respected
