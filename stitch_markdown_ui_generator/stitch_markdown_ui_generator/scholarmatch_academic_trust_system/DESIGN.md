---
name: ScholarMatch Academic Trust System
colors:
  surface: '#f8f9ff'
  surface-dim: '#ccdbf4'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dde9ff'
  surface-container-highest: '#d5e3fd'
  on-surface: '#0d1c2f'
  on-surface-variant: '#3e4947'
  inverse-surface: '#233144'
  inverse-on-surface: '#ebf1ff'
  outline: '#6e7977'
  outline-variant: '#bdc9c6'
  surface-tint: '#006a63'
  primary: '#005c55'
  on-primary: '#ffffff'
  primary-container: '#0f766e'
  on-primary-container: '#a3faef'
  inverse-primary: '#80d5cb'
  secondary: '#505f79'
  on-secondary: '#ffffff'
  secondary-container: '#d0e0ff'
  on-secondary-container: '#54637d'
  tertiary: '#0a5e2e'
  on-tertiary: '#ffffff'
  tertiary-container: '#2c7744'
  on-tertiary-container: '#aefcbc'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#9cf2e8'
  primary-fixed-dim: '#80d5cb'
  on-primary-fixed: '#00201d'
  on-primary-fixed-variant: '#00504a'
  secondary-fixed: '#d5e3ff'
  secondary-fixed-dim: '#b7c7e5'
  on-secondary-fixed: '#0b1c32'
  on-secondary-fixed-variant: '#384760'
  tertiary-fixed: '#a6f4b5'
  tertiary-fixed-dim: '#8bd79b'
  on-tertiary-fixed: '#00210b'
  on-tertiary-fixed-variant: '#005226'
  background: '#f8f9ff'
  on-background: '#0d1c2f'
  surface-variant: '#d5e3fd'
typography:
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.025em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.02em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.015em
  title-md:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.01em
  title-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 22.5px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
  metric-bold:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-sm: 1rem
  gutter-lg: 2rem
  margin: 1.5rem
  margin-sm: 1rem
  margin-lg: 3rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

The design system is engineered for Malaysian pre-tertiary and tertiary students navigating high-stakes scholarship applications. It establishes institutional credibility without bureaucratic friction, projecting clarity, calm guidance, and rigorous academic merit. The aesthetic blends **Modern Corporate** structure with **Empathetic Minimalism**, prioritizing dense tabular criteria, eligibility status indicators, and predictive score comparisons.

### Core Visual Principles
- **Clarity Over Embellishment:** High legibility, strict contrast boundaries, and zero extraneous ornament ensure users make critical financial choices with total certainty.
- **Supportive Authority:** Deep federal slate tones anchor primary navigation and headings, balanced by grounded tropical teal accents that invoke purposeful progress and upward mobility.
- **Defensive Hierarchy:** Status badges and delta markers (e.g., academic threshold margins) provide instant comprehension of complex qualification matrices.
- **Brand Identity Integration:** The wordmark pairs a clean, disciplined sans-serif typographic mark with an integrated academic ribbon/bookmark motif nested alongside a minimal mortarboard cap.

## Colors

The color architecture is built around calibrated contrast tiers supporting dense data tables, interactive filter arrays, and unambiguous validation states.

### Color Tokens & Usage

- **Canvas & Background (`#F7F8FA`):** Foundations and page backgrounds, keeping bright white components distinctly elevated without heavy shadows.
- **Surface & Cards (`#FFFFFF`):** High-priority containers, modal windows, table rows, and active filter panels.
- **Navigation & Headings (`#14243B`):** Deep navy-slate delivering authoritative structure for top navbars, sidebars, and structural page headers.
- **Primary Action (`#0F766E`):** Deep teal for primary buttons, active toggles, selected filters, and focal pagination links.
- **Primary Hover (`#115E59`):** Interacting dark teal for hover, active click, and focused interactive states.
- **Body Text (`#334155`):** High-contrast slate yielding fatigue-free reading across long terms and requirement sheets.
- **Secondary / Muted Text (`#64748B`):** Mid-tone slate for supporting metadata, field helper text, and secondary timestamps.
- **Subtle Borders (`#E2E8F0`):** Precise card perimeters, input field boundaries, and table dividers.

### Semantic Status Matrix

Status chips and audit indicators strictly pair contextual text, background, and stroke values:

- **Overall Eligible:** Text `#166534`, Background `#DCFCE7`, Border `#BBF7D0`.
- **Manual Verification Required:** Text `#92400E`, Background `#FEF3C7`, Border `#FDE68A`.
- **Not Eligible:** Text `#991B1B`, Background `#FEE2E2`, Border `#FECACA`.
- **Neutral / Not Applicable:** Text `#475569`, Background `#F1F5F9`, Border `#E2E8F0`.

## Typography

The typography system relies on a single high-performance grotesk typeface (`Inter`), optimized for tabular numbers, multi-column criteria lists, and varying density viewports. 

### Implementation Guidelines
- **Headings:** Always rendered in `#14243B` with tight tracking (`-0.015em` to `-0.025em`) to preserve visual density and anchor user attention during quick scanning.
- **Numbers & Metrics:** Use `font-feature-settings: "cv05", "tnum"` for numeric scores, CGPA scales, SPM grade counts, and ringgit currency values to ensure precise vertical column alignment.
- **Badges and Criteria Tags:** Use `label-sm` and `label-md` with `font-weight: 500` to avoid stroke degradation against saturated or tinted badge backgrounds.

