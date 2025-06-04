<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Appwrite\Client;
use Appwrite\Services\Account;

class DashboardController extends ControllerBase {

  public function dashboard(Request $request) {
    $client = new Client();
    $client
      ->setEndpoint('https://fra.cloud.appwrite.io/v1')
      ->setProject('683ea5970037a0cd8c8b')
      ->setSelfSigned(true); // if you're self-hosting with self-signed certs

    // Manually forward the Appwrite session cookie
    $cookies = $request->cookies->all();
    foreach ($cookies as $key => $value) {
      if (str_starts_with($key, 'a_session_')) {
        $client->setCookie($key, $value);
      }
    }

    $account = new Account($client);

    try {
      $user = $account->get();

      $markup = "<h2>Welcome, {$user['name']}</h2>";
      $markup .= "<p>Email: {$user['email']}</p>";
      $markup .= "<a href='/appwrite/logout'><button>Logout</button></a>";

      return [
        '#type' => 'markup',
        '#markup' => $markup,
      ];
    }
    catch (\Exception $e) {
      return new Response('Failed to fetch user info: ' . $e->getMessage(), 403);
    }
  }
}
