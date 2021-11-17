<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;

class LogTableListener
{
    private ContaoFramework $framework;

    /**
     * @var Image|Adapter
     */
    private $image;

    private ContentCompositionListener $contentComposition;

    public function __construct(ContaoFramework $framework, ContentCompositionListener $contentComposition)
    {
        $this->framework = $framework;
        $this->contentComposition = $contentComposition;
        $this->image = $this->framework->getAdapter(Image::class);
    }

    /**
     * @Callback(table="tl_log", target="list.operations.uri.button")
     */
    public function renderUriButton(array $row, ?string $href = '', string $label = '', string $title = '', string $icon = '', string $attributes = ''): string
    {
        $uri = $row['uri'] ?? '';

        if ('FE' !== $row['source'] || !$uri || !$this->framework->getAdapter(Validator::class)->isUrl($uri)) {
            return $this->image->getHtml('forward_1.svg', $label).' ';
        }

        return sprintf(
            '<a href="%s" title="%s" target="_blank" rel="noopener">%s</a> ',
            $uri,
            StringUtil::specialchars($title),
            $this->image->getHtml($icon, $label)
        );
    }

    /**
     * @Callback(table="tl_log", target="list.operations.articles.button")
     */
    public function renderArticlesButton(array $row, ?string $href = '', string $label = '', string $title = '', string $icon = '', string $attributes = ''): string
    {
        $pageId = $row['page'] ?? 0;

        if ($pageId && $page = $this->framework->getAdapter(PageModel::class)->findByPk($pageId)) {
            return $this->contentComposition->renderPageArticlesOperation($page->row(), $href, $label, $title, $icon);
        }

        return $this->image->getHtml(preg_replace('/\.svg$/i', '_.svg', $icon), $label).' ';
    }
}
