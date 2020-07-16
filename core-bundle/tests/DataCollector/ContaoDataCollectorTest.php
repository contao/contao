<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataCollector;

use Contao\ContentImage;
use Contao\ContentText;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Tests\Fixtures\DataCollector\TestClass;
use Contao\CoreBundle\Tests\Fixtures\DataCollector\vendor\foo\bar\BundleTestClass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContaoDataCollectorTest extends TestCase
{
    public function testCollectsDataInBackEnd(): void
    {
        $GLOBALS['TL_DEBUG'] = [
            'classes_set' => [System::class],
            'classes_aliased' => ['ContentText' => ContentText::class],
            'classes_composerized' => ['ContentImage' => ContentImage::class],
            'additional_data' => 'data',
        ];

        $collector = new ContaoDataCollector($this->createParameterBag());
        $collector->collect(new Request(), new Response());

        $this->assertSame(['ContentText' => ContentText::class], $collector->getClassesAliased());
        $this->assertSame(['ContentImage' => ContentImage::class], $collector->getClassesComposerized());

        $version = PackageUtil::getContaoVersion();

        $this->assertSame(
            [
                'version' => $version,
                'framework' => true,
                'models' => 0,
                'frontend' => false,
                'preview' => false,
                'layout' => '',
                'template' => '',
                'legacy_routing' => false,
            ],
            $collector->getSummary()
        );

        $this->assertSame($version, $collector->getContaoVersion());
        $this->assertSame([System::class], $collector->getClassesSet());
        $this->assertSame(['additional_data' => 'data'], $collector->getAdditionalData());
        $this->assertSame('contao', $collector->getName());

        unset($GLOBALS['TL_DEBUG']);
    }

    public function testCollectsDataInFrontEnd(): void
    {
        /** @var LayoutModel&MockObject $layout */
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findByPk' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;

        $GLOBALS['objPage'] = $page;

        $collector = new ContaoDataCollector($this->createParameterBag());
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => PackageUtil::getContaoVersion(),
                'framework' => false,
                'models' => 0,
                'frontend' => true,
                'preview' => false,
                'layout' => 'Default (ID 2)',
                'template' => 'fe_page',
                'legacy_routing' => false,
            ],
            $collector->getSummary()
        );

        $collector->reset();

        $this->assertSame([], $collector->getSummary());

        unset($GLOBALS['objPage']);
    }

    public function testStoresTheLegacyRoutingData(bool $legacyRouting = false, bool $prependLocale = false, string $urlSuffix = '.html'): void
    {
        $collector = new ContaoDataCollector($this->createParameterBag($legacyRouting, $prependLocale, $urlSuffix));
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'enabled' => $legacyRouting,
                'prepend_locale' => $prependLocale,
                'url_suffix' => $urlSuffix,
                'hooks' => [],
            ],
            $collector->getLegacyRouting()
        );
    }

    public function legacyRoutingProvider(): \Generator
    {
        yield [false, false, '.html'];
        yield [true, false, '.html'];
        yield [true, false, '.html'];
        yield [true, true, '.html'];
        yield [true, true, '.php'];
    }

    public function testIncludesTheLegacyRoutingHooks(): void
    {
        $GLOBALS['TL_HOOKS'] = [
            'getPageIdFromUrl' => [
                [TestClass::class, 'onGetPageIdFromUrl'],
                ['foo.service', 'onGetPageIdFromUrl'],
            ],
            'getRootPageFromUrl' => [
                [TestClass::class, 'onGetRootPageFromUrl'],
                ['bar.service', 'onGetRootPageFromUrl'],
            ],
        ];

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->exactly(4))
            ->method('importStatic')
            ->withConsecutive([TestClass::class], ['foo.service'], [TestClass::class], ['bar.service'])
            ->willReturnOnConsecutiveCalls(new TestClass(), new BundleTestClass(), new TestClass(), new BundleTestClass())
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);

        $parameterBag = $this->createParameterBag(true);
        $parameterBag->set('kernel.project_dir', \dirname(__DIR__).'/Fixtures/DataCollector');

        $collector = new ContaoDataCollector($parameterBag);
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                ['name' => 'getPageIdFromUrl', 'class' => TestClass::class, 'method' => 'onGetPageIdFromUrl', 'package' => ''],
                ['name' => 'getPageIdFromUrl', 'class' => BundleTestClass::class, 'method' => 'onGetPageIdFromUrl', 'package' => 'foo/bar'],
                ['name' => 'getRootPageFromUrl', 'class' => TestClass::class, 'method' => 'onGetRootPageFromUrl', 'package' => ''],
                ['name' => 'getRootPageFromUrl', 'class' => BundleTestClass::class, 'method' => 'onGetRootPageFromUrl', 'package' => 'foo/bar'],
            ],
            $collector->getLegacyRouting()['hooks']
        );
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = new ContaoDataCollector($this->createParameterBag());

        $method = new \ReflectionMethod($collector, 'getData');
        $method->setAccessible(true);

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }

    private function createParameterBag(bool $legacyRouting = false, bool $prependLocale = false, string $urlSuffix = '.html'): ParameterBagInterface
    {
        return new ParameterBag([
            'contao.legacy_routing' => $legacyRouting,
            'contao.prepend_locale' => $prependLocale,
            'contao.url_suffix' => $urlSuffix,
        ]);
    }
}
