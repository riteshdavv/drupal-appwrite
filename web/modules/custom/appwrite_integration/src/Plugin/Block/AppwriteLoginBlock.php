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
    $appwrite_endpoint = 'https://fra.cloud.appwrite.io/v1';
    $project_id = '683ea5970037a0cd8c8b'; // Replace with actual Appwrite project ID
    $success_url = 'http://drupal-appwrite.ddev.site/appwrite/bridge';
    $failure_url = 'http://drupal-appwrite.ddev.site/appwrite/failure';

    $login_url = "{$appwrite_endpoint}/account/sessions/oauth2/github?project={$project_id}&success={$success_url}&failure={$failure_url}&token=true";

    return [
      '#markup' => 
        '<a href="' . $login_url . '"><button class="button">Login with GitHub</button></a>',
      '#attached' => [
        'library' => [], // You can attach a custom CSS/JS library if needed
      ],
    ];
  }

}
