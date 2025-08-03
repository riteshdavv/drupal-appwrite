<?php

namespace Drupal\appwrite_integration\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\NodeInterface;
use Drupal\appwrite_integration\Service\AppwriteDocumentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Subscribes to node insert and update events.
 */
class NodeSyncSubscriber implements EventSubscriberInterface {

  protected $config;
  protected $messenger;
  protected $appwriteService;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    AppwriteDocumentService $appwrite_document
  ) {
    $this->config = $config_factory->get('appwrite_integration.settings');
    $this->messenger = $messenger;
    $this->appwriteService = $appwrite_document_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['entity.insert'][] = ['syncNodeToAppwrite'];
    $events['entity.update'][] = ['syncNodeToAppwrite'];
    return $events;
  }

  /**
   * Sync eligible nodes to Appwrite when created or updated.
   */
  public function syncNodeToAppwrite(Event $event) {
    /* Selected roles can trigger Appwrite sync */
    $current_user = \Drupal::currentUser();
    $allowed_roles = $this->config->get('allowed_roles_for_sync') ?: [];

    if (empty(array_intersect($current_user->getRoles(), $allowed_roles))) {
      $this->messenger->addWarning("You don't have permission to sync this content to Appwrite.");
      return;
    }

    $entity = $event->getEntity();

    if (!$entity instanceof NodeInterface) {
      return;
    }
    $enabled = $this->config->get('enable_document_sync');
    $allowed_types = $this->config->get('sync_content_types') ?? [];

    if (!$enabled || !in_array($entity->bundle(), $allowed_types)) {
      return;
    }
    $database_id = $this->config->get('database_id');
    $collection_id = $this->config->get('collection_id');

    $document_id = 'node-' . $entity->id();
    $payload = $this->serializeNode($entity);
    $result = $this->appwriteService->createDocument($database_id, $collection_id, $document_id, $payload);

    if ($result) {
      $this->messenger->addStatus('Node ' . $entity->id() . ' synced to Appwrite.');
    }
    else {
      $this->messenger->addError('Failed to sync node ' . $entity->id() . ' to Appwrite.');
    }

    /* Write to Table */
    $db = \Drupal::database();
    try {
      $db->merge('appwrite_node_sync_map')
        ->key(['nid' => $entity->id()])
        ->fields([
          'document_id' => $document_id,
          'status' => $result ? 'success' : 'failed',
          'last_synced' => \Drupal::time()->getCurrentTime(),
          'error_message' => $result ? NULL : 'Failed to sync document',
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger->addError('DB error while writing sync record: ' . $e->getMessage());
    }
  }

  /**
   * Convert node to Appwrite-friendly JSON array.
   */
  protected function serializeNode(NodeInterface $node) {
    $data = [];

    $data['nid'] = $node->id();
    $data['uuid'] = $node->uuid();
    $data['type'] = $node->bundle();
    $data['title'] = $node->label();
    $data['created'] = $node->getCreatedTime();
    $data['changed'] = $node->getChangedTime();
    $data['author'] = $node->getOwner()->getDisplayName();

    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $data['body'] = $node->get('body')->value;
    }

    return $data;
  }

}