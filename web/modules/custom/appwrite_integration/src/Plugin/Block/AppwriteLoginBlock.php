<?php

namespace Drupal\appwrite_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides an Appwrite login button block.
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
    $google_url = Url::fromUri('internal:/appwrite/google/login')->toString();
    $github_url = Url::fromUri('internal:/appwrite/github/login')->toString();

    $markup = '
      <div class="appwrite-login-buttons">
        <a href="' . $google_url . '"><button class="button appwrite-login-google">Login with Google</button></a>
        <a href="' . $github_url . '"><button class="button appwrite-login-github">Login with GitHub</button></a>
      </div>
    ';

    return [
      '#markup' => $markup,
      '#allowed_tags' => ['div', 'a', 'button'],
    ];
  }

}
