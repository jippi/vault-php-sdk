Vault PHP SDK
=============

Installation
------------

This library can be installed with composer:

    composer require jippi/vault-php-sdk

Usage
-----

The simple way to use this SDK, is to instantiate the service factory:

    $sf = new Jippi\Vault\ServiceFactory();

Then, a service could be retrieved from this factory:

    $sys = $sf->get('sys');

All services methods follow the same convention:

    $response = $service->method($mandatoryArgument, $someOptions);

* All API mandatory arguments are placed as first;
* All API optional arguments are directly mapped from `$someOptions`;
* All methods return raw guzzle response.

Examples
--------

The `examples` directory is a pure extract from my own CakePHP 3 app using the SDK - its crude,
but should show the basics of the SDK

Available services
------------------

* sys
* data

License
-------

MIT
