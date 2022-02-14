<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\PhpunitExtension;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;

final class GlobalStateWatcher implements AfterTestHook, BeforeTestHook
{
    private string $globalKeys;
    private string $globals;
    private string $staticMembers;
    private string $phpIni;
    private string $setFunctions;
    private string $fileSystem;
    private string $constants;
    private string $env;

    public function executeBeforeTest(string $test): void
    {
        $this->globalKeys = $this->buildGlobalKeys();
        $this->globals = $this->buildGlobals();
        $this->staticMembers = $this->buildStaticMembers();
        $this->phpIni = $this->buildPhpIni();
        $this->setFunctions = $this->buildSetFunctions();
        $this->fileSystem = $this->buildFileSystem();
        $this->constants = $this->buildConstants();
        $this->env = $this->buildEnv();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        foreach (['globalKeys', 'globals', 'staticMembers', 'phpIni', 'setFunctions', 'fileSystem', 'constants', 'env'] as $member) {
            if ($this->$member !== ($after = $this->{'build'.$member}())) {
                throw new ExpectationFailedException(sprintf("\nUnexpected change to global state (%s) in %s\n%s", $member, $test, $this->diff($this->$member, $after)));
            }
        }
    }

    private function diff($before, $after): string
    {
        return (new Differ(new StrictUnifiedDiffOutputBuilder([
            'contextLines' => 10,
            'fromFile' => 'before',
            'toFile' => 'after',
        ])))->diff($before, $after);
    }

    private function buildGlobalKeys(): string
    {
        return "\$GLOBALS['".implode("']\n\$GLOBALS['", array_keys($GLOBALS))."']\n";
    }

    private function buildGlobals(): string
    {
        return print_r($GLOBALS, true);
    }

    private function buildPhpIni(): string
    {
        return print_r(ini_get_all(null, false), true);
    }

    private function buildSetFunctions(): string
    {
        return print_r([
            'setlocale' => setlocale(LC_ALL, '0'),
            'error_reporting' => error_reporting(),
            'date_default_timezone_get' => date_default_timezone_get(),
            'mb_internal_encoding' => mb_internal_encoding(),
            'umask' => umask(),
        ], true);
    }

    private function buildFileSystem(): string
    {
        $root = dirname(__DIR__, 3);

        $files = array_map(
            fn($path) => substr($path, \strlen($root) + 1),
            glob("$root/*-bundle/tests/**/*"),
        );

        sort($files);

        return implode("\n", $files);
    }

    private function buildConstants(): string
    {
        return print_r(get_defined_constants(), true);
    }

    private function buildEnv(): string
    {
        return print_r(
            array_filter(
                getenv(),
                fn ($key) => !\in_array($key, ['SYMFONY_DEPRECATIONS_SERIALIZE', 'SYMFONY_EXPECTED_DEPRECATIONS_SERIALIZE'], true),
                ARRAY_FILTER_USE_KEY,
            ),
            true
        );
    }

    private function buildStaticMembers(): string
    {
        $data = [];

        foreach (get_declared_classes() as $class) {
            if (0 !== strncmp('Contao\\', $class, 7) || 0 === strncmp('Contao\\TestCase\\', $class, 16)) {
                continue;
            }

            foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                if (!$property->isInitialized()) {
                    continue;
                }

                $value = $property->getValue();

                if ($value === $property->getDefaultValue()) {
                    continue;
                }

                if (\is_array($value)) {
                    $value = 'array('.\count($value).')';
                }

                if (\is_object($value)) {
                    $value = \get_class($value);
                }

                $data["$class::{$property->name}"] = $value;
            }
        }

        return substr(print_r($data, true), 8, -2);
    }
}
