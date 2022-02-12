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
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final class GlobalStateWatcher implements AfterTestHook, BeforeTestHook
{
    private string $globalKeys;
    private string $globals;
    private string $staticMembers;

    public function executeBeforeTest(string $test): void
    {
        $this->globalKeys = $this->buildGlobalKeys();
        $this->globals = $this->buildGlobals();
        $this->staticMembers = $this->buildStaticMembers();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        foreach (['globalKeys', 'globals', 'staticMembers'] as $member) {
            if ($this->$member !== ($after = $this->{'build'.$member}())) {
                throw new ExpectationFailedException(sprintf("\nUnexpected change to global state in %s\n%s", $test, $this->diff($this->$member, $after)));
            }
        }
    }

    private function diff($before, $after): string
    {
        return (new Differ(new UnifiedDiffOutputBuilder("--- Before\n+++ After\n")))->diff($before, $after);
    }

    private function buildGlobalKeys(): string
    {
        return implode("\n", array_keys($GLOBALS))."\n";
    }

    private function buildGlobals(): string
    {
        return print_r($GLOBALS, true);
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
