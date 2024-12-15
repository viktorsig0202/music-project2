<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\spotify_search\Service\SpotifySearchService;

class CreateArtistForm extends FormBase {

  protected $spotifySearchService;

  public function __construct(SpotifySearchService $spotifySearchService) {
    $this->spotifySearchService = $spotifySearchService;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('spotify_search.service'));
  }

  public function getFormId() {
    return 'create_artist_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['artist_name'] = [
      '#type' => 'textfield',
      '#title' => t('Artist Name'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'music_manager.spotify_autocomplete',
      '#autocomplete_route_parameters' => ['type' => 'artist'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create Artist'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $artistName = $form_state->getValue('artist_name');
    $spotifyData = $this->spotifySearchService->searchArtist($artistName);

    if ($spotifyData) {
      $spotifyId = $spotifyData['id'];
      $genres = implode(', ', $spotifyData['genres']);
      $imageUrl = $spotifyData['images'];
      $spotifyUrl = $spotifyData['url'];

      $node = \Drupal\node\Entity\Node::create([
        'type' => 'artist',
        'title' => $artistName,
        'field_spotify_id' => $spotifyId,
        'field_genres' => $genres,
        'field_image_url' => $imageUrl,
        'field_spotify_link' => $spotifyUrl,
      ]);
      $node->save();

      \Drupal::messenger()->addMessage(t('The artist "@name" has been created successfully.', ['@name' => $artistName]));
    }
    else {
      \Drupal::messenger()->addError(t('Unable to retrieve data for artist "@name".', ['@name' => $artistName]));
    }
  }
}
