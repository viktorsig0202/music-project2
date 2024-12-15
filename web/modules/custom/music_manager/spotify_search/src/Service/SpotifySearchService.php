<?php

namespace Drupal\spotify_search\Service;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a service for interacting with the Spotify API.
 */
class SpotifySearchService {

  protected $httpClient;
  protected $logger;
  protected $clientId;
  protected $clientSecret;

  public function __construct(Client $http_client, LoggerChannelFactoryInterface $logger_factory, $client_id, $client_secret) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('spotify_search');
    $this->clientId = $client_id;
    $this->clientSecret = $client_secret;
  }

  /**
   * Get Spotify API access token.
   */
  public function getAccessToken() {
    try {
      $response = $this->httpClient->post('https://accounts.spotify.com/api/token', [
        'headers' => ['Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")],
        'form_params' => ['grant_type' => 'client_credentials'],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data['access_token'] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching Spotify access token: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Search for an artist by name.
   */
  public function searchArtist($name) {
    return $this->searchItem($name, 'artist');
  }

  /**
   * Search for an album by name.
   */
  public function searchAlbum($name) {
    return $this->searchItem($name, 'album');
  }

  /**
   * General search method for items (artist, album, track).
   */
  private function searchItem($name, $type) {
    $access_token = $this->getAccessToken();
    if (!$access_token) return NULL;

    try {
      $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
        'headers' => ['Authorization' => "Bearer $access_token"],
        'query' => ['q' => $name, 'type' => $type, 'limit' => 5],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      if (!empty($data["{$type}s"]['items'][0])) {
        $item = $data["{$type}s"]['items'][0];
        return [
          'id' => $item['id'] ?? '',
          'name' => $item['name'] ?? '',
          'artist' => $item['artists'][0]['name'] ?? '',
          'genres' => $item['genres'] ?? [],
          'images' => $item['images'][0]['url'] ?? '',
          'url' => $item['external_urls']['spotify'] ?? '',
          'release_date' => $item['release_date'] ?? '',
          'total_tracks' => $item['total_tracks'] ?? '',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Error searching for {$type}: @message", ['@message' => $e->getMessage()]);
    }
    return NULL;
  }

  public function search($access_token, $query) {
    $url = 'https://api.spotify.com/v1/search';

    try {
      $response = $this->httpClient->get($url, [
        'headers' => ['Authorization' => "Bearer $access_token"],
        'query' => ['q' => $query, 'type' => 'track,artist,album', 'limit' => 3],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      $results = ['tracks' => [], 'artists' => [], 'albums' => []];

      // Extract tracks, artists, albums.
      if (!empty($data['tracks']['items'])) {
        foreach ($data['tracks']['items'] as $item) {
          $results['tracks'][] = ['name' => $item['name'], 'id' => $item['id']];
        }
      }
      if (!empty($data['artists']['items'])) {
        foreach ($data['artists']['items'] as $item) {
          $results['artists'][] = ['name' => $item['name'], 'id' => $item['id']];
        }
      }
      if (!empty($data['albums']['items'])) {
        foreach ($data['albums']['items'] as $item) {
          $results['albums'][] = ['name' => $item['name'], 'id' => $item['id']];
        }
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error searching Spotify: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  public function getTrackDetails($access_token, $id) {
    return $this->fetchDetails($access_token, "https://api.spotify.com/v1/tracks/$id", 'track');
  }

  public function getArtistDetails($access_token, $id) {
    return $this->fetchDetails($access_token, "https://api.spotify.com/v1/artists/$id", 'artist');
  }

  public function getAlbumDetails($access_token, $id) {
    return $this->fetchDetails($access_token, "https://api.spotify.com/v1/albums/$id", 'album');
  }

  private function fetchDetails($access_token, $url, $type) {
    try {
      $response = $this->httpClient->get($url, ['headers' => ['Authorization' => "Bearer $access_token"]]);
      $data = json_decode($response->getBody(), TRUE);

      if ($type === 'track') {
        return [
          'name' => $data['name'],
          'album' => $data['album']['name'],
          'artist' => $data['artists'][0]['name'],
          'duration' => round($data['duration_ms'] / 60000, 2),
          'popularity' => $data['popularity'],
          'link' => $data['external_urls']['spotify'],
        ];
      }
      elseif ($type === 'artist') {
        return [
          'name' => $data['name'],
          'followers' => $data['followers']['total'],
          'genres' => implode(', ', $data['genres']),
          'image' => $data['images'][0]['url'] ?? '',
          'link' => $data['external_urls']['spotify'],
        ];
      }
      elseif ($type === 'album') {
        return [
          'name' => $data['name'],
          'artist' => $data['artists'][0]['name'],
          'release_date' => $data['release_date'],
          'total_tracks' => $data['total_tracks'],
          'link' => $data['external_urls']['spotify'],
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching details: @message', ['@message' => $e->getMessage()]);
    }
    return NULL;
  }
}

