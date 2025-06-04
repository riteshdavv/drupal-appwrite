<?php

namespace Drupal\appwrite_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Appwrite settings.
 */
class AppwriteConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['appwrite_integration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appwrite_integration_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('appwrite_integration.settings');

    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Appwrite Endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#description' => $this->t('The Appwrite server endpoint (e.g., https://cloud.appwrite.io/v1)'),
      '#required' => TRUE,
    ];

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#default_value' => $config->get('project_id'),
      '#description' => $this->t('Your Appwrite project ID'),
      '#required' => TRUE,
    ];

    $form['oauth_providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled OAuth Providers'),
      '#options' => [
        'github' => $this->t('GitHub'),
        'google' => $this->t('Google'),
        'facebook' => $this->t('Facebook'),
      ],
      '#default_value' => $config->get('oauth_providers') ?: ['github'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('appwrite_integration.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('oauth_providers', array_filter($form_state->getValue('oauth_providers')))
      ->save();

    parent::submitForm($form, $form_state);
  }
}