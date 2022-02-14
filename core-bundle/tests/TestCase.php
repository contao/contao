<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\TestCase\ContaoTestCase;
use Roave\BetterReflection\BetterReflection;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class TestCase extends ContaoTestCase
{
    private static array $betterReflectionCache = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!\defined('TL_FILES_URL')) {
            \define('TL_FILES_URL', '');
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        parent::tearDown();
    }

    /**
     * @param array<int, class-string|array> $classNames
     */
    protected function resetStaticProperties(array $classNames): void
    {
        foreach ($classNames as $class) {
            $methods = null;

            if (\is_array($class)) {
                $methods = $class[1];
                $class = $class[0];
            }

            if (!class_exists($class, false)) {
                continue;
            }

            foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                if (null !== $methods && !\in_array($property->getName(), $methods, true)) {
                    continue;
                }

                if ($property->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                $property->setAccessible(true);

                if (!$property->isInitialized()) {
                    continue;
                }

                [$hasDefaultValue, $defaultValue] = $this->getDefaultStaticProperty($property);

                if (!$hasDefaultValue || $property->getValue() === $defaultValue) {
                    continue;
                }

                $property->setValue($defaultValue);
            }
        }
    }

    protected function getFixturesDir(): string
    {
        return __DIR__.\DIRECTORY_SEPARATOR.'Fixtures';
    }

    /**
     * Mocks a request scope matcher.
     */
    protected function mockScopeMatcher(): ScopeMatcher
    {
        return new ScopeMatcher(
            new RequestMatcher(null, null, null, null, ['_scope' => 'backend']),
            new RequestMatcher(null, null, null, null, ['_scope' => 'frontend'])
        );
    }

    /**
     * Mocks a session containing the Contao attribute bags.
     */
    protected function mockSession(): SessionInterface
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setId('test-id');

        $beBag = new ArrayAttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');

        $session->registerBag($beBag);

        $feBag = new ArrayAttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $session->registerBag($feBag);

        return $session;
    }

    private function getDefaultStaticProperty(\ReflectionProperty $property): array
    {
        // See https://github.com/php/php-src/commit/3eb97a456648c739533d92c81102cb919eab01c9
        if (\PHP_VERSION_ID >= 80100) {
            return [$property->hasDefaultValue(), $property->getDefaultValue()];
        }

        $class = $property->getDeclaringClass()->getName();
        $name = $property->getName();
        $cacheKey = $class.'::'.$name;

        if (isset(self::$betterReflectionCache[$cacheKey])) {
            return self::$betterReflectionCache[$cacheKey];
        }

        if (method_exists(BetterReflection::class, 'reflector')) {
            $betterProperty = (new BetterReflection())->reflector()->reflectClass($class)->getProperty($name);
        } else {
            /** @phpstan-ignore-next-line */
            $betterProperty = (new BetterReflection())->classReflector()->reflect($class)->getProperty($name);
        }

        return self::$betterReflectionCache[$cacheKey] = [
            $betterProperty->isDefault(),
            $betterProperty->getDefaultValue(),
        ];
    }
}
