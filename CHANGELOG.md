# Changelog

All notable changes to `b13/view-tracker` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Origin of a visit.** The `<vt:pixel />` ViewHelper now renders a small
  script that requests the pixel and passes along the host name of the
  referring page, which only the browser knows. Path, query string, and
  fragment of the referring URL never leave the browser. The new column
  `referrer` stores the value when View Tracker Pro is installed. Without
  JavaScript, a `<noscript>` pixel counts the view as before. Requires a
  database schema update.
- **Composer metadata for TYPO3 v14.** `composer.json` now carries the
  extension version and package information that TYPO3 v14.2+ reads instead
  of `ext_emconf.php`.

### Changed

- **Page-module statistics visibility has its own class.** The check moved
  from the event listener into `PageModuleStatisticsVisibility`; behavior is
  unchanged. The listener identifier is now
  `b13-view-tracker/page-module-statistics`.
- **Declared dependencies match the code.** `composer.json` now requires
  `typo3/cms-backend`, `typo3/cms-extbase`, `typo3/cms-fluid`, and
  `typo3/cms-frontend` in addition to `typo3/cms-core`, and TYPO3 v13.4 as
  the lowest version, matching `ext_emconf.php`.

### Removed

- **`RedisStorage`.** The class was an unfinished example: it could not be
  configured and returned fixed numbers. To store views elsewhere, implement
  `StorageInterface` as described in the README.

### Fixed

- **Pixel requests on sites with prefixed languages.** The tracking
  middleware now runs after the site resolver. On sites where every language
  has a path prefix (for example `/de/` and `/en/`), the pixel answered with
  a 404 and no view was counted.
- **Overlong `Accept-Language` headers.** Only well-formed language tags that
  fit the column are stored. A longer value made the insert fail on MySQL,
  MariaDB, and PostgreSQL in strict mode, so the view was lost.
- **No deprecation notice on TYPO3 v14.** The ViewHelper uses the system
  resource API for the script URL where it is available.

### Security

- **No request headers in the log.** A counted view no longer writes the
  `Referer` and `User-Agent` headers to the TYPO3 log. It produces at most a
  `debug` entry with page ID and page type.

### Documentation

- **Privacy section** lists what each package stores, what it only reads, and
  suggests wording for a privacy policy.
- **Excluded doktypes** are documented correctly: a configured list replaces
  the defaults instead of extending them.

### Tests

- Unit and functional tests for counting, bot filtering, editor exclusion,
  language parsing, storage queries, the ViewHelper, and the visibility
  settings.

## [1.1.1] — 2026-07-09

### Added

- **Configurable endpoint.** The site setting `view_tracker.endpoint` in the
  `b13/view-tracker` site set overrides the path per site. Middleware and
  ViewHelper read the same value.

### Changed

- **Tracking endpoint renamed from `/_pixel` to `/_b13vt`.** The previous name
  was caught by EasyPrivacy and similar tracker blocklists, which silently
  dropped part of the view counts. If you use the `<vt:pixel />` ViewHelper,
  flush the TYPO3 caches and the new URL is emitted automatically. If a
  template hand-codes `<img src="/_pixel?…">`, update the path to `/_b13vt`
  or switch to the ViewHelper.

## [1.1.0] — 2026-05-26

### Added

- **Restrict the Page-module statistics.** The site settings
  `view_tracker.pageModuleStatistics.visibleForAdminsOnly` and
  `view_tracker.pageModuleStatistics.visibleForGroups` limit which backend
  users see the statistics. Admins always see them.

## [1.0.3] — 2026-05-07

### Fixed

- **Tracking pixel is hidden from assistive technology** with
  `role="presentation"`.

## [1.0.2] — 2026-04-14

### Changed

- New extension icon.

## [1.0.1] — 2026-02-26

### Added

- **`data-extension` attribute** on the statistics in the Page module, so
  that tab extensions can detect them.

## [1.0.0] — 2026-02-24

- Initial release.
