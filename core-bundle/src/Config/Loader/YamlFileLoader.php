<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Loader;

use Contao\CoreBundle\Translation\LegacyGlobalsProcessor;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Translation\Loader\YamlFileLoader as SymfonyYamlFileLoader;

/**
 * Reads YAML files and converts them into Contao language arrays.
 */
class YamlFileLoader extends Loader
{
    public function __construct(private readonly bool $addToGlobals = false)
    {
        parent::__construct();
    }

    public function load(mixed $resource, string|null $type = null): string
    {
        return $this->convertYamlToPhp((string) $resource, $type ?: 'en');
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return 'yaml' === Path::getExtension((string) $resource, true);
    }

    private function convertYamlToPhp(string $name, string $language): string
    {
        $return = '';
        $translations = new SymfonyYamlFileLoader()->load($name, $language)->all('messages');

        foreach ($translations as $key => $value) {
            $parts = LegacyGlobalsProcessor::getPartsFromKey($key);
            $return .= LegacyGlobalsProcessor::getStringRepresentation($parts, $value);

            if ($this->addToGlobals) {
                LegacyGlobalsProcessor::addGlobal($parts, $value);
            }
        }

        return $return;
    }
}
