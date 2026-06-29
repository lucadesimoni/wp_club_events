=== Club Events Manager ===
Contributors: lucadesimoni
Tags: events, calendar, google calendar, ics, club
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern event management for clubs — sync multiple Google Calendars, timeline,
overview, tiles & blog embeds, ICS export, sharing, and email subscriptions.

== Description ==

Club Events Manager helps clubs publish and manage events with a polished,
theme-adaptive frontend.

* Configurable event types with per-type colours (falls back to the Astra
  theme colour when none is set).
* Connect multiple Google Calendars and sync multiple event types each.
* Display events as tiles, cards, a vertical timeline, a monthly overview, a
  yearly agenda, or compact lists.
* Sharing (native Web Share with WhatsApp / Facebook / Email / Copy fallback)
  and one-click ICS "Add to calendar" — inline on listings and event pages.
* Email subscriptions and a frontend submission form.
* Comprehensive Astra theme bridge for colours, typography, and buttons.

== Shortcodes ==

* `[club_events_tiles]` — blog-card style previews of the next events.
* `[club_events_timeline]` — vertical timeline grouped by month.
* `[club_events_overview]` — monthly calendar grid + list.
* `[club_events_cards]` — responsive card grid.
* `[club_events_yearly]` — full-year agenda by month.
* `[club_events_list]` — compact list for sidebars.
* `[club_events_share]` — share + ICS actions for the current event.
* `[club_events_subscribe]` — email subscription form.
* `[club_events_submit]` — frontend event submission.
* `[club_events_my_events]` — user event dashboard.

== Changelog ==

= 1.1.0 =
* Configurable event types with colour + Astra theme-colour fallback.
* Multiple calendars, each syncing multiple event types.
* Dashboard tiles / table / timeline views.
* New [club_events_tiles] blog-card preview shortcode (incl. no-image variant).
* Sharing (native + WhatsApp/Facebook/Email/Copy) and inline ICS download on
  overview listings and event pages.
* Comprehensive Astra design-token bridge; sleek mobile presentation.
* Fixed invalid nested-anchor markup in tile/card wrappers.

= 1.0.0 =
* Initial release: Google Calendar sync, timeline & overview views, blog
  embeds, ICS export, and email subscriptions.
