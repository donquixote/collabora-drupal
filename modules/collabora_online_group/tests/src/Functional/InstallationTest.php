<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test module installation with groupmedia view override.
 */
class InstallationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'collabora_online_group',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the module install correctly.
   */
  public function testModuleInstallation(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
