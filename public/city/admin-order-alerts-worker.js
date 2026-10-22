'use strict';
// Only schedule checks. Authentication, requests and sound stay in the admin page.
setInterval(() => postMessage('poll'), 5000);
