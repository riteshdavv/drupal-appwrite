<?php

namespace Drupal\appwrite_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block to link to Appwrite multiple file delete.
 *
 * @Block(
 *   id = "appwrite_multiple_delete_block",
 *   admin_label = @Translation("Appwrite Multiple Files Delete Block")
 * )
 */
class AppwriteDeleteFilesBlock extends BlockBase {
  public function build() {
    $delete_files_url = Url::fromRoute('appwrite_integration.bulk_delete')->toString();

    $markup = '
      <div class="appwrite-delete-files-buttons">
        <a href="' . $delete_files_url . '"><button class="button appwrite-delete-files">' . $this->t('Delete Files') . '</button></a>
      </div>
    ';

    return [
      '#markup' => $markup,
      '#allowed_tags' => ['div', 'a', 'button'],
    ];
  }
}