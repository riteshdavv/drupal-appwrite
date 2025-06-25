<?php

namespace Drupal\appwrite_integration\Commands;

use Drupal\appwrite_integration\Service\AppwriteUserRoleMapper;
use Drush\Commands\DrushCommands;
use Drush\Style\DrushStyle;

/**
 * Drush commands for Appwrite user synchronization.
 */
class AppwriteSyncCommands extends DrushCommands {

  /**
   * The user role mapper service.
   */
  protected $userRoleMapper;

  /**
   * Constructor.
   */
  public function __construct(AppwriteUserRoleMapper $user_role_mapper) {
    $this->userRoleMapper = $user_role_mapper;
    parent::__construct();
  }

  /**
   * Sync all users from Appwrite to Drupal.
   *
   * @param array $options
   *   Command options.
   *
   * @option limit
   *   Number of users to sync per batch.
   * @option offset
   *   Offset to start syncing from.
   *
   * @command appwrite:sync-users
   * @aliases asu
   */
  public function syncUsers(array $options = ['limit' => 100, 'offset' => 0]) {
    $io = new DrushStyle($this->input(), $this->output());
    
    $io->title('Syncing Appwrite Users to Drupal');
    
    try {
      $synced_count = $this->userRoleMapper->syncAllUsers(
        $options['limit'],
        $options['offset']
      );
      
      $io->success("Successfully synced {$synced_count} users.");
      
    } catch (\Exception $e) {
      $io->error("Failed to sync users: " . $e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }
    
    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Sync a specific user by Appwrite user ID.
   *
   * @param string $user_id
   *   The Appwrite user ID.
   *
   * @command appwrite:sync-user
   * @aliases asu-single
   */
  public function syncUser($user_id) {
    $io = new DrushStyle($this->input(), $this->output());
    
    if (empty($user_id)) {
      $io->error('User ID is required.');
      return DrushCommands::EXIT_FAILURE;
    }
    
    try {
      $drupal_user = $this->userRoleMapper->syncUser($user_id);
      
      $io->success("Successfully synced user: {$drupal_user->getEmail()}");
      $io->writeln("Drupal User ID: {$drupal_user->id()}");
      $io->writeln("Roles: " . implode(', ', $drupal_user->getRoles()));
      
    } catch (\Exception $e) {
      $io->error("Failed to sync user: " . $e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }
    
    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Show current role mappings.
   *
   * @command appwrite:show-mappings
   * @aliases asm
   */
  public function showMappings() {
    $io = new DrushStyle($this->input(), $this->output());
    
    $mappings = $this->userRoleMapper->getRoleMappings();
    
    $io->title('Current Role Mappings');
    
    $rows = [];
    foreach ($mappings as $appwrite_key => $drupal_role) {
      $rows[] = [$appwrite_key, $drupal_role];
    }
    
    $io->table(['Appwrite Key', 'Drupal Role'], $rows);
    
    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Add a new role mapping.
   *
   * @param string $appwrite_key
   *   The Appwrite key (team, attribute, etc.).
   * @param string $drupal_role
   *   The Drupal role machine name.
   *
   * @command appwrite:add-mapping
   * @aliases aam
   */
  public function addMapping($appwrite_key, $drupal_role) {
    $io = new DrushStyle($this->input(), $this->output());
    
    if (empty($appwrite_key) || empty($drupal_role)) {
      $io->error('Both Appwrite key and Drupal role are required.');
      return DrushCommands::EXIT_FAILURE;
    }
    
    try {
      $this->userRoleMapper->updateRoleMappings([
        $appwrite_key => $drupal_role
      ]);
      
      $io->success("Added mapping: {$appwrite_key} -> {$drupal_role}");
      
    } catch (\Exception $e) {
      $io->error("Failed to add mapping: " . $e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }
    
    return DrushCommands::EXIT_SUCCESS;
  }
}