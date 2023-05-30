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

    private function diff(string $before, string $after): string
    {
        $options = [
            'contextLines' => 10,
            'fromFile' => 'before',
            'toFile' => 'after',
        ];

        return (new Differ(new StrictUnifiedDiffOutputBuilder($options)))->diff($before, $after);
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
        return print_r(
            [
                'setlocale' => setlocale(LC_ALL, '0'),
                'error_reporting' => error_reporting(),
                'date_default_timezone_get' => date_default_timezone_get(),
                'mb_internal_encoding' => mb_internal_encoding(),
                'mb_substitute_character' => mb_substitute_character(),
                'umask' => umask(),
                'getcwd' => getcwd(),
                'get_include_path' => get_include_path(),
                'ob_get_level' => ob_get_level(),
                'libxml_get_errors' => libxml_get_errors(),
                'stream_get_wrappers' => stream_get_wrappers(),
                'stream_get_filters' => stream_get_filters(),
                'preg_last_error' => preg_last_error(),
                'http_response_code' => http_response_code(),
                'headers_list' => headers_list(),
            ],
            true
        );
    }

    private function buildFileSystem(): string
    {
        $root = \dirname(__DIR__, 3);

        $files = array_map(
            static fn ($path) => substr($path, \strlen($root) + 1),
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
                static fn ($key) => !\in_array($key, ['SYMFONY_DEPRECATIONS_SERIALIZE', 'SYMFONY_EXPECTED_DEPRECATIONS_SERIALIZE'], true),
                ARRAY_FILTER_USE_KEY,
            ),
            true
        );
    }

    private function buildStaticMembers(): string
    {
        $data = [];

        foreach (get_declared_classes() as $class) {
            foreach ([
                'Composer\InstalledVersions',
                'Contao\CoreBundle\Util\LocaleUtil',
                'Contao\TestCase\\',
                'Doctrine\Deprecations\Deprecation',
                'Doctrine\Instantiator\\',
                'Imagine\\',
                'Mock_',
                'PHPUnit\\',
                'ScssPhp\\',
                'SebastianBergmann\\',
                'Symfony\Bridge\PhpUnit\\',
                'Symfony\Component\Cache\Adapter\\',
                'Symfony\Component\Config\Resource\ComposerResource',
                'Symfony\Component\Console\Helper\\',
                'Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag',
                'Symfony\Component\ErrorHandler\\',
                'Symfony\Component\Filesystem\\',
                'Symfony\Component\HttpClient\Internal\CurlClientState',
                'Symfony\Component\Mime\Address',
                'Symfony\Component\Mime\MimeTypes',
                'Symfony\Component\String\\',
                'Symfony\Component\VarDumper\\',
                'Symfony\Component\Yaml\\',
                'Webmozart\PathUtil\\',
            ] as $ignorePrefix) {
                if (0 === strncmp("$ignorePrefix\\", $class, \strlen($ignorePrefix))) {
                    continue 2;
                }
            }

            foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                if (!$property->isInitialized()) {
                    continue;
                }

                if ($property->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if (0 === strncmp('__phpunit', $property->getName(), 9)) {
                    continue;
                }

                $value = $property->getValue();

                if ($value === $property->getDefaultValue()) {
                    continue;
                }

                if ($value instanceof \WeakMap && 0 === $value->count() && $property->hasType() && !$property->getType()->allowsNull()) {
                    continue;
                }

                if (\is_array($value)) {
                    $value = 'array('.\count($value).')';
                }

                if (\is_object($value)) {
                    $value = $value::class;
                }

                $data["$class::$property->name"] = $value;
            }
        }

        return substr(print_r($data, true), 8, -2);
    }
}
