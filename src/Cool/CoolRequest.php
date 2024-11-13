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
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to fetch a WOPI client url.
 */
class CoolRequest {

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   Logger channel.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Config factory.
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     *   Request stack.
     * @param \GuzzleHttp\ClientInterface $client
     *   Http client.
     */
    public function __construct(
        #[Autowire(service: 'logger.channel.collabora_online')]
        protected readonly LoggerChannelInterface $logger,
        protected readonly ConfigFactoryInterface $configFactory,
        protected readonly RequestStack $requestStack,
        protected readonly ClientInterface $client,
    ) {}

    /**
     * Gets the URL for the WOPI client.
     *
     * @return string
     *   The WOPI client url, or NULL on failure.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   The client url cannot be retrieved.
     */
    public function getWopiClientURL(): string {
        $discovery = $this->getDiscovery();

        $discovery_parsed = simplexml_load_string($discovery);
        if (!$discovery_parsed) {
            throw new CoolRequestException(
                'The retrieved discovery.xml file is not a valid XML file.',
                102,
            );
        }

        $wopi_src = $this->getWopiSrcUrl($discovery_parsed, 'text/plain');

        return $wopi_src;
    }

    /**
     * Loads the WOPI server url from configuration.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   The WOPI server url is misconfigured, or the protocol does not match
     *   that of the current Drupal request.
     */
    protected function getWopiClientServerBaseUrl(): string {
        $cool_settings = $this->configFactory->get('collabora_online.settings')->get('cool');
        if (!$cool_settings) {
            throw new CoolRequestException(
                'The Collabora Online connection is not configured.',
                // Use the same code as was previously used in this case.
                201,
            );
        }
        $wopi_client_server = $cool_settings['server'] ?? NULL;
        if (!$wopi_client_server) {
            throw new CoolRequestException(
                'Collabora Online server address is not valid.',
                201,
            );
        }
        $wopi_client_server = trim($wopi_client_server);

        if (!preg_match('@^(https?)://@', $wopi_client_server, $matches)) {
            throw new CoolRequestException(
                'Warning! You have to specify the scheme protocol too (http|https) for the server address.',
                204,
            );
        }

        $wopi_client_server_scheme = $matches[1];
        $current_request_scheme = $this->requestStack->getCurrentRequest()->getScheme();

        if ($wopi_client_server_scheme !== $current_request_scheme) {
            throw new CoolRequestException(
                'Collabora Online server address scheme does not match the current page url scheme.',
                202,
            );
        }

        return $wopi_client_server;
    }

    /**
     * Gets the contents of discovery.xml from the Collabora server.
     *
     * @return string
     *   The full contents of discovery.xml.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   The client url cannot be retrieved.
     */
    protected function getDiscovery(): string {
        $discovery_url = $this->getWopiClientServerBaseUrl() . '/hosting/discovery';

        $cool_settings = $this->configFactory->get('collabora_online.settings')->get('cool');
        if (!$cool_settings) {
            throw new CoolRequestException(
                'The Collabora Online connection is not configured.',
                // Use the same code as was previously used in this case.
                203,
            );
        }
        $disable_checks = !empty($cool_settings['disable_cert_check']);

        try {
            $response = $this->client->get($discovery_url, [
                RequestOptions::VERIFY => !$disable_checks,
            ]);
            $res = $response->getBody()->getContents();
        }
        catch (ClientExceptionInterface $e) {
            $this->logger->error('Cannot fetch from @url.', ['@url' => $discovery_url]);
            throw new CoolRequestException(
                'Not able to retrieve the discovery.xml file from the Collabora Online server.',
                203,
                $e,
            );
        }
        return $res;
    }

    /**
     * Extracts a WOPI url from the parsed discovery.xml.
     *
     * @param \SimpleXMLElement $discovery_parsed
     *   Parsed contents from discovery.xml from the Collabora server.
     *   Currently, NULL or FALSE are supported too, but lead to NULL return
     *   value.
     * @param string $mimetype
     *   MIME type for which to fetch the WOPI url. E.g. 'text/plain'.
     *
     * @return string
     *   WOPI url as configured for this MIME type in discovery.xml.
     *
     * @throws \Drupal\collabora_online\Exception\CoolRequestException
     *   No WOPI url was found for this MIME type.
     */
    protected function getWopiSrcUrl(\SimpleXMLElement $discovery_parsed, string $mimetype): string {
        $result = $discovery_parsed->xpath(sprintf('/wopi-discovery/net-zone/app[@name=\'%s\']/action', $mimetype));
        if (!empty($result[0]['urlsrc'][0])) {
            return (string) $result[0]['urlsrc'][0];
        }
        throw new CoolRequestException(
            'The requested mime type is not handled.',
            103,
        );
    }

}
