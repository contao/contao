<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Translation;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\MessageCatalogue;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\LogicException;
use Symfony\Component\Translation\MessageCatalogueInterface;

class MessageCatalogueTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testPassesLocaleFromParent(): void
    {
        $catalogue = $this->createCatalogue();

        $this->assertSame('en', $catalogue->getLocale());
    }

    public function testGetsDomainsFromResourceFinder(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->method('getLocale')
            ->willReturn('en')
        ;

        $parentCatalogue
            ->expects($this->once())
            ->method('getDomains')
            ->willReturn(['foobar', 'bazfoo'])
        ;

        $finder = $this->createStub(Finder::class);
        $finder
            ->method('name')
            ->with('/\.(php|xlf)$/')
            ->willReturn($finder)
        ;

        $finder
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                new \SplFileInfo('tl_page.php'),
                new \SplFileInfo('default.php'),
                new \SplFileInfo('tl_page.xlf'),
                new \SplFileInfo('default.xlf'),
                new \SplFileInfo('default.php'),
            ]))
        ;

        $resourceFinder = $this->createStub(ResourceFinder::class);
        $resourceFinder
            ->method('findIn')
            ->with('languages/en')
            ->willReturn($finder)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue, $resourceFinder);

        $this->assertSame(['foobar', 'bazfoo', 'contao_default', 'contao_tl_page'], $catalogue->getDomains());
    }

    public function testChecksHasAgainstGlobals(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->method('getLocale')
            ->willReturn('en')
        ;

        $parentCatalogue
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['foo', 'foobar', true],
                ['bar', 'foobar', false],
            ])
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);

        $this->assertTrue($catalogue->has('foo', 'foobar'));
        $this->assertFalse($catalogue->has('bar', 'foobar'));

        $this->assertFalse($catalogue->has('MSC.foobar', 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foobar'] = 'baz';

        $this->assertTrue($catalogue->has('MSC.foobar', 'contao_default'));

        unset($GLOBALS['TL_LANG']);

        $this->assertFalse($catalogue->has('MSC.foobar', 'contao_default'));
    }

    public function testChecksDefinesAgainstGlobals(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->method('getLocale')
            ->willReturn('en')
        ;

        $parentCatalogue
            ->expects($this->exactly(2))
            ->method('defines')
            ->willReturnMap([
                ['foo', 'foobar', true],
                ['bar', 'foobar', false],
            ])
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);

        $this->assertTrue($catalogue->defines('foo', 'foobar'));
        $this->assertFalse($catalogue->defines('bar', 'foobar'));

        $this->assertFalse($catalogue->defines('MSC.foobar', 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foobar'] = 'baz';

        $this->assertTrue($catalogue->defines('MSC.foobar', 'contao_default'));

        unset($GLOBALS['TL_LANG']);

        $this->assertFalse($catalogue->defines('MSC.foobar', 'contao_default'));
    }

    public function testGetsFromGlobals(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->method('getLocale')
            ->willReturn('en')
        ;

        $parentCatalogue
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['foo', 'foobar', 'Foo'],
                ['bar', 'foobar', 'bar'],
            ])
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);

        $this->assertSame('Foo', $catalogue->get('foo', 'foobar'));
        $this->assertSame('bar', $catalogue->get('bar', 'foobar'));

        $this->assertSame('MSC.foobar', $catalogue->get('MSC.foobar', 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foobar'] = 'baz';

        $this->assertSame('baz', $catalogue->get('MSC.foobar', 'contao_default'));

        unset($GLOBALS['TL_LANG']);

        $this->assertSame('MSC.foobar', $catalogue->get('MSC.foobar', 'contao_default'));
    }

    #[DataProvider('getForwardedDomainMethods')]
    public function testForwardsIfDomainIsNotContao(string $method, array $params, array $paramsContaoDomain, mixed $return = null): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method($method)
            ->with(...$params)
            ->willReturnCallback(static fn () => $return)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);

        $this->assertSame($return, $catalogue->$method(...$params));

        $this->expectException(LogicException::class);

        $catalogue->$method(...$paramsContaoDomain);
    }

    public static function getForwardedDomainMethods(): iterable
    {
        yield [
            'all',
            ['foobar'],
            ['contao_default'],
            ['foo' => 'Foo', 'bar' => 'Bar'],
        ];

        yield [
            'set',
            ['foo', 'Foo', 'foobar'],
            ['foo', 'Foo', 'contao_default'],
        ];

        yield [
            'replace',
            [['foo' => 'Foo', 'bar' => 'Bar'], 'foobar'],
            [['foo' => 'Foo', 'bar' => 'Bar'], 'contao_default'],
        ];

        yield [
            'add',
            [['foo' => 'Foo', 'bar' => 'Bar'], 'foobar'],
            [['foo' => 'Foo', 'bar' => 'Bar'], 'contao_default'],
        ];
    }

    /**
     * @param list<class-string>                   $paramMockClasses
     * @param class-string|list<class-string>|null $returnMockClassOrClasses
     */
    #[DataProvider('getCompletelyForwardedMethods')]
    public function testForwardsCompletelyToParent(string $method, array $paramMockClasses, array|string|null $returnMockClassOrClasses = null): void
    {
        $params = array_map($this->createStub(...), $paramMockClasses);
        $return = null;

        if (\is_string($returnMockClassOrClasses)) {
            $return = $this->createStub($returnMockClassOrClasses);
        }

        if (\is_array($returnMockClassOrClasses)) {
            $return = array_map($this->createStub(...), $returnMockClassOrClasses);
        }

        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method($method)
            ->with(...$params)
            ->willReturnCallback(static fn () => $return)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $this->assertSame($return, $catalogue->$method(...$params));
    }

    public static function getCompletelyForwardedMethods(): iterable
    {
        yield [
            'addCatalogue',
            [MessageCatalogueInterface::class],
        ];

        yield [
            'addFallbackCatalogue',
            [MessageCatalogueInterface::class],
        ];

        yield [
            'getFallbackCatalogue',
            [],
            MessageCatalogueInterface::class,
        ];

        yield [
            'getResources',
            [],
            [ResourceInterface::class],
        ];

        yield [
            'addResource',
            [ResourceInterface::class],
        ];
    }

    public function testPopulatesGlobalsFromSymfonyTranslations(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method('all')
            ->with('contao_tl_content')
            ->willReturn(['tl_content.headline.0' => 'Headline'])
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $catalogue->populateGlobals('contao_tl_content');

        $this->assertSame('Headline', $GLOBALS['TL_LANG']['tl_content']['headline'][0]);
    }

    public function testDoesNotPopulateGlobalsFromSymfonyTranslationsOfNonContaoDomain(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->never())
            ->method('all')
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $catalogue->populateGlobals('foobar');

        $this->assertEmpty($GLOBALS['TL_LANG'] ?? null);
    }

    public function testReturnsGlobalsStringRepresentationFromSymfonyTranslations(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method('all')
            ->with('contao_tl_content')
            ->willReturn(['tl_content.headline.0' => 'Headline'])
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $string = $catalogue->getGlobalsString('contao_tl_content');

        $this->assertSame("\$GLOBALS['TL_LANG']['tl_content']['headline']['0'] = 'Headline';\n", $string);
    }

    public function testReturnsEmptyGlobalsStringRepresentationFromSymfonyTranslationsOfNonContaoDomain(): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->never())
            ->method('all')
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $string = $catalogue->getGlobalsString('foobar');

        $this->assertSame('', $string);
    }

    private function createCatalogue(MessageCatalogueInterface|null $catalogue = null, ResourceFinder|null $resourceFinder = null): MessageCatalogue
    {
        if (!$catalogue) {
            $catalogue = $this->createStub(MessageCatalogueInterface::class);
            $catalogue
                ->method('getLocale')
                ->willReturn('en')
            ;
        }

        $framework = $this->createContaoFrameworkStub([System::class => $this->createAdapterStub(['loadLanguageFile'])]);
        $resourceFinder ??= $this->createStub(ResourceFinder::class);

        return new MessageCatalogue($catalogue, $framework, $resourceFinder);
    }
}
