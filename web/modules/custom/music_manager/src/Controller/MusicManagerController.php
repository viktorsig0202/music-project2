<?php

namespace Drupal\music_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class MusicManagerController extends ControllerBase {

  /**
   * Returns the search bar and custom content creation links.
   */
  public function searchPage() {
    // Render the search bar form.
    $form = \Drupal::formBuilder()->getForm('Drupal\spotify_search\Form\SpotifySearchForm');

    // Define links to custom content creation forms.
    $links = [
      [
        'title' => $this->t('Create Artist'),
        'url' => Url::fromRoute('music_manager.create_artist'),
      ],
      [
        'title' => $this->t('Create Album'),
        'url' => Url::fromRoute('music_manager.create_album'),
      ],
      [
        'title' => $this->t('Create Track'),
        'url' => Url::fromRoute('music_manager.create_track'),
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

  /**
   * Menu page with links to custom forms.
   */
  public function menuPage() {
    return [
      '#theme' => 'item_list',
      '#items' => [
        [
          '#type' => 'link',
          '#title' => $this->t('Create Artist'),
          '#url' => Url::fromRoute('music_manager.create_artist'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Create Album'),
          '#url' => Url::fromRoute('music_manager.create_album'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Create Track'),
          '#url' => Url::fromRoute('music_manager.create_track'),
        ],
      ],
    ];
  }

  /**
   * Returns the form for creating an artist.
   */
  public function createArtist() {
    return \Drupal::formBuilder()->getForm('Drupal\music_manager\Form\CreateArtistForm');
  }

  /**
   * Returns the form for creating an album.
   */
  public function createAlbum() {
    return \Drupal::formBuilder()->getForm('Drupal\music_manager\Form\CreateAlbumForm');
  }

  /**
   * Returns the form for creating a track.
   */
  public function createTrack() {
    return \Drupal::formBuilder()->getForm('Drupal\music_manager\Form\CreateTrackForm');
  }
}

