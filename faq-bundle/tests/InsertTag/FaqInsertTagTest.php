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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\FaqBundle\InsertTag\FaqInsertTag;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class FaqInsertTagTest extends ContaoTestCase
{
    #[DataProvider('replacesTheFaqTagsProvider')]
    public function testReplacesTheFaqTags(string $insertTag, array $parameters, int|null $referenceType, string|null $url, string $expectedValue, OutputType $expectedOutputType): void
    {
        $page = $this->createStub(PageModel::class);

        $categoryModel = $this->createStub(FaqCategoryModel::class);
        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faqModel = $this->createClassWithPropertiesStub(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => $faqModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects(null === $url ? $this->never() : $this->once())
            ->method('generate')
            ->with($faqModel, [], $referenceType)
            ->willReturn($url ?? '')
        ;

        $listener = $this->createFaqInsertTag($adapters, $urlGenerator);
        $result = $listener(new ResolvedInsertTag($insertTag, new ResolvedParameters($parameters), []));

        $this->assertSame($expectedValue, $result->getValue());
        $this->assertSame($expectedOutputType, $result->getOutputType());
    }

    public static function replacesTheFaqTagsProvider(): iterable
    {
        yield [
            'faq',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'faq/what-does-foobar-mean.html',
            '<a href="faq/what-does-foobar-mean.html">What does &quot;foobar&quot; mean?</a>',
            OutputType::html,
        ];

        yield [
            'faq',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'faq/what-does-foobar-mean.html',
            '<a href="faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">What does &quot;foobar&quot; mean?</a>',
            OutputType::html,
        ];

        yield [
            'faq_open',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'faq/what-does-foobar-mean.html',
            '<a href="faq/what-does-foobar-mean.html">',
            OutputType::html,
        ];

        yield [
            'faq_open',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'faq/what-does-foobar-mean.html',
            '<a href="faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'faq_open',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/faq/what-does-foobar-mean.html',
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'faq_open',
            ['2', 'absolute', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/faq/what-does-foobar-mean.html',
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'faq_url',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'faq/what-does-foobar-mean.html',
            'faq/what-does-foobar-mean.html',
            OutputType::url,
        ];

        yield [
            'faq_url',
            ['2', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/faq/what-does-foobar-mean.html',
            'http://domain.tld/faq/what-does-foobar-mean.html',
            OutputType::url,
        ];

        yield [
            'faq_url',
            ['2', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/faq/what-does-foobar-mean.html',
            'http://domain.tld/faq/what-does-foobar-mean.html',
            OutputType::url,
        ];

        yield [
            'faq_url',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/faq/what-does-foobar-mean.html',
            'http://domain.tld/faq/what-does-foobar-mean.html',
            OutputType::url,
        ];

        yield [
            'faq_title',
            ['2'],
            null,
            null,
            'What does "foobar" mean?',
            OutputType::text,
        ];
    }

    public function testHandlesEmptyUrls(): void
    {
        $page = $this->createStub(PageModel::class);
        $page
            ->method('getFrontendUrl')
            ->willReturn('')
        ;

        $categoryModel = $this->createStub(FaqCategoryModel::class);
        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faqModel = $this->createClassWithPropertiesStub(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => $faqModel]),
        ];

        $listener = $this->createFaqInsertTag($adapters);

        $this->assertSame(
            '<a href="./">What does &quot;foobar&quot; mean?</a>',
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
            FaqModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => null]),
        ];

        $listener = $this->createFaqInsertTag($adapters);

        $this->assertSame('', $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['2']), []))->getValue());
    }

    public function testReturnsAnEmptyStringIfTheRouterThrowsAnException(): void
    {
        $faqModel = $this->createStub(FaqModel::class);
        $faqModel
            ->method('getRelated')
            ->willReturn(null)
        ;

        $adapters = [
            FaqModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => $faqModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willThrowException(new ForwardPageNotFoundException())
        ;

        $listener = $this->createFaqInsertTag($adapters, $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('faq_url', new ResolvedParameters(['3']), []))->getValue());
    }

    private function createFaqInsertTag(array $adapters = [], ContentUrlGenerator|null $urlGenerator = null): FaqInsertTag
    {
        $twig = new Environment($this->createStub(LoaderInterface::class));

        $twig->addExtension(
            new ContaoExtension(
                $twig,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $twig),
            ),
        );

        $insertTagParser = $this->createStub(InsertTagParser::class);
        $insertTagParser
            ->method('replace')
            ->willReturnArgument(0)
        ;

        $twig->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
            ]),
        );

        $urlGenerator ??= $this->createStub(ContentUrlGenerator::class);

        return new FaqInsertTag($this->createContaoFrameworkStub($adapters), $urlGenerator, $twig);
    }
}
