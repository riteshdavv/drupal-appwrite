<?php

namespace Drupal\appwrite_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to link to Appwrite file upload.
 *
 * @Block(
 *   id = "appwrite_upload_block",
 *   admin_label = @Translation("Appwrite Upload Block")
 * )
 */
class AppwriteUploadBlock extends BlockBase {
  public function build() {
    $upload_file_url = Url::fromRoute('appwrite_integration.file_upload')->toString();

    $markup = '
      <div class="appwrite-files-buttons">
        <a href="' . $upload_file_url . '"><button class="button appwrite-upload">Upload File</button></a>
      </div>
    ';

    return [
      '#markup' => $markup,
      '#allowed_tags' => ['div', 'a', 'button'],
    ];
  }
}
