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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\MessageCatalogue;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\LogicException;
use Symfony\Component\Translation\MessageCatalogueInterface;

class MessageCatalogueTest extends TestCase
{
    use ExpectDeprecationTrait;

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

        $finder = $this->createMock(Finder::class);
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

        $resourceFinder = $this->createMock(ResourceFinder::class);
        $resourceFinder
            ->method('findIn')
            ->with('languages/en')
            ->willReturn($finder)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue, null, $resourceFinder);

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
            ->withConsecutive(['foo', 'foobar'], ['bar', 'foobar'])
            ->willReturnOnConsecutiveCalls(true, false)
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
            ->withConsecutive(['foo', 'foobar'], ['bar', 'foobar'])
            ->willReturnOnConsecutiveCalls(true, false)
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
            ->withConsecutive(['foo', 'foobar'], ['bar', 'foobar'])
            ->willReturnOnConsecutiveCalls('Foo', 'bar')
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

    /**
     * @dataProvider getForwardedDomainMethods
     */
    public function testForwardsIfDomainIsNotContao(string $method, array $params, array $paramsContaoDomain, mixed $return = null): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method($method)
            ->with(...$params)
            ->willReturn($return)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);

        $this->assertSame($return, $catalogue->$method(...$params));

        $this->expectException(LogicException::class);

        $catalogue->$method(...$paramsContaoDomain);
    }

    public function getForwardedDomainMethods(): \Generator
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
     * @dataProvider getCompletelyForwardedMethods
     */
    public function testForwardsCompletelyToParent(string $method, array $params, mixed $return = null): void
    {
        $parentCatalogue = $this->createMock(MessageCatalogueInterface::class);
        $parentCatalogue
            ->expects($this->once())
            ->method($method)
            ->with(...$params)
            ->willReturn($return)
        ;

        $catalogue = $this->createCatalogue($parentCatalogue);
        $this->assertSame($return, $catalogue->$method(...$params));
    }

    public function getCompletelyForwardedMethods(): \Generator
    {
        yield [
            'addCatalogue',
            [$this->createMock(MessageCatalogueInterface::class)],
        ];

        yield [
            'addFallbackCatalogue',
            [$this->createMock(MessageCatalogueInterface::class)],
        ];

        yield [
            'getFallbackCatalogue',
            [],
            $this->createMock(MessageCatalogueInterface::class),
        ];

        yield [
            'getResources',
            [],
            [$this->createMock(ResourceInterface::class)],
        ];

        yield [
            'addResource',
            [$this->createMock(ResourceInterface::class)],
        ];
    }

    private function createCatalogue(MessageCatalogueInterface|null $catalogue = null, ContaoFramework|null $framework = null, ResourceFinder|null $resourceFinder = null): MessageCatalogue
    {
        if (!$catalogue instanceof MessageCatalogueInterface) {
            $catalogue = $this->createMock(MessageCatalogueInterface::class);
            $catalogue
                ->method('getLocale')
                ->willReturn('en')
            ;
        }

        $framework ??= $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);
        $resourceFinder ??= $this->createMock(ResourceFinder::class);

        return new MessageCatalogue($catalogue, $framework, $resourceFinder);
    }
}
