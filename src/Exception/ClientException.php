<?php
namespace Jippi\Vault\Exception;

use RuntimeException;

class ClientException extends RuntimeException implements VaultExceptionInterface
{

    public $response;

    public function __construct($message, $code = null, $response = null)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function response()
    {
        return $this->response;
    }

}
