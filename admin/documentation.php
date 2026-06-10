<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Documentation {

    const DOCS_URL = 'https://trailplugin.com/docs';

    public static function init() {
        add_action( 'admin_menu', [ self::class, 'menu'          ] );
        add_action( 'admin_menu', [ self::class, 'override_url'  ], 999 );
    }

    public static function menu() {
        // Registered so WP shows the item in the sidebar.
        // The URL is replaced with the external docs link in override_url().
        add_submenu_page(
            'edit.php?post_type=tk_route',
            '',
            __( 'Documentation', 'trailkit' ),
            'edit_posts',
            'trailkit-docs',
            '__return_null'
        );
    }

    // Replace the internal admin URL with the external docs URL directly in
    // the $submenu global. The sidebar link then opens trailplugin.com/docs
    // without going through a WP admin page first.
    public static function override_url() {
        global $submenu;
        $parent = 'edit.php?post_type=tk_route';
        if ( ! isset( $submenu[ $parent ] ) ) return;
        foreach ( $submenu[ $parent ] as &$item ) {
            if ( isset( $item[2] ) && $item[2] === 'trailkit-docs' ) {
                $item[2] = self::DOCS_URL;
                break;
            }
        }
    }

    public static function styles( $hook ) {
        // No internal docs page — kept for back-compat, never fires.
        if ( $hook !== 'trailkit_page_trailkit-docs' ) return;
        wp_add_inline_style( 'wp-admin', self::get_css() );
    }

    private static function get_css(): string {
        return '
        .tk-docs-wrap { max-width: 1100px; }
        .tk-docs-wrap h1 { display:flex; align-items:center; gap:10px; }
        .tk-docs-tabs { display:flex; gap:2px; border-bottom:2px solid #0df246; margin:20px 0 0; flex-wrap:wrap; }
        .tk-docs-tab { padding:10px 18px; cursor:pointer; border:none; background:none; font-size:13px; font-weight:600; color:#555; border-radius:4px 4px 0 0; border:2px solid transparent; border-bottom:none; margin-bottom:-2px; }
        .tk-docs-tab:hover { background:#f0fff4; color:#0a7c1e; }
        .tk-docs-tab.active { background:#fff; color:#0a7c1e; border-color:#0df246; border-bottom-color:#fff; }
        .tk-docs-panel { display:none; background:#fff; border:2px solid #0df246; border-top:none; border-radius:0 0 8px 8px; padding:28px 32px; }
        .tk-docs-panel.active { display:block; }
        .tk-docs-panel h2 { font-size:1.3rem; color:#1a1a1a; margin:0 0 6px; padding-bottom:10px; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; gap:8px; }
        .tk-docs-panel h3 { font-size:1rem; color:#1a1a1a; margin:24px 0 8px; }
        .tk-docs-panel h4 { font-size:.875rem; font-weight:700; color:#333; margin:16px 0 6px; }
        .tk-docs-panel p { color:#444; line-height:1.7; margin:0 0 12px; }
        .tk-docs-panel ul, .tk-docs-panel ol { color:#444; line-height:1.8; padding-left:20px; margin:0 0 16px; }
        .tk-docs-panel li { margin-bottom:4px; }
        .tk-docs-panel code { background:#f4f9f4; border:1px solid #d4edda; padding:2px 7px; border-radius:4px; font-size:12px; color:#1a6633; font-family:monospace; }
        .tk-docs-panel pre { background:#1a1a2e; color:#e8f4e8; padding:16px 20px; border-radius:8px; overflow-x:auto; margin:10px 0 18px; font-size:12.5px; line-height:1.6; }
        .tk-docs-panel pre code { background:none; border:none; padding:0; color:inherit; font-size:inherit; }
        .tk-docs-shortcode { background:#f8fcf8; border:1px solid #c3e6cb; border-radius:8px; padding:16px 20px; margin:14px 0; }
        .tk-docs-shortcode code.sc { display:block; font-size:13px; color:#0a7c1e; margin-bottom:12px; background:none; border:none; padding:0; }
        .tk-docs-shortcode table { width:100%; border-collapse:collapse; font-size:12.5px; }
        .tk-docs-shortcode table th { text-align:left; padding:6px 10px; background:#e8f5e9; color:#1a6633; font-weight:700; border:1px solid #c3e6cb; }
        .tk-docs-shortcode table td { padding:6px 10px; border:1px solid #d4edda; color:#333; vertical-align:top; }
        .tk-docs-shortcode table td:first-child { font-family:monospace; font-size:11.5px; color:#0a7c1e; white-space:nowrap; }
        .tk-docs-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin-left:6px; }
        .tk-docs-badge-pro { background:#fff3e0; color:#e65100; border:1px solid #ffe0b2; }
        .tk-docs-badge-lite { background:#e8f5e9; color:#1b5e20; border:1px solid #c8e6c9; }
        .tk-docs-badge-new { background:#e3f2fd; color:#0d47a1; border:1px solid #bbdefb; }
        .tk-docs-field-table { width:100%; border-collapse:collapse; font-size:12.5px; margin:8px 0 18px; }
        .tk-docs-field-table th { text-align:left; padding:8px 12px; background:#f5f5f5; color:#333; font-weight:700; border:1px solid #ddd; }
        .tk-docs-field-table td { padding:7px 12px; border:1px solid #e0e0e0; color:#444; vertical-align:top; }
        .tk-docs-field-table td:first-child { font-family:monospace; font-size:11.5px; color:#0a7c1e; white-space:nowrap; font-weight:600; }
        .tk-docs-field-table td:nth-child(2) { color:#666; font-size:12px; }
        .tk-docs-callout { padding:14px 18px; border-radius:8px; margin:14px 0; display:flex; gap:10px; align-items:flex-start; }
        .tk-docs-callout-tip { background:#e8f5e9; border-left:4px solid #4caf50; }
        .tk-docs-callout-warn { background:#fff8e1; border-left:4px solid #ffc107; }
        .tk-docs-callout-info { background:#e3f2fd; border-left:4px solid #2196f3; }
        .tk-docs-callout p { margin:0; font-size:13px; }
        .tk-docs-toc { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:8px; padding:16px 20px; margin-bottom:24px; }
        .tk-docs-toc h4 { margin:0 0 8px; font-size:.875rem; color:#333; }
        .tk-docs-toc ul { margin:0; padding-left:16px; }
        .tk-docs-toc li { margin-bottom:3px; }
        .tk-docs-toc a { color:#0a7c1e; text-decoration:none; font-size:13px; }
        .tk-docs-toc a:hover { text-decoration:underline; }
        .tk-docs-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin:16px 0; }
        .tk-docs-card { background:#f8fcf8; border:1px solid #c3e6cb; border-radius:8px; padding:16px 18px; }
        .tk-docs-card h4 { margin:0 0 8px; color:#1a6633; font-size:.875rem; }
        .tk-docs-card p { margin:0; font-size:12.5px; color:#555; line-height:1.6; }
        .tk-docs-version { font-size:11px; color:#999; float:right; }
        .tk-docs-search-bar { position:relative; margin-bottom:4px; }
        .tk-docs-search-bar input { width:100%; padding:8px 12px 8px 36px; border:1px solid #ddd; border-radius:6px; font-size:13px; }
        .tk-docs-search-bar svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#999; }
        ';
    }

    public static function render() {
        if ( ! current_user_can( 'edit_posts' ) ) return;
        $is_pro = tk_is_pro();
        $tabs = [
            'start'      => '🚀 Getting Started',
            'routes'     => '🗺 Routes',
            'pois'       => '📍 POIs',
            'guides'     => '👤 Guides',
            'shortcodes' => '[ ] Shortcodes',
            'templates'  => '🎨 Templates',
            'css'        => '✏️ CSS & Styling',
            'license'    => '🔑 License & Pro',
            'faq'        => '❓ FAQ',
        ];
        $active = sanitize_key( $_GET['tab'] ?? 'start' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! array_key_exists( $active, $tabs ) ) $active = 'start';
        ?>
        <div class="wrap tk-docs-wrap">
            <h1>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0df246" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17L9 7l4 6 3-4 5 8H3z"/></svg>
                TrailKit — Documentation
                <span class="tk-docs-version">v<?php echo esc_html( TK_VERSION ) ?> <?php echo $is_pro ? '<span class="tk-docs-badge tk-docs-badge-pro">PRO</span>' : '<span class="tk-docs-badge tk-docs-badge-lite">LITE</span>' ?></span>
            </h1>
            <p style="color:#666;margin:4px 0 0"><?php esc_html_e('Complete reference for TrailKit — Adventure Routes, POIs & Guides for WordPress.','trailkit') ?></p>

            <div class="tk-docs-tabs">
                <?php foreach ( $tabs as $key => $label ): ?>
                <button class="tk-docs-tab <?php echo esc_attr( $key ) === $active ? 'active' : '' ?>" onclick="tkDocsTab('<?php echo esc_attr( $key ) ?>')" data-tab="<?php echo esc_attr( $key ) ?>">
                    <?php echo esc_html($label) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <?php
            self::tab_start( $active );
            self::tab_routes( $active );
            self::tab_pois( $active );
            self::tab_guides( $active );
            self::tab_shortcodes( $active );
            self::tab_templates( $active );
            self::tab_css( $active );
            self::tab_license( $active, $is_pro );
            self::tab_faq( $active );
            ?>

            <script>
            function tkDocsTab(key) {
                document.querySelectorAll('.tk-docs-tab').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tk-docs-panel').forEach(p => p.classList.remove('active'));
                document.querySelector('[data-tab="'+key+'"]').classList.add('active');
                document.getElementById('tk-docs-'+key).classList.add('active');
            }
            </script>
        </div>
        <?php
    }

    /* ═══════════════════════════════════════════════════════════
       TABS
    ═══════════════════════════════════════════════════════════ */

    private static function tab_start( $active ) { ?>
        <div id="tk-docs-start" class="tk-docs-panel <?php echo $active === 'start' ? 'active' : '' ?>">
            <h2>🚀 Getting Started</h2>

            <div class="tk-docs-toc">
                <h4>On this page</h4>
                <ul>
                    <li><a href="#start-requirements">Requirements</a></li>
                    <li><a href="#start-install">Installation</a></li>
                    <li><a href="#start-first-route">Adding your first route</a></li>
                    <li><a href="#start-display">Displaying content</a></li>
                    <li><a href="#start-demo">Demo data</a></li>
                </ul>
            </div>

            <h3 id="start-requirements">Requirements</h3>
            <table class="tk-docs-field-table">
                <tr><th>Requirement</th><th>Minimum</th><th>Notes</th></tr>
                <tr><td>WordPress</td><td>6.0+</td><td>Tested up to 6.7</td></tr>
                <tr><td>PHP</td><td>7.4+</td><td>PHP 8.x recommended</td></tr>
                <tr><td>Browser (maps)</td><td>Any modern browser</td><td>Leaflet requires internet for map tiles</td></tr>
                <tr><td>ACF</td><td>Not required</td><td>Works with native meta boxes only</td></tr>
            </table>

            <h3 id="start-install">Installation</h3>
            <ol>
                <li>Go to <strong>Plugins → Add New → Upload Plugin</strong></li>
                <li>Upload <code>trailkit.zip</code> and click <strong>Install Now</strong></li>
                <li>Click <strong>Activate</strong></li>
                <li>Go to <strong>Routes → Settings</strong> to configure URL slugs and map defaults</li>
                <li>Go to <strong>Settings → Permalinks</strong> and click Save (required after activation)</li>
            </ol>

            <div class="tk-docs-callout tk-docs-callout-warn">
                <span>⚠️</span>
                <p><strong>Permalinks:</strong> After activating TrailKit, always go to Settings → Permalinks and click Save to register the new post type URLs.</p>
            </div>

            <h3 id="start-first-route">Adding your first route</h3>
            <ol>
                <li>In the WordPress admin, go to <strong>Routes → Add New</strong></li>
                <li>Set the <strong>Title</strong> (route name)</li>
                <li>Write a description in the <strong>content editor</strong></li>
                <li>Set a <strong>Featured Image</strong> — this becomes the hero photo</li>
                <li>Fill in the <strong>Route Details</strong> meta box: difficulty, distance, elevation, duration</li>
                <li>Add <strong>GPS coordinates</strong> (lat/lng) for the map pin</li>
                <li>Assign <strong>Activity</strong> and <strong>Region</strong> taxonomy terms</li>
                <li>Click <strong>Publish</strong></li>
            </ol>

            <h3 id="start-display">Displaying content on a page</h3>
            <p>Add a shortcode to any page or post:</p>
            <pre><code>[tk_routes columns="3" limit="9"]</code></pre>
            <p>Or use the map shortcode:</p>
            <pre><code>[tk_map type="routes" height="450px"]</code></pre>
            <p>Full shortcode reference → <button class="button-link" onclick="tkDocsTab('shortcodes')">Shortcodes tab</button></p>

            <h3 id="start-demo">Demo data</h3>
            <p>TrailKit includes sample routes, POIs, and guides to help you explore the plugin. To install it:</p>
            <ol>
                <li>Go to <strong>Routes → Settings</strong></li>
                <li>Scroll to <strong>Demo Data</strong></li>
                <li>Click <strong>Install Demo Data</strong></li>
            </ol>
            <p>Demo data creates 3 routes, 3 POIs, 1 guide, and 3 demo pages. Remove it at any time with the <strong>Remove Demo Data</strong> button.</p>

            <div class="tk-docs-callout tk-docs-callout-tip">
                <span>💡</span>
                <p><strong>Tip:</strong> Remove demo data before going live. All demo posts and pages are tagged with <code>_tk_is_demo: 1</code> and are removed cleanly without touching your real content.</p>
            </div>

            <h3>Quick Links</h3>
            <div class="tk-docs-grid">
                <div class="tk-docs-card">
                    <h4>📝 Add Routes</h4>
                    <p><a href="<?php echo esc_url( admin_url('post-new.php?post_type=tk_route') ) ?>">Create a new route</a> — add title, description, hero image, difficulty, GPS, and gallery.</p>
                </div>
                <div class="tk-docs-card">
                    <h4>📍 Add POIs</h4>
                    <p><a href="<?php echo esc_url( admin_url('post-new.php?post_type=tk_poi') ) ?>">Create a new point of interest</a> — waterfall, viewpoint, beach, or any natural landmark.</p>
                </div>
                <div class="tk-docs-card">
                    <h4>👤 Add Guides</h4>
                    <p><a href="<?php echo esc_url( admin_url('post-new.php?post_type=tk_guide') ) ?>">Create a guide profile</a> — photo, bio, contact info, specialties, and service area.</p>
                </div>
                <div class="tk-docs-card">
                    <h4>⚙️ Settings</h4>
                    <p><a href="<?php echo esc_url( admin_url('edit.php?post_type=tk_route&page=trailkit-settings') ) ?>">Configure slugs, map center, demo data, and Pro license.</a></p>
                </div>
            </div>
        </div>
    <?php }

    private static function tab_routes( $active ) { ?>
        <div id="tk-docs-routes" class="tk-docs-panel <?php echo $active === 'routes' ? 'active' : '' ?>">
            <h2>🗺 Routes</h2>
            <p>Routes represent hiking trails, trekking expeditions, kayak circuits, or any multi-point outdoor activity with measurable stats.</p>

            <h3>Taxonomies</h3>
            <table class="tk-docs-field-table">
                <tr><th>Taxonomy</th><th>Type</th><th>Description</th></tr>
                <tr><td>tk_activity</td><td>Flat</td><td>Activity type: hiking, mountaineering, kayaking, cycling, etc.</td></tr>
                <tr><td>tk_region</td><td>Hierarchical</td><td>Geographic region — create your own regions in Routes → Regions</td></tr>
            </table>

            <h3>Meta Fields — Route Details box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Type</th><th>Description</th></tr>
                <tr><td>Difficulty</td><td><code>_tk_difficulty</code></td><td>select</td><td>easy | moderate | hard | extreme</td></tr>
                <tr><td>Distance (km)</td><td><code>_tk_distance</code></td><td>number</td><td>Total distance in kilometres</td></tr>
                <tr><td>Elevation gain (m)</td><td><code>_tk_elevation</code></td><td>number</td><td>Total elevation gain in metres</td></tr>
                <tr><td>Estimated time</td><td><code>_tk_time</code></td><td>text</td><td>Free text e.g. "3-4 hours", "5-6 days"</td></tr>
                <tr><td>Start latitude</td><td><code>_tk_lat</code></td><td>text</td><td>GPS latitude of the starting point</td></tr>
                <tr><td>Start longitude</td><td><code>_tk_lng</code></td><td>text</td><td>GPS longitude of the starting point</td></tr>
                <tr><td>Conditions alert</td><td><code>_tk_conditions_alert</code></td><td>text</td><td>Warning displayed in an orange box on the route page</td></tr>
                <tr><td>Hero image position</td><td><code>_tk_hero_position</code></td><td>text</td><td>CSS background-position e.g. "center 30%"</td></tr>
                <tr><td>Google Maps link</td><td><code>_tk_gmaps_url</code></td><td>url</td><td>Direct link to Google Maps</td></tr>
            </table>

            <h3>Meta Fields — GPS & Map box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Type</th><th>Description</th></tr>
                <tr><td>GPS Points</td><td><code>_tk_points</code></td><td>JSON</td><td>Array of GPS waypoints for the route polyline</td></tr>
            </table>
            <p>Format for GPS points:</p>
            <pre><code>[
  {"lat": 10.4806, "lng": -66.9036, "ele": 900},
  {"lat": 10.4900, "lng": -66.9100, "ele": 950},
  ...
]</code></pre>
            <div class="tk-docs-callout tk-docs-callout-info">
                <span>ℹ️</span>
                <p>GPX file import is available in <strong>TrailKit Pro</strong>. In the Lite version, paste GPS coordinates manually or use an online GPX-to-JSON converter.</p>
            </div>

            <h3>Meta Fields — Gallery & Links box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Type</th><th>Notes</th></tr>
                <tr><td>Gallery</td><td><code>_tk_gallery</code></td><td>JSON array of IDs</td><td>Max <?php echo intval( TK_GALLERY_LIMIT ) ?> images in Lite <?php echo tk_is_pro() ? '' : '— unlimited in Pro' ?></td></tr>
            </table>

            <h3>Difficulty colours</h3>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin:8px 0 16px">
                <?php foreach(['easy'=>'#22c55e','moderate'=>'#f59e0b','hard'=>'#ef4444','extreme'=>'#7c3aed'] as $d=>$c): ?>
                <span style="background:<?php echo esc_attr($c) ?>;color:#fff;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase"><?php echo esc_html($d) ?></span>
                <?php endforeach; ?>
            </div>

            <h3>Lite limits</h3>
            <table class="tk-docs-field-table">
                <tr><th>Resource</th><th>Lite</th><th>Pro</th></tr>
                <tr><td>Published routes</td><td><?php echo intval( TK_LIMIT ) ?></td><td>Unlimited</td></tr>
                <tr><td>Gallery images per route</td><td><?php echo intval( TK_GALLERY_LIMIT ) ?></td><td>Unlimited</td></tr>
            </table>
        </div>
    <?php }

    private static function tab_pois( $active ) { ?>
        <div id="tk-docs-pois" class="tk-docs-panel <?php echo $active === 'pois' ? 'active' : '' ?>">
            <h2>📍 Points of Interest</h2>
            <p>POIs represent specific natural or cultural landmarks: waterfalls, viewpoints, beaches, caves, lagoons, peaks.</p>

            <h3>Taxonomies</h3>
            <table class="tk-docs-field-table">
                <tr><th>Taxonomy</th><th>Type</th><th>Description</th></tr>
                <tr><td>tk_poi_type</td><td>Hierarchical</td><td>POI category: waterfall, viewpoint, beach, cave, lagoon, peak, river, ruins…</td></tr>
                <tr><td>tk_activity</td><td>Flat</td><td>Shared with Routes — activities available at this POI</td></tr>
                <tr><td>tk_region</td><td>Hierarchical</td><td>Shared with Routes — geographic region</td></tr>
            </table>

            <h3>Meta Fields — Location & Details box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Type</th><th>Description</th></tr>
                <tr><td>Latitude</td><td><code>_tk_lat</code></td><td>text</td><td>GPS latitude of the POI</td></tr>
                <tr><td>Longitude</td><td><code>_tk_lng</code></td><td>text</td><td>GPS longitude of the POI</td></tr>
                <tr><td>Conditions alert</td><td><code>_tk_conditions_alert</code></td><td>text</td><td>Optional warning shown on the POI page</td></tr>
                <tr><td>Hero image position</td><td><code>_tk_hero_position</code></td><td>text</td><td>CSS background-position for the hero image</td></tr>
                <tr><td>Google Maps link</td><td><code>_tk_gmaps_url</code></td><td>url</td><td>Direct link to Google Maps location</td></tr>
            </table>

            <h3>Meta Fields — Gallery & Links box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Notes</th></tr>
                <tr><td>Gallery</td><td><code>_tk_gallery</code></td><td>JSON array of attachment IDs. Max <?php echo intval( TK_GALLERY_LIMIT ) ?> in Lite.</td></tr>
            </table>

            <h3>Default POI types</h3>
            <p>The following types are created automatically on plugin activation:</p>
            <code>viewpoint · waterfall · beach · village · cave · lagoon · peak · river · ruins</code>
            <p style="margin-top:8px">Add custom types in <a href="<?php echo esc_url( admin_url('edit-tags.php?taxonomy=tk_poi_type&post_type=tk_poi') ) ?>">POIs → POI Types</a>.</p>
        </div>
    <?php }

    private static function tab_guides( $active ) { ?>
        <div id="tk-docs-guides" class="tk-docs-panel <?php echo $active === 'guides' ? 'active' : '' ?>">
            <h2>👤 Guides</h2>
            <p>Guide profiles showcase local certified guides with their contact info, specialties, pricing, and service area map.</p>

            <h3>Meta Fields — Contact & Details box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Type</th><th>Description</th></tr>
                <tr><td>WhatsApp</td><td><code>_tk_whatsapp</code></td><td>text</td><td>Phone number with country code e.g. +58 414 555 0001</td></tr>
                <tr><td>Email</td><td><code>_tk_email</code></td><td>email</td><td>Contact email address</td></tr>
                <tr><td>Instagram handle</td><td><code>_tk_instagram</code></td><td>text</td><td>Instagram username without the @ symbol</td></tr>
                <tr><td>Price from (USD/day)</td><td><code>_tk_price_from</code></td><td>number</td><td>Minimum daily rate in USD</td></tr>
                <tr><td>Featured guide</td><td><code>_tk_is_featured</code></td><td>checkbox</td><td>Marks this guide as featured — shown first in directory and with a ★ badge</td></tr>
            </table>

            <h3>Meta Fields — Specialties box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Description</th></tr>
                <tr><td>Specialties</td><td><code>_tk_specialties</code></td><td>Stored as JSON array. Available: hiking, climbing, mountaineering, cycling, mountain-biking, kayaking, diving, rappelling, camping, photography, gastronomy, culture, 4x4, motorcycling</td></tr>
            </table>

            <h3>Meta Fields — Service Area box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Description</th></tr>
                <tr><td>Latitude</td><td><code>_tk_lat</code></td><td>Center of the guide's service area</td></tr>
                <tr><td>Longitude</td><td><code>_tk_lng</code></td><td>Center of the guide's service area</td></tr>
                <tr><td>Radius (km)</td><td><code>_tk_radius_km</code></td><td>Service radius — shown as a circle on the guides map</td></tr>
            </table>

            <h3>Meta Fields — Profile Photo box</h3>
            <table class="tk-docs-field-table">
                <tr><th>Field</th><th>Meta key</th><th>Description</th></tr>
                <tr><td>Profile photo</td><td><code>_tk_photo_id</code></td><td>WordPress attachment ID. Shown as avatar on cards and single page.</td></tr>
            </table>

            <h3>Filtering guides in shortcodes</h3>
            <p>Filter by specialty using the <code>specialty</code> attribute. Use the slug values from the Specialties list:</p>
            <pre><code>[tk_guides specialty="mountaineering" columns="3"]
[tk_guides featured="true" limit="4"]
[tk_guides region="andes" columns="2"]</code></pre>
        </div>
    <?php }

    private static function tab_shortcodes( $active ) { ?>
        <div id="tk-docs-shortcodes" class="tk-docs-panel <?php echo $active === 'shortcodes' ? 'active' : '' ?>">
            <h2>[ ] Shortcodes</h2>
            <p>Place these shortcodes in any page, post, or widget to display your TrailKit content. All shortcodes return cached HTML via output buffering.</p>

            <div class="tk-docs-callout tk-docs-callout-tip">
                <span>💡</span>
                <p><strong>Assets are loaded automatically</strong> only on pages that use a TrailKit shortcode — no performance impact on the rest of your site.</p>
            </div>

            <?php
            $shortcodes = [
                [
                    'tag'  => '[tk_routes]',
                    'desc' => 'Displays a responsive grid of route cards.',
                    'attrs' => [
                        ['activity',   '"hiking"', 'Filter by activity slug (comma-separated for multiple)'],
                        ['difficulty', '"hard"',   'Filter by difficulty: easy | moderate | hard | extreme'],
                        ['region',     '"andes"',  'Filter by region slug (comma-separated for multiple)'],
                        ['limit',      '9',         'Maximum number of routes to show'],
                        ['columns',    '3',         'Grid columns: 1, 2, 3, or 4'],
                        ['orderby',    '"date"',    'WordPress orderby parameter: date | title | menu_order'],
                        ['order',      '"DESC"',    'Sort direction: DESC | ASC'],
                    ],
                    'example' => '[tk_routes activity="hiking" difficulty="moderate" region="andes" limit="6" columns="3"]',
                ],
                [
                    'tag'  => '[tk_pois]',
                    'desc' => 'Displays a responsive grid of POI cards.',
                    'attrs' => [
                        ['type',    '"waterfall"', 'Filter by POI type slug'],
                        ['region',  '"andes"',      'Filter by region slug'],
                        ['limit',   '9',             'Maximum number of POIs to show'],
                        ['columns', '3',             'Grid columns: 1, 2, 3, or 4'],
                        ['orderby', '"date"',        'WordPress orderby parameter'],
                        ['order',   '"DESC"',        'Sort direction: DESC | ASC'],
                    ],
                    'example' => '[tk_pois type="waterfall,viewpoint" region="gran-sabana" limit="6" columns="3"]',
                ],
                [
                    'tag'  => '[tk_guides]',
                    'desc' => 'Displays guide profile cards.',
                    'attrs' => [
                        ['featured',   '"true"',          'Show only featured guides (checkbox must be checked)'],
                        ['specialty',  '"mountaineering"', 'Filter by specialty slug'],
                        ['region',     '"andes"',          'Filter by region slug'],
                        ['limit',      '12',               'Maximum number of guides to show'],
                        ['columns',    '3',                'Grid columns: 1, 2, or 3'],
                    ],
                    'example' => '[tk_guides featured="true" specialty="hiking" columns="2"]',
                ],
                [
                    'tag'  => '[tk_map]',
                    'desc' => 'Renders an interactive Leaflet map with markers loaded via AJAX.',
                    'attrs' => [
                        ['type',   '"routes"', 'What to show on the map: routes | pois | all'],
                        ['region', '"andes"',   'Filter markers by region'],
                        ['height', '"450px"',   'Map height (any CSS value)'],
                        ['zoom',   '7',          'Initial zoom level (1–18)'],
                        ['lat',    '"8.0"',      'Map center latitude (defaults to Settings value)'],
                        ['lng',    '"-66.0"',    'Map center longitude (defaults to Settings value)'],
                    ],
                    'example' => '[tk_map type="all" height="500px" zoom="6" lat="8.0" lng="-66.0"]',
                ],
            ];
            foreach ( $shortcodes as $sc ): ?>
            <div class="tk-docs-shortcode">
                <code class="sc"><?php echo esc_html( $sc['tag'] ) ?></code>
                <p style="margin:0 0 10px;font-size:13px;color:#444"><?php echo esc_html( $sc['desc'] ) ?></p>
                <table>
                    <tr><th>Attribute</th><th>Default</th><th>Description</th></tr>
                    <?php foreach ( $sc['attrs'] as $a ): ?>
                    <tr>
                        <td><?php echo esc_html( $a[0] ) ?></td>
                        <td><code><?php echo esc_html( $a[1] ) ?></code></td>
                        <td><?php echo esc_html( $a[2] ) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p style="margin:12px 0 0;font-size:12px;color:#666">Example:</p>
                <pre style="margin:4px 0 0"><code><?php echo esc_html( $sc['example'] ) ?></code></pre>
            </div>
            <?php endforeach; ?>
        </div>
    <?php }

    private static function tab_templates( $active ) { ?>
        <div id="tk-docs-templates" class="tk-docs-panel <?php echo $active === 'templates' ? 'active' : '' ?>">
            <h2>🎨 Template Overrides</h2>
            <p>TrailKit uses a WooCommerce-style template override system. Copy any template to your theme and edit it freely — plugin updates will never overwrite your customisations.</p>

            <h3>How it works</h3>
            <p>Create a <code>trailkit/</code> folder inside your active theme directory, then copy the template file there:</p>
            <pre><code>wp-content/
  themes/
    your-theme/
      trailkit/              ← create this folder
        single-route.php  ← your override
        route-card.php    ← another override
        ...</code></pre>

            <h3>Available templates</h3>
            <table class="tk-docs-field-table">
                <tr><th>Template file</th><th>Used for</th><th>Path in plugin</th></tr>
                <tr><td>single-route.php</td><td>Individual route page (hero, stats, map, gallery)</td><td><code>templates/single-route.php</code></td></tr>
                <tr><td>single-poi.php</td><td>Individual POI page</td><td><code>templates/single-poi.php</code></td></tr>
                <tr><td>single-guide.php</td><td>Individual guide profile page</td><td><code>templates/single-guide.php</code></td></tr>
                <tr><td>route-card.php</td><td>Route card shown in [tk_routes] grid</td><td><code>templates/route-card.php</code></td></tr>
                <tr><td>poi-card.php</td><td>POI card shown in [tk_pois] grid</td><td><code>templates/poi-card.php</code></td></tr>
                <tr><td>guide-card.php</td><td>Guide card shown in [tk_guides] grid</td><td><code>templates/guide-card.php</code></td></tr>
            </table>

            <div class="tk-docs-callout tk-docs-callout-tip">
                <span>💡</span>
                <p>The plugin templates live in <code><?php echo esc_html( TK_DIR . 'templates/' ) ?></code>. Open one in a text editor to understand the available variables before creating your override.</p>
            </div>

            <h3>Available PHP variables in templates</h3>
            <h4>single-route.php / route-card.php</h4>
            <pre><code>$post_id   // Current route post ID
$data      // Array from TK_Route_Fields::get($post_id):
           //   difficulty, distance, elevation, time,
           //   lat, lng, points, conditions_alert,
           //   gmaps_url, gallery, hero_position
$thumb     // URL of the featured image (full size)</code></pre>

            <h4>single-guide.php / guide-card.php</h4>
            <pre><code>$post_id   // Current guide post ID
$guide     // Array from TK_Guide_Fields::get($post_id):
           //   whatsapp, email, instagram, price_from,
           //   specialties (JSON array), photo_id,
           //   is_featured, lat, lng, radius_km</code></pre>

            <h3>Using TrailKit helper functions in your override</h3>
            <pre><code>&lt;?php
// Get route data
$data = TK_Route_Fields::get( $post_id );

// Get guide data
$guide = TK_Guide_Fields::get( $post_id );

// Get POI data
$poi = TK_POI_Fields::get( $post_id );

// Check if Pro license is active
if ( tk_is_pro() ) {
    // Show Pro features
}
?&gt;</code></pre>
        </div>
    <?php }

    private static function tab_css( $active ) { ?>
        <div id="tk-docs-css" class="tk-docs-panel <?php echo $active === 'css' ? 'active' : '' ?>">
            <h2>✏️ CSS & Styling</h2>
            <p>All TrailKit styles are scoped to <code>.tk-*</code> class names — zero conflicts with your theme. Customise via CSS variables.</p>

            <h3>CSS Variables — Brand tokens</h3>
            <p>These are global (on <code>:root</code>) and control the TrailKit brand colours:</p>
            <table class="tk-docs-field-table">
                <tr><th>Variable</th><th>Default</th><th>Use</th></tr>
                <tr><td>--tk-primary</td><td>#0df246</td><td>Accent colour (green)</td></tr>
                <tr><td>--tk-primary-dim</td><td>rgba(13,242,70,.12)</td><td>Transparent accent for backgrounds</td></tr>
                <tr><td>--tk-orange</td><td>#ff6b00</td><td>Featured guide badge</td></tr>
                <tr><td>--tk-easy</td><td>#22c55e</td><td>Easy difficulty badge</td></tr>
                <tr><td>--tk-moderate</td><td>#f59e0b</td><td>Moderate difficulty badge</td></tr>
                <tr><td>--tk-hard</td><td>#ef4444</td><td>Hard difficulty badge</td></tr>
                <tr><td>--tk-extreme</td><td>#7c3aed</td><td>Extreme difficulty badge</td></tr>
                <tr><td>--tk-radius</td><td>0.5rem</td><td>Default border radius</td></tr>
                <tr><td>--tk-radius-lg</td><td>0.75rem</td><td>Large border radius (cards)</td></tr>
            </table>

            <h3>CSS Variables — Surface tokens</h3>
            <p>These are scoped to <code>.tk-card, .tk-single, .tk-grid</code> etc. They inherit from your theme's <code>theme.json</code> palette automatically (WordPress 5.8+):</p>
            <table class="tk-docs-field-table">
                <tr><th>Variable</th><th>Inherits from theme.json</th><th>Default (light)</th></tr>
                <tr><td>--tk-bg</td><td>--wp--preset--color--base</td><td>#ffffff</td></tr>
                <tr><td>--tk-bg-card</td><td>--wp--preset--color--base-2</td><td>#f8fafc</td></tr>
                <tr><td>--tk-border</td><td>--wp--preset--color--base-3</td><td>#e2e8f0</td></tr>
                <tr><td>--tk-text</td><td>--wp--preset--color--contrast</td><td>#0f172a</td></tr>
                <tr><td>--tk-text-muted</td><td>--wp--preset--color--contrast-2</td><td>#64748b</td></tr>
                <tr><td>--tk-text-faint</td><td>--wp--preset--color--contrast-3</td><td>#94a3b8</td></tr>
                <tr><td>--tk-font</td><td>--wp--preset--font-family--body</td><td>inherit</td></tr>
            </table>

            <div class="tk-docs-callout tk-docs-callout-info">
                <span>ℹ️</span>
                <p><strong>Dark mode</strong> is automatic via <code>@media (prefers-color-scheme: dark)</code>. When the visitor's device is in dark mode, the surface tokens switch to dark values automatically.</p>
            </div>

            <h3>Overriding styles in your theme</h3>
            <p>Add a rule in your theme's stylesheet (<code>style.css</code> or the Additional CSS editor in Customizer):</p>
            <pre><code>/* Change the accent colour to match your brand */
:root {
  --tk-primary: #ff6b00;
  --tk-primary-dim: rgba(255, 107, 0, 0.12);
}

/* Override card backgrounds */
.tk-card, .tk-single, .tk-grid {
  --tk-bg-card: #fafafa;
  --tk-border:  #d1d5db;
}</code></pre>

            <h3>Key CSS classes reference</h3>
            <table class="tk-docs-field-table">
                <tr><th>Class</th><th>Element</th></tr>
                <tr><td>.tk-card</td><td>Route / POI / Guide card</td></tr>
                <tr><td>.tk-grid</td><td>Card grid wrapper</td></tr>
                <tr><td>.tk-grid--2col / --3col / --4col</td><td>Column count modifiers</td></tr>
                <tr><td>.tk-single</td><td>Single route/POI page wrapper</td></tr>
                <tr><td>.tk-single__hero</td><td>Hero image section</td></tr>
                <tr><td>.tk-single__stats-wrap</td><td>Stats bar (distance, elevation, etc.)</td></tr>
                <tr><td>.tk-single__body</td><td>Content body area</td></tr>
                <tr><td>.tk-gallery</td><td>Photo gallery grid</td></tr>
                <tr><td>.tk-gallery__item</td><td>Individual gallery photo link</td></tr>
                <tr><td>.tk-badge</td><td>Difficulty badge (absolute over image)</td></tr>
                <tr><td>.tk-tag</td><td>Activity / taxonomy pill</td></tr>
                <tr><td>.tk-alert</td><td>Conditions alert box</td></tr>
                <tr><td>.tk-map-wrap</td><td>Map container</td></tr>
                <tr><td>.tk-contact-card</td><td>Guide contact sidebar card</td></tr>
            </table>

            <h3>Disabling TrailKit styles</h3>
            <p>To fully replace TrailKit CSS with your own:</p>
            <pre><code>// In your theme's functions.php
add_action('wp_enqueue_scripts', function() {
    wp_dequeue_style('trailkit');
}, 20);

// Then add your own CSS file that targets .tk-* classes</code></pre>
        </div>
    <?php }

    private static function tab_license( $active, $is_pro ) { ?>
        <div id="tk-docs-license" class="tk-docs-panel <?php echo $active === 'license' ? 'active' : '' ?>">
            <h2>🔑 License & Pro Features</h2>

            <div class="tk-docs-callout <?php echo $is_pro ? 'tk-docs-callout-tip' : 'tk-docs-callout-info' ?>">
                <?php if ( $is_pro ): ?>
                <span>✅</span>
                <p><strong>TrailKit Pro is active.</strong> You have access to all Pro features.</p>
                <?php else: ?>
                <span>ℹ️</span>
                <p>You are using <strong>TrailKit Lite</strong>. <a href="<?php echo esc_url( admin_url('edit.php?post_type=tk_route&page=trailkit-settings') ) ?>">Activate a Pro license</a> or <a href="https://trailplugin.com" target="_blank">get one at trailplugin.com</a> to unlock all features.</p>
                <?php endif; ?>
            </div>

            <h3>Current limits (Lite)</h3>
            <table class="tk-docs-field-table">
                <tr><th>Content type</th><th>Lite limit</th><th>Pro</th></tr>
                <tr><td>Routes</td><td><?php echo intval( TK_LIMIT ) ?> published</td><td>Unlimited</td></tr>
                <tr><td>Points of Interest</td><td><?php echo intval( TK_LIMIT ) ?> published</td><td>Unlimited</td></tr>
                <tr><td>Guides</td><td>1 published</td><td>Unlimited</td></tr>
                <tr><td>Gallery images per post</td><td><?php echo intval( TK_GALLERY_LIMIT ) ?></td><td>Unlimited</td></tr>
            </table>

            <h3>Pro features</h3>
            <table class="tk-docs-field-table">
                <tr><th>Feature</th><th>Lite</th><th>Pro</th></tr>
                <tr><td>Unlimited content</td><td>✗</td><td>✓</td></tr>
                <tr><td>Gallery lightbox slider</td><td>✗</td><td>✓</td></tr>
                <tr><td>GPS polyline on route map</td><td>✓ (data stored)</td><td>✓ (rendered)</td></tr>
                <tr><td>Elevation profile chart</td><td>✗</td><td>✓ <span class="tk-docs-badge tk-docs-badge-new">Soon</span></td></tr>
                <tr><td>GPX import</td><td>✗</td><td>✓ <span class="tk-docs-badge tk-docs-badge-new">Soon</span></td></tr>
                <tr><td>Unlimited gallery images</td><td>✗</td><td>✓</td></tr>
            </table>

            <h3>Activating your license</h3>
            <ol>
                <li>Go to <a href="<?php echo esc_url( admin_url('edit.php?post_type=tk_route&page=trailkit-settings') ) ?>"><strong>Routes → Settings → Pro License</strong></a></li>
                <li>Paste your license key (format: <code>TK-XXXX-XXXX-XXXX-XXXX</code>)</li>
                <li>Click <strong>Activate License</strong></li>
                <li>The page reloads showing a green <strong>Active</strong> badge</li>
            </ol>

            <div class="tk-docs-callout tk-docs-callout-warn">
                <span>⚠️</span>
                <p><strong>License requires internet:</strong> Activation contacts the TrailKit license server at <code>trailplugin.com</code>. If the connection fails, check your hosting firewall settings for outgoing HTTP requests.</p>
            </div>

            <h3>License plans</h3>
            <table class="tk-docs-field-table">
                <tr><th>Plan</th><th>Sites</th><th>Price</th></tr>
                <tr><td>Single Site</td><td>1 domain</td><td>$79 / year</td></tr>
                <tr><td>Agency</td><td>Up to 2 domains</td><td>$149.99 / year</td></tr>
                <tr><td>Unlimited</td><td>Unlimited domains</td><td>$199.99 / year</td></tr>
                <tr><td>Lifetime</td><td>1 domain</td><td>$199 one-time</td></tr>
            </table>

            <h3>14-Day Free Trial</h3>
            <p>Try all Pro features free for 14 days — no credit card required.</p>
            <ol>
                <li>Visit <a href="<?php echo esc_url( 'https://trailplugin.com/#trial' ) ?>" target="_blank" rel="noopener">trailplugin.com/#trial</a> and enter your email</li>
                <li>You will receive a trial license key by email</li>
                <li>Go to <a href="<?php echo esc_url( admin_url('edit.php?post_type=tk_route&page=trailkit-settings') ) ?>">Routes → Settings → Pro License</a></li>
                <li>Paste your trial key and click <strong>Activate License</strong></li>
            </ol>
            <p>One trial per domain. After 14 days the site reverts to Lite unless you purchase a Pro plan.</p>

            <h3>Removing a license (domain transfer)</h3>
            <p>To move your license to a different domain:</p>
            <ol>
                <li>On the <strong>current</strong> site: Routes → Settings → Pro License → <strong>Remove License</strong></li>
                <li>Contact <a href="mailto:gabriel@mag.cr">gabriel@mag.cr</a> to release the domain from your license</li>
                <li>On the <strong>new</strong> site: activate the same key</li>
            </ol>
        </div>
    <?php }

    private static function tab_faq( $active ) { ?>
        <div id="tk-docs-faq" class="tk-docs-panel <?php echo $active === 'faq' ? 'active' : '' ?>">
            <h2>❓ FAQ & Troubleshooting</h2>

            <?php
            $faqs = [
                [
                    'q' => 'Routes / POIs / Guides return a 404 page',
                    'a' => 'Go to <strong>Settings → Permalinks</strong> and click Save. This re-registers the custom post type rewrite rules. Always do this after activating or deactivating TrailKit.',
                    'type' => 'warn',
                ],
                [
                    'q' => 'The map is blank or not loading',
                    'a' => 'The map tiles are loaded from OpenStreetMap via the internet. Check: (1) The page includes a <code>[tk_map]</code> or <code>[tk_routes]</code> shortcode, (2) there are no JavaScript errors in the browser console, (3) your hosting allows outgoing connections to <code>unpkg.com</code> and <code>tile.openstreetmap.org</code>.',
                    'type' => 'warn',
                ],
                [
                    'q' => 'License activation says "Invalid license key" or "License key not found"',
                    'a' => 'This usually means the license server can\'t be reached, or the key doesn\'t exist. Check: (1) Go to <code>' . esc_html( TK_API_URL . '/api/health' ) . '</code> — if it shows <code>"status":"degraded"</code>, the server has a configuration issue. (2) Verify the key matches exactly what was provided (format: TK-XXXX-XXXX-XXXX-XXXX). (3) Check if your server can make outbound HTTP requests.',
                    'type' => 'warn',
                ],
                [
                    'q' => 'License activation says "already active on another site"',
                    'a' => 'Each Single Site license can only be used on one domain. To transfer: (1) Remove the license on the current site via Routes → Settings. (2) Email gabriel@mag.cr to release the domain. (3) Activate on the new site. If you need multiple sites, upgrade to Agency (2 sites) or Unlimited.',
                    'type' => 'info',
                ],
                [
                    'q' => 'I published more than 3 routes but they show as Draft',
                    'a' => 'TrailKit Lite limits published content to ' . TK_LIMIT . ' routes, ' . TK_LIMIT . ' POIs, and 1 guide. Items over the limit are automatically saved as Draft. Upgrade to Pro to publish unlimited content.',
                    'type' => 'info',
                ],
                [
                    'q' => 'My theme styles are conflicting with TrailKit cards',
                    'a' => 'TrailKit uses scoped <code>.tk-*</code> class names specifically to avoid conflicts. If you see theme styles bleeding in, inspect the element in browser DevTools to find which theme CSS rule is overriding TrailKit. Add a more specific rule in your theme\'s Additional CSS targeting <code>.tk-card { ... }</code>.',
                    'type' => 'info',
                ],
                [
                    'q' => 'How do I change the colours to match my brand?',
                    'a' => 'Add this to your theme\'s Additional CSS (Appearance → Customize → Additional CSS):<br><br><code>:root { --tk-primary: #your-color; --tk-primary-dim: rgba(r,g,b,.12); }</code><br><br>See the CSS tab for the complete variable reference.',
                    'type' => 'tip',
                ],
                [
                    'q' => 'The gallery button ("Select Images") does not open anything',
                    'a' => 'The media uploader requires the WordPress media library. Check: (1) You are on a Route, POI, or Guide edit screen (not a regular post). (2) Clear your browser cache. (3) Deactivate other plugins temporarily to check for JavaScript conflicts with the media library.',
                    'type' => 'warn',
                ],
                [
                    'q' => 'Can I translate TrailKit into my language?',
                    'a' => 'Yes. TrailKit uses the text domain <code>trailkit</code> and includes a <code>languages/trailkit.pot</code> file. Use <a href="https://poedit.net/" target="_blank">Poedit</a> to create a <code>.po</code> and <code>.mo</code> file for your language, then upload to <code>wp-content/languages/plugins/</code>.',
                    'type' => 'tip',
                ],
                [
                    'q' => 'How do I uninstall TrailKit cleanly?',
                    'a' => 'Go to Plugins → Installed Plugins, deactivate TrailKit, then click Delete. The <code>uninstall.php</code> will automatically remove all plugin options, transients, and cron jobs. Your route, POI, and guide posts are preserved (to delete them too, use a plugin like WP Optimize before uninstalling).',
                    'type' => 'info',
                ],
            ];
            foreach ( $faqs as $faq ):
                $type_class = 'tk-docs-callout-' . ( $faq['type'] ?? 'info' );
                $icon = $faq['type'] === 'warn' ? '⚠️' : ( $faq['type'] === 'tip' ? '💡' : 'ℹ️' );
            ?>
            <div style="border:1px solid #e0e0e0;border-radius:8px;margin-bottom:14px;overflow:hidden">
                <div style="padding:12px 16px;background:#fafafa;border-bottom:1px solid #e0e0e0;font-weight:700;font-size:13.5px;color:#1a1a1a">
                    <?php echo esc_html( $faq['q'] ) ?>
                </div>
                <div class="tk-docs-callout <?php echo esc_attr( $type_class ) ?>" style="border-radius:0;margin:0;border-left-width:4px">
                    <span><?php echo esc_html( $icon ) ?></span>
                    <p><?php echo wp_kses_post( $faq['a'] ) ?></p>
                </div>
            </div>
            <?php endforeach; ?>

            <h3 style="margin-top:28px">Still need help?</h3>
            <div class="tk-docs-grid">
                <div class="tk-docs-card">
                    <h4>📧 Email Support</h4>
                    <p>For Pro license holders: <a href="mailto:gabriel@mag.cr">gabriel@mag.cr</a>. Include your site URL and a description of the issue.</p>
                </div>
                <div class="tk-docs-card">
                    <h4>🌐 Documentation</h4>
                    <p>Full online documentation, changelog, and video tutorials at <a href="https://trailplugin.com" target="_blank">trailplugin.com</a>.</p>
                </div>
            </div>
        </div>
    <?php }
}

TK_Documentation::init();
