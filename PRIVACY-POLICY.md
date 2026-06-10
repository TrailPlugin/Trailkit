# TrailKit — Privacy Policy

**Last updated: June 2026**

This privacy policy describes how the TrailKit WordPress plugin ("TrailKit", "the plugin") handles data when you activate a Pro license.

---

## What data is collected

When you activate a Pro license key in TrailKit, the plugin sends the following information to the TrailKit license server at `trailplugin.com`:

- Your license key
- Your site's domain name (e.g. `example.com`)
- Your WordPress version
- The TrailKit plugin version

This same data is sent once per week as a background license check-in to keep your Pro status up to date.

## Why it is collected

This data is used solely to:

- Validate that your license key is active and has not expired
- Enforce the site limit of your license plan (Single Site, Agency, Unlimited)
- Send renewal reminders to the email address on your license before your subscription expires

## Who sees the data

License data is stored in a Supabase database managed by Gabriel Arias (gabriel@mag.cr). It is never sold, shared, or used for advertising.

## When collection starts and stops

Data is **only** transmitted after you actively enter a license key in **Routes → Settings → Pro License** and click Activate. Lite users who have not entered a key are never tracked.

To stop all data transmission, go to **Routes → Settings → Pro License** and click **Remove License**. This deletes all stored license data from your site.

## Lite users

If you are using the free Lite version without a license key, no data is ever sent to any external server by TrailKit. Leaflet map tiles are loaded from OpenStreetMap servers (`tile.openstreetmap.org`) when maps are displayed — see [OpenStreetMap's privacy policy](https://wiki.osmfoundation.org/wiki/Privacy_Policy) for details.

## Contact

For questions about this policy, contact: **gabriel@mag.cr**

---

> **Note for deployment:** Publish this page at `https://trailplugin.com/privacy` before submitting the plugin to WordPress.org. The readme.txt links to that URL.
