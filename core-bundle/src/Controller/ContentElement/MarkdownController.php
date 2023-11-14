<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\InsertTag\CommonMarkExtension;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\FilesModel;
use Contao\Input;
use Contao\Template;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'texts')]
class MarkdownController extends AbstractContentElementController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.insert_tag.parser'] = InsertTagParser::class;

        return $services;
    }

    #[\Override]
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        if ('sourceFile' === $model->markdownSource) {
            $markdown = $this->getContentFromFile($model->singleSRC);
        } else {
            $markdown = $model->code ?? '';
        }

        if ('' === $markdown) {
            return new Response();
        }

        $config = $this->getContaoAdapter(Config::class);
        $input = $this->getContaoAdapter(Input::class);
        $html = $this->createConverter($model, $request)->convert($markdown)->getContent();

        $template->content = $input->stripTags($html, $config->get('allowedTags'), $config->get('allowedAttributes'));

        return $template->getResponse();
    }

    /**
     * Hint: This is protected on purpose, so you can override it for your app specific requirements.
     * If you want to provide an extension with additional logic, consider providing your own special
     * content element for that.
     */
    protected function createConverter(ContentModel $model, Request $request): ConverterInterface
    {
        $environment = new Environment([
            'external_link' => [
                'internal_hosts' => $request->getHost(),
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkExtension($this->container->get('contao.insert_tag.parser')));
        $environment->addExtension(new CommonMarkCoreExtension());

        // Support GitHub flavoured Markdown (using the individual extensions because we don't want the
        // DisallowedRawHtmlExtension which is included by default)
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());

        // Automatically mark external links as such if we have a request
        $environment->addExtension(new ExternalLinkExtension());

        return new MarkdownConverter($environment);
    }

    private function getContentFromFile(string|null $file): string
    {
        if (!$file) {
            return '';
        }

        $filesAdapter = $this->getContaoAdapter(FilesModel::class);
        $filesModel = $filesAdapter->findByPk($file);

        if (!$filesModel instanceof FilesModel) {
            return '';
        }

        $path = $filesModel->getAbsolutePath();

        if (!(new Filesystem())->exists($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}
