<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Smoke test to check that CI is working.
 */
class SmokeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'collabora_online_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the module install correctly.
   *
   * To be removed when other tests are implemented.
   */
  public function testModuleInstallation(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
