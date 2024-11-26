<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online\ExistingSite;

use Drupal\collabora_online\Cool\CoolRequest;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests requests to Collabora from PHP.
 */
class FetchClientUrlTest extends ExistingSiteBase {

    /**
     * Tests fetching the client url.
     */
    public function testFetchClientUrl(): void {
        $cool_request = new CoolRequest();
        $client_url = $cool_request->getWopiClientURL();
        // The protocol, domain and port are known when this test runs in the
        // docker-compose setup.
        $this->assertMatchesRegularExpression('@^http://collabora\.test:9980/browser/[0-9a-f]+/cool\.html\?$@', $client_url);
        $this->assertSame('0: Success', $cool_request->errorString());
    }

    /**
     * Tests fetching client url when the connection is misconfigured.
     */
    public function testFetchClientUrlWithMisconfiguration(): void {
        \Drupal::configFactory()
            ->get('collabora_online.settings')
            ->setSettingsOverride([
                'cool' => [
                    'server' => 'httx://example.com',
                ],
            ]);
        $cool_request = new CoolRequest();
        $client_url = $cool_request->getWopiClientURL();
        $this->assertNull($client_url);
        $this->assertSame('204: Warning! You have to specify the scheme protocol too (http|https) for the server address.', $cool_request->errorString());
    }

}
