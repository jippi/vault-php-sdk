<?php
namespace Jippi\Vault;

class OptionsResolver
{
    public static function resolve(array $options, array $availableOptions)
    {
        return array_intersect_key($options, array_flip($availableOptions));
    }

    public static function required(array $options, array $requiredOptions)
    {
        $diff = array_diff($requiredOptions, array_keys($options));
        if (empty($diff)) {
            return $options;
        }

        throw new Exception\ClientException('Missing required arguments: ' . join($diff, ','));
    }
}
