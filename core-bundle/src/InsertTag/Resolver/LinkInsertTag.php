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
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface;

class LinkInsertTag
{
    public function __construct(private ContaoFramework $framework, private TokenChecker $tokenChecker)
    {
    }

    #[AsInsertTag('link', type: OutputType::html)]
    #[AsInsertTag('link_open', type: OutputType::html)]
    #[AsInsertTag('link_title', type: OutputType::text)]
    #[AsInsertTag('link_name', type: OutputType::text)]
    #[AsInsertTag('link_url', type: OutputType::url)]
    public function replaceInsertTag(ResolvedInsertTag $insertTag): string
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
                    return '';
                }

                $urlParam = $this->framework->createInstance(FrontendUser::class)->loginPage;
            }

            $objNextPage = $this->framework->getAdapter(PageModel::class)->findByIdOrAlias($urlParam);

            if (null === $objNextPage) {
                // Prevent broken markup with link_open and link_close (see #92)
                if ('link_open' === $insertTag->getName()) {
                    return '<a>';
                }

                return '';
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

        // Replace the tag
        switch ($insertTag->getName()) {
            case 'link':
                return sprintf('<a href="%s" title="%s"%s%s>%s</a>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget, $strName);

            case 'link_open':
                return sprintf('<a href="%s" title="%s"%s%s>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget);

            case 'link_url':
                return $strUrl ?: './';

            case 'link_title':
                return StringUtil::specialcharsAttribute($strTitle);

            case 'link_name':
                return StringUtil::specialcharsAttribute($strName);
        }

        throw new InvalidInsertTagException();
    }

    #[AsInsertTag('link_close', type: OutputType::html)]
    public function replaceInsertTagClose(ResolvedInsertTag $insertTag): string
    {
        return '</a>';
    }
}
