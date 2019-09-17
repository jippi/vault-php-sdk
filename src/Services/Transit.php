<?php
namespace Jippi\Vault\Services;

use Jippi\Vault\Client;
use Jippi\Vault\OptionsResolver;

/**
 * This service class handle all Vault HTTP API endpoints starting in /transit/
 *
 */
class Transit
{
    /**
     * Client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Create a new Sys service with an optional Client
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function getKey($keyName)
    {
		return $this->client->get('/v1/transit/keys/' . urlencode($keyName));
    }

    public function createKey($keyName, array $body = [])
    {
		$body = OptionsResolver::resolve($body, ['type', 'derived', 'convergent_encryption']);
		$body = OptionsResolver::required($body, ['type']);

		$params = [
			'body' => json_encode($body)
		];

		return $this->client->put('/transit/keys/' . urlencode($keyName), $params);
    }

    public function rotateKey($keyName)
    {
        return $this->client->post('/transit/keys/' . urlencode($keyName) . '/rotate');
    }

    public function encrypt($keyName, $plainText, array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['context', 'nonce']);
        $body['plaintext'] = base64_encode($plainText);

        $params = [
            'body' => json_encode($body)
        ];

        $response = $this->client->post('/transit/encrypt/' . urlencode($keyName), $params);
        return json_decode($response->getBody(), true)['data']['ciphertext'];
    }

    public function decrypt($keyName, $cipherText, array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['context', 'nonce']);
        $body['ciphertext'] = $cipherText;

        $params = [
            'body' => json_encode($body)
        ];

        $response = $this->client->post('/transit/decrypt/' . urlencode($keyName), $params);
        return base64_decode(json_decode($response->getBody(), true)['data']['plaintext']);
    }

    public function rewrap($keyName, $cipherText, array $body = [])
    {
        $body = OptionsResolver::resolve($body, ['context', 'nonce']);
        $body['ciphertext'] = $cipherText;

        $params = [
            'body' => json_encode($body)
        ];

        $response = $this->client->post('/transit/rewrap/' . urlencode($keyName), $params);
        return json_decode($response->getBody(), true)['data']['ciphertext'];
    }
}