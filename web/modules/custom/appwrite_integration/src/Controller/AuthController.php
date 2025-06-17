<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;

/**
 * Controller for Appwrite OAuth integration.
 */
class AuthController extends ControllerBase {

  // /**
  //  * Login page with GitHub OAuth button.
  //  */
  // public function login() {
  //   $build = [
  //     '#theme' => 'appwrite_integration_login',
  //     '#attached' => [
  //       'library' => [
  //         'appwrite_integration/appwrite-auth',
  //       ],
  //     ],
  //   ];

  //   return $build;
  // }


  /**
   * Dynamic “Login with …” page.
   */
  public function login($provider) {
    return [
      '#theme' => 'appwrite_integration_login',
      '#attached' => [
        'library' => ['appwrite_integration/appwrite-auth'],
        // Tell JS which provider we’re on, if you want:
        'drupalSettings' => [
          'appwrite_integration' => [
            'provider' => $provider,
          ],
        ],
      ],
    ];
  }

  /**
   * Dynamic page title callback.
   */
  public function loginTitle($provider) {
    return $this->t('Sign in with @provider', ['@provider' => ucfirst($provider)]);
  }



  /**
   * Dashboard page for authenticated users.
   */
  public function dashboard() {
    $build = [
      '#theme' => 'appwrite_integration_dashboard',
      '#attached' => [
        'library' => [
          'appwrite_integration/appwrite-auth',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Auth callback handler.
   */
  public function authCallback() {
    $build = [
      '#theme' => 'appwrite_integration_callback',
      '#attached' => [
        'library' => [
          'appwrite_integration/appwrite-auth',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Logout handler.
   */
  public function logout() {
    // JavaScript will handle the actual logout and redirect
    $build = [
      '#markup' => '<div id="logout-handler">Logging out...</div>',
      '#attached' => [
        'library' => [
          'appwrite_integration/appwrite-auth',
        ],
      ],
    ];

    return $build;
  }

}
