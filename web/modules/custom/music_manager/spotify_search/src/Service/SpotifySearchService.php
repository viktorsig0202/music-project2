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
    $access_token = $this->getAccessToken();
    if (!$access_token) return NULL;

    try {
      $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
        'headers' => ['Authorization' => "Bearer $access_token"],
        'query' => ['q' => $name, 'type' => 'artist', 'limit' => 5],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      if (!empty($data['artists']['items'][0])) {
        $artist = $data['artists']['items'][0];
        return [
          'id' => $artist['id'] ?? '',
          'name' => $artist['name'] ?? '',
          'genres' => $artist['genres'] ?? [],
          'images' => $artist['images'][0]['url'] ?? '',
          'url' => $artist['external_urls']['spotify'] ?? '',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error searching for artist: @message', ['@message' => $e->getMessage()]);
    }
    return NULL;
  }


  public function search($access_token, $query) {
    $url = 'https://api.spotify.com/v1/search';

    try {
      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => "Bearer $access_token",
        ],
        'query' => [
          'q' => $query,
          'type' => 'track,artist,album',
          'limit' => 3,
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      $results = [
        'tracks' => [],
        'artists' => [],
        'albums' => [],
      ];

      // Extract tracks (name and ID).
      if (!empty($data['tracks']['items'])) {
        foreach ($data['tracks']['items'] as $item) {
          $results['tracks'][] = [
            'name' => $item['name'],
            'id' => $item['id'],
          ];
        }
      }

      // Extract artists (name and ID).
      if (!empty($data['artists']['items'])) {
        foreach ($data['artists']['items'] as $item) {
          $results['artists'][] = [
            'name' => $item['name'],
            'id' => $item['id'],
          ];
        }
      }

      // Extract albums (name and ID).
      if (!empty($data['albums']['items'])) {
        foreach ($data['albums']['items'] as $item) {
          $results['albums'][] = [
            'name' => $item['name'],
            'id' => $item['id'],
          ];
        }
      }

      return $results;

    } catch (\Exception $e) {
      $this->logger->error('Error searching Spotify: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }
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

  }
  public function getTrackDetails($access_token, $id) {
    $url = "https://api.spotify.com/v1/tracks/$id";
    $type = 'track';
    return $this->fetchDetails($access_token, $url, $type);
  }

  public function getArtistDetails($access_token, $id) {
    $url = "https://api.spotify.com/v1/artists/$id";
    $type = 'artist';
    return $this->fetchDetails($access_token, $url, $type);
  }

  public function getAlbumDetails($access_token, $id) {
    $url = "https://api.spotify.com/v1/albums/$id";
    $type = 'album';
    return $this->fetchDetails($access_token, $url,$type);
  }

  private function fetchDetails($access_token, $url, $type) {
    if ($type === 'track') {
      $fields = 'name,album.name,artists.name,duration_ms,popularity,external_urls.spotify';
    }
    elseif ($type === 'artist') {
      $fields = 'name,followers.total,genres,external_urls.spotify';
    }
    elseif ($type === 'album') {
      $fields = 'name,artists.name,release_date,total_tracks,external_urls.spotify';
    }
    else {
      return '<p>Invalid type.</p>';
    }
    try {
      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => "Bearer $access_token",
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      $details = '';

      if ($type === 'track') {
        $details .= '<p><strong>Track:</strong> ' . $data['name'] . '</p>';
        $details .= '<p><strong>Album:</strong> ' . $data['album']['name'] . '</p>';
        $details .= '<p><strong>Artist:</strong> ' . $data['artists'][0]['name'] . '</p>';
        $details .= '<p><strong>Duration:</strong> ' . round($data['duration_ms'] / 60000, 2) . ' minutes</p>';
        $details .= '<p><strong>Popularity:</strong> ' . $data['popularity'] . '</p>';
        $details .= '<p><a href="' . $data['external_urls']['spotify'] . '" target="_blank">Listen on Spotify</a></p>';
      }
      elseif ($type === 'artist') {
        $details .= '<p><strong>Artist:</strong> ' . $data['name'] . '</p>';
        $details .= '<p><strong>Followers:</strong> ' . $data['followers']['total'] . '</p>';
        $details .= '<p><strong>Genres:</strong> ' . implode(', ', $data['genres']) . '</p>';
        $details .= '<p><img src="' . $data['images'][0]['url'] . '"></p>';
        $details .= '<p><a href="' . $data['external_urls']['spotify'] . '" target="_blank">View on Spotify</a></p>';
      }
      elseif ($type === 'album') {
        $details .= '<p><strong>Album:</strong> ' . $data['name'] . '</p>';
        $details .= '<p><strong>Artist:</strong> ' . $data['artists'][0]['name'] . '</p>';
        $details .= '<p><strong>Release Date:</strong> ' . $data['release_date'] . '</p>';
        $details .= '<p><strong>Total Tracks:</strong> ' . $data['total_tracks'] . '</p>';
        $details .= '<p><a href="' . $data['external_urls']['spotify'] . '" target="_blank">View on Spotify</a></p>';
      }
      return $details;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching details from Spotify: @message', ['@message' => $e->getMessage()]);
      return '<p>Error fetching details.</p>';
    }
  }

}
