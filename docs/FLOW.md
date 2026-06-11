# Events Calendar for Bricks Builder — Flow (current)

This document reflects the **current working flow** of the plugin: list/grid/carousel templates, per-part styling, and AJAX load more.

## Responsibilities

- **`ecbb.php`**: bootstrap, constants, dependency checks, includes `Events_Brick_Addon`.
- **`includes/class-ecbb.php`**: registers the Bricks element, enqueues assets, provides AJAX endpoint for Load More.
- **`includes/elements/class-element-ecbb-events-widget.php`**: the `ecbb-events-loop` Bricks element (controls, TEC query, rendering).
- **`includes/events-widget/ecbb-events-widget-query.php`**: `tribe_get_events` / WP_Query argument builders for the widget and load-more AJAX.
- **`includes/events-widget/ecbb-events-widget-loop-markup.php`**: shared event part HTML (date, tickets, images, hover helpers); list Style 2 shell is in `template/list/list-style-2.php`.
- **`includes/events-widget/template/list/list-style-1.php`**: list Style 1 shell markup helpers.
- **`includes/events-widget/template/list/list-style-2.php`**: list Style 2 shell markup helpers (month rail, inner row).
- **`includes/events-widget/grid/ecbb-events-widget-grid-markup.php`**: grid card shell markup helpers.
- **`assets/css/events-widget/ecbb-events-widget-base.css`**: shared list/grid layout, load more, dual featured-image hover.
- **`assets/css/events-widget/template/list/list-style-1.css`**: list item Style 1 chrome.
- **`assets/css/events-widget/template/list/list-style-2.css`**: Style 2 (magazine list + grid card chrome).
- **`assets/css/events-widget/grid/ecbb-events-widget-grid.css`**: grid card layout chrome.
- **`assets/js/events-load-more.js`**: handles Load More and carousel behaviour (arrows + autoplay).

## Bootstrap → init

1. WP loads `ecbb.php`.
2. Constants are defined: `EVENTS_BRICK_ADDON_VERSION`, `EVENTS_BRICK_ADDON_DIR`, `EVENTS_BRICK_ADDON_URL`.
3. Dependencies are checked:
   - **Bricks theme** (`get_template() === 'bricks'`)
   - **The Events Calendar** (`class_exists('Tribe__Events__Main')` or `function_exists('tribe_get_events')`)
4. On `init`, the plugin instantiates `Events_Brick_Addon` (only when Bricks is available).

## Element registration

`Events_Brick_Addon::register_elements()` registers:

- **name**: `ecbb-events-loop`
- **file**: `includes/elements/class-element-ecbb-events-widget.php`
- **class**: `ECBB\\Element_ECBB_Events_Widget`

## Main render path (`Element_ecbb_Events_Loop::render()`)

### 1) Templates (List / Grid / Carousel)

The element renders events inside:

- wrapper: `.ecbb-events-loop`
- list container: `.ecbb-events-loop__list`
  - `--list` for vertical list
  - `--grid` for CSS grid
  - `--carousel` for scroll/slider carousel

Layout uses CSS variables set on the root wrapper:

- `--ecbb-gap` (gap between events)
- `--ecbb-grid-cols`, `--ecbb-grid-cols-tablet`, `--ecbb-grid-cols-mobile`
- `--ecbb-carousel-view-desktop`, `--ecbb-carousel-view-tablet`, `--ecbb-carousel-view-mobile`

CSS is split across `assets/css/events-widget/ecbb-events-widget-base.css`, `template/list/list-style-*.css`, and `grid/ecbb-events-widget-grid.css` (enqueued from `ECBB_Plugin::enqueue_events_widget_styles()`).

### 2) TEC query

The element **fetches events via TEC**:

- builds args with `get_tec_query_args()`
- calls `tribe_get_events( $args )`

Args mapping:

- `status`:
  - `upcoming` → `starts_after = 'now'`
  - `past` → `starts_before = 'now'`
  - `all` → no date filter
- `posts_per_page` → `posts_per_page`
- `order` → `order`
- optional `category_slug` → `tax_query` on `tribe_events_cat` (slug)

### 3) Rendering

For each `WP_Post` returned:

- sets global `$post` + `setup_postdata($post)`
- prints `.ecbb-events-loop__item`
- iterates the repeater `parts` and renders each part with `render_part($post, $item, $idx)`
- resets `$post` via `wp_reset_postdata()`

Which fields appear is determined only by the **Event parts** repeater (add/remove/reorder rows); there are no separate visibility toggles for description, venue, tags, or organizer.

### 4) Per-part styling

Each repeater row has styling fields (color, hover color, font size, weight, image sizing, etc.).

Important detail:

- Bricks stores color controls as objects like `{ "raw": "#7a038f" }`.
- The element generates **instance-scoped CSS** targeting `.ecbb-event-part--idx-{n}` and descendants with `!important`.
- This avoids theme/link overrides and avoids inline-style sanitization issues.

## Load More (AJAX)

### Frontend behaviour

When enabled (and template is not Carousel), the element prints:

- `.ecbb-load-more` with a button `.ecbb-load-more__btn`

`assets/js/events-load-more.js` sends an AJAX request to `admin-ajax.php` using prefixed POST keys:

- `action = ecbb_events_load_more`
- `nonce` (created in `wp_localize_script`)
- `ecbb_settings` (JSON string)
- `ecbb_offset`
- `ecbb_limit`

The response returns:

- `html` (new `.ecbb-events-loop__item` blocks)
- `nextOffset`
- `hasMore`

When `hasMore` is false, the button is replaced with “No more events” and hides after a short delay.

### Server handler

`Events_Brick_Addon::ajax_events_load_more()`:

- verifies nonce
- uses `WP_Query` (offset-based) on `post_type = tribe_events`
- orders by `_EventStartDate`
- filters upcoming/past by comparing `_EventStartDate` to `current_time('mysql')`
- optional category filter via `tax_query`
- returns HTML built by `render_part_html()` so appended items match the same part rendering rules.


