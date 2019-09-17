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

        return $this->client->put('/' . $path, $params);
    }

    public function get($path)
    {
        return $this->client->get('/' . $path);
    }

    public function delete($path)
    {
        return $this->client->delete('/' . $path);
    }

    public function list($path) 
    {
        return $this->client->list('/' . $path);
    }
}
