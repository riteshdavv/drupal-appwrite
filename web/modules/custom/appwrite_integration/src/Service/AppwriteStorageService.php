<?php

namespace Drupal\appwrite_integration\Service;

use Appwrite\Client;
use Appwrite\Services\Storage;
use Appwrite\InputFile;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

class AppwriteStorageService {

  protected $client;
  protected $storage;
  protected $messenger;

  /**
   * Constructs the AppwriteStorageService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $config = $config_factory->get('appwrite_integration.settings');

    $endpoint = $config->get('endpoint');
    $project_id = $config->get('project_id');

    $this->messenger = $messenger;

    try {
      $this->client = new Client();
      $this->client
        ->setEndpoint($endpoint)
        ->setProject($project_id);

      $this->storage = new Storage($this->client);
    }
    catch (\Throwable $e) {
      $this->messenger->addError('⚠️ Failed to initialize Appwrite client: ' . $e->getMessage());
    }
  }

  /**
   * Upload a file to the configured bucket.
   */

   public function uploadFile(string $bucketId, string $filePath, string $filename): ?array {
    try {
      $inputFile = InputFile::withPath($filePath, mime_content_type($filePath), $filename);
  
      // No explicit permissions passed — uses server privileges
      return $this->storage->createFile(
        $bucketId,
        uniqid(),
        $inputFile,
        null  // Allows upload under server context
      );
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 File upload failed: ' . $e->getMessage());
      return NULL;
    }
  }

        

  /**
   * List all files from a bucket.
   */
  public function listFiles(string $bucketId): ?array {
    try {
      return $this->storage->listFiles($bucketId)['files'] ?? [];
    }
    catch (\Throwable $e) {
      $this->messenger->addError('⚠️ Failed to list files: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Download a file by ID.
   */

  public function downloadFile(string $bucketId, string $fileId) {
    try {
      return $this->storage->getFileDownload($bucketId, $fileId);
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 File download failed: ' . $e->getMessage());
      return NULL;
    }
  }
  

  public function getFile(string $bucketId, string $fileId) {
    try {
      return $this->storage->getFile($bucketId, $fileId);
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 File could not be retrieved: ' . $e->getMessage());
      return NULL;
    }
  }

  public function getFileView(string $bucketId, string $fileId) {
    try {
      return $this->storage->getFileView($bucketId, $fileId);
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 File could not be viewed: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Delete a file from Appwrite.
   */
  public function deleteFile(string $bucketId, string $fileId): bool {
    try {
      $this->storage->deleteFile($bucketId, $fileId);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->messenger->addError('🚫 Failed to delete file: ' . $e->getMessage());
      return FALSE;
    }
  }

}