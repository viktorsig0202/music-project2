<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class MusicSearchController extends ControllerBase {

  public function searchForm() {
    return [
      '#markup' => $this->t('Search functionality here.'),
    ];
  }
}
