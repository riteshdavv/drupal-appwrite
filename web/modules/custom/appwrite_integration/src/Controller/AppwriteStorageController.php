<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\appwrite_integration\Service\AppwriteStorageService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class AppwriteStorageController extends ControllerBase {

  protected $storageService;
  protected $configFactory;
  protected $messenger;

  public function __construct(
    AppwriteStorageService $storageService,
    ConfigFactoryInterface $configFactory,
    MessengerInterface $messenger
  ) {
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

  public function listFiles() {
    $bucket_id = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
    $files = $this->storageService->listFiles($bucket_id);
    
    // Sort by createdAt descending
    usort($files, function ($a, $b) {
      return strtotime($b['$createdAt']) <=> strtotime($a['$createdAt']);
    });
    
    $header = [
      $this->t('File ID'),
      $this->t('Preview'),
      $this->t('Name'),
      $this->t('Size'),
      $this->t('Created'),
      $this->t('Type'),
      $this->t('View'),
      $this->t('Download'),
      $this->t('Delete'),
    ];
  
    $rows = [];
  
    foreach ($files as $file) {
      $id = $file['$id'];
      $is_image = str_starts_with($file['mimeType'], 'image/');
      $preview_markup = $is_image
        ? '<img src="' . Url::fromRoute('appwrite_integration.file_view', ['file_id' => $id])->toString() . '" alt="' . $file['name'] . '" style="max-height:45px;" />'
        : '-';
  
      $file_size = $file['sizeOriginal'] >= 1048576
        ? sprintf("%.2f MB", $file['sizeOriginal'] / 1048576)
        : sprintf("%.2f KB", $file['sizeOriginal'] / 1024);
  
      $created = \Drupal::service('date.formatter')->formatTimeDiffSince(strtotime($file['$createdAt'])) . ' ago';
  
      $rows[] = [
        $id,
        [
          'data' => [
            '#type' => 'inline_template',
            '#template' => $preview_markup,
          ],
        ],
        $file['name'],
        $file_size,
        $created,
        $file['mimeType'],
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'view' => [
                'title' => $this->t('View'),
                'url' => Url::fromRoute('appwrite_integration.file_view', ['file_id' => $id]),
              ],
            ],
          ],
        ],
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'download' => [
                'title' => $this->t('Download'),
                'url' => Url::fromRoute('appwrite_integration.file_download', ['file_id' => $id]),
              ],
            ],
          ],
        ],
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('appwrite_integration.file_delete', ['file_id' => $id]),
              ],
            ],
          ],
        ],
      ];
    }
  
    $form['files_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No files found in the bucket.'),
    ];
  
    return $form;
  }  


  public function downloadFile($file_id) {
    try {
      $config = $this->configFactory->get('appwrite_integration.settings');
      $bucketId = $config->get('bucket_id');

      // Fetch file stream (content)
      $fileStream = $this->storageService->downloadFile($bucketId, $file_id);

      // Fetch metadata
      $fileMeta = $this->storageService->getFile($bucketId, $file_id);

      // ✅ Extract needed headers
      $filename = $fileMeta['name'] ?? ('appwrite-file-' . $file_id);
      $mimeType = $fileMeta['mimeType'] ?? 'application/octet-stream';

      $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($fileStream) {
        echo $fileStream;
      });

      $response->headers->set('Content-Type', $mimeType);
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

      return $response;
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 Failed to download file: ' . $e->getMessage());
      return $this->redirect('<front>');
    }
  } 

  public function getFileView(string $file_id): Response {
    $bucketId = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
  
    try {
      $fileStream = $this->storageService->downloadFile($bucketId, $file_id);
      $fileMeta = $this->storageService->getFile($bucketId, $file_id);
  
      $mimeType = $fileMeta['mimeType'] ?? 'application/octet-stream';
  
      return new StreamedResponse(function () use ($fileStream) {
        echo $fileStream;
      }, 200, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'inline',
      ]);
  
    } catch (\Throwable $e) {
      $this->messenger()->addError('❌ Failed to view file: ' . $e->getMessage());
      return new RedirectResponse(Url::fromRoute('appwrite_integration.file_list')->toString());
    }
  }

  public function deleteFile($file_id) {
    $bucket_id = $this->configFactory->get('appwrite_integration.settings')->get('bucket_id');
    $file_ids = explode(',', $file_id);
  
    foreach ($file_ids as $id) {
      $id = trim($id);
      if (empty($id)) {
        continue;
      }
  
      if ($this->storageService->deleteFile($bucket_id, $id)) {
        $this->messenger()->addStatus($this->t('✅ Deleted file @id', ['@id' => $id]));
      } else {
        $this->messenger()->addError($this->t('🚫 Failed to delete file @id', ['@id' => $id]));
      }
    }
  
    return $this->redirect('appwrite_integration.file_list');
  } 
  
}
