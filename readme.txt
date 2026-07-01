=== Events Calendar for Bricks Builder ===
Contributors: narinder-singh,satindersingh,coolplugins
Tags: bricks, events, calendar, the-events-calendar, page-builder
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Requires Plugins: the-events-calendar
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display The Events Calendar events in Bricks Builder with list and grid layouts, configurable event parts, and per-part styling.

== Description ==

**Events Calendar for Bricks Builder** adds an **Events Widget** element to Bricks so you can build custom event listings without leaving the Bricks editor.

The widget queries events from [The Events Calendar](https://wordpress.org/plugins/the-events-calendar/) and renders them in polished list or grid layouts. Each event field is configurable: show or hide parts, reorder them, and style typography, backgrounds, buttons, and hover states from the Bricks panel.

= Requirements =

* WordPress 6.0 or higher
* [The Events Calendar](https://wordpress.org/plugins/the-events-calendar/) plugin (active)
* [Bricks](https://bricksbuilder.io/) theme (active)

= Features =

* **List and grid templates** — switch between vertical list and multi-column grid layouts
* **Two list styles** — Style 1 (side date column) and Style 2 (card layout with image, date badge, and meta row)
* **Flexible event parts** — title, description, date & time, venue, organizer, cost, categories, tags, featured image, read more, tickets, RSVP, and event link
* **Events query controls** — filter by category, upcoming/past/range, order, and number of events
* **Per-part styling** — typography, alignment, spacing, button styles, borders, and hover colors/animations on supported parts
* **Layout options** — featured image visibility, category badge on image (list/grid), date badge on image (Style 2), grid columns, gap, and card appearance
* **Empty state message** — customizable “no events found” text and heading tag
* **Performance-conscious loading** — widget styles and helpers load only when the element is used on a page

= How to use =

1. Install and activate **The Events Calendar** and the **Bricks** theme.
2. Activate **Events Calendar for Bricks Builder**.
3. Edit a page in Bricks and add the **Events Widget** element.
4. Choose a layout (list or grid), configure the query, and add or reorder event parts in the **Elements** repeater.

== Installation ==

1. Upload the `events-calendar-for-bricks` folder to `/wp-content/plugins/`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure **The Events Calendar** and the **Bricks** theme are installed and active.
4. Open Bricks Builder and insert the **Events Widget** element where you want events to appear.

== Frequently Asked Questions ==

= Does this plugin work without Bricks? =

No. This plugin is a Bricks element integration. The Bricks theme must be active.

= Does this plugin replace The Events Calendar? =

No. It extends The Events Calendar by providing a Bricks widget to display events. You still need The Events Calendar for event data and management.

= Can I style individual event fields? =

Yes. Each row in the **Elements** repeater represents one event part. Use the **Style** tab on that row for typography, background, padding, button styling, and hover options (where supported).

= Which list style should I use? =

**Style 1** shows a date column beside the content — good for classic event lists. **Style 2** uses a card layout with a large image, optional date badge, category pills, and a meta row — good for visual, magazine-style listings.

== Changelog ==

= 1.0.0 =
* Initial release
* Events Widget Bricks element (`ecbb-events-loop`)
* List templates: Style 1 and Style 2
* Grid template with configurable columns
* Event parts repeater with query, layout, and per-part style controls
* Featured image overlays (category badge, date badge)
* Hover styling for titles, categories, tags, buttons, and images

== Upgrade Notice ==

= 1.0.0 =
Initial release.
