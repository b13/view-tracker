# View Tracker for TYPO3

Track page views reliably using a 1x1 pixel that bypasses caching.

For dashboard widgets, analytics, and data aggregation, see
[View Tracker Pro](https://github.com/b13/view-tracker-pro).

## Installation

```bash
composer require b13/view-tracker
```

## Including the Pixel

Add the pixel to your main template. Make sure to use the localized page ID
if you want to track language versions separately.

### Option 1: `<img>` tag

```html
<img src="/_b13vt?page={data.uid}" style="position: fixed; z-index: -1000;" loading="eager" alt="" />
```

### Option 2: ViewHelper (recommended)

The ViewHelper automatically includes the correct page ID, language, and page type:

```html
<html xmlns:vt="http://typo3.org/ns/B13/ViewTracker/ViewHelpers"
      data-namespace-typo3-fluid="true">

<vt:pixel />
</html>
```

## Page Module Statistics

View statistics are shown automatically in the header area of each page in
the TYPO3 backend. Charts include total views, last 7 / 30 days, last 12
months, and views by page type.

## Configuration

### Excluded Page Doktypes

By default, view statistics are not shown for non-content page types (folders,
shortcuts, external links, etc.). You can extend the exclusion list:

```php
// ext_localconf.php
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']['excludedDoktypes'][] = 116;
```

### Page-module statistics visibility

By default every backend user that can open the Page module sees the
statistics widget. Two site settings narrow that audience. Admins always
see the widget regardless of these settings.

Import the site set in your `config/sites/<id>/config.yaml`:

```yaml
dependencies:
  - b13/view-tracker
```

Then override in `config/sites/<id>/settings.yaml`:

```yaml
view_tracker:
  pageModuleStatistics:
    # Restrict to admin users only — every non-admin loses the widget.
    visibleForAdminsOnly: true
    # OR keep visibleForAdminsOnly: false and limit by BE user group UIDs.
    # Empty (default) means no group restriction.
    visibleForGroups: '12,17'
```

Behaviour matrix (non-admin users):

| `visibleForAdminsOnly` | `visibleForGroups` | Non-admin sees widget? |
|---|---|---|
| `false` (default) | empty (default) | yes — backwards-compatible |
| `false` | `'12,17'` | only if member of group 12 or 17 |
| `true` | (ignored) | never |

### Storage Type

By default, views are written to the `tx_view_tracker_count` database table.
An empty Redis implementation is provided as an example for alternative storage.

### Tracking Endpoint

The middleware listens on `/_b13vt` by default. The path is deliberately
opaque so common tracker blocklists (EasyPrivacy etc.) don't catch it. If a
blocklist eventually picks it up, override the path per site — both the
middleware and the ViewHelper read from the same site setting.

Import the site set in your `config/sites/<id>/config.yaml`:

```yaml
dependencies:
  - b13/view-tracker
```

Then override in `config/sites/<id>/settings.yaml`:

```yaml
view_tracker:
  endpoint: '/_yourpath'
```

After changing the value, flush TYPO3 caches so the ViewHelper re-renders.

## Upgrading from 1.0

The tracking endpoint moved from `/_pixel` to `/_b13vt`. If your templates use
the `<vt:pixel />` ViewHelper, just flush caches — the new URL is emitted
automatically. If you copied the raw `<img src="/_pixel?…">` snippet into a
template, update the path manually (or switch to the ViewHelper).

## Privacy

View Tracker counts page views without cookies and without storing any data
that identifies a visitor. Each counted view is one row in
`tx_view_tracker_count`. Which columns are filled depends on the installed
packages:

| Package | Stored per view | Read from the request, then discarded |
|---|---|---|
| `b13/view-tracker` | page ID, page type, primary browser language (e.g. `de-de`), timestamp | `User-Agent` (to skip obvious bots) |
| `b13/view-tracker-pro` | browser name, operating system name, device type (no versions) | `User-Agent` (parsed, then discarded) |
| `b13/view-tracker-geoip` | country code (e.g. `DE`) | client IP address (looked up in a local database, never stored or sent anywhere) |
| `b13/view-tracker-cdn-country` | country code (e.g. `DE`) | country header set by the CDN (no IP address involved) |

Without the Pro package and a country package, the columns `browser`, `os`,
`device_type` and `country` stay empty.

What View Tracker does **not** do:

- set or read cookies, local storage or any other identifier in the browser
  (it only checks whether a TYPO3 backend session cookie is present, so that
  editors are not counted)
- store IP addresses, the full `User-Agent` string or the referrer
- write request headers or IP addresses to the TYPO3 log; a counted view
  produces at most a `debug` entry with page ID and page type
- link two page views to the same visitor

Note that your web server and CDN may still write their own access logs with
IP addresses and headers. These are outside the scope of this extension.

Suggested wording for a privacy policy (adapt it to the installed packages):

> We count page views on this website with the TYPO3 extension View Tracker.
> For each page view, we store the page, the time, the browser language and,
> where applicable, the browser, the operating system, the device type and the
> country. We do not set cookies, do not store IP addresses and cannot link
> page views to a person or to each other. The data is processed on our own
> server and is not passed on to third parties.

## License

GPL-2.0-or-later — see the [LICENSE](LICENSE) file for details.

## Related Extensions

- **[View Tracker Pro](https://b13.com)** — dashboard widgets, data aggregation, and server-side analytics (browser, OS, device type detection)
- **[View Tracker CDN Country](https://github.com/b13/view-tracker-cdn-country)** — country detection via CDN headers (Cloudflare, Fastly, etc.)
- **[View Tracker GeoIP](https://b13.com)** — country detection via MaxMind GeoIP database

Interested in View Tracker Pro or GeoIP? [Contact us](https://b13.com/about).

## Background, authors & maintenance

This extension was created by Johannes Schlier in 2025 for [b13 GmbH, Stuttgart](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of our work, 
we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
