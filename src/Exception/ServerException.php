<?php
namespace Jippi\Vault\Exception;
use RuntimeException;

class ServerException extends RuntimeException implements VaultExceptionInterface
{

    public $response;

    public function __construct($message, $code, $response)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function response()
    {
        return $this->response;
    }


}
