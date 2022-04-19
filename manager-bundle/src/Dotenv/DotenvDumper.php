<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Dotenv;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

class DotenvDumper
{
    private Filesystem $filesystem;
    private array $parameters = [];

    public function __construct(private string $dotenvFile, Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();

        if (!file_exists($dotenvFile)) {
            return;
        }

        $dotenv = new Dotenv();
        $dotenv->usePutenv(false);

        $parameters = $dotenv->parse(file_get_contents($dotenvFile));

        if (0 !== \count($parameters)) {
            $this->parameters = array_merge($this->parameters, $parameters);
        }
    }

    public function setParameter(string $name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function setParameters(array $params): void
    {
        foreach ($params as $name => $value) {
            $this->setParameter($name, $value);
        }
    }

    public function unsetParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    public function dump(): void
    {
        // Remove the .env file if there are no parameters
        if (0 === \count($this->parameters)) {
            $this->filesystem->remove($this->dotenvFile);

            return;
        }

        $parameters = [];

        foreach ($this->parameters as $key => $value) {
            $parameters[] = $key.'='.$this->escape($value);
        }

        $this->filesystem->dumpFile($this->dotenvFile, implode("\n", $parameters)."\n");
    }

    private function escape($value): bool|int|string
    {
        if (!\is_string($value) || !preg_match('/[$ "\']/', $value)) {
            return $value;
        }

        $quotes = "'";

        if (str_contains($value, "'")) {
            $quotes = '"';
        }

        $mapper = [$quotes => '\\'.$quotes];

        if ('"' === $quotes && str_contains($value, '$')) {
            $mapper['$'] = '\$';
        }

        return $quotes.str_replace(array_keys($mapper), array_values($mapper), $value).$quotes;
    }
}