## Layout & Spacing

The layout is built on an **8px baseline rhythm** with a standard 12-column fluid grid, optimized for responsive administration dashboards and public applicant portals.

### Grid & Breakpoints
- **Desktop (≥1200px):** 12 columns, `gutter-lg` (32px), `margin-lg` (48px), max-width container set to `1280px` or `1440px` for dense data exploration.
- **Tablet (768px – 1199px):** 8 columns, `gutter` (24px), `margin` (24px). Dual-pane layouts collapse side filter rails into an expandable drawer.
- **Mobile (<768px):** 4 columns, `gutter-sm` (16px), `margin-sm` (16px). All criteria grids reflow to single-column card stacks.

### Content Spacing
- Internal component padding relies on `space-md` (16px) for compact listing rows and `space-lg` (24px) for full scholarship overview cards.
- Vertical section spacing strictly follows multiples of `space-md` (`16px`, `24px`, `32px`, `48px`).

## Elevation & Depth

Visual hierarchy is maintained through **tonal stacking** and subtle, low-opacity ambient shadows paired with crisp outlines. This avoids visual noise and ensures maximum performance on standard web stacks.

### Depth Layers
- **Level 0 (Flat / Canvas):** `#F7F8FA` background. No shadow, no border.
- **Level 1 (Default Surface / Cards):** `#FFFFFF` surface backed by `border: 1px solid #E2E8F0` and `box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05)`.
- **Level 2 (Active / Hover / Dropdown):** `#FFFFFF` surface backed by `border: 1px solid #CBD5E1` and `box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06), 0 2px 4px -2px rgba(0, 0, 0, 0.04)`.
- **Level 3 (Modals / Sticky Filters / Slide-outs):** `#FFFFFF` surface with `box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.03)`.

### Border Discipline
All card-like elements must retain their `1px solid #E2E8F0` border even when applying elevation. Elevation acts as a presence modifier, not a structural boundary replacement.

## Shapes

The design system employs a **Rounded** aesthetic (`roundedness: 2`), balancing human-centered friendliness with academic discipline.

### Geometry Standards
- **Cards & Data Containers:** Default radius of `10px` to `12px` (`0.625rem` – `0.75rem`).
- **Interactive Controls (Buttons, Form Inputs):** Standard `8px` (`0.5rem`) for sharp, dependable tactile perception.
- **Badges, Status Chips, & Pill Filters:** `9999px` (fully rounded pill) to distinguish metadata from structural containers and interactive controls.
- **Progress Trackers & Gauges:** Pill contours for outer tracks (`9999px`) to maintain smooth visual flow.

## Components

All components are designed for direct implementation in Blade templates using standard Bootstrap-compatible or Tailwind-compatible structural classes.

### 1. Buttons
- **Primary:** Background `#0F766E`, text `#FFFFFF`, border none, hover background `#115E59`. Focus ring: `2px solid #FFFFFF` inner, `2px solid #0F766E` outer.
- **Secondary / Outline:** Background `#FFFFFF`, text `#14243B`, border `1px solid #E2E8F0`, hover background `#F8FAFC`, hover border `#CBD5E1`.
- **Ghost:** Background transparent, text `#0F766E`, hover background `#F0FDFA`.
- **Padding:** Vertical `8px`, horizontal `16px`; font size `14px` (`label-md`), font weight `500`.

### 2. Status Badges & Academic Margin Tags
- **Status Badges:** Inline-flex, items-center, gap of `6px`, padding `4px 10px`, border radius `9999px`, font size `12px`, font weight `500`. Includes a 12px leading status icon (checkmark, alert triangle, or x-circle).
- **Academic Margin Label:** Distinct metric chip comparing student credentials to scholarship minimum requirements (e.g., `+26.7% above minimum`).
  - Positive Margin: Background `#F0FDF4`, border `1px solid #BBF7D0`, text `#15803D`.
  - Borderline / Strict Margin: Background `#FFFBEB`, border `1px solid #FDE68A`, text `#B45309`.

### 3. Cards & Tables
- **Scholarship Overview Card:** Pure white background, `12px` border radius, `1px solid #E2E8F0`, `space-lg` (24px) internal padding. Features a dual-column header containing scholarship sponsor metadata on the left and qualification status badge on the right.
- **Tabular Data Views:** Headers rendered in `#14243B` on `#F8FAFC` background with `1px solid #E2E8F0` bottom border, font size `13px` bold with uppercase tracking. Alternating hover state on rows using `#F8FAFC`.

### 4. Input Fields & Select Controls
- **Fields:** Height `40px`, border radius `8px`, border `1px solid #E2E8F0`, background `#FFFFFF`, font size `14px`, text `#334155`.
- **Focus State:** Border `#0F766E`, box-shadow `0 0 0 3px rgba(15, 118, 110, 0.15)`.
- **Checkboxes & Radios:** Teal fill (`#0F766E`) when checked, with white indicator glyph. Unchecked border `1.5px solid #CBD5E1`.

### 5. Filter Panels
- Multi-facet sidebar panels featuring accordion sections for household income tiers (B40, M40, T20), minimum academic results (SPM/STPM/Matriculation), field of study, and state residency.