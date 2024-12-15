<?php

namespace Drupal\music_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\spotify_search\Service\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Spotify autocomplete.
 */
class SpotifyAutocompleteController extends ControllerBase {

  protected $spotifySearchService;

  public function __construct(SpotifySearchService $spotifySearchService) {
    $this->spotifySearchService = $spotifySearchService;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('spotify_search.service'));
  }

  /**
   * Handles autocomplete requests.
   */
  public function handleAutocomplete(Request $request, $type) {
    $results = [];
    $query = $request->query->get('q');

    if ($query && $type === 'artist') {
      $spotifyData = $this->spotifySearchService->searchArtist($query);
      if ($spotifyData) {
        $results[] = [
          'value' => $spotifyData['name'],
          'label' => $spotifyData['name'] . ' (' . implode(', ', $spotifyData['genres']) . ')',
        ];
      }
    }
    elseif ($query && $type === 'track') {
      $spotifyData = $this->spotifySearchService->searchTrack($query);
      if ($spotifyData) {
        $results[] = [
          'value' => $spotifyData['name'],
          'label' => $spotifyData['name'] . ' (' . $spotifyData['album'] . ')',
        ];
      }
    }
    elseif ($query && $type === 'album') {
      $spotifyData = $this->spotifySearchService->searchAlbum($query);
      if ($spotifyData) {
        $results[] = [
          'value' => $spotifyData['name'],
          'label' => $spotifyData['name'] . ' (' . $spotifyData['artist'] . ')',
        ];
      }
    }

    return new JsonResponse($results);
  }
}
