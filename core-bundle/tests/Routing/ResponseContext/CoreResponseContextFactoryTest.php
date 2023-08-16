<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory(
            $responseAccessor,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenChecker::class),
            new HtmlDecoder($this->createMock(InsertTagParser::class)),
            $this->createMock(RequestStack::class),
            $this->createMock(InsertTagParser::class)
        );

        $responseContext = $factory->createResponseContext();

        $this->assertInstanceOf(ResponseHeaderBag::class, $responseContext->getHeaderBag());
    }

    public function testWebpageResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory(
            $responseAccessor,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenChecker::class),
            new HtmlDecoder($this->createMock(InsertTagParser::class)),
            $this->createMock(RequestStack::class),
            $this->createMock(InsertTagParser::class)
        );

        $responseContext = $factory->createWebpageResponseContext();

        $this->assertInstanceOf(HtmlHeadBag::class, $responseContext->get(HtmlHeadBag::class));
        $this->assertTrue($responseContext->has(JsonLdManager::class));
        $this->assertFalse($responseContext->isInitialized(JsonLdManager::class));

        /** @var JsonLdManager $jsonLdManager */
        $jsonLdManager = $responseContext->get(JsonLdManager::class);

        $this->assertSame(
            [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebPage',
                    ],
                ],
            ],
            $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->toArray()
        );

        $this->assertTrue($responseContext->isInitialized(JsonLdManager::class));
    }

    public function testContaoWebpageResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $insertTagsParser = $this->createMock(InsertTagParser::class);
        $insertTagsParser
            ->method('replaceInline')
            ->withConsecutive(['My title'], ['My description'], ['{{link_url::42}}'])
            ->willReturnOnConsecutiveCalls('My title', 'My description', 'de/foobar.html')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.com/'));

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 0;
        $pageModel->title = 'My title';
        $pageModel->description = 'My description';
        $pageModel->robots = 'noindex,nofollow';
        $pageModel->enableCanonical = true;
        $pageModel->canonicalLink = '{{link_url::42}}';
        $pageModel->noSearch = false;
        $pageModel->protected = false;

        $factory = new CoreResponseContextFactory(
            $responseAccessor,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenChecker::class),
            new HtmlDecoder($insertTagsParser),
            $requestStack,
            $insertTagsParser
        );

        $responseContext = $factory->createContaoWebpageResponseContext($pageModel);

        $this->assertInstanceOf(HtmlHeadBag::class, $responseContext->get(HtmlHeadBag::class));
        $this->assertSame('My title', $responseContext->get(HtmlHeadBag::class)->getTitle());
        $this->assertSame('My description', $responseContext->get(HtmlHeadBag::class)->getMetaDescription());
        $this->assertSame('noindex,nofollow', $responseContext->get(HtmlHeadBag::class)->getMetaRobots());
        $this->assertSame('https://example.com/de/foobar.html', $responseContext->get(HtmlHeadBag::class)->getCanonicalUriForRequest(new Request()));

        $this->assertTrue($responseContext->has(JsonLdManager::class));
        $this->assertTrue($responseContext->isInitialized(JsonLdManager::class));

        /** @var JsonLdManager $jsonLdManager */
        $jsonLdManager = $responseContext->get(JsonLdManager::class);

        $this->assertSame(
            [
                '@context' => 'https://schema.contao.org/',
                '@type' => 'Page',
                'title' => 'My title',
                'pageId' => 0,
                'noSearch' => false,
                'protected' => false,
                'groups' => [],
                'fePreview' => false,
            ],
            $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class)->toArray()
        );
    }

    /**
     * @dataProvider getContaoWebpageResponseContextCanonicalUrls
     */
    public function testContaoWebpageResponseContextCanonicalUrls(string $url, string $expected): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $insertTagsParser = $this->createMock(InsertTagParser::class);
        $insertTagsParser
            ->method('replaceInline')
            ->withConsecutive([''], [''], ['{{link_url::42}}'])
            ->willReturnOnConsecutiveCalls('My title', 'My description', $url)
        ;

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.com/'));

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 0;
        $pageModel->enableCanonical = true;
        $pageModel->canonicalLink = '{{link_url::42}}';
        $pageModel->noSearch = false;
        $pageModel->protected = false;

        $factory = new CoreResponseContextFactory(
            $responseAccessor,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenChecker::class),
            new HtmlDecoder($insertTagsParser),
            $requestStack,
            $insertTagsParser
        );

        $responseContext = $factory->createContaoWebpageResponseContext($pageModel);

        $this->assertSame($expected, $responseContext->get(HtmlHeadBag::class)->getCanonicalUriForRequest(new Request()));
    }

    public function getContaoWebpageResponseContextCanonicalUrls(): \Generator
    {
        yield ['//example.de/foobar.html', 'https://example.de/foobar.html'];
        yield ['/de/foobar.html', 'https://example.com/de/foobar.html'];
        yield ['de/foobar.html', 'https://example.com/de/foobar.html'];
        yield ['foobar.html', 'https://example.com/foobar.html'];
        yield ['https://example.de/foobar.html', 'https://example.de/foobar.html'];
        yield ['http://example.de/foobar.html', 'http://example.de/foobar.html'];
    }

    public function testDecodingAndCleanupOnContaoResponseContext(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 0;
        $pageModel->title = 'We went from Alpha &#62; Omega';
        $pageModel->description = 'My description <strong>contains</strong> HTML<br>.';
        $pageModel->noSearch = false;
        $pageModel->protected = false;

        $insertTagsParser = $this->createMock(InsertTagParser::class);
        $insertTagsParser
            ->method('replaceInline')
            ->willReturnArgument(0)
        ;

        $factory = new CoreResponseContextFactory(
            $this->createMock(ResponseContextAccessor::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TokenChecker::class),
            new HtmlDecoder($insertTagsParser),
            $this->createMock(RequestStack::class),
            $insertTagsParser
        );

        $responseContext = $factory->createContaoWebpageResponseContext($pageModel);

        $this->assertSame('We went from Alpha > Omega', $responseContext->get(HtmlHeadBag::class)->getTitle());
        $this->assertSame('My description contains HTML.', $responseContext->get(HtmlHeadBag::class)->getMetaDescription());

        /** @var JsonLdManager $jsonLdManager */
        $jsonLdManager = $responseContext->get(JsonLdManager::class);

        $this->assertSame(
            [
                '@context' => 'https://schema.contao.org/',
                '@type' => 'Page',
                'title' => 'We went from Alpha > Omega',
                'pageId' => 0,
                'noSearch' => false,
                'protected' => false,
                'groups' => [],
                'fePreview' => false,
            ],
            $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class)->toArray()
        );
    }
}
