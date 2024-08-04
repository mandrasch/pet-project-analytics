/**
 * @package koko-analytics
 * @author Danny van Kooten
 * @license GPL-3.0+
 */

/**
 * Approach is also inspired by https://github.com/plausible/analytics/blob/master/tracker/src/plausible.js,
 * GNU AFFERO GENERAL PUBLIC LICENSE, Version 3
 */

// This will be encapsulated as anon function by webpack

"use strict";

var scriptEl = document.currentScript;
if (!scriptEl) {
  console.error("No scriptEl found");
}

// TODO: use WP endpoint ...
var analyticsEndpoint =
  new URL(scriptEl.src).origin + "/wp-json/pp-analytics/v1/event";

// TODO: POST request to WP site (without nonce)
var postPayload = {};
postPayload.u = location.href;
postPayload.d = scriptEl.getAttribute("data-domain"); // TODO: add error?
postPayload.r = document.referrer || null;

// Initialize XMLHttpRequest
var request = new XMLHttpRequest();
request.open("POST", analyticsEndpoint, true);
request.setRequestHeader("Content-Type", "application/json"); // Use application/json for proper parsing

// Handle the response
request.onreadystatechange = function () {
  if (request.readyState === 4) { // Request is complete
    if (request.status >= 200 && request.status < 300) { // Successful response
      console.log("Data sent successfully", request.responseText);
    } else {
      console.error("Failed to send data", request.status, request.statusText);
    }
  }
};

// Send the request with JSON payload
request.send(JSON.stringify(postPayload));
