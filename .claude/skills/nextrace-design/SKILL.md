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

- Font: Inter (Regular, Medium, Bold, ExtraBold), self-hosted through the `fonts` option of `laravel-vite-plugin` in `vite.config.ts`. The type scale lives in the Style guide page (all Inter): Display / Hero 60/64 ExtraBold, Heading / H1 48/52, H1 mobile 30/34, H2 20/26, Card title 17/22 Bold, Logo / Wordmark 20/27 ExtraBold, Body Large 18/28, Body Default 16/26, Body Small 14/20 (and Medium), Label / Button 14/20 Bold, Label / Chip 12/16 Bold, Caption 13/18, Overline 11/14 Bold.
- Colours: 11 variables in the collection **NextRace colors**: bg `#F6F4EE`, card `#FFFFFF`, ink `#14231F`, muted `#5D6B66`, line `#E2DDD0`, accent `#0F7A5C`, soft `#E3F2EA`, soft-ink `#0B5A43`, warn `#FBF0D4`, warn-ink `#6B4A07`, favorite `#EF4444`. Always bind fills and strokes to these variables; do not hardcode hex.
- Radius: only two values. **8px** for buttons, inputs, chips, badges, cards, panels, dialogs and the hero; **full (999)** for pills and the favorite button.
- Spacing scale: 8, 16, 24, 32, 48, 64.
- Icons (24px, `icon/...`): calendar, clock, pin, route, heart, globe, search, menu, close, back, check.
- Touch targets are at least 44px on mobile and tablet (buttons, menu rows 48px).

## Layout

- **Header and footer are full width.** Only the body sits in a centred container: 1152px wide with 32px side padding on desktop (`max-w-6xl`).
- Breakpoints: Mobile 390, Tablet 768, Desktop 1440. Side padding: 16 / 24 / container. Grid: 1 / 2 / 3 columns, gap 16-20.
- Pages in the Figma file: `Style guide`, `Components`, `Home` (Desktop 1440, Tablet 768, Mobile 390), `Home | Language modal`, `Events` (Desktop 1440, Tablet 768, Mobile 390, plus `Mobile 390 / Menu` and `Mobile 390 / Search open`), `Single event page`, `Favorites`, `Favorites | Empty state`, `Login`, `Register`. Profile and settings pages are not in Figma.
- The events page is just the header, a card grid and the footer: no hero, no filters row or Filters sheet, no race count. The wordmark is `Nextrace`; there is no theme toggle.
- Components page: `Header` (Desktop 1440 x 119, Tablet and Mobile x 70), `Footer` (Desktop, Tablet, Mobile), `Badge` (Neutral, Accent, Outline), `Button` (Primary, Outline, Language), `Field / Text input`, `Event card` (`Favorited=No/Yes`) and `Upcoming events` (Desktop, Tablet, Mobile). Pages use instances of these; never draw a copy.
- Header heights: 70px on tablet and mobile; desktop is 119px (70px bar plus a 49px distance bar). In code the mobile header is used up to 1024px and the desktop header from `lg`.

## Components

- **Event card** (one Figma component, reuse via instances): photo on top (16:10), date chip top-left, favorite button top-right, then title, city and region, distance chips (up to four, extra ones hidden) and one status badge (`Registration open` green, `Date TBC` amber). If `image_url` is empty, show the placeholder (contour or flat colour), never a stock photo of another race.
- **Upcoming events**: a section component (Desktop, Tablet, Mobile) shown on Home between the hero and the three features.
- **Footer**: a single line, 49px on desktop and tablet. Left: `© 2026 Nextrace`. Right: Events and Favorites links. No note, no Log in, no language toggle and no theme toggle. The mobile variant (90px) stacks the same content.

## Desktop and tablet

- Header: logo, a search field with a `Search` button, the language button (`Button / Language`) and Log in. On desktop a full-width bar of distance tabs sits under it (All races, Marathon, Half marathon, 10K, 5K, Ultra, Trail). Tablet uses the 70px header without the tab bar.
- Body: a card grid (3 columns on desktop, 2 on tablet) on the events page. Home has a hero with the primary button, Upcoming events, then three feature cards.

## Mobile patterns

- Header: logo, a search (magnifier) button and a burger button, 44px each, with tight vertical padding (2px).
- **Distance categories live in the burger menu**, like worldsmarathons.com: a scrollable list (All races, Full marathons, Half marathons, 10 km, 5 km, Ultra, Trail, Other) with counts and chevrons, then Favorites, Log in and the EN/EL toggle. The list scrolls, so more categories can be added without redesign. There is no category chip row on the page itself (we tried it and the clipped last chip looked bad).
- **No search field in the menu or the header.** The magnifier slides a search panel down under the header (input plus a `Search` button), the magnifier becomes a close icon and the content below is dimmed (frame `Search open`).
- Page body: a one-column card list. There is no Filters button or sheet in the current design (it was removed).

## Porting to code

Do it on a new branch from `origin/main` (follow the `git-branch-workflow` skill). Main touchpoints: `resources/js/components/EventCard.vue` (photo card, with a fallback to the existing `ContourArt` when `event.image` is empty), `resources/js/pages/Events/Index.vue` (grid `sm:grid-cols-2 lg:grid-cols-3`, `events.per_page` default 12 instead of 10), `resources/js/components/DefaultHeader.vue` and `DefaultFooter.vue` (full-width bars, container only around the page body), a new mobile menu and search panel, and `resources/css/app.css` tokens. Add new strings to both `lang/en` and `lang/el`. The map was already removed (PR remove-events-map); do not bring it back. A first attempt to port the card and footer was rolled back at the user's request because the design was not agreed yet.

## Open items

- Real photos: events have an `image_url` field but the data is empty. Stock photos from Wikimedia Commons were used only in a throwaway HTML mockup; they require author attribution (CC BY and CC BY-SA) and must not be shown as photos of the specific race. Get photos from organisers with permission.
- Not designed yet: dark theme (removed from the site on purpose; `ThemeToggle.vue` and `useAppearance` stay for later), loading states, an expanded burger with sub-categories, tablet menu, mobile Favorites/Login/Register, Profile and settings pages. The event detail page uses sample data and flat colours, no photo.
- Header Login/Register: the design has one button, the site has two. To be aligned in a separate ticket.
- The throwaway mockup `.claude/photo-led-mockup.html` is not part of the repository; delete it when no longer needed.
