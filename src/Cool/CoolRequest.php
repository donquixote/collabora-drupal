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
 * Service to fetch a WOPI client url.
 */
class CoolRequest {

    /**
     * Constructor.
     *
     * @param \Drupal\collabora_online\Cool\CoolDiscoveryXmlEndpoint $discoveryXmlEndpoint
     *   Service to load the discovery.xml from the Collabora server.
     */
    public function __construct(
        protected readonly CoolDiscoveryXmlEndpoint $discoveryXmlEndpoint,
    ) {}

    /**
     * Gets the URL for the WOPI client.
     *
     * @param string $mimetype
     *   Mime type for which to get the WOPI client url.
     *   This refers to config entries in the discovery.xml file.
     *
     * @return string
     *   The WOPI client url, or NULL on failure.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   The client url cannot be retrieved.
     */
    public function getWopiClientURL(string $mimetype = 'text/plain'): string {
        $discovery_parsed = $this->getParsedXml();

        $result = $discovery_parsed->xpath(sprintf('/wopi-discovery/net-zone/app[@name=\'%s\']/action', $mimetype));
        if (!empty($result[0]['urlsrc'][0])) {
            return (string) $result[0]['urlsrc'][0];
        }
        throw new CoolRequestException('The requested mime type is not handled.');
    }

    /**
     * Fetches the discovery.xml, and gets the parsed contents.
     *
     * @return \SimpleXMLElement
     *   Parsed xml from the discovery.xml.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   Fetching the discovery.xml failed, or the result is not valid xml.
     */
    protected function getParsedXml(): \SimpleXMLElement {
        $discovery = $this->discoveryXmlEndpoint->getDiscoveryXml();

        $discovery_parsed = simplexml_load_string($discovery);
        if (!$discovery_parsed) {
            throw new CoolRequestException('The retrieved discovery.xml file is not a valid XML file.');
        }

        return $discovery_parsed;
    }

}
