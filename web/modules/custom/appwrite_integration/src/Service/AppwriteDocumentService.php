<?php

namespace Drupal\appwrite_integration\Service;

use Appwrite\Client;
use Appwrite\Services\Databases;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Exception;

class AppwriteDocumentService {

  protected $client;
  protected $databases;
  protected $config;
  protected $messenger;

  /**
   * Constructs the object for AppwriteDocumentService.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->config = $config_factory->get('appwrite_integration.settings');
    $this->messenger = $messenger;

    $endpoint = $this->config->get('appwrite_endpoint');
    $project_id = $this->config->get('appwrite_project_id');
    $api_key = $this->config->get('appwrite_api_key');

    try {
      $this->client = (new Client())
        ->setEndpoint($endpoint)
        ->setProject($project_id)
        ->setKey($api_key);

      $this->databases = new Databases($this->client);
    }
    catch (Exception $e) {
      $this->messenger->addError('Failed to initialize Appwrite client: ' . $e->getMessage());
    }
  }

  /**
   * Create a new document in Appwrite DB.
   */
  public function createDocument(string $databaseId, string $collectionId, string $documentId, array $data) {
    try {
      return $this->databases->createDocument($databaseId, $collectionId, $documentId, $data);
    }
    catch (Exception $e) {
      $this->messenger->addError('Error creating Appwrite document: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Update an existing Appwrite document.
   */
  public function updateDocument(string $databaseId, string $collectionId, string $documentId, array $data) {
    try {
      return $this->databases->updateDocument($databaseId, $collectionId, $documentId, $data);
    }
    catch (Exception $e) {
      $this->messenger->addError('Error updating Appwrite document: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Delete a document from Appwrite.
   */
  public function deleteDocument(string $databaseId, string $collectionId, string $documentId) {
    try {
      return $this->databases->deleteDocument($databaseId, $collectionId, $documentId);
    }
    catch (Exception $e) {
      $this->messenger->addError('Error deleting Appwrite document: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Fetch a document from Appwrite.
   */
  public function getDocument(string $databaseId, string $collectionId, string $documentId) {
    try {
      return $this->databases->getDocument($databaseId, $collectionId, $documentId);
    }
    catch (Exception $e) {
      $this->messenger->addError('Error fetching Appwrite document: ' . $e->getMessage());
      return NULL;
    }
  }

}