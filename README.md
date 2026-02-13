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
<img src="/_pixel?page={data.uid}" style="position: fixed; z-index: -1000;" loading="eager" alt="" />
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

### Storage Type

By default, views are written to the `tx_view_tracker_count` database table.
An empty Redis implementation is provided as an example for alternative storage.

## Privacy

The pixel records only the page ID, page type, browser language, and a
timestamp. No cookies, IP addresses, or personal data are stored.

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
