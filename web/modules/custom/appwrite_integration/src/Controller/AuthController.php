<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Appwrite\Client;
use Appwrite\Services\Account;

/**
 * Controller for Appwrite OAuth integration.
 */
class AuthController extends ControllerBase {

  protected $logger;

  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('appwrite_integration');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * Renders login page.
   */
  public function login($provider) {
    return [
      '#theme' => 'appwrite_integration_login',
      '#attached' => [
        'library' => ['appwrite_integration/appwrite-auth'],
        'drupalSettings' => [
          'appwrite_integration' => ['provider' => $provider],
        ],
      ],
    ];
  }

  /**
   * Callback Page Title.
   */
  public function loginTitle() {
    return $this->t('Sign in to continue');
  }

  /**
   * OAuth callback handler.
   */
  public function authCallback() {
    return [
      '#theme' => 'appwrite_integration_callback',
      '#attached' => [
        'library' => ['appwrite_integration/appwrite-auth'],
      ],
    ];
  }

  /**
   * Finalizes login after receiving Appwrite session and JWT.
   */
  public function finalizeLogin(Request $request): JsonResponse {
  try {
    $data = json_decode($request->getContent(), TRUE);
    if (!$data || empty($data['session']) || empty($data['jwt'])) {
      throw new \Exception('Missing Appwrite session or JWT');
    }

    $client = new Client();
    $client
      ->setEndpoint($this->config('appwrite_integration.settings')->get('endpoint'))
      ->setProject($this->config('appwrite_integration.settings')->get('project_id'))
      ->setJWT($data['jwt']);

    $account = new Account($client);
    $appwrite_user = $account->get();

    $this->logger->info('Fetched Appwrite user: @email', [
      '@email' => $appwrite_user['email'] ?? 'unknown'
    ]);

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $existing = $user_storage->loadByProperties(['mail' => $appwrite_user['email']]);
    $drupal_user = !empty($existing) ? reset($existing) : NULL;

    if (!$drupal_user) {
      // New user creation
      $drupal_user = User::create([
        'name' => strtolower(explode('@', $appwrite_user['email'])[0]),
        'mail' => $appwrite_user['email'],
        'status' => 1,
        'field_appwrite_user_id' => $appwrite_user['$id'],
      ]);
      $drupal_user->save();
      $this->logger->info('Created new Drupal user: @uid', ['@uid' => $drupal_user->id()]);
    }

    // Ensure field_appwrite_user_id is stored (for existing users too)
    if ($drupal_user->hasField('field_appwrite_user_id') &&
        $drupal_user->get('field_appwrite_user_id')->isEmpty()) {
      $drupal_user->set('field_appwrite_user_id', $appwrite_user['$id']);
      $drupal_user->save();
      $this->logger->info('Appwrite ID assigned to user @uid', ['@uid' => $drupal_user->id()]);
    }

    // Optional: map roles via service (if implemented)
    if (\Drupal::hasService('appwrite_integration.user_role_mapper')) {
      $role_mapper = \Drupal::service('appwrite_integration.user_role_mapper');
      $role_mapper->syncUser($appwrite_user['$id']);
    }

    user_login_finalize($drupal_user);
    $this->logger->info('User login finalized for @uid', ['@uid' => $drupal_user->id()]);

    return new JsonResponse([
      'status' => 'success',
      'message' => 'User logged in successfully.',
      'user_id' => $drupal_user->id(),
    ]);

  } catch (\Exception $e) {
    $this->logger->error('Finalize login error: @error', ['@error' => $e->getMessage()]);
    return new JsonResponse([
      'status' => 'error',
      'message' => $e->getMessage(),
    ], 500);
  }
}


  /**
   * Displays user dashboard.
   */
  public function dashboard() {
  $account = $this->currentUser();
  $uid = $account->id();
  $drupal_user = \Drupal\user\Entity\User::load($uid);

  $user_data = [
    'id' => $drupal_user ? $drupal_user->id() : 'N/A',
    'roles' => $drupal_user ? $drupal_user->getRoles() : [],
    'appwrite_id' => '',
  ];

  if ($drupal_user && $drupal_user->hasField('field_appwrite_user_id')) {
    $user_data['appwrite_id'] = $drupal_user->get('field_appwrite_user_id')->value ?? '';
  }

  return [
    '#theme' => 'appwrite_integration_dashboard',
    '#drupal_user' => $user_data,
    '#attached' => [
      'library' => ['appwrite_integration/appwrite-auth'],
    ],
  ];
}

  /**
   * Simple logout page.
   */
  public function logout() {
    return [
      '#markup' => '<div id="logout-handler">Logging out...</div>',
      '#attached' => [
        'library' => ['appwrite_integration/appwrite-auth'],
      ],
    ];
  }
}
