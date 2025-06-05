<?php

namespace Drupal\appwrite_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an Appwrite login button.
 *
 * @Block(
 *   id = "appwrite_login_block",
 *   admin_label = @Translation("Appwrite Login Block")
 * )
 */
class AppwriteLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $login_url = "https://drupal-appwrite.ddev.site/appwrite/login";

    return [
      '#markup' => 
        '<a href="' . $login_url . '"><button class="button">Login with GitHub</button></a>',
      '#attached' => [
        'library' => [],
      ],
    ];
  }

}
