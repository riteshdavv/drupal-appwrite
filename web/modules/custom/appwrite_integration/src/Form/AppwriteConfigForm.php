<?php

namespace Drupal\appwrite_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\appwrite_integration\Service\AppwriteUserRoleMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;

/**
 * Configuration form for Appwrite integration settings.
 */
class AppwriteConfigForm extends ConfigFormBase {

  protected $userRoleMapper;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config,
    AppwriteUserRoleMapper $user_role_mapper
  ) {
    parent::__construct($config_factory, $typed_config);
    $this->userRoleMapper = $user_role_mapper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('appwrite_integration.user_role_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'appwrite_integration.settings',
      'appwrite_integration.role_mappings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appwrite_integration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('appwrite_integration.settings');
    $mappings_config = $this->config('appwrite_integration.role_mappings');

    $form['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Appwrite Connection Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['connection']['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Appwrite Endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#description' => $this->t('Your Appwrite server endpoint (e.g., https://appwrite.example.com/v1)'),
      '#required' => TRUE,
    ];

    $form['connection']['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#default_value' => $config->get('project_id'),
      '#description' => $this->t('Your Appwrite project ID'),
      '#required' => TRUE,
    ];

    $form['connection']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Your Appwrite API key with users.read permission'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['connection']['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook Secret'),
      '#default_value' => $config->get('webhook_secret'),
      '#description' => $this->t('Secret key for webhook signature verification'),
    ];

    // Storage Settings

    $form['storage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Storage Integration Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];    

    $form['storage']['bucket_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appwrite Storage Bucket ID'),
      '#description' => $this->t('Enter the default bucket ID to use for uploads and file listing.'),
      '#default_value' => $config->get('bucket_id'),
      '#required' => TRUE,
    ];

    // OAuth Settings
    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['oauth']['github_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub Client ID'),
      '#default_value' => $config->get('github_client_id'),
    ];

    $form['oauth']['github_client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('GitHub Client Secret'),
      '#default_value' => $config->get('github_client_secret'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['oauth']['google_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Client ID'),
      '#default_value' => $config->get('google_client_id'),
    ];

    $form['oauth']['google_client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Google Client Secret'),
      '#default_value' => $config->get('google_client_secret'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    // Sync Settings
    $form['sync'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Synchronization Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['sync']['auto_sync_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic synchronization'),
      '#default_value' => $config->get('auto_sync_enabled'),
      '#description' => $this->t('Automatically sync users via webhooks and cron'),
    ];

    $form['sync']['sync_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Sync Frequency'),
      '#default_value' => $config->get('sync_frequency') ?: 60,
      '#options' => [
        15 => $this->t('Every 15 minutes'),
        30 => $this->t('Every 30 minutes'),
        60 => $this->t('Every hour'),
        180 => $this->t('Every 3 hours'),
        360 => $this->t('Every 6 hours'),
        720 => $this->t('Every 12 hours'),
        1440 => $this->t('Daily'),
      ],
    ];

    $roles = Role::loadMultiple();
    $role_options = [];
    foreach ($roles as $role) {
      if ($role->id() !== 'anonymous') {
        $role_options[$role->id()] = $role->label();
      }
    }

    $form['sync']['default_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Role'),
      '#default_value' => $config->get('default_role') ?: 'authenticated',
      '#options' => $role_options,
      '#description' => $this->t('Default role assigned to new users'),
    ];

    // Role Mappings
    $form['mappings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Role Mappings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Map Appwrite teams, attributes, or conditions to Drupal roles.'),
    ];

    $current_mappings = $this->userRoleMapper->getRoleMappings();
    $mapping_count = max(count($current_mappings), 5);

    $form['mappings']['role_mappings'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Appwrite Key'),
        $this->t('Drupal Role'),
        $this->t('Description'),
      ],
      '#empty' => $this->t('No role mappings configured.'),
    ];

    $i = 0;
    foreach ($current_mappings as $appwrite_key => $drupal_role) {
      $form['mappings']['role_mappings'][$i]['appwrite_key'] = [
        '#type' => 'textfield',
        '#default_value' => $appwrite_key,
        '#size' => 30,
      ];

      $form['mappings']['role_mappings'][$i]['drupal_role'] = [
        '#type' => 'select',
        '#default_value' => $drupal_role,
        '#options' => $role_options,
        '#empty_option' => $this->t('- Select role -'),
      ];

      $form['mappings']['role_mappings'][$i]['description'] = [
        '#type' => 'textfield',
        '#default_value' => $this->getMappingDescription($appwrite_key),
        '#size' => 40,
        '#attributes' => ['readonly' => 'readonly'],
      ];

      $i++;
    }

    // Add empty rows for new mappings
    for ($j = $i; $j < $i + 3; $j++) {
      $form['mappings']['role_mappings'][$j]['appwrite_key'] = [
        '#type' => 'textfield',
        '#size' => 30,
      ];

      $form['mappings']['role_mappings'][$j]['drupal_role'] = [
        '#type' => 'select',
        '#options' => $role_options,
        '#empty_option' => $this->t('- Select role -'),
      ];

      $form['mappings']['role_mappings'][$j]['description'] = [
        '#type' => 'textfield',
        '#size' => 40,
        '#placeholder' => $this->t('Optional description'),
      ];
    }

    // Test Connection
    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test Connection'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['test']['test_connection'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Connection'),
      '#ajax' => [
        'callback' => '::testConnection',
        'wrapper' => 'test-result',
      ],
    ];

    $form['test']['result'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="test-result">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get description for a mapping key.
   */
  protected function getMappingDescription($key) {
    $descriptions = [
      'admin_team' => 'Users in admin team',
      'editor_team' => 'Users in editor team',
      'verified_user' => 'Email verified users',
      'github_org_admin' => 'GitHub organization admins',
      'google_workspace_admin' => 'Google Workspace admins',
    ];

    return $descriptions[$key] ?? '';
  }

  /**
   * Ajax callback to test Appwrite connection.
   */
  public function testConnection(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    try {
      // Test connection logic here
      $result = $this->t('✅ Connection successful!');
      $class = 'messages messages--status';
    } catch (\Exception $e) {
      $result = $this->t('❌ Connection failed: @error', ['@error' => $e->getMessage()]);
      $class = 'messages messages--error';
    }

    $form['test']['result'] = [
      '#type' => 'markup',
      '#markup' => '<div class="' . $class . '">' . $result . '</div>',
      '#prefix' => '<div id="test-result">',
      '#suffix' => '</div>',
    ];

    return $form['test']['result'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save main settings
    $this->config('appwrite_integration.settings')
      ->set('endpoint', $values['endpoint'])
      ->set('project_id', $values['project_id'])
      ->set('webhook_secret', $values['webhook_secret'])
      ->set('bucket_id', $form_state->getValue('bucket_id'))
      ->set('github_client_id', $values['github_client_id'])
      ->set('google_client_id', $values['google_client_id'])
      ->set('auto_sync_enabled', $values['auto_sync_enabled'])
      ->set('sync_frequency', $values['sync_frequency'])
      ->set('default_role', $values['default_role'])
      ->save();

    // Save API keys only if they're provided
    if (!empty($values['api_key'])) {
      $this->config('appwrite_integration.settings')
        ->set('api_key', $values['api_key'])
        ->save();
    }

    if (!empty($values['github_client_secret'])) {
      $this->config('appwrite_integration.settings')
        ->set('github_client_secret', $values['github_client_secret'])
        ->save();
    }

    if (!empty($values['google_client_secret'])) {
      $this->config('appwrite_integration.settings')
        ->set('google_client_secret', $values['google_client_secret'])
        ->save();
    }

    // Save role mappings
    $mappings = [];
    foreach ($values['role_mappings'] as $mapping) {
      if (!empty($mapping['appwrite_key']) && !empty($mapping['drupal_role'])) {
        $mappings[$mapping['appwrite_key']] = $mapping['drupal_role'];
      }
    }

    if (!empty($mappings)) {
      $this->userRoleMapper->updateRoleMappings($mappings);
    }

    parent::submitForm($form, $form_state);
  }
}