Collabora Online connector for Drupal
=====================================

This module integrate Collabora Online to Drupal. You can use it to
view and edit documents from within Drupal.

Requirements:

- Collabora Online server installed and running.
- Drupal 10 (tested on 10.1), maybe compatible with 9.
- JWT and Media are set as dependencies for the module and are
  necessary.

Copy the content of this directory into
`modules/custom/collabora_online` like a Drupal module.

Configuration
-------------

There are few step necessary in Drupal to configure the integration.

Log into Drupal as an admin.

### JWT key

Go to Configuration > System > Keys

- Create a JWT HMAC key, with the HS256 algorithm.
- Set it to be provided by the configuration.

### Collabora Online

Go to Configuration > Media > Collabora Online Settings

- Collabora Online server URL: the URL of the collabora online
  server. Note that you have to take into considerartion containers. If
  you run Drupal in one container and Collabora Online in another, you
  can not use `localhost`.
- WOPI host base URL: the important part is how the Collabora Online
  server can reach the Drupal server.
- JWT Private Key ID: the id of the key created above.

Optional

- Disable TLS certificate check for COOL: If you configure a
  development server you might have self-signed certificate. Checking
  this is **INSECURE** but allow the drupal server to contact the
  collabora online server is the certificate doesn't check.

### COOL

Some configuration changes might be necessary on the Collabora Online
side.

CSP for embedding must set properly to embed the Collabora Online
frame.

License
-------

This module is published under the MPL-2.0 license.
