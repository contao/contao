<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\LinkInsertTag;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LinkInsertTagTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testThrowsExceptionWithoutParams(): void
    {
        $this->expectException(InvalidInsertTagException::class);
        $this->expectExceptionMessage('Missing parameters for link insert tag.');

        $listener = new LinkInsertTag(
            $this->createMock(ContaoFramework::class),
            $this->createMock(TokenChecker::class),
            $this->createMock(ContentUrlGenerator::class),
        );

        /** @var ResolvedInsertTag $tag */
        $tag = $this->getInsertTagParser()->parseTag('link');

        $listener->replaceInsertTag($tag);
    }

    #[DataProvider('getConvertedInsertTags')]
    public function testReplacedInsertTag(string $insertTag, string|false $expected, OutputType $outputType): void
    {
        $page1 = $this->mockClassWithProperties(PageModel::class, ['title', 'pageTitle', 'target', 'cssClass']);
        $page1->alias = 'foobar';
        $page1->title = 'Foobar';
        $page1->pageTitle = 'Foobar Meta';

        $page2 = $this->mockClassWithProperties(PageModel::class, ['title', 'pageTitle', 'target', 'cssClass']);
        $page2->alias = 'moobar';
        $page2->title = 'Moobar';
        $page2->target = true;

        $page3 = $this->mockClassWithProperties(PageModel::class, ['title', 'pageTitle', 'target', 'cssClass']);
        $page3->alias = 'koobar';
        $page3->title = 'Koobar';
        $page3->cssClass = 'koobar';

        $page4 = $this->mockClassWithProperties(PageModel::class, ['title', 'pageTitle', 'target', 'cssClass']);
        $page4->alias = 'index';
        $page4->title = 'Index';

        $pageAdapter = $this->mockAdapter(['findByIdOrAlias']);
        $pageAdapter
            ->method('findByIdOrAlias')
            ->willReturnMap([
                ['1', $page1],
                ['2', $page2],
                ['3', $page3],
                ['4', $page4],
                ['5', null],
            ])
        ;

        $contaoFramework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->method('generate')
            ->willReturnCallback(
                static function (PageModel $content, array $parameters, int $referenceType): string {
                    if (UrlGeneratorInterface::ABSOLUTE_URL === $referenceType) {
                        return 'https://example.com/'.$content->alias;
                    }

                    if ('index' === $content->alias) {
                        return '';
                    }

                    return '/'.$content->alias;
                },
            )
        ;

        $container = new ContainerBuilder();
        $container->setParameter('contao.sanitizer.allowed_url_protocols', ['https']);

        System::setContainer($container);

        $listener = new LinkInsertTag(
            $contaoFramework,
            $this->createMock(TokenChecker::class),
            $contentUrlGenerator,
        );

        /** @var ResolvedInsertTag $tag */
        $tag = $this->getInsertTagParser()->parseTag($insertTag);

        $result = match ($tag->getName()) {
            'link_close' => $listener->replaceInsertTagClose($tag),
            default => $listener->replaceInsertTag($tag),
        };

        $this->assertSame($expected, $result->getValue());
        $this->assertSame($outputType, $result->getOutputType());
    }

    public static function getConvertedInsertTags(): iterable
    {
        yield ['link::1', '<a href="/foobar">Foobar</a>', OutputType::html];
        yield ['link::1::absolute', '<a href="https://example.com/foobar">Foobar</a>', OutputType::html];
        yield ['link::1::blank', '<a href="/foobar" target="_blank" rel="noreferrer noopener">Foobar</a>', OutputType::html];
        yield ['link::1::blank::absolute', '<a href="https://example.com/foobar" target="_blank" rel="noreferrer noopener">Foobar</a>', OutputType::html];
        yield ['link_open::1', '<a href="/foobar">', OutputType::html];
        yield ['link_close', '</a>', OutputType::html];
        yield ['link_url::1', '/foobar', OutputType::url];
        yield ['link_title::1', 'Foobar Meta', OutputType::html];
        yield ['link_name::1', 'Foobar', OutputType::html];

        yield ['link::2', '<a href="/moobar" target="_blank" rel="noreferrer noopener">Moobar</a>', OutputType::html];
        yield ['link_open::2', '<a href="/moobar" target="_blank" rel="noreferrer noopener">', OutputType::html];
        yield ['link_url::2', '/moobar', OutputType::url];
        yield ['link_title::2', 'Moobar', OutputType::html];
        yield ['link_name::2', 'Moobar', OutputType::html];

        yield ['link::3', '<a href="/koobar" class="koobar">Koobar</a>', OutputType::html];
        yield ['link_open::3', '<a href="/koobar" class="koobar">', OutputType::html];
        yield ['link_url::3', '/koobar', OutputType::url];
        yield ['link_title::3', 'Koobar', OutputType::html];
        yield ['link_name::3', 'Koobar', OutputType::html];

        yield ['link_url::4', '/', OutputType::url];

        yield ['link::5', '', OutputType::text];
        yield ['link_open::5', '<a>', OutputType::html];

        yield ['link::https://foobar.com', '<a href="https://foobar.com">foobar.com</a>', OutputType::html];
        yield ['link_open::https://foobar.com', '<a href="https://foobar.com">', OutputType::html];
        yield ['link_url::https://foobar.com', 'https://foobar.com', OutputType::url];
    }

    public function testReturnsEmptyForLoginPageIfUserIsNotLoggedIn(): void
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('hasFrontendUser')
            ->willReturn(false)
        ;

        $listener = new LinkInsertTag(
            $this->createMock(ContaoFramework::class),
            $tokenChecker,
            $this->createMock(ContentUrlGenerator::class),
        );

        /** @var ResolvedInsertTag $tag */
        $tag = $this->getInsertTagParser()->parseTag('link::login');

        $result = $listener->replaceInsertTag($tag);

        $this->assertSame('', $result->getValue());
    }

    public function testReturnsLoginPageIfUserIsLoggedIn(): void
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('hasFrontendUser')
            ->willReturn(true)
        ;

        $loginPage = $this->mockClassWithProperties(PageModel::class, ['title', 'pageTitle', 'target', 'cssClass']);
        $loginPage->alias = '/login';
        $loginPage->title = 'Login';

        $pageAdapter = $this->mockAdapter(['findByIdOrAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByIdOrAlias')
            ->with('1701')
            ->willReturn($loginPage)
        ;

        $frontendUser = $this->mockClassWithProperties(FrontendUser::class, ['loginPage']);
        $frontendUser->loginPage = '1701';

        $contaoFramework = $this->mockContaoFramework([PageModel::class => $pageAdapter], [FrontendUser::class => $frontendUser]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($loginPage)
            ->willReturn('/login')
        ;

        $listener = new LinkInsertTag(
            $contaoFramework,
            $tokenChecker,
            $contentUrlGenerator,
        );

        /** @var ResolvedInsertTag $tag */
        $tag = $this->getInsertTagParser()->parseTag('link::login');

        $result = $listener->replaceInsertTag($tag);

        $this->assertSame('<a href="/login">Login</a>', $result->getValue());
    }

    private function getInsertTagParser(): InsertTagParser
    {
        return new InsertTagParser(
            $this->createMock(ContaoFramework::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(FragmentHandler::class),
            $this->createMock(RequestStack::class),
            (new \ReflectionClass(InsertTags::class))->newInstanceWithoutConstructor(),
        );
    }
}
