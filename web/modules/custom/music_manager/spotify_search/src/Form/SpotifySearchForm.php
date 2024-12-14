<?php

namespace Drupal\spotify_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_search\Service\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Spotify search form.
 */
class SpotifySearchForm extends FormBase
{

  /**
   * The Spotify search service.
   *
   * @var \Drupal\spotify_search\Service\SpotifySearchService
   */
  protected $spotifySearch;

  /**
   * Constructs a SpotifySearchForm object.
   *
   * @param \Drupal\spotify_search\Service\SpotifySearchService $spotify_search
   *   The Spotify search service.
   */
  public function __construct(SpotifySearchService $spotify_search)
  {
    $this->spotifySearch = $spotify_search;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('spotify_search.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'spotify_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 1;

    if ($step === 1) {
      // Step 1: Search form.
      $form['search'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Search Spotify'),
        '#description' => $this->t('Enter a track, artist, or album name to search Spotify.'),
        '#required' => TRUE,
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
      ];
    }
    elseif ($step === 2) {
      // Step 2: Display search results with radio buttons for selection.
      $search_results = $form_state->get('search_results');
      if (!empty($search_results)) {
        $form['selection'] = [
          '#type' => 'radios',
          '#title' => $this->t('Select a result'),
          '#options' => $search_results,
          '#required' => TRUE,
        ];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('View Details'),
        ];
      }
    }
    elseif ($step === 3) {
      // Step 3: Display detailed information about the selected result.
      $details = $form_state->get('selected_details');
      $form['details'] = [
        '#type' => 'markup',
        '#markup' => $details,
      ];
      $form['actions']['restart'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search Again'),
      ];
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 1;

    if ($step === 1) {
      // Perform the Spotify search.
      $search_term = $form_state->getValue('search');
      $access_token = $this->spotifySearch->getAccessToken();

      if ($access_token) {
        $results = $this->spotifySearch->search($access_token, $search_term);
        if ($results) {
          // Prepare results for radio button options.
          $options = [];
          foreach ($results['tracks'] as $track) {
            $options['track:' . $track['id']] = 'Track: ' . $track['name'];
          }
          foreach ($results['artists'] as $artist) {
            $options['artist:' . $artist['id']] = 'Artist: ' . $artist['name'];
          }
          foreach ($results['albums'] as $album) {
            $options['album:' . $album['id']] = 'Album: ' . $album['name'];
          }

          $form_state->set('search_results', $options);
          $form_state->set('step', 2);
          $form_state->setRebuild();
        }
        else {
          \Drupal::messenger()->addWarning($this->t('No results found for "@term".', ['@term' => $search_term]));
        }
      }
      else {
        \Drupal::messenger()->addError($this->t('Failed to authenticate with Spotify API.'));
      }
    }
    elseif ($step === 2) {
      // Handle selection and fetch detailed information.
      $selection = $form_state->getValue('selection');

      if ($selection) {
        [$type, $id] = explode(':', $selection);

        $access_token = $this->spotifySearch->getAccessToken();
        $details = NULL;

        // Fetch details based on the selected type (track, artist, or album).
        if ($type === 'track') {
          $details = $this->spotifySearch->getTrackDetails($access_token, $id);
        }
        elseif ($type === 'artist') {
          $details = $this->spotifySearch->getArtistDetails($access_token, $id);
        }
        elseif ($type === 'album') {
          $details = $this->spotifySearch->getAlbumDetails($access_token, $id);
        }

        $form_state->set('selected_details', $details);
        $form_state->set('step', 3);
        $form_state->setRebuild();
      }
      else {
        \Drupal::messenger()->addError($this->t('No item was selected.'));
      }
    }
    elseif ($step === 3) {
      // Reset the form for a new search.
      $form_state->set('step', 1);
      $form_state->setRebuild();
    }
  }

}
