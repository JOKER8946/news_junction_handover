<?php
/**
 * Google OAuth config for News Junction.
 *
 * Only `client_id` is needed by the current sign-in flow. We use the Google
 * Identity Services (GIS) ID-token flow: the browser receives a signed JWT
 * from Google and posts it to /process/google-callback.php, which verifies
 * the JWT against Google's public keys. No secret is needed for that.
 *
 * `client_secret` is stored here for future flows (offline access / refresh
 * tokens / Calendar / Drive APIs) that DO need the secret.
 */

return [
    'client_id'     => '700137174191-ada8kemea5ep3ajd7kpn490huii6l339.apps.googleusercontent.com',
    'client_secret' => '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',
];
