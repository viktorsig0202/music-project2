<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling search results.
 */
class SearchResultsController extends ControllerBase {

  /**
   * Display search results.
   */
  public function results(Request $request) {
    $query = $request->query->get('query');

    if (!$query) {
      return [
        '#markup' => $this->t('No search query provided.'),
      ];
    }

    // Call Spotify and Discogs services.
    $spotifyService = \Drupal::service('spotify_lookup.service');
    $discogsService = \Drupal::service('discogs_lookup.service');

    $spotifyResults = $spotifyService->search($query);
    $discogsResults = $discogsService->search($query);

    return [
      '#theme' => 'item_list',
      '#items' => [
        'Spotify Results' => $spotifyResults,
        'Discogs Results' => $discogsResults,
      ],
      '#title' => $this->t('Search Results for @query', ['@query' => $query]),
    ];
  }
}
