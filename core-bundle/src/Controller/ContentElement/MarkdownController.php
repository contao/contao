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

use Contao\ContentModel;
use Contao\FilesModel;
use Contao\Template;
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\MarkdownConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class MarkdownController extends AbstractContentElementController
{
    /**
     * @var MarkdownConverterInterface
     */
    private $converter;

    public function __construct(MarkdownConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    public static function createController(RequestStack $requestStack): self
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new GithubFlavoredMarkdownExtension()); // Support GitHub flavoured Markdown

        // Automatically mark external links as such if we have a request
        if (null !== ($request = $requestStack->getCurrentRequest())) {
            $environment->addExtension(new ExternalLinkExtension());
            $environment->mergeConfig([
                'external_link' => [
                    'internal_hosts' => $request->getHost(),
                    'open_in_new_window' => true,
                    'html_class' => 'external-link',
                    'noopener' => 'external',
                    'noreferrer' => 'external',
                ],
            ]);
        }

        $converter = new MarkdownConverter($environment);

        return new self($converter);
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        $markdown = '';

        if ('sourceFile' === $model->markdownSource) {
            /** @var FilesModel $filesModel */
            $filesModel = $this->get('contao.framework')->getAdapter(FilesModel::class)->findByPk($model->singleSRC);

            if (null !== $filesModel) {
                $markdown = (string) $filesModel->getContent();
            }
        } else {
            $markdown = $model->code;
        }

        return new Response($this->converter->convertToHtml($markdown));
    }
}
