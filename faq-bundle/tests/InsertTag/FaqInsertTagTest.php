<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\InsertTag;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FaqBundle\InsertTag\FaqInsertTag;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FaqInsertTagTest extends ContaoTestCase
{
    public function testReplacesTheFaqTags(): void
    {
        $page = $this->createMock(PageModel::class);

        $categoryModel = $this->createMock(FaqCategoryModel::class);
        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faqModel = $this->mockClassWithProperties(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $matcher = $this->exactly(10);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($matcher)
            ->method('generate')
            ->willReturnCallback(
                function (...$parameters) use ($matcher, $faqModel) {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'faq/what-does-foobar-mean.html';
                    }

                    if (2 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'faq/what-does-foobar-mean.html';
                    }

                    if (3 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'faq/what-does-foobar-mean.html';
                    }

                    if (4 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'faq/what-does-foobar-mean.html';
                    }

                    if (5 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/faq/what-does-foobar-mean.html';
                    }

                    if (6 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/faq/what-does-foobar-mean.html';
                    }

                    if (7 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'faq/what-does-foobar-mean.html';
                    }

                    if (8 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/faq/what-does-foobar-mean.html';
                    }

                    if (9 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/faq/what-does-foobar-mean.html';
                    }

                    if (10 === $matcher->numberOfInvocations()) {
                        $this->assertSame($faqModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/faq/what-does-foobar-mean.html';
                    }
                },
            )
        ;

        $listener = new FaqInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html">What does "foobar" mean?</a>',
            $listener(new ResolvedInsertTag('faq', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">What does "foobar" mean?</a>',
            $listener(new ResolvedInsertTag('faq', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html">',
            $listener(new ResolvedInsertTag('faq_open', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('faq_open', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('faq_open', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('faq_open', new ResolvedParameters(['2', 'absolute', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            'faq/what-does-foobar-mean.html',
            $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertEquals(
            new InsertTagResult('What does "foobar" mean?', OutputType::text),
            $listener(new ResolvedInsertTag('faq_title', new ResolvedParameters(['2']), [])),
        );
    }

    public function testHandlesEmptyUrls(): void
    {
        $page = $this->createMock(PageModel::class);
        $page
            ->method('getFrontendUrl')
            ->willReturn('')
        ;

        $categoryModel = $this->createMock(FaqCategoryModel::class);
        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faqModel = $this->mockClassWithProperties(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $listener = new FaqInsertTag($this->mockContaoFramework($adapters), $this->createMock(ContentUrlGenerator::class));

        $this->assertSame(
            '<a href="./">What does "foobar" mean?</a>',
            $listener(new ResolvedInsertTag('faq', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="./">',
            $listener(new ResolvedInsertTag('faq_open', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            './',
            $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2']), []))->getValue(),
        );
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $listener = new FaqInsertTag($this->mockContaoFramework($adapters), $this->createMock(ContentUrlGenerator::class));

        $this->assertSame('', $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2']), []))->getValue());
    }

    public function testReturnsAnEmptyStringIfTheRouterThrowsAnException(): void
    {
        $faqModel = $this->createMock(FaqModel::class);
        $faqModel
            ->method('getRelated')
            ->willReturn(null)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willThrowException(new ForwardPageNotFoundException())
        ;

        $listener = new FaqInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['3']), []))->getValue());
    }
}
