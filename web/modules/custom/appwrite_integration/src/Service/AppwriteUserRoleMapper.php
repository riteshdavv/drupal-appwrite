<?php

namespace Drupal\appwrite_integration\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Appwrite\Client;
use Appwrite\Services\Users;
use Appwrite\Services\Teams;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Service for mapping Appwrite users to Drupal user roles.
 */
class AppwriteUserRoleMapper {

  /**
   * The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   */
  protected $loggerFactory;

  /**
   * Appwrite client.
   */
  protected $appwriteClient;

  /**
   * Appwrite users service.
   */
  protected $appwriteUsers;

  /**
   * Appwrite teams service.
   */
  protected $appwriteTeams;

  /**
   * Role mapping configuration.
   */
  protected $roleMappings;


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('typed_data_manager'),
      $container->get('appwrite_integration.user_role_mapper')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(
  EntityTypeManagerInterface $entity_type_manager,
  LoggerChannelFactoryInterface $logger_factory,
  
) {

  // Only init client when config is present
  try {
    $this->initializeAppwriteClient();

    // Load role mappings
    $this->initializeRoleMappings();
  } catch (\Throwable $e) {
    $this->loggerFactory->error('Client initialization failed: @msg', ['@msg' => $e->getMessage()]);
  }

  $this->initializeRoleMappings();
}


  /**
   * Initialize Appwrite client and services.
   */
  protected function initializeAppwriteClient() {
  $endpoint = \Drupal::config('appwrite_integration.settings')->get('endpoint');
  $projectId = \Drupal::config('appwrite_integration.settings')->get('project_id');
  $apiKey = \Drupal::config('appwrite_integration.settings')->get('api_key');

  if (empty($endpoint) || empty($projectId) || empty($apiKey)) {
    \Drupal::logger('appwrite_integration')->warning('Appwrite client configuration incomplete.');
    return;
  }

  $this->appwriteClient = new Client();
  $this->appwriteClient
    ->setEndpoint($endpoint)
    ->setProject($projectId)
    ->setKey($apiKey);

  $this->appwriteUsers = new Users($this->appwriteClient);
  $this->appwriteTeams = new Teams($this->appwriteClient);
}


  /**
   * Initialize role mappings configuration.
   */
  protected function initializeRoleMappings() {
    // Default role mappings - customize based on your needs
    $this->roleMappings = [
      // Appwrite team/attribute => Drupal role
      'admin_team' => 'administrator',
      'editor_team' => 'editor',
      'moderator_team' => 'moderator',
      'premium_user' => 'premium_subscriber',
      'verified_user' => 'authenticated',
      
      // OAuth provider specific mappings
      'github_org_admin' => 'developer',
      'google_workspace_admin' => 'administrator',
      
      // Custom attributes
      'user_level_admin' => 'administrator',
      'user_level_editor' => 'editor',
      'user_level_basic' => 'authenticated',
    ];
  }

