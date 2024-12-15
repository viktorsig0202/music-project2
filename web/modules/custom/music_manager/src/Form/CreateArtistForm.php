<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_search\Service\SpotifySearchService;

/**
 * Provides a form to create an Artist with Spotify autocomplete.
 */
class CreateArtistForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_artist_form';
  }

  /**
   * The Spotify search service.
   *
   * @var \Drupal\spotify_search\Service\SpotifySearchService
   */
  protected $spotifySearch;

  /**
   * Constructs a new form instance.
   */
  public function __construct() {
    $this->spotifySearch = \Drupal::service('spotify_search.service');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Artist name with autocomplete.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist Name'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'music_manager.spotify_autocomplete',
      '#autocomplete_route_parameters' => ['type' => 'artist'],
      '#ajax' => [
        'callback' => '::autocompleteCallback',
        'event' => 'autocompleteclose',
        'wrapper' => 'spotify-details-wrapper',
      ],
    ];

    $form['spotify_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];


// Wrapper for Spotify details.
    $form['spotify_details'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'spotify-details-wrapper'],
    ];
    // If Spotify data exists in the form state, populate details.
    if ($form_state->get('spotify_data')) {
      $data = $form_state->get('spotify_data');
      $form['spotify_details']['genres'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Genres'),
        '#default_value' => implode(', ', $data['genres'] ?? []),
      ];
      $form['spotify_details']['followers'] = [
        '#type' => 'number',
        '#title' => $this->t('Followers'),
        '#default_value' => $data['followers'] ?? 0,
      ];
    }

    // Submit button to save the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Artist'),
    ];

    return $form;
  }

  /**
   * AJAX callback method to update Spotify details dynamically.
   */
  public function autocompleteCallback(array &$form, FormStateInterface $form_state) {
    $selected_value = $form_state->getValue('name');

    if ($selected_value && strpos($selected_value, ' - ') !== FALSE) {
      [$name, $id] = explode(' - ', $selected_value, 2);

      // Ensure values are not null.
      $name = $name ?? '';
      $id = $id ?? '';

      // Update the form values.
      $form_state->setValue('name', $name);
      $form_state->setValue('spotify_id', $id);

      // Fetch Spotify details using ID.
      if (!empty($id)) {
        $access_token = $this->spotifySearch->getAccessToken();
        $details = $this->spotifySearch->getArtistDetails($access_token, $id);

        if (!empty($details)) {
          $form_state->set('spotify_data', [
            'genres' => $details['genres'] ?? [],
            'followers' => $details['followers']['total'] ?? 0,
            'images' => $details['images'] ?? [],
            'id' => $id,
            'name' => $name,

          ]);
        }
      }
    }

    return $form['spotify_details'];
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a new node of type 'artist'.
    $node = \Drupal\node\Entity\Node::create([
      'type' => 'artist',
      'title' => $form_state->getValue('name'), // Save the name as the title.
      'field_spotify_id' => $form_state->getValue('spotify_id'), // Save the ID in the Spotify ID field.
      'field_genres' => $form_state->getValue(['spotify_details', 'genres']),
      'field_followers' => $form_state->getValue(['spotify_details', 'followers']),
    ]);

    $node->save();

    \Drupal::messenger()->addMessage($this->t('The artist "@name" has been created successfully.', [
      '@name' => $form_state->getValue('name'),
    ]));
  }

}
