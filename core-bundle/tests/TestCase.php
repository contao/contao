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

use Contao\Config;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Dbafs;
use Contao\File;
use Contao\Files;
use Contao\Model;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class TestCase extends ContaoTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!\defined('TL_FILES_URL')) {
            \define('TL_FILES_URL', '');
        }
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TL_CONFIG'],
            $GLOBALS['TL_MIME'],
            $GLOBALS['TL_LANG'],
        );

        $this->resetStaticProperties([
            System::class,
            Config::class,
            LocaleUtil::class,
            Dbafs::class,
            Files::class,
            File::class,
            Registry::class,
            Model::class,
            PageModel::class,

            Terminal::class,
            Table::class,
            ProgressBar::class,
        ]);

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

            foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                if (null !== $methods && !\in_array($property->getName(), $methods, true)) {
                    continue;
                }

                $property->setAccessible(true);

                if (!$property->isInitialized()) {
                    continue;
                }

                // getDefaultValue() is only supported in PHP 8
                if (method_exists($property, 'getDefaultValue') && method_exists($property, 'hasDefaultValue')) {
                    $hasDefaultValue = $property->hasDefaultValue();
                    $defaultValue = $property->getDefaultValue();
                } else {
                    $hasDefaultValue = \array_key_exists(
                        $property->getName(),
                        $property->getDeclaringClass()->getDefaultProperties(),
                    );

                    $defaultValue = $hasDefaultValue
                        ? $property->getDeclaringClass()->getDefaultProperties()[$property->getName()]
                        : null
                    ;
                }

                if (!$hasDefaultValue || $property->getValue() === $defaultValue) {
                    continue;
                }

                $property->setValue($property->getDefaultValue());
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
}
