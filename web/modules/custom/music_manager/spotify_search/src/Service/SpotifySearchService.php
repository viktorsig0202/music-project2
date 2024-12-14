<?php

namespace Drupal\spotify_search\Service;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a service for interacting with the Spotify API.
 */
class SpotifySearchService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Spotify API client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Spotify API client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Constructs the SpotifySearchService.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param string $client_id
   *   Spotify API client ID.
   * @param string $client_secret
   *   Spotify API client secret.
   */
  public function __construct(Client $http_client, LoggerChannelFactoryInterface $logger_factory, $client_id, $client_secret) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('spotify_search');
    $this->clientId = $client_id;
    $this->clientSecret = $client_secret;
  }

  /**
   * Gets a Spotify API access token.
   *
   * @return string|null
   *   The access token, or NULL on failure.
   */
  public function getAccessToken() {
    $url = 'https://accounts.spotify.com/api/token';

    try {
      $response = $this->httpClient->post($url, [
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ],
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      return $data['access_token'] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching Spotify access token: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
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
    try {
      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => "Bearer $access_token",
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);
      return json_encode($data, JSON_PRETTY_PRINT);
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching details from Spotify: @message', ['@message' => $e->getMessage()]);
      return '<p>Error fetching details.</p>';
    }
  }

}
