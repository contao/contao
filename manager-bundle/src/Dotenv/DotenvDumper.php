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
    private string $dotenvFile;
    private Filesystem $filesystem;
    private array $parameters;
    private array $setParameters = [];
    private array $unsetParameters = [];

    public function __construct(string $dotenvFile, Filesystem $filesystem = null)
    {
        $this->dotenvFile = $dotenvFile;
        $this->filesystem = $filesystem ?? new Filesystem();

        if (!file_exists($dotenvFile)) {
            return;
        }

        $dotenv = new Dotenv();
        $dotenv->usePutenv(false);

        $this->parameters = $dotenv->parse(file_get_contents($dotenvFile));
    }

    public function setParameter(string $name, $value): void
    {
        if (($this->parameters[$name] ?? null) === $value) {
            unset($this->setParameters[$name]);

            return;
        }

        $this->setParameters[$name] = $value;
    }

    public function setParameters(array $params): void
    {
        foreach ($params as $name => $value) {
            $this->setParameter($name, $value);
        }
    }

    public function unsetParameter(string $name): void
    {
        unset($this->setParameters[$name]);
        $this->unsetParameters[] = $name;
    }

    public function dump(): void
    {
        $file = '';
        $lines = [];

        if (file_exists($this->dotenvFile)) {
            $lines = preg_split('/\r\n|\r|\n/', file_get_contents($this->dotenvFile));
        }

        if ('' === end($lines)) {
            array_pop($lines);
        }

        foreach ($lines as $line) {
            foreach ($this->setParameters as $name => $value) {
                if (str_starts_with($line, "$name=")) {
                    $file .= $name.'='.$this->escape($value)."\n";
                    unset($this->setParameters[$name]);
                    continue 2;
                }
            }

            foreach ($this->unsetParameters as $name) {
                if (str_starts_with($line, "$name=")) {
                    continue 2;
                }
            }

            $file .= $line."\n";
        }

        foreach ($this->setParameters as $name => $value) {
            $file .= $name.'='.$this->escape($value)."\n";
        }

        // Remove the .env file if there are no parameters
        if ('' === trim($file)) {
            $this->filesystem->remove($this->dotenvFile);

            return;
        }

        // Do not use Filesystem::dumpFile, as this file could be a symlink (see #6065)
        file_put_contents($this->dotenvFile, $file);
    }

    /**
     * @return string|int|bool
     */
    private function escape($value)
    {
        if (!\is_string($value) || !preg_match('/[$ "\']/', $value)) {
            return $value;
        }

        $quotes = "'";

        if (false !== strpos($value, "'")) {
            $quotes = '"';
        }

        $mapper = [$quotes => '\\'.$quotes];

        if ('"' === $quotes && false !== strpos($value, '$')) {
            $mapper['$'] = '\$';
        }

        return $quotes.str_replace(array_keys($mapper), array_values($mapper), $value).$quotes;
    }
}
