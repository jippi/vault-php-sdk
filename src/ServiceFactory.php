<?php
namespace Jippi\Vault;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    private static $services = [
        'sys' => 'Jippi\Vault\Services\Sys',
        'data' => 'Jippi\Vault\Services\Data',
        'auth/token' => 'Jippi\Vault\Services\Auth\Token',
        'auth/approle'=>'Jippi\Vault\Services\Auth\AppRole',
        'auth/awsEc2' =>'Jippi\Vault\Services\Auth\AwsEc2'
    ];

    private $client;

    public function __construct(array $options = array(), LoggerInterface $logger = null, GuzzleClient $guzzleClient = null)
    {
        $this->client = new Client($options, $logger, $guzzleClient);
    }

    public function get($service, array $args = array())
    {
        if (!array_key_exists($service, self::$services)) {
            throw new \InvalidArgumentException(sprintf('The service "%s" is not available. Pick one among "%s".', $service, implode('", "', array_keys(self::$services))));
        }

        $className = self::$services[$service];

        // Use a ReflectionClass object to allow for arbitrary constructor argumnts to be passed through
        $reflection_class = new \ReflectionClass($className);
        // Every class constructed by this factory takes a Jippi\Vault\Client as its first argument, so we ensure that is the fact here
        array_unshift($args, $this->client);
        return $reflection_class->newInstanceArgs($args);
    }
}
