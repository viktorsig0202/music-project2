<?php

namespace Drupal\music_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class MusicManagerController extends ControllerBase {

  /**
   * Returns the search bar and content creation links.
   */
  public function searchPage() {
    // Render the search bar form.
    $form = \Drupal::formBuilder()->getForm('Drupal\spotify_search\Form\SpotifySearchForm');

    // Define links to create content for the specified content types.
    $links = [
      [
        'title' => $this->t('Create Artist'),
        'url' => Url::fromRoute('node.add', ['node_type' => 'artist']),
      ],
      [
        'title' => $this->t('Create Album'),
        'url' => Url::fromRoute('node.add', ['node_type' => 'album']),
      ],
      [
        'title' => $this->t('Create Track'),
        'url' => Url::fromRoute('node.add', ['node_type' => 'track']),
      ],
    ];

    // Generate render array for links.
    $render_links = [];
    foreach ($links as $link) {
      $render_links[] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => $link['url'],
      ];
    }

    // Return the render array.
    return [
      'form' => $form,
      'links' => [
        '#theme' => 'item_list',
        '#items' => $render_links,
      ],
    ];
  }
}
