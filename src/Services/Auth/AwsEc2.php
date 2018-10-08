<?php

namespace Jippi\Vault\Services\Auth;


use Jippi\Vault\Client;


/**
 * This service class handles Vault HTTP API endpoints for the AWS auth backend
 */
class AwsEc2
{
    /**
     * Client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Constructor - initialize Client field
     * @param Client|null $client
     * @param string|'/v1/auth/aws' $mountPoint
     */
    public function __construct(Client $client = null, $mountPoint = null)
    {
        $this->client = $client ?: new Client();
        $this->mountPoint = $mountPoint ?: 'aws';
    }

    /**
     *  Issues a Vault token based on the presented credentials and a request to the AWS EC2 auth backend.
     * @param string $pkcs7
     * @param string $nonce
     * @param string $role
     * @return mixed
     */
    public function login($pkcs7, $nonce = null, $role = null)
    {
        $nonce = $nonce ?: '';
        $role = $role ?: '';
        $body = ['pkcs7' => $pkcs7, 'nonce' => $nonce, 'role' => $role];
        $params = [
            'body' => json_encode($body)
        ];
        return \GuzzleHttp\json_decode($this->client->post("/v1/auth/$this->mountPoint/login", $params)->getBody());
    }


}
