<?php

namespace Drupal\appwrite_integration\Service;

use Appwrite\Client;

/**
 * Provides an Appwrite client service.
 */
class AppwriteClient {

  /**
   * Returns an authenticated Appwrite client.
   *
   * @return \Appwrite\Client
   */
  public function getClient(): Client {
    $client = new Client();
    $client
      ->setEndpoint('https://fra.cloud.appwrite.io/v1')
      ->setProject('683700db0032c6996d72')
      ->setKey('');

    return $client;
  }

}
