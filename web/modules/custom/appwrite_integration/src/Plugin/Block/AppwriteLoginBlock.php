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
    $google_url = Url::fromRoute('appwrite_integration.oauth_login', ['provider' => 'google'])->toString();
    $github_url = Url::fromRoute('appwrite_integration.oauth_login', ['provider' => 'github'])->toString();

    $markup = '
      <div class="appwrite-login-buttons">
        <a href="' . $google_url . '">
          <button class="button appwrite-login-google">' . $this->t('Login with Google') . '</button>
        </a>
        <a href="' . $github_url . '">
          <button class="button appwrite-login-github">' . $this->t('Login with GitHub') . '</button>
        </a>
      </div>
    ';


    return [
      '#markup' => $markup,
      '#allowed_tags' => ['div', 'a', 'button'],
      '#attached' => [
        'library' => ['appwrite_integration/appwrite-auth'],
      ]
    ];
  }

}
