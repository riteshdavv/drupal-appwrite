<?php

namespace Drupal\appwrite_integration\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\appwrite_integration\Service\AppwriteUserRoleMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for syncing users from Appwrite.
 *
 * @QueueWorker(
 *   id = "appwrite_user_sync",
 *   title = @Translation("Appwrite User Sync"),
 *   cron = {"time" = 30}
 * )
 */
class UserSyncQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The user role mapper service.
   */
  protected $userRoleMapper;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AppwriteUserRoleMapper $user_role_mapper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userRoleMapper = $user_role_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('appwrite_integration.user_role_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $user_id = $data['user_id'] ?? '';
    $operation = $data['operation'] ?? 'sync';
    
    if (empty($user_id)) {
      throw new \Exception('Missing user_id in queue item');
    }
    
    switch ($operation) {
      case 'sync':
        $this->userRoleMapper->syncUser($user_id);
        break;
        
      case 'delete':
        $this->handleUserDeletion($user_id);
        break;
        
      default:
        throw new \Exception("Unknown operation: {$operation}");
    }
  }

  /**
   * Handle user deletion from Drupal when deleted in Appwrite.
   */
  protected function handleUserDeletion($appwrite_user_id) {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    
    $users = $user_storage->loadByProperties([
      'field_appwrite_user_id' => $appwrite_user_id
    ]);
    
    if (!empty($users)) {
      $user = reset($users);
      
      // Option 1: Delete the user completely
      // $user->delete();
      
      // Option 2: Block the user instead of deleting
      $user->block();
      $user->save();
      
      \Drupal::logger('appwrite_integration')->info('User blocked due to Appwrite deletion: @email', [
        '@email' => $user->getEmail()
      ]);
    }
  }
}