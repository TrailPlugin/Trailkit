=== TrailKit — Adventure Routes, POIs & Guides ===
Contributors: gabrielarias
Tags: routes, maps, hiking, outdoor, guides
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add adventure routes, points of interest, and guide profiles to any WordPress site. Works with any theme, no dependencies.

== Description ==

TrailKit gives you three powerful Custom Post Types to build outdoor and adventure tourism websites:

* **Routes** — with difficulty, distance, elevation, GPS waypoints, and gallery
* **Points of Interest** — with coordinates, photo gallery, and conditions alerts
* **Guides** — with contact info, specialties, service area, and profile photo

**Works with any theme.** No plugin dependencies. All data is stored in standard WordPress postmeta using native meta boxes.

= Key Features =

* 3 Custom Post Types: Routes, Points of Interest, Guides
* Shared taxonomies: Activity, Region + POI Type
* Native meta boxes (no plugin dependencies)
* Shortcodes: `[tk_routes]`, `[tk_pois]`, `[tk_guides]`, `[tk_map]`
* Interactive Leaflet map with AJAX-loaded markers
* Template override system (like WooCommerce)
* Dark mode ready CSS variables
* Translation ready

= Shortcode Examples =

`[tk_routes activity="hiking" difficulty="moderate" limit="6" columns="3"]`
`[tk_pois type="waterfall" region="andes" limit="9"]`
`[tk_guides featured="true" specialty="climbing"]`
`[tk_map type="routes" height="450px" lat="10.48" lng="-66.90" zoom="8"]`
`[tk_map type="all" height="500px"]`

= Upgrade to Pro =

[TrailKit Pro](https://trailplugin.com) removes the 3-item limit and unlocks:
GPS polyline maps, elevation profiles, GPX import, gallery lightbox slider, and priority support.

Get a license or start a free 14-day trial at [trailplugin.com](https://trailplugin.com).

== External Services ==

TrailKit uses the following external services:

**OpenStreetMap (map tiles)**
When a map is displayed, tiles are loaded from `tile.openstreetmap.org`. No user data is sent. The Leaflet library itself is bundled locally with the plugin.
- [Leaflet Terms](https://leafletjs.com/)
- [OpenStreetMap Terms](https://www.openstreetmap.org/copyright)

**Open-Meteo (weather widget — Pro only)**
When the live weather widget is enabled on a route (Pro feature), the visitor's browser fetches weather data from `api.open-meteo.com` using the route's GPS coordinates. No personal user data is sent — only latitude and longitude. Lite users are not affected.
- [Open-Meteo Terms](https://open-meteo.com/en/terms)
- [Open-Meteo Privacy Policy](https://open-meteo.com/en/terms)

**TrailKit License Server (Pro licenses only)**
When you activate a Pro license key, the plugin contacts the TrailKit license server at `trailplugin.com` to validate your key. Your site domain, WordPress version, and plugin version are transmitted. This only happens when you enter a license key in Settings — Lite users are not affected.
- [TrailKit Privacy Policy](https://trailplugin.com/privacy)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/trailplugin/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Routes → Settings to configure slugs and map defaults
4. Add `[tk_routes]` to any page to display your routes
5. Go to Settings → Permalinks and click Save to register the custom URLs

== Frequently Asked Questions ==

= Do I need any other plugins? =
No. TrailKit works entirely with native WordPress meta boxes. There are no required dependencies — just install and activate.

= Can I customize the card templates? =
Yes. Copy any file from `wp-content/plugins/trailplugin/templates/` to `wp-content/themes/your-theme/trailplugin/` and edit it freely.

= What is the Lite limit? =
3 routes, 3 POIs, and 1 guide. [Upgrade to Pro](https://trailplugin.com) for unlimited content.

= After activating I get 404 errors on route pages. =
Go to Settings → Permalinks and click Save. This re-registers the custom post type rewrite rules. Always do this after activating or deactivating TrailKit.

= The map is blank or not loading. =
The map tiles are loaded from OpenStreetMap. Check that your server allows outgoing connections to `tile.openstreetmap.org`.

= How do I get Pro? =
Visit [trailplugin.com](https://trailplugin.com) to purchase a license or start a free 14-day trial. After purchase, enter your key in Routes → Settings → Pro License.

== Changelog ==

= 1.0.3 =
* New: `[tk_map id="123"]` embeds the map of a single route, POI, or guide anywhere (e.g. inside a blog post) — shows only the map by default
* New: info toggles on the single-item map — `title`, `mapsbtn` (Open in Google Maps), plus per-type blocks: `category`/`coords`/`description` (POI), `difficulty`/`distance`/`elevation`/`time` (route), `specialties`/`price`/`contact`/`radius` (guide), and `gallery`
* New: "Map Embed / Shortcode" box in the POI, route, and guide editors — tick what to show and copy the generated shortcode
* Routes render their full GPS track fitted to the map; guides render a service-area circle
* Fixed: embedded maps now initialize reliably inside block themes (FSE) — map init is emitted inline on DOMContentLoaded instead of relying on the enqueued script's "after" data, which block themes did not always print

= 1.0.2 =
* `[tk_map]` now supports `type="guides"` to display guide markers only
* `[tk_map type="all"]` now includes guides alongside routes and POIs (previously routes + POIs only)
* Guide markers rendered in blue (`#38bdf8`) with a translucent service-area radius circle
* Guide popups show specialties and starting daily rate
* Fixed: filter-pill click on embedded maps now dismisses the "click to interact" overlay immediately

= 1.0.1 =
* Route import box: accepts both JSON (from TrailKit Planner) and GPX files — auto-detected by format
* Route import fills distance, elevation gain/loss, duration, start coordinates, and full track points
* POI import box: accepts JSON and GPX files — fills lat/lng, name, category, description, and Google Maps URL
* Added Category field to POIs (free text input)
* Added Description field to POIs (shown on single POI page with styled left-border block)
* Elevation chart: filter out zero-elevation points from routes with partial elevation data
* Elevation chart: fill area now always extends down to 0 m baseline
* Elevation chart: fixed canvas path split bug (moveTo inside smoothPathFraction caused a diagonal artifact)

= 1.0.0 =
* Initial release