  /**
   * Sync a single user from Appwrite to Drupal.
   */
  public function syncUser($appwrite_user_id) {
    try {
      // Get user from Appwrite
      $appwrite_user = $this->appwriteUsers->get($appwrite_user_id);
      
      // Find or create Drupal user
      $drupal_user = $this->findOrCreateDrupalUser($appwrite_user);
      
      // Map and assign roles
      $roles = $this->determineUserRoles($appwrite_user);
      $this->assignRolesToUser($drupal_user, $roles);
      
      // Update user attributes
      $this->updateUserAttributes($drupal_user, $appwrite_user);
      
      $this->loggerFactory->info('Successfully synced user: @email', [
        '@email' => $appwrite_user['email']
      ]);
      
      return $drupal_user;
      
    } catch (\Exception $e) {
      $this->loggerFactory->error('Failed to sync user @id: @error', [
        '@id' => $appwrite_user_id,
        '@error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Find existing Drupal user or create new one.
   */
  protected function findOrCreateDrupalUser($appwrite_user) {
  $email = $appwrite_user['email'];
  $appwrite_id = $appwrite_user['$id'];

  $user_storage = $this->entityTypeManager->getStorage('user');

  $existing_users = $user_storage->loadByProperties(['mail' => $email]);

  if (!empty($existing_users)) {
    $drupal_user = reset($existing_users);
    $this->loggerFactory->info('Found existing Drupal user for @mail', ['@mail' => $email]);
  } else {
    $username = strtolower(explode('@', $email)[0]);
    $drupal_user = User::create([
      'name' => $username,
      'mail' => $email,
      'status' => 1,
    ]);
    $this->loggerFactory->info('Created new Drupal user for @mail', ['@mail' => $email]);
  }

  // Update field_appwrite_user_id if present
  if ($drupal_user->hasField('field_appwrite_user_id')) {
    $current = $drupal_user->get('field_appwrite_user_id')->value;
    if ($current !== $appwrite_id) {
      $drupal_user->set('field_appwrite_user_id', $appwrite_id);
      $this->loggerFactory->info('Set field_appwrite_user_id = @id', ['@id' => $appwrite_id]);
    }
  }

  $drupal_user->save();
  return $drupal_user;
}


  /**
   * Generate username from Appwrite user data.
   */
  protected function generateUsername($appwrite_user) {
    $name = $appwrite_user['name'] ?? '';
    $email = $appwrite_user['email'] ?? '';
    
    if (!empty($name)) {
      $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    } else {
      $username = strtolower(explode('@', $email)[0]);
    }
    
    // Ensure username is unique
    $original_username = $username;
    $counter = 1;
    while ($this->usernameExists($username)) {
      $username = $original_username . $counter;
      $counter++;
    }
    
    return $username;
  }

  /**
   * Check if username already exists.
   */
  protected function usernameExists($username) {
    $existing = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);
    return !empty($existing);
  }

  /**
   * Determine user roles based on Appwrite user data.
   */
  protected function determineUserRoles($appwrite_user) {
    $roles = ['authenticated']; // Default role
    
    // Check team memberships
    $team_roles = $this->getUserTeamRoles($appwrite_user['$id']);
    foreach ($team_roles as $team_role) {
      if (isset($this->roleMappings[$team_role])) {
        $roles[] = $this->roleMappings[$team_role];
      }
    }
    
    // Check custom attributes
    $prefs = $appwrite_user['prefs'] ?? [];
    foreach ($prefs as $key => $value) {
      $mapping_key = $key . '_' . $value;
      if (isset($this->roleMappings[$mapping_key])) {
        $roles[] = $this->roleMappings[$mapping_key];
      }
    }
    
    // Check OAuth provider specific data
    $oauth_roles = $this->getOAuthBasedRoles($appwrite_user);
    $roles = array_merge($roles, $oauth_roles);
    
    return array_unique($roles);
  }

  /**
   * Get user's team roles from Appwrite.
   */
  protected function getUserTeamRoles($user_id) {
    $team_roles = [];
    
    try {
      // Get user's team memberships
      $memberships = $this->appwriteTeams->listMemberships();
      
      foreach ($memberships['memberships'] as $membership) {
        if ($membership['userId'] === $user_id) {
          $team_id = $membership['teamId'];
          $roles = $membership['roles'];
          
          // Map team roles to our role mapping keys
          foreach ($roles as $role) {
            $team_roles[] = $team_id . '_' . $role;
            $team_roles[] = $role; // Also add just the role name
          }
        }
      }
    } catch (\Exception $e) {
      $this->loggerFactory->warning('Failed to get team roles for user @id: @error', [
        '@id' => $user_id,
        '@error' => $e->getMessage()
      ]);
    }
    
    return $team_roles;
  }

  /**
   * Get roles based on OAuth provider data.
   */
  protected function getOAuthBasedRoles($appwrite_user) {
    $roles = [];
    
    // Check OAuth provider identities
    $identities = $appwrite_user['identities'] ?? [];
    
    foreach ($identities as $identity) {
      $provider = $identity['provider'] ?? '';
      $provider_data = $identity['providerAccessToken'] ?? [];
      
      switch ($provider) {
        case 'github':
          $github_roles = $this->getGitHubRoles($provider_data);
          $roles = array_merge($roles, $github_roles);
          break;
          
        case 'google':
          $google_roles = $this->getGoogleRoles($provider_data);
          $roles = array_merge($roles, $google_roles);
          break;
      }
    }
    
    return $roles;
  }

  /**
   * Get roles based on GitHub organization membership.
   */
  protected function getGitHubRoles($provider_data) {
    $roles = [];
    
    // This would require additional API calls to GitHub
    // Implement based on your specific GitHub organization structure
    
    return $roles;
  }

  /**
   * Get roles based on Google Workspace membership.
   */
  protected function getGoogleRoles($provider_data) {
    $roles = [];
    
    // This would require additional API calls to Google
    // Implement based on your specific Google Workspace structure
    
    return $roles;
  }

  /**
   * Assign roles to Drupal user.
   */
  protected function assignRolesToUser(User $user, array $roles) {
    // Remove existing roles (except authenticated)
    $current_roles = $user->getRoles(TRUE); // Exclude anonymous
    foreach ($current_roles as $role_id) {
      if ($role_id !== 'authenticated') {
        $user->removeRole($role_id);
      }
    }
    
    // Add new roles
    foreach ($roles as $role_id) {
      if ($role_id !== 'authenticated' && Role::load($role_id)) {
        $user->addRole($role_id);
      }
    }
    
    $user->save();
  }

  /**
   * Update user attributes from Appwrite data.
   */
  protected function updateUserAttributes(User $drupal_user, $appwrite_user) {
    $changed = FALSE;
    
    // Update name if different
    $appwrite_name = $appwrite_user['name'] ?? '';
    if (!empty($appwrite_name) && $drupal_user->getDisplayName() !== $appwrite_name) {
      if ($drupal_user->hasField('field_display_name')) {
        $drupal_user->set('field_display_name', $appwrite_name);
        $changed = TRUE;
      }
    }
    
    // Update email verification status
    $email_verified = $appwrite_user['emailVerification'] ?? FALSE;
    if ($drupal_user->hasField('field_email_verified')) {
      $drupal_user->set('field_email_verified', $email_verified);
      $changed = TRUE;
    }
    
    // Update last login from Appwrite
    $access_time = strtotime($appwrite_user['accessedAt'] ?? 'now');
    if ($drupal_user->getLastAccessedTime() < $access_time) {
      $drupal_user->setLastAccessTime($access_time);
      $changed = TRUE;
    }
    
    // Store additional Appwrite metadata
    if ($drupal_user->hasField('field_appwrite_metadata')) {
      $metadata = [
        'registration' => $appwrite_user['registration'] ?? '',
        'phone_verification' => $appwrite_user['phoneVerification'] ?? FALSE,
        'preferences' => $appwrite_user['prefs'] ?? [],
      ];
      $drupal_user->set('field_appwrite_metadata', json_encode($metadata));
      $changed = TRUE;
    }
    
    if ($changed) {
      $drupal_user->save();
    }
  }

  /**
   * Sync all users from Appwrite.
   */
  public function syncAllUsers($limit = 100, $offset = 0) {
    try {
      $users = $this->appwriteUsers->list([], $limit, $offset);
      $synced_count = 0;
      
      foreach ($users['users'] as $appwrite_user) {
        try {
          $this->syncUser($appwrite_user['$id']);
          $synced_count++;
        } catch (\Exception $e) {
          // Log error but continue with other users
          $this->loggerFactory->error('Failed to sync user @id: @error', [
            '@id' => $appwrite_user['$id'],
            '@error' => $e->getMessage()
          ]);
        }
      }
      
      $this->loggerFactory->info('Synced @count users out of @total', [
        '@count' => $synced_count,
        '@total' => count($users['users'])
      ]);
      
      return $synced_count;
      
    } catch (\Exception $e) {
      $this->loggerFactory->error('Failed to sync users: @error', [
        '@error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Update role mappings configuration.
   */
  public function updateRoleMappings(array $mappings) {
    $this->roleMappings = array_merge($this->roleMappings, $mappings);
    
    // Save to configuration
    \Drupal::configFactory()
      ->getEditable('appwrite_integration.role_mappings')
      ->setData($this->roleMappings)
      ->save();
  }

  /**
   * Get current role mappings.
   */
  public function getRoleMappings() {
    return $this->roleMappings;
  }
}