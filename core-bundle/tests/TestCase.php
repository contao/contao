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
        ]);

        parent::tearDown();
    }

    /**
     * @param array<int, class-string>|null $classNames
     */
    protected function resetStaticProperties(array $classNames = null): void
    {
        $classNames ??= array_filter(
            get_declared_classes(),
            static fn ($class) => 0 === strncmp('Contao\\', $class, 7)
                && 0 !== strncmp('Contao\\TestCase\\', $class, 16)
                && !preg_match('/^Contao\\\\[^\\\\]+\\\\Tests\\\\/', $class)
        );

        foreach ($classNames as $class) {
            foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                $property->setAccessible(true);

                if (!$property->isInitialized()) {
                    continue;
                }

                if ($property->getValue() === $property->getDefaultValue() || !$property->hasDefaultValue()) {
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
