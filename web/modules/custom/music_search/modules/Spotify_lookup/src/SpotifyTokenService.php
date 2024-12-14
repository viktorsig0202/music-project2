<?php

namespace Drupal\spotify_lookup;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

class SpotifyTokenService {

  protected $httpClient;
  protected $configFactory;
  protected $logger;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  public function getAccessToken() {
    // Retrieve client_id and client_secret from the config.
    $config = $this->configFactory->get('spotify_lookup.settings');
    $clientId = $config->get('client_id');
    $clientSecret = $config->get('client_secret');

    if (!$clientId || !$clientSecret) {
      $this->logger->error('Spotify credentials are missing.');
      return NULL;
    }

    try {
      // Create the HTTP request to Spotify's token endpoint.
      $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
        'auth' => [$clientId, $clientSecret], // Basic Auth with client_id and client_secret
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (isset($data['access_token'])) {
        return $data['access_token'];
      }
      else {
        $this->logger->error('Spotify token response does not contain access_token.');
        return NULL;
      }

    } catch (\Exception $e) {
      // Log any exceptions during the request.
      $this->logger->error('Failed to fetch Spotify access token: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }
}
