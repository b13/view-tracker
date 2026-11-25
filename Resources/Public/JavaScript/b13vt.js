/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

/**
 * Requests the tracking pixel and passes along the host name of the
 * referring page. Only the host name leaves the browser, never the full
 * referrer URL. Without JavaScript the <noscript> pixel is used instead.
 */
(function () {
  var script = document.currentScript;
  if (!script || !script.dataset.src) {
    return;
  }
  var host = '';
  try {
    host = document.referrer ? new URL(document.referrer).hostname : '';
  } catch (e) {
    host = '';
  }
  var pixel = new Image(1, 1);
  pixel.src = script.dataset.src + '&ref=' + encodeURIComponent(host);
})();
