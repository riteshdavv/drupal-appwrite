<?php

namespace Drupal\appwrite_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Appwrite\Client;
use Appwrite\Services\Account;

class LogoutController extends ControllerBase {

  public function logout(Request $request) {
    $client = new Client();
    $client
        ->setEndpoint('https://fra.cloud.appwrite.io/v1')
        ->setProject('683ea5970037a0cd8c8b')
        ->setSelfSigned(true);

    // Pass session cookie manually
    $cookies = $request->cookies->all();
    foreach ($cookies as $key => $value) {
        if (str_starts_with($key, 'a_session_')) {
        $client->setCookie($key, $value);
        }
    }

    $account = new Account($client);

    try {
        $account->deleteSession('current');
        return new RedirectResponse('/');
    }
    catch (\Exception $e) {
        return new Response('Logout failed: ' . $e->getMessage(), 403);
    }
  }

}
