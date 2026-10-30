---
name: nextrace-design
description: "NextRace design decisions and the Figma source of truth. ALWAYS activate before any UI, layout, styling, responsive, header, footer, event card, filter, menu or search work, and before building or editing anything in the NextRace Figma file or porting it to Vue. Records the photo-led direction, tokens, breakpoints, mobile patterns, and what is still open."
license: MIT
metadata:
  author: nextrace
---

# NextRace design

Source of truth: the Figma file **NextRace – Photo-led design** (https://www.figma.com/design/fvBPi385tS8VokeCk8ah3J), in the team "Stelios's Starter team". Read it before changing the UI, and keep it in sync when the design changes.

## Figma access

- Plan limits: the Starter team has a small monthly Figma MCP call quota and the user's seat there is view-only. On 2026-10-28 the quota ran out ("tool call limit on the Starter plan"). If calls fail, do not retry in a loop: tell the user, and suggest moving the file to the "Делосфера" team (Full seat) or waiting for the reset.
- Load the Figma skills first: `get_figma_skill` for `skill://figma/figma-use/SKILL.md` and `figma-generate-design` before any `use_figma` call; `figma-create-new-file` before `create_new_file`.
- Prefer few, larger `use_figma` calls (batch related work) to save quota.
- Photos cannot be fetched into Figma through the Plugin API; cards use flat colour blocks with SVG hills as placeholders. The user drops real photos into the `photo` frame of a card.

## Direction

**Photo-led**: large photos, a card grid, one green accent, quiet typography. References: worldsmarathons.com (photo cards, simple category nav moved into a menu on mobile). Calm cream background with white cards, not dark. Chosen over a sporty dark style and a text-only minimal list.

## Tokens

- Font: Manrope (Regular, Medium, Bold, ExtraBold). The Figma variable collection is **NextRace colors**: bg `#F6F4EE`, card `#FFFFFF`, ink `#14231F`, muted `#5D6B66`, line `#E2DDD0`, accent `#0F7A5C`, on-accent `#FFFFFF`, soft `#E3F2EA`, soft-ink `#0B5A43`, warn `#FBF0D4`, warn-ink `#6B4A07`, footer-line `#D6CFBD`, chip `#EEF0EE`. Always bind fills and strokes to these variables; do not hardcode hex.
- Radius: the user wants small corners. In Figma: cards and mobile hero 10, desktop hero 12, buttons, inputs and selects 6-8, chips 5, bottom sheet top 16. Pills (fully round chips and tabs) and circles (favorite button) stay round. In code the single token `--radius` in `resources/css/app.css` is currently `0.75rem` (12px, merged in PR smaller-border-radius); it is a little larger than the Figma cards, so align it when porting.
- Touch targets are at least 44px on mobile and tablet (buttons, chips, menu rows 48px).

## Layout

- **Header and footer are full width.** Only the body sits in a centred container: 1152px wide with 32px side padding on desktop (`max-w-6xl`).
- Breakpoints: Mobile 390, Tablet 768, Desktop 1440. Side padding: 16 / 24 / container. Grid: 1 / 2 / 3 columns, gap 16-20.
- Frames in Figma (synced with the site on 2026-10-30): `Events page / Desktop 1440`, `Tablet 768`, `Mobile 390`, plus `Mobile 390 / Menu`, `/ Search open`, `/ Filters sheet` (designed, not built in code) and the component `Event card`; `Event page / Desktop 1440` and `/ Mobile 390` (event detail); `Home`, `Favorites (empty)`, `Login` and `Register` at `Desktop 1440`. The events page has no hero, filters row or race count, the footer is white, the wordmark is `Nextrace`, and there is no theme toggle. Profile and settings pages are not in Figma.
- Header heights: the user cut them by hand from about 105px to about 70px. Keep the header around 70px; verify it again when the file is reachable (see Open items).

## Components

- **Event card** (one Figma component, reuse via instances): photo on top (16:10), date chip top-left, favorite button top-right, then title, city and region, distance chips (up to four, extra ones hidden) and one status badge (`Registration open` green, `Date TBC` amber). If `image_url` is empty, show the placeholder (contour or flat colour), never a stock photo of another race.
- **Footer**: a single minimal line. Left: NextRace, copyright, "Dates come from organisers". Right: Events, Favorites and the language toggle. The user removed **Log in** and the **theme toggle** from the footer. On mobile it stacks: brand, note, links and language toggle.

## Desktop and tablet

- Header: logo, search field (fills the width), `EN / EL`, Log in. Under it a full-width bar of distance tabs (All races, Marathon, Half marathon, 10K, 5K, Ultra, Trail). It fits in one line; when categories outgrow it, show the main ones plus `More ▾`.
- Body: hero (rounded, text on terrain placeholder), filters row (Region, Month, Distance, Status) with the race count, then the grid.

## Mobile patterns

- Header: logo, a search (magnifier) button and a burger button, 44px each, with tight vertical padding (2px).
- **Distance categories live in the burger menu**, like worldsmarathons.com: a scrollable list (All races, Full marathons, Half marathons, 10 km, 5 km, Ultra, Trail, Other) with counts and chevrons, then Favorites, Log in and the EN/EL toggle. The list scrolls, so more categories can be added without redesign. There is no category chip row on the page itself (we tried it and the clipped last chip looked bad).
- **No search field in the menu or the header.** The magnifier slides a search panel down under the header (input plus a `Search` button), the magnifier becomes a close icon and the content below is dimmed (frame `Search open`).
- Page body: hero, a single `Filters` button with the race count, and a one-column card list.
- **Filters sheet** (bottom sheet): Distance, Region, Month and Status as wrapping option chips (multi-select with a check), a sticky bar with `Reset` and `Show N races`. Wrapping chips scale to any number of options.

## Porting to code

Do it on a new branch from `origin/main` (follow the `git-branch-workflow` skill). Main touchpoints: `resources/js/components/EventCard.vue` (photo card, with a fallback to the existing `ContourArt` when `event.image` is empty), `resources/js/pages/Events/Index.vue` (grid `sm:grid-cols-2 lg:grid-cols-3`, `events.per_page` default 12 instead of 10), `resources/js/components/DefaultHeader.vue` and `DefaultFooter.vue` (full-width bars, container only around the page body), a new mobile menu and search panel, and `resources/css/app.css` tokens. Add new strings to both `lang/en` and `lang/el`. The map was already removed (PR remove-events-map); do not bring it back. A first attempt to port the card and footer was rolled back at the user's request because the design was not agreed yet.

## Open items

- Verify the manual header cut to about 70px in Figma once the quota is available: header height, 44px buttons, and the Search-open and Menu frames.
- Real photos: events have an `image_url` field but the data is empty. Stock photos from Wikimedia Commons were used only in a throwaway HTML mockup; they require author attribution (CC BY and CC BY-SA) and must not be shown as photos of the specific race. Get photos from organisers with permission.
- Not designed yet: dark theme (removed from the site on purpose; `ThemeToggle.vue` and `useAppearance` stay for later), loading states, an expanded burger with sub-categories, tablet menu, mobile Home/Favorites/Login/Register, Profile and settings pages. The event detail frames use sample data and flat colours, no photo (the hero was removed from the page).
- The throwaway mockup `.claude/photo-led-mockup.html` is not part of the repository; delete it when no longer needed.
