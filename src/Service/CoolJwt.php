<?php

declare(strict_types=1);

namespace Drupal\collabora_online\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\key\KeyRepositoryInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service with functionality related to the JWT token.
 */
class CoolJwt {

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Config factory service.
     * @param \Drupal\key\KeyRepositoryInterface $keyRepository
     *   Key repository service.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger channel for this module.
     * @param \Drupal\Core\Session\AccountInterface $currentUser
     *   Current user.
     */
    public function __construct(
        protected readonly ConfigFactoryInterface $configFactory,
        #[Autowire(service: 'key.repository')]
        protected readonly KeyRepositoryInterface $keyRepository,
        #[Autowire(service: 'logger.channel.collabora_online')]
        protected readonly LoggerInterface $logger,
        protected readonly AccountInterface $currentUser,
    ) {}

    /**
     * Obtains the signing key from the key storage.
     *
     * @return string
     *   The key value.
     */
    protected function getKey() {
        $default_config = $this->configFactory->get('collabora_online.settings');
        $key_id = $default_config->get('cool')['key_id'];

        $key = $this->keyRepository->getKey($key_id)->getKeyValue();
        return $key;
    }

    /**
     * Decodes and verifies a JWT token.
     *
     * Verification include:
     *  - matching $id with fid in the payload
     *  - verifying the expiration.
     *
     * @param string $token
     *   The token to verify.
     * @param int|string $id
     *   Media id for which the token was created.
     *   This could be in string form like '123'.
     *
     * @return \stdClass|null
     *   Data decoded from the token, or NULL on failure or if the token has
     *   expired.
     */
    public function verifyTokenForId(
        #[\SensitiveParameter]
        string $token,
        $id,
    ) {
        $key = $this->getKey();
        try {
            $payload = JWT::decode($token, new Key($key, 'HS256'));

            if ($payload && ($payload->fid == $id) && ($payload->exp >= gettimeofday(TRUE))) {
                return $payload;
            }
        }
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return NULL;
    }

    /**
     * Creates a JWT token for a media entity.
     *
     * The token will carry the following:
     *
     * - fid: the Media id in Drupal.
     * - uid: the User id for the token. Permissions should be checked
     *   whenever.
     * - exp: the expiration time of the token.
     * - wri: if true, then this token has write permissions.
     *
     * The signing key is stored in Drupal key management.
     *
     * @param int|string $id
     *   Media id, which could be in string form like '123'.
     * @param int $ttl
     *   Access token TTL in seconds.
     * @param bool $can_write
     *   TRUE if the token is for an editor in write/edit mode.
     *
     * @return string
     *   The access token.
     */
    public function tokenForFileId($id, $ttl, $can_write = FALSE) {
        $payload = [
            "fid" => $id,
            "uid" => $this->currentUser->id(),
            "exp" => $ttl,
            "wri" => $can_write,
        ];
        $key = $this->getKey();
        $jwt = JWT::encode($payload, $key, 'HS256');

        return $jwt;
    }

    /**
     * Gets the TTL of the token in seconds, from the EPOCH.
     *
     * @return int
     *   Token TTL in seconds.
     */
    public function getAccessTokenTtl() {
        $default_config = $this->configFactory->get('collabora_online.settings');
        $ttl = $default_config->get('cool')['access_token_ttl'];

        return gettimeofday(TRUE) + $ttl;
    }

}
