# Changelog

## 1.1.0

### Breaking

- **Tracking endpoint renamed from `/_pixel` to `/_b13vt`.** The previous name
  was being caught by EasyPrivacy and similar tracker blocklists, silently
  dropping a portion of view counts. The new default is vendor-namespaced and
  opaque.

  - **No action needed** if you use the `<vt:pixel />` / `<viewtracker:pixel />`
    ViewHelper — flush TYPO3 caches and the new URL is emitted automatically.
  - **Action required** if a template hand-codes `<img src="/_pixel?…">`:
    update the path to `/_b13vt` (or switch to the ViewHelper).

### Added

- New site setting `view_tracker.endpoint` (added to the `b13/view-tracker`
  Site Set) to override the endpoint per site:
  ```yaml
  # config/sites/<id>/settings.yaml
  view_tracker:
    endpoint: '/_yourpath'
  ```
  Both the middleware and the ViewHelper read from the same value via
  `$site->getSettings()`.

### Changed

- Removed `TrackViewMiddleware::TARGET` constant in favor of the configurable
  endpoint (read from site settings inside `process()`).
- Middleware now requires a resolved `Site` in the request and falls through
  to the next handler otherwise (no behavior change in normal frontend requests).
- Extension description no longer mentions "pixel" — minor cosmetic alignment
  with the rename.

## 1.0.0

- Initial release.
