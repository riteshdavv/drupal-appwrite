<?php

namespace Drupal\appwrite_integration\Controller;

use Symfony\Component\HttpFoundation\Response;

class BridgeController {

  public function bridge() {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appwrite Token Bridge</title>
</head>
<body>
  <h1>Appwrite Token Bridge</h1>
  <script>
    const hash = window.location.hash;
    const params = new URLSearchParams(hash.substring(1));
    const secret = params.get("secret");

    if (secret) {
      fetch("/appwrite/success", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ secret })
      }).then(() => {
        window.location.href = "/";
      }).catch(() => {
        document.body.innerHTML = "Something went wrong.";
      });
    } else {
      document.body.innerHTML = "No token received.";
    }
  </script>
  <noscript>Please enable JavaScript to complete login.</noscript>
</body>
</html>
HTML;

    return new Response($html);
  }

}
