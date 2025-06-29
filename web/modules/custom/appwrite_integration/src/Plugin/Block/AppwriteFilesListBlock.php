<?php

namespace Drupal\appwrite_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to link to Appwrite files list.
 *
 * @Block(
 *   id = "appwrite_files_list_block",
 *   admin_label = @Translation("Appwrite Files List Block")
 * )
 */
class AppwriteFilesListBlock extends BlockBase {
  public function build() {
    $list_files_url = Url::fromRoute('appwrite_integration.file_list')->toString();

    $markup = '
      <div class="appwrite-files-buttons">
        <a href="' . $list_files_url . '"><button class="button appwrite-list-files">Files List</button></a>
      </div>
    ';

    return [
      '#markup' => $markup,
      '#allowed_tags' => ['div', 'a', 'button'],
    ];
  }
}