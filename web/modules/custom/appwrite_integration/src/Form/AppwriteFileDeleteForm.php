<?php

namespace Drupal\appwrite_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\appwrite_integration\Service\AppwriteStorageService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

class AppwriteFileDeleteForm extends FormBase {

  protected $storageService;
  protected $configFactory;
  protected $messenger;

  public function __construct(AppwriteStorageService $storageService, ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->storageService = $storageService;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('appwrite_integration.storage'),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  public function getFormId() {
    return 'appwrite_file_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $bucket_id = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
    $files = $this->storageService->listFiles($bucket_id);

    if (empty($files)) {
      $this->messenger()->addWarning($this->t('No files found in the bucket.'));
      return ['#markup' => $this->t('No files available for deletion.')];
    }

    $options = [];
    foreach (array_reverse($files) as $file) {
      $options[$file['$id']] = [
        'id' => $file['$id'],
        'name' => $file['name'],
        'size' => sprintf("%.2f KB", $file['sizeOriginal'] / 1024),
        'type' => $file['mimeType'],
      ];
    }

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'id' => $this->t('File ID'),
        'name' => $this->t('Name'),
        'size' => $this->t('Size'),
        'type' => $this->t('Type'),
      ],
      '#options' => $options,
      '#empty' => $this->t('No files available for deletion.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected Files'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bucket_id = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
    $selected = array_filter($form_state->getValue('files'));

    if (empty($selected)) {
      $this->messenger()->addError($this->t('🚫 No files selected for deletion.'));
      return;
    }

    foreach ($selected as $file_id) {
      $success = $this->storageService->deleteFile($bucket_id, $file_id);
      if ($success) {
        $this->messenger()->addStatus($this->t('✅ Deleted file @id', ['@id' => $file_id]));
      } else {
        $this->messenger()->addError($this->t('❌ Failed to delete file @id', ['@id' => $file_id]));
      }
    }
  }
}