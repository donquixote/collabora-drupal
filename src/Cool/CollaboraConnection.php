<?php

declare(strict_types=1);

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

use Drupal\collabora_online\Exception\CollaboraNotAvailableException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to load the discovery.xml from the Collabora server.
 */
class CollaboraConnection {

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
     * Loads the WOPI server url from configuration.
     *
     * @throws \Drupal\collabora_online\Exception\CollaboraNotAvailableException
     *   The WOPI server url is misconfigured, or the protocol does not match
     *   that of the current Drupal request.
     */
    protected function getWopiClientServerBaseUrl(): string {
        $cool_settings = $this->configFactory->get('collabora_online.settings')->get('cool');
        if (!$cool_settings) {
            throw new CollaboraNotAvailableException('The Collabora Online connection is not configured.');
        }
        $wopi_client_server = $cool_settings['server'] ?? NULL;
        if (!$wopi_client_server) {
            throw new CollaboraNotAvailableException('The configured Collabora Online server address is empty.');
        }
        $wopi_client_server = trim($wopi_client_server);

        if (!preg_match('@^https?://@', $wopi_client_server)) {
            throw new CollaboraNotAvailableException(sprintf(
                "The configured Collabora Online server address must begin with 'http://' or 'https://'. Found '%s'.",
                $wopi_client_server,
            ));
        }

        return $wopi_client_server;
    }

    /**
     * Gets the contents of discovery.xml from the Collabora server.
     *
     * @return string
     *   The full contents of discovery.xml.
     *
     * @throws \Drupal\collabora_online\Exception\CollaboraNotAvailableException
     *   The client url cannot be retrieved.
     */
    public function getDiscoveryXml(): string {
        $discovery_url = $this->getWopiClientServerBaseUrl() . '/hosting/discovery';

        $cool_settings = $this->configFactory->get('collabora_online.settings')->get('cool');
        if (!$cool_settings) {
            throw new CollaboraNotAvailableException('The Collabora Online connection is not configured.');
        }
        $disable_checks = !empty($cool_settings['disable_cert_check']);

        try {
            $response = $this->client->get($discovery_url, [
                RequestOptions::VERIFY => !$disable_checks,
            ]);
            $xml = $response->getBody()->getContents();
        }
        catch (ClientExceptionInterface $e) {
            // The backtrace of a client exception is typically not very
            // interesting. Just log the message.
            $this->logger->error("Failed to fetch from '@url': @message.", [
                '@url' => $discovery_url,
                '@message' => $e->getMessage(),
            ]);
            throw new CollaboraNotAvailableException(
                'Not able to retrieve the discovery.xml file from the Collabora Online server.',
                previous: $e,
            );
        }
        return $xml;
    }

}
