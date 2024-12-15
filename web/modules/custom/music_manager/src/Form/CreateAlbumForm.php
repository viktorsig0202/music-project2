<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\spotify_search\Service\SpotifySearchService;
use Drupal\node\Entity\Node;

class CreateAlbumForm extends FormBase {

  protected $spotifySearchService;

  public function __construct(SpotifySearchService $spotifySearchService) {
    $this->spotifySearchService = $spotifySearchService;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('spotify_search.service'));
  }

  public function getFormId() {
    return 'create_album_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['album_name'] = [
      '#type' => 'textfield',
      '#title' => t('Album Name'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'music_manager.spotify_autocomplete',
      '#autocomplete_route_parameters' => ['type' => 'album'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create Album'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $albumName = $form_state->getValue('album_name');
    $spotifyData = $this->spotifySearchService->searchAlbum($albumName);

    if ($spotifyData) {
      // Safely handle the 'image' key.
      $imageUrl = isset($spotifyData['image']) && !empty($spotifyData['image'])
        ? $spotifyData['image']
        : '';

      // Create the node with the Spotify album data.
      $node = Node::create([
        'type' => 'album',
        'title' => $spotifyData['name'],
        'field_album_spotify_id' => $spotifyData['id'],
        'field_artist_name' => $spotifyData['artist'],
        'field_release_date' => $spotifyData['release_date'],
        'field_total_tracks' => $spotifyData['total_tracks'],
        'field_album_image_url' => $imageUrl,
        'field_album_spotify_link' => ['uri' => $spotifyData['url']],
      ]);
      $node->save();

      \Drupal::messenger()->addMessage(t('The album "@name" has been created successfully.', ['@name' => $spotifyData['name']]));
    }
    else {
      \Drupal::messenger()->addError(t('Unable to retrieve data for album "@name".', ['@name' => $albumName]));
    }
  }
}
