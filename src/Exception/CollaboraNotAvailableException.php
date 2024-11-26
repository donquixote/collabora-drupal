<?php

declare(strict_types=1);

namespace Drupal\collabora_online\Exception;

/**
 * Collabora is not available.
 *
 * The reason could be:
 *   - The configuration for this module is empty or invalid.
 *   - The Collabora service is not responding, or is not behaving as expected.
 */
class CollaboraNotAvailableException extends \Exception {

}
