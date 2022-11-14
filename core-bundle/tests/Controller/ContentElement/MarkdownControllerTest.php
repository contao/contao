<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\ContentElement\MarkdownController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkdownControllerTest extends ContentElementTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testWithCodeInput(): void
    {
        $container = $this->mockContainer('<h1>Headline</h1>'."\n");

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceText';
        $contentModel->code = '# Headline';

        $controller = new MarkdownController();
        $controller->setContainer($container);
        $controller(new Request(), $contentModel, 'main');
    }

    public function testInsertTagsInLinksAreCorrectlyReplaced(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->once())
            ->method('replaceInline')
            ->with('{{news_url::42}}')
            ->willReturn('https://contao.org/news-alias that-needs-encoding.html')
        ;

        $container = $this->mockContainer('<p><a rel="noopener noreferrer" target="_blank" class="external-link" href="https://contao.org/news-alias%20that-needs-encoding.html">My text for my link</a></p>'."\n");
        $container->set('contao.insert_tag.parser', $insertTagParser);

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceText';
        $contentModel->code = '[My text for my link]({{news_url::42}})';

        System::setContainer($container);

        $controller = new MarkdownController();
        $controller->setContainer($container);
        $controller(new Request(), $contentModel, 'main');
    }

    public function testDisallowedTagsAreCorrectlyStripped(): void
    {
        $expectedHtml = <<<'HTML'
            <h1>Headline</h1>
            &#60;iframe src&#61;&#34;https://example.com&#34;&#62;&#60;/iframe&#62;
            &#60;script&#62;I might be evil.&#60;/script&#62;
            <img>
            <video controls="">
                <source src="contao.mp4" type="video/mp4">
            </video>
            <p>Foobar.</p>

            HTML;

        $container = $this->mockContainer($expectedHtml);

        System::setContainer($container);

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceText';
        $contentModel->code = <<<'MARKDOWN'
            # Headline

            <iframe src="https://example.com"></iframe>
            <script>I might be evil.</script>
            <img onerror="I might be evil">
            <video controls>
                <source src="contao.mp4" type="video/mp4">
            </video>

            Foobar.
            MARKDOWN;

        $controller = new MarkdownController();
        $controller->setContainer($container);
        $controller(new Request(), $contentModel, 'main');
    }

    public function testWithFileInput(): void
    {
        $fs = new Filesystem();
        $tempTestFile = $fs->tempnam($this->getTempDir(), '');
        $fs->dumpFile($tempTestFile, '# Headline');

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel
            ->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn($tempTestFile)
        ;

        $filesAdapter = $this->mockConfiguredAdapter(['findByPk' => $filesModel]);
        $container = $this->mockContainer('<h1>Headline</h1>'."\n", [FilesModel::class => $filesAdapter]);

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceFile';
        $contentModel->singleSRC = 'uuid';

        $controller = new MarkdownController();
        $controller->setContainer($container);
        $controller(new Request(), $contentModel, 'main');

        $fs->remove($tempTestFile);
    }

    private function mockContainer(string $expectedMarkdown, array $frameworkAdapters = []): Container
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $template
            ->method('__set')
            ->withConsecutive(
                [$this->equalTo('headline'), $this->isNull()],
                [$this->equalTo('hl'), $this->equalTo('h1')],
                [$this->equalTo('class'), $this->equalTo('ce_markdown')],
                [$this->equalTo('cssID'), $this->equalTo('')],
                [$this->equalTo('inColumn'), $this->equalTo('main')],
                [$this->equalTo('content'), $this->equalTo($expectedMarkdown)],
            )
        ;

        if (!isset($frameworkAdapters[Input::class])) {
            $frameworkAdapters[Input::class] = new Adapter(Input::class);
        }

        $framework = $this->mockContaoFramework($frameworkAdapters);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, ['ce_markdown'])
            ->willReturn($template)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));

        return $container;
    }

    public function testOutputsMarkdownAsHtml(): void
    {
        $response = $this->renderWithModelData(
            new MarkdownController(),
            [
                'type' => 'markdown',
                'code' => "## Headline\n * my\n * list",
            ]
        );

        $expectedOutput = <<<'HTML'
            <div class="content-markdown">
                <h2>Headline</h2>
                    <ul>
                        <li>my</li>
                        <li>list</li>
                    </ul>
                </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
