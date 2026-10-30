---
name: nextrace-figma-design
description: "How to create and change designs in the NextRace Figma file. ALWAYS activate when a ticket needs a design change: a new page, a new or changed component, a new state or viewport, a token or Style guide change. Rules for pages, the Components page, instancing components from pages, the three viewports, naming, tokens and the Style guide. Use together with nextrace-design (the decisions) and nextrace-ticket (the ticket)."
license: MIT
metadata:
  author: nextrace
---

# NextRace Figma design rules

File: **NextRace – Photo-led design**, https://www.figma.com/design/fvBPi385tS8VokeCk8ah3J. It is the source of truth for the site: the code follows it, never the other way round. Do this work only for an agreed ticket (see `nextrace-ticket`). The decisions behind the look (direction, tokens, mobile patterns) live in `nextrace-design`; this skill is about how to build in the file.

## Before you start

1. Read the ticket. Design exactly what it describes; ask the user if something is unclear.
2. Load the Figma skills first: `get_figma_skill` for `skill://figma/figma-use/SKILL.md` (and `figma-generate-design` for whole pages) before any `use_figma` call. Do not retry in a loop if the Figma quota fails; tell the user.
3. Look at the current file before adding anything: list the pages, the Components page and the Style guide. Reuse what exists. Do not create a second version of something that is already a component or a token.
4. Prefer few, larger `use_figma` calls (batch related work). Plugin API reminders: call `await page.loadAsync()` before touching a page's children, and call `setCurrentPageAsync` at most once per call. You cannot remove the current page; switch to another page first.

## File structure

- **One Figma page per screen.** Page names are plain screen names: `Home`, `Events`, `Single event page`, `Favorites`, `Login and Register`. A state that changes the whole screen gets its own page named `<Screen> | <State>`: `Favorites | Empty state`, `Home | Language modal`.
- **Components page** holds every reusable piece as a real component (or component set). Nothing else lives there.
- **Style guide page** holds fonts (including the Inter specimen), colours, spacing, radii and the text styles in a table. It is the reference for everything below.
- No leftover pages (`Page 1`, scratch work). Delete experiments before finishing.

## Three viewports on every page

Every screen page contains all three frames, **always in this order left to right: Desktop 1440, Tablet 768, Mobile 390**. Name the frames `<Screen> / Desktop 1440`, `<Screen> / Tablet 768`, `<Screen> / Mobile 390` (add the state name for extra frames, e.g. `Mobile 390 / Menu`, `Mobile 390 / Search open`).

- Use the same content in all three; only the layout changes. Grid: 3 / 2 / 1 columns for cards.
- Below desktop (so on tablet too), the header uses the mobile pattern: logo, search button, burger. The desktop header is only for 1440.
- Side margins: desktop container 1152 wide with a 176 page margin; tablet and mobile use the page padding from the Style guide.
- A page is not done until all three frames exist and have been checked in a screenshot.

## Components

- Build a component once on the Components page, then **instance it on pages**. Never copy or detach an instance, and never rebuild a header, footer, card, badge, button or field by hand on a page. A change goes into the component and every page follows.
- Variants of one component sit **in one row** (for example Header for Desktop, Tablet and Mobile; Badge Neutral, Accent, Outline; Event card Favorited = No / Yes). Use component sets with variant properties; name properties and values in plain words (`Favorited=Yes`).
- Name components `<Component>` or `<Component> / <Variant>` (`Upcoming events / Desktop`, `Header / Mobile`). Existing set: Header, Footer, Badge, Button (Outline, Primary, Language), Field / Text input, Event card, Upcoming events.
- Keep **equal gaps of 80px** between components on the Components page, with no background frame behind them: the component itself is the top-level node.
- Components use auto-layout; text and containers hug their content (`layoutSizingVertical = HUG`) unless a fixed size is intended. Check that nothing is clipped, especially in tablet and mobile variants.
- On pages, use `swapComponent` to switch an instance's variant instead of rebuilding it. Override text and images on the instance only; structure changes belong in the component.
- A state that matters visually gets an explicit variant (the favourite heart is `Event card` with `Favorited=Yes`), not a manual recolour on the page.

## Tokens and the Style guide

Follow the Style guide strictly; do not invent values.

- **Font:** Inter (Regular, Medium, Bold, Extra Bold). Apply the published text styles; do not set free-form font sizes. A size that is not in the scale is a Style guide change, not a local override.
- **Colours:** the variable collection with 11 colours: bg, card, ink, muted, line, accent, soft, soft-ink, warn, warn-ink, favorite. Bind fills and strokes to the variables (`setBoundVariableForPaint`); never paste hex values.
- **Radius:** 8 and full only.
- **Spacing:** 8 / 16 / 24 / 32 / 48 / 64 for padding, gaps and margins.
- Touch targets are at least 44px on tablet and mobile.

**Changing the Style guide** (a new colour, size, radius or spacing step, a new text style) needs the developer's agreement first, because the code has to follow. Put the proposal in the ticket (what, why, where it is used), get the OK, then change the Style guide and the variables, and update every place that uses the old value. Add a "Style guide change" note to the ticket so the code ticket covers the token change as well.

## Finishing a design task

1. Take a screenshot of every new or changed frame (all three viewports) and check: order, equal gaps, no clipping, instances not detached, variables bound, text styles applied.
2. Make sure no unused components, styles, variables or pages remain.
3. Update the ticket: Figma links under **Design**, and a comment saying what changed (pages, components, Style guide).
4. Tell the user what was designed and wait for their OK. Code starts only after the design is approved and the ticket is taken into development. Do not edit Vue or `lang/` files as part of the design step.
