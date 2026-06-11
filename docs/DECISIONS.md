# Events Calendar for Bricks Builder — Decisions

This file captures the "why" behind the current structure so we can keep it aligned as the plugin evolves.

## Current approach: one loop element renders all parts

- **Decision**: Provide a single `ecbb-events-loop` Bricks element that queries events and prints common parts (title/date/venue/etc).
- **Why**:
  - Fast to implement and easy for users (one element, one repeater for parts).
  - No need to build Bricks nestable/query-child integration.
  - Rendering uses TEC functions when available and falls back to WP meta when not.
- **Trade-offs**:
  - Event parts are not native Bricks elements, so users don't get the exact same UX as using Bricks Heading/Image/Text elements.
  - Styling is mostly CSS-driven (via emitted classes) unless we add many per-part controls.

## Templates: list / grid / carousel

- **Decision**: Keep one element and offer multiple layout templates.
- **Why**:
  - Users can switch between list/grid/carousel without rebuilding the element.
  - Uses stable HTML structure + CSS variables for responsiveness.
- **Trade-offs**:
  - Carousel behaviour requires JS (autoplay + arrows).

## Load More via AJAX

- **Decision**: Implement offset-based “Load More” using `admin-ajax.php`.
- **Why**:
  - Reliable paging by `offset` (using `WP_Query`), appends HTML without reload.
  - Keeps the repeater `parts` rendering consistent via a shared `render_part_html()` server method.
- **Trade-offs**:
  - Duplicates some rendering logic between the element and AJAX handler (kept intentionally for consistent markup).

## Per-part styling uses instance-scoped CSS

- **Decision**: Generate instance-scoped CSS rules for per-part styling instead of relying on inline styles.
- **Why**:
  - Inline styles on `<a>` can be removed by sanitization, and theme CSS frequently overrides link/text colors.
  - Bricks stores color controls as objects (e.g. `{ "raw": "#..." }`), and CSS output is the most reliable way to apply it.

## Removed: deprecated single-purpose elements

- **Decision**: Remove the previously created standalone elements (`ecbb-event-title`, `ecbb-event-date`, `ecbb-event-description`) and their helper.
- **Why**:
  - They were not registered/loaded anywhere in the plugin runtime.
  - They increased maintenance surface and confusion without providing builder-native styling.
  - Their functionality is already covered inside `ecbb-events-loop` parts.

## Future direction options (not implemented)

If the goal becomes “use native Bricks styling/UX for each piece”, consider one of these:

1. **Bricks Query Loop + dynamic tags**
   - Users build the card with native Bricks elements.
   - Plugin supplies dynamic tags for TEC-specific fields (start date, venue, etc).

2. **Nestable loop element**
   - Custom element provides the TEC query/context.
   - Inner children are real Bricks elements (best UX, more complex).

