<?php

/*
 * Copyright the Collabora Online contributors.
 *
 * SPDX-License-Identifier: MPL-2.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Drupal\collabora_online\Cool;

use Drupal\collabora_online\Exception\CoolRequestException;

/**
 * Gets the contents of discovery.xml from the Collabora server.
 *
 * @param string $server
 *   Url of the Collabora Online server.
 *
 * @return string|false
 *   The full contents of discovery.xml, or FALSE on failure.
 */
function getDiscovery($server) {
    $discovery_url = $server . '/hosting/discovery';

    $default_config = \Drupal::config('collabora_online.settings');
    if ($default_config === NULL) {
        return FALSE;
    }
    $disable_checks = (bool) $default_config->get('cool')['disable_cert_check'];

    $stream_context = stream_context_create([
        'ssl' => [
            'verify_peer'       => !$disable_checks,
            'verify_peer_name'  => !$disable_checks,
        ],
    ]);
    $res = file_get_contents($discovery_url, FALSE, $stream_context);
    return $res;
}

/**
 * Extracts a WOPI url from the parsed discovery.xml.
 *
 * @param \SimpleXMLElement|null|false $discovery_parsed
 *   Parsed contents from discovery.xml from the Collabora server.
 *   Currently, NULL or FALSE are supported too, but lead to NULL return value.
 * @param string $mimetype
 *   MIME type for which to fetch the WOPI url. E.g. 'text/plain'.
 *
 * @return mixed|null
 *   WOPI url as configured for this MIME type in discovery.xml, or NULL if none
 *   was found for the given MIME type.
 */
function getWopiSrcUrl($discovery_parsed, $mimetype) {
    if ($discovery_parsed === NULL || $discovery_parsed == FALSE) {
        return NULL;
    }
    $result = $discovery_parsed->xpath(sprintf('/wopi-discovery/net-zone/app[@name=\'%s\']/action', $mimetype));
    if ($result && count($result) > 0) {
        return $result[0]['urlsrc'];
    }
    return NULL;
}

/**
 * Checks if a string starts with another string.
 *
 * @param string $haystack
 *   Haystack.
 * @param string $needle
 *   Needle.
 *
 * @return bool
 *   TRUE if $ss is a prefix of $s.
 *
 * @see str_starts_with()
 */
function strStartsWith($haystack, $needle) {
    return strrpos($haystack, $needle) === 0;
}

/**
 * Service to fetch a WOPI client url.
 */
class CoolRequest {

    /**
     * Gets the URL for the WOPI client.
     *
     * @return string
     *   The WOPI client url, or NULL on failure.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   The client url cannot be retrieved.
     */
    public function getWopiClientURL() {
        $_HOST_SCHEME = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $default_config = \Drupal::config('collabora_online.settings');
        $wopi_client_server = $default_config->get('cool')['server'];
        if (!$wopi_client_server) {
            throw new CoolRequestException(
                'Collabora Online server address is not valid.',
                201,
            );
        }
        $wopi_client_server = trim($wopi_client_server);

        if (!strStartsWith($wopi_client_server, 'http')) {
            throw new CoolRequestException(
                'Warning! You have to specify the scheme protocol too (http|https) for the server address.',
                204,
            );
        }

        if (!strStartsWith($wopi_client_server, $_HOST_SCHEME . '://')) {
            throw new CoolRequestException(
                'Collabora Online server address scheme does not match the current page url scheme.',
                202,
            );
        }

        $discovery = getDiscovery($wopi_client_server);
        if ($discovery === FALSE) {
            throw new CoolRequestException(
                'Not able to retrieve the discovery.xml file from the Collabora Online server.',
                203,
            );
        }

        $discovery_parsed = simplexml_load_string($discovery);
        if (!$discovery_parsed) {
            throw new CoolRequestException(
                'The retrieved discovery.xml file is not a valid XML file.',
                102,
            );
        }

        $wopi_src = strval(getWopiSrcUrl($discovery_parsed, 'text/plain')[0]);
        if (!$wopi_src) {
            throw new CoolRequestException(
                'The requested mime type is not handled.',
                103,
            );
        }

        return $wopi_src;
    }

}
