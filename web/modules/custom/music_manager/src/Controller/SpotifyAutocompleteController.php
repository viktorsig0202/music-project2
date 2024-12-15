<?php

namespace Drupal\music_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\spotify_search\Service\SpotifySearchService;

/**
 * Handles Spotify Autocomplete requests.
 */
class SpotifyAutocompleteController extends ControllerBase {

  /**
   * The Spotify search service.
   *
   * @var \Drupal\spotify_search\Service\SpotifySearchService
   */
  protected $spotifySearch;

  /**
   * Constructs the controller with SpotifySearchService.
   */
  public function __construct() {
    $this->spotifySearch = \Drupal::service('spotify_search.service');
  }

  /**
   * Handles autocomplete requests to Spotify API.
   */
  public function handleAutocomplete(Request $request, $type) {
    $search_term = $request->query->get('q');
    $results = [];

    if ($search_term) {
      $access_token = $this->spotifySearch->getAccessToken();
      if ($access_token) {
        // Perform search based on type.
        $spotify_results = $this->spotifySearch->search($access_token, $search_term);

        if ($type === 'track' && !empty($spotify_results['tracks'])) {
          foreach ($spotify_results['tracks'] as $track) {
            $results[] = [
              'value' => $track['name'],
              'label' => $track['id'] . ' - ' . $track['name'],
            ];
          }
        }
        elseif ($type === 'artist' && !empty($spotify_results['artists'])) {
          foreach ($spotify_results['artists'] as $artist) {
            $results[] = [
              'value' => $artist['name'],
              'label' => $artist['id'] . ' - ' . $artist['name'],
            ];
          }
        }
        elseif ($type === 'album' && !empty($spotify_results['albums'])) {
          foreach ($spotify_results['albums'] as $album) {
            $results[] = [
              'value' => $album['name'],
              'label' => $album['id'] . ' - ' . $album['name'],
            ];
          }
        }
      }
    }

    return new JsonResponse($results);
  }
}
