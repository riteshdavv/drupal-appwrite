<?php

namespace Drupal\appwrite_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AppwriteSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['appwrite_integration.settings'];
  }

  public function getFormId() {
    return 'appwrite_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('appwrite_integration.settings');

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appwrite Project ID'),
      '#default_value' => $config->get('project_id'),
      '#required' => TRUE,
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appwrite API Endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appwrite API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('appwrite_integration.settings')
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
