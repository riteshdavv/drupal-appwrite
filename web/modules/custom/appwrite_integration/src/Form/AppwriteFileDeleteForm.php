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

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'id' => $this->t('File ID'),
        'preview' => $this->t('Preview'),
        'name' => $this->t('Name'),
        'size' => $this->t('Size'),
        'type' => $this->t('Type'),
        'created' => $this->t('Created'),
      ],
      '#options' => [],
      '#empty' => $this->t('No files available for deletion.'),
    ];

    foreach (array_reverse($files) as $file) {
      $is_image = str_starts_with($file['mimeType'], 'image/');
      $preview = $is_image
        ? '<img src="' . \Drupal\Core\Url::fromRoute('appwrite_integration.file_view', ['file_id' => $file['$id']])->toString() . '" style="max-height:40px;" />'
        : '-';

      $created_timestamp = strtotime($file['$createdAt']);
      $created_relative = \Drupal::service('date.formatter')->formatTimeDiffSince($created_timestamp);
      $created_str = $created_relative . ' ago';

      $size = $file['sizeOriginal'] >= 1048576
        ? sprintf("%.2f MB", $file['sizeOriginal'] / 1048576)
        : sprintf("%.2f KB", $file['sizeOriginal'] / 1024);

      $form['files']['#options'][$file['$id']] = [
        'id' => $file['$id'],
        'preview' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => $preview,
          ],
        ],
        'name' => $file['name'],
        'size' => $size,
        'type' => $file['mimeType'],
        'created' => $created_str,
      ];
    }

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
