<?php

namespace Drupal\appwrite_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\appwrite_integration\Service\AppwriteStorageService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

class FileUploadForm extends FormBase {

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
    return 'appwrite_file_upload_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['enctype'] = 'multipart/form-data'; // VERY IMPORTANT

    $form['upload_files'] = [
      '#type' => 'file',
      '#title' => $this->t('Choose files to upload'),
      '#description' => $this->t('Max 5MB each. You can upload multiple files.'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload Files'),
    ];

    return $form;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bucket_id = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
    $index = 0;

    $validators = ['FileExtension' => []]; // Allow all extensions

    while (isset($_FILES['files']['name']['upload_files'][$index])) {
      $file = file_save_upload('upload_files', $validators, FALSE, $index);
      if (!$file) {
        $index++;
        continue;
      }

      $file_path = $file->getFileUri();
      $real_path = \Drupal::service('file_system')->realpath($file_path);
      $filename = $file->getFilename();
      $filesize = filesize($real_path);

      if ($filesize > 5 * 1024 * 1024) {
        $this->messenger()->addWarning($this->t('⚠️ @file skipped (over 5MB)', ['@file' => $filename]));
      } else {
        $response = $this->storageService->uploadFile($bucket_id, $real_path, $filename);
        if ($response) {
          $this->messenger()->addStatus($this->t('✅ Uploaded @file (ID: @id)', [
            '@file' => $filename,
            '@id' => $response['$id'] ?? 'unknown',
          ]));
        } else {
          $this->messenger()->addError($this->t('🚫 Failed to upload @file.', ['@file' => $filename]));
        }
      }

      $index++;
    }

    if ($index === 0) {
      $this->messenger()->addError($this->t('🚫 No files received or all failed.'));
    }
  }


}