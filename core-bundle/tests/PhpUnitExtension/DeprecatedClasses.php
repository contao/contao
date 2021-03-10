<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\PhpUnitExtension;

use Contao\CoreBundle\DataContainer\PaletteNotFoundException;
use Contao\CoreBundle\DataContainer\PalettePositionException;
use Contao\CoreBundle\Tests\Fixtures\Database\DoctrineArrayStatement;
use Contao\CoreBundle\Translation\Translator;
use Contao\GdImage;
use Doctrine\DBAL\Driver\Result;
use PHPUnit\Framework\Constraint\StringMatchesFormatDescription;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

final class DeprecatedClasses implements AfterLastTestHook, BeforeFirstTestHook
{
    private $failed = false;

    public function executeAfterLastTest(): void
    {
        if ($this->failed) {
            echo "\n\n";

            throw new ExpectationFailedException(sprintf('Expected deprecations were not triggered or did not match. See %s::deprecationProvider()', self::class));
        }
    }

    public function executeBeforeFirstTest(): void
    {
        foreach ($this->deprecationProvider() as $className => $deprecationMessages) {
            try {
                $this->expectDeprecatedClass($className, $deprecationMessages);
            } catch (ExpectationFailedException $exception) {
                $this->failed = true;
                echo $exception->toString()."\n";
            }
        }

        if ($this->failed) {
            echo "\n";
        }
    }

    private function deprecationProvider(): array
    {
        $deprecations = [
            GdImage::class => ['Using the "Contao\GdImage" class has been deprecated %s.'],
            PaletteNotFoundException::class => ['Using the "Contao\CoreBundle\Exception\PaletteNotFoundException" class has been deprecated %s.'],
            PalettePositionException::class => ['Using the "Contao\CoreBundle\Exception\PalettePositionException" class has been deprecated %s.'],
            Translator::class => ['%simplements "Symfony\Component\Translation\TranslatorInterface" that is deprecated%s'],
        ];

        // Deprecated since doctrine/dbal 2.11.0
        if (interface_exists(Result::class)) {
            $deprecations[DoctrineArrayStatement::class] = ['%s extends "Doctrine\DBAL\Cache\ArrayStatement" that is deprecated.'];
        }

        return $deprecations;
    }

    private function expectDeprecatedClass(string $className, array $expectedDeprecations): void
    {
        // Skip, if the class was already autoloaded
        if (class_exists($className, false)) {
            return;
        }

        $unhandledErrors = [];

        $previousHandler = set_error_handler(
            static function ($errno, $errstr) use (&$expectedDeprecations, &$previousHandler, &$unhandledErrors) {
                foreach ($expectedDeprecations as $key => $expectedDeprecation) {
                    if ((new StringMatchesFormatDescription($expectedDeprecation))->evaluate($errstr, '', true)) {
                        unset($expectedDeprecations[$key]);

                        return true;
                    }
                }

                $unhandledErrors[] = $errstr;

                if ($previousHandler) {
                    return $previousHandler(...\func_get_args());
                }

                return false;
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );

        try {
            class_exists($className);
        } finally {
            restore_error_handler();
        }

        if (!\count($expectedDeprecations)) {
            return;
        }

        $expectedDeprecation = array_values($expectedDeprecations)[0];

        if (\count($unhandledErrors)) {
            (new StringMatchesFormatDescription($expectedDeprecation))->evaluate(
                $unhandledErrors[0],
                sprintf('Expected deprecation for "%s" did not match.', $className)
            );
        }

        throw new ExpectationFailedException(sprintf('Expected deprecation for "%s" was not triggered: "%s"', $className, $expectedDeprecation));
    }
}
