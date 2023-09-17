<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface;

class LinkInsertTag
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    #[AsInsertTag('link')]
    #[AsInsertTag('link_open')]
    #[AsInsertTag('link_title')]
    #[AsInsertTag('link_name')]
    #[AsInsertTag('link_url')]
    public function replaceInsertTag(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $strTarget = null;
        $strClass = '';

        if (null === $insertTag->getParameters()->get(0)) {
            throw new InvalidInsertTagException('Missing parameters for link insert tag.');
        }

        $urlParam = $insertTag->getParameters()->get(0);

        // External links
        if (str_starts_with($urlParam, 'http://') || str_starts_with($urlParam, 'https://')) {
            $strUrl = StringUtil::specialcharsUrl($urlParam);
            $strTitle = $urlParam;
            $strName = str_replace(['http://', 'https://'], '', $strUrl);
        }

        // Regular link
        else {
            // User login page
            if ('login' === $urlParam) {
                if (!$this->tokenChecker->hasFrontendUser()) {
                    return new InsertTagResult('');
                }

                $urlParam = $this->framework->createInstance(FrontendUser::class)->loginPage;
            }

            $objNextPage = $this->framework->getAdapter(PageModel::class)->findByIdOrAlias($urlParam);

            if (!$objNextPage) {
                // Prevent broken markup with link_open and link_close (see #92)
                if ('link_open' === $insertTag->getName()) {
                    return new InsertTagResult('<a>', OutputType::html);
                }

                return new InsertTagResult('');
            }

            $strUrl = '';

            // Do not generate URL for insert tags that don't need it
            if (\in_array($insertTag->getName(), ['link', 'link_open', 'link_url'], true)) {
                switch ($objNextPage->type) {
                    case 'redirect':
                        $strUrl = $objNextPage->url;

                        if (0 === strncasecmp($strUrl, 'mailto:', 7)) {
                            $strUrl = StringUtil::encodeEmail($strUrl);
                        }
                        break;

                    case 'forward':
                        if ($objNextPage->jumpTo) {
                            $objNext = $this->framework->getAdapter(PageModel::class)->findPublishedById($objNextPage->jumpTo);
                        } else {
                            $objNext = $this->framework->getAdapter(PageModel::class)->findFirstPublishedRegularByPid($objNextPage->id);
                        }

                        if ($objNext instanceof PageModel) {
                            try {
                                $strUrl = \in_array('absolute', \array_slice($insertTag->getParameters()->all(), 1), true) ? $objNext->getAbsoluteUrl() : $objNext->getFrontendUrl();
                            } catch (ExceptionInterface) {
                            }
                            break;
                        }
                        // no break

                    default:
                        try {
                            $strUrl = \in_array('absolute', \array_slice($insertTag->getParameters()->all(), 1), true) ? $objNextPage->getAbsoluteUrl() : $objNextPage->getFrontendUrl();
                        } catch (ExceptionInterface) {
                        }
                        break;
                }
            }

            $strName = $objNextPage->title;
            $strTarget = $objNextPage->target ? ' target="_blank" rel="noreferrer noopener"' : '';
            $strClass = $objNextPage->cssClass ? sprintf(' class="%s"', $objNextPage->cssClass) : '';
            $strTitle = $objNextPage->pageTitle ?: $objNextPage->title;
        }

        if (!$strTarget && \in_array('blank', \array_slice($insertTag->getParameters()->all(), 1), true)) {
            $strTarget = ' target="_blank" rel="noreferrer noopener"';
        }

        return match ($insertTag->getName()) {
            'link' => new InsertTagResult(sprintf('<a href="%s" title="%s"%s%s>%s</a>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget, $strName), OutputType::html),
            'link_open' => new InsertTagResult(sprintf('<a href="%s" title="%s"%s%s>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget), OutputType::html),
            'link_url' => new InsertTagResult($strUrl ?: './', OutputType::url),
            'link_title' => new InsertTagResult(StringUtil::specialcharsAttribute($strTitle), OutputType::html),
            'link_name' => new InsertTagResult(StringUtil::specialcharsAttribute($strName), OutputType::html),
            default => throw new InvalidInsertTagException(),
        };
    }

    #[AsInsertTag('link_close')]
    public function replaceInsertTagClose(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult('</a>', OutputType::html);
    }
}
