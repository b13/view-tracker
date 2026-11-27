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
 *
 * Campaign parameters (utm_source, utm_medium, utm_campaign) of the current
 * page are passed along as well; the rest of the query string is not.
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
  var src = script.dataset.src + '&ref=' + encodeURIComponent(host);
  try {
    var params = new URLSearchParams(window.location.search);
    ['utm_source', 'utm_medium', 'utm_campaign'].forEach(function (name) {
      var value = params.get(name);
      if (value) {
        src += '&' + name + '=' + encodeURIComponent(value.slice(0, 100));
      }
    });
  } catch (e) {
    // No campaign parameters then; the view is still counted.
  }
  var pixel = new Image(1, 1);
  pixel.src = src;
})();
