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
use Contao\CoreBundle\Controller\ContentElement\MarkdownController;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkdownControllerTest extends ContaoTestCase
{
    public function testWithCodeInput(): void
    {
        $container = $this->mockContainer('<h1>Headline</h1>'."\n");

        /** @var ContentModel&MockObject $contentModel */
        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceText';
        $contentModel->code = '# Headline';

        $controller = new MarkdownController();
        $controller->setContainer($container);
        $controller(new Request(), $contentModel, 'main');
    }

    public function testDisallowedTagsAreCorrectlyStripped(): void
    {
        $expectedHtml = <<<'HTML'
            <h1>Headline</h1>

            I might be evil.
            <video controls>
                <source src="contao.mp4" type="video/mp4">
            </video>
            <p>Foobar.</p>

            HTML;

        $container = $this->mockContainer($expectedHtml);

        /** @var ContentModel&MockObject $contentModel */
        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->markdownSource = 'sourceText';
        $contentModel->code = <<<'MARKDOWN'
            # Headline

            <iframe src="https://example.com"></iframe>

            <script>I might be evil.</script>

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

        /** @var FilesModel&MockObject $filesModel */
        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel
            ->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn($tempTestFile)
        ;

        $filesAdapter = $this->mockConfiguredAdapter(['findByPk' => $filesModel]);
        $container = $this->mockContainer('<h1>Headline</h1>'."\n", [FilesModel::class => $filesAdapter]);

        /** @var ContentModel&MockObject $contentModel */
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
        /** @var FrontendTemplate&MockObject $template */
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

        $framework = $this->mockContaoFramework($frameworkAdapters);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, ['ce_markdown'])
            ->willReturn($template)
        ;

        $container = new Container();
        $container->set('contao.framework', $framework);

        return $container;
    }
}
