<?php

namespace Drupal\appwrite_integration\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Appwrite\Client;
use Appwrite\Services\Account;

class AuthController extends ControllerBase {

  public function success(Request $request) {
    $data = json_decode($request->getContent(), true);
    $secret = $data['secret'] ?? null;

    if (!$secret) {
      return new JsonResponse(['error' => 'Token missing'], 400);
    }

    // Initialize Appwrite Client
    $client = new Client();
    $client
      ->setEndpoint('https://fra.cloud.appwrite.io/v1') // Replace with your actual endpoint
      ->setProject('683ea5970037a0cd8c8b') // Replace with your actual project ID
      ->setSession($secret); // <-- Use the session token here

    $account = new Account($client);
    $session = $account->getSession('current');

    try {
      $user = $account->get();
      // Store user info or log them in, optionally
      \Drupal::messenger()->addMessage('Welcome, ' . $user['name']);
      return new JsonResponse(['status' => 'Login successful']);
    }
    catch (\Exception $e) {
      \Drupal::logger('appwrite_integration')->error($e->getMessage());
      return new JsonResponse(['error' => 'OAuth failed: ' . $e->getMessage()], 500);
    }
  }
}
