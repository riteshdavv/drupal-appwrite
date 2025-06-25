<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\appwrite_integration\Service\AppwriteUserRoleMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Controller for handling Appwrite webhooks.
 */
class AppwriteWebhookController extends ControllerBase {

  /**
   * The user role mapper service.
   */
  protected $userRoleMapper;

  /**
   * Constructor.
   */
  public function __construct(AppwriteUserRoleMapper $user_role_mapper) {
    $this->userRoleMapper = $user_role_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('appwrite_integration.user_role_mapper')
    );
  }

  /**
   * Handle user webhooks from Appwrite.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function handleUserWebhook(Request $request) {
    try {
      // Verify webhook signature
      $this->verifyWebhookSignature($request);
      
      // Get webhook payload
      $payload = json_decode($request->getContent(), TRUE);
      
      if (!$payload) {
        throw new BadRequestHttpException('Invalid JSON payload');
      }
      
      // Process the webhook based on event type
      $event_type = $payload['event'] ?? '';
      $user_data = $payload['data'] ?? [];
      
      switch ($event_type) {
        case 'users.create':
          $this->handleUserCreate($user_data);
          break;
          
        case 'users.update':
          $this->handleUserUpdate($user_data);
          break;
          
        case 'users.sessions.create':
          $this->handleUserLogin($user_data);
          break;
          
        case 'users.verification.create':
          $this->handleUserVerification($user_data);
          break;
          
        case 'teams.memberships.create':
        case 'teams.memberships.update':
        case 'teams.memberships.delete':
          $this->handleTeamMembershipChange($user_data);
          break;
          
        default:
          $this->getLogger('appwrite_integration')->info('Unhandled webhook event: @event', [
            '@event' => $event_type
          ]);
      }
      
      return new JsonResponse(['status' => 'success']);
      
    } catch (\Exception $e) {
      $this->getLogger('appwrite_integration')->error('Webhook processing failed: @error', [
        '@error' => $e->getMessage()
      ]);
      
      return new JsonResponse([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Verify webhook signature for security.
   */
  protected function verifyWebhookSignature(Request $request) {
    $webhook_secret = $this->config('appwrite_integration.settings')->get('webhook_secret');
    
    if (empty($webhook_secret)) {
      throw new UnauthorizedHttpException('', 'Webhook secret not configured');
    }
    
    $signature = $request->headers->get('X-Appwrite-Webhook-Signature');
    if (empty($signature)) {
      throw new UnauthorizedHttpException('', 'Missing webhook signature');
    }
    
    $payload = $request->getContent();
    $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
    
    if (!hash_equals($expected_signature, $signature)) {
      throw new UnauthorizedHttpException('', 'Invalid webhook signature');
    }
  }

  /**
   * Handle user creation webhook.
   */
  protected function handleUserCreate($user_data) {
    $user_id = $user_data['$id'] ?? '';
    
    if (!empty($user_id)) {
      // Sync the new user
      $this->userRoleMapper->syncUser($user_id);
      
      $this->getLogger('appwrite_integration')->info('New user synced from webhook: @id', [
        '@id' => $user_id
      ]);
    }
  }

  /**
   * Handle user update webhook.
   */
  protected function handleUserUpdate($user_data) {
    $user_id = $user_data['$id'] ?? '';
    
    if (!empty($user_id)) {
      // Re-sync the updated user
      $this->userRoleMapper->syncUser($user_id);
      
      $this->getLogger('appwrite_integration')->info('User updated from webhook: @id', [
        '@id' => $user_id
      ]);
    }
  }

  /**
   * Handle user login webhook.
   */
  protected function handleUserLogin($session_data) {
    $user_id = $session_data['userId'] ?? '';
    
    if (!empty($user_id)) {
      // Update last access time
      $this->updateUserLastAccess($user_id);
      
      $this->getLogger('appwrite_integration')->info('User login tracked: @id', [
        '@id' => $user_id
      ]);
    }
  }

  /**
   * Handle user verification webhook.
   */
  protected function handleUserVerification($user_data) {
    $user_id = $user_data['userId'] ?? '';
    
    if (!empty($user_id)) {
      // Re-sync user to update verification status
      $this->userRoleMapper->syncUser($user_id);
      
      $this->getLogger('appwrite_integration')->info('User verification updated: @id', [
        '@id' => $user_id
      ]);
    }
  }

  /**
   * Handle team membership changes.
   */
  protected function handleTeamMembershipChange($membership_data) {
    $user_id = $membership_data['userId'] ?? '';
    
    if (!empty($user_id)) {
      // Re-sync user to update roles based on team membership
      $this->userRoleMapper->syncUser($user_id);
      
      $this->getLogger('appwrite_integration')->info('User team membership changed: @id', [
        '@id' => $user_id
      ]);
    }
  }

  /**
   * Update user's last access time in Drupal.
   */
  protected function updateUserLastAccess($appwrite_user_id) {
    try {
      // Find Drupal user by Appwrite ID
      $users = $this->entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['field_appwrite_user_id' => $appwrite_user_id]);
      
      if (!empty($users)) {
        $user = reset($users);
        $user->setLastAccessTime(time());
        $user->save();
      }
    } catch (\Exception $e) {
      $this->getLogger('appwrite_integration')->error('Failed to update last access: @error', [
        '@error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Health check endpoint for webhook.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function healthCheck() {
    return new JsonResponse([
      'status' => 'healthy',
      'timestamp' => time(),
      'service' => 'appwrite_integration'
    ]);
  }
}