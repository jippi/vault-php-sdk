<?php
namespace Jippi\Vault\Services;

use Jippi\Vault\Client;
use Jippi\Vault\OptionsResolver;

/**
 * This service class handle data read/write
 *
 */
class Data
{
    /**
     * Client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Create a new Data service with an optional Client
     *
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function write($path, $body)
    {
        $params = [
            'body' => json_encode($body)
        ];

        return $this->client->put('/v1/' . $path, $params);
    }

    public function get($path)
    {
        return $this->client->get('/v1/' . $path);
    }

    public function delete($path)
    {
        return $this->client->delete('/v1/' . $path);
    }

}
