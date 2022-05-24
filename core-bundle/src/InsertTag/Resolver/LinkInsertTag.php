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
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Routing\Exception\ExceptionInterface;

class LinkInsertTag
{
    #[AsInsertTag('link', type: OutputType::html)]
    #[AsInsertTag('link_open', type: OutputType::html)]
    #[AsInsertTag('link_title', type: OutputType::text)]
    #[AsInsertTag('link_name', type: OutputType::text)]
    #[AsInsertTag('link_url', type: OutputType::url)]
    public function replaceInsertTag(ResolvedInsertTag $insertTag): string
    {
        $strTarget = null;
        $strClass = '';
        $container = System::getContainer();
        $blnFeUserLoggedIn = $container->get('contao.security.token_checker')->hasFrontendUser();

        if (!$insertTag->getParameters()->has(0)) {
            // TODO: throw custom exception
            return $insertTag->serialize();
        }

        $urlParam = $insertTag->getParameters()->get(0);

        // External links
        if (strncmp($urlParam, 'http://', 7) === 0 || strncmp($urlParam, 'https://', 8) === 0)
        {
            $strUrl = StringUtil::specialcharsUrl($urlParam);
            $strTitle = $urlParam;
            $strName = str_replace(array('http://', 'https://'), '', $strUrl);
        }

        // Regular link
        else
        {
            // User login page
            if ($urlParam == 'login')
            {
                if (!$blnFeUserLoggedIn)
                {
                    break;
                }

                $urlParam = FrontendUser::getInstance()->loginPage;
            }

            $objNextPage = PageModel::findByIdOrAlias($urlParam);

            if ($objNextPage === null)
            {
                // Prevent broken markup with link_open and link_close (see #92)
                if ($insertTag->getName() === 'link_open')
                {
                    return '<a>';
                }

                return '';
            }

            $strUrl = '';

            // Do not generate URL for insert tags that don't need it
            if (\in_array($insertTag->getName(), array('link', 'link_open', 'link_url'), true))
            {
                switch ($objNextPage->type)
                {
                    case 'redirect':
                        $strUrl = $objNextPage->url;

                        if (strncasecmp($strUrl, 'mailto:', 7) === 0)
                        {
                            $strUrl = StringUtil::encodeEmail($strUrl);
                        }
                        break;

                    case 'forward':
                        if ($objNextPage->jumpTo)
                        {
                            $objNext = PageModel::findPublishedById($objNextPage->jumpTo);
                        }
                        else
                        {
                            $objNext = PageModel::findFirstPublishedRegularByPid($objNextPage->id);
                        }

                        if ($objNext instanceof PageModel)
                        {
                            try
                            {
                                $strUrl = \in_array('absolute', \array_slice($elements, 2), true) ? $objNext->getAbsoluteUrl() : $objNext->getFrontendUrl();
                            }
                            catch (ExceptionInterface $exception)
                            {
                            }
                            break;
                        }
                    // no break

                    default:
                        try
                        {
                            $strUrl = \in_array('absolute', \array_slice($elements, 2), true) ? $objNextPage->getAbsoluteUrl() : $objNextPage->getFrontendUrl();
                        }
                        catch (ExceptionInterface $exception)
                        {
                        }
                        break;
                }
            }

            $strName = $objNextPage->title;
            $strTarget = $objNextPage->target ? ' target="_blank" rel="noreferrer noopener"' : '';
            $strClass = $objNextPage->cssClass ? sprintf(' class="%s"', $objNextPage->cssClass) : '';
            $strTitle = $objNextPage->pageTitle ?: $objNextPage->title;
        }

        if (!$strTarget && \in_array('blank', \array_slice($elements, 2), true))
        {
            $strTarget = ' target="_blank" rel="noreferrer noopener"';
        }

        // Replace the tag
        switch ($insertTag->getName())
        {
            case 'link':
                $arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>%s</a>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget, $strName);
                break;

            case 'link_open':
                $arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>', $strUrl ?: './', StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget);
                break;

            case 'link_url':
                $arrCache[$strTag] = $strUrl ?: './';
                break;

            case 'link_title':
                $arrCache[$strTag] = StringUtil::specialcharsAttribute($strTitle);
                break;

            case 'link_name':
                $arrCache[$strTag] = StringUtil::specialcharsAttribute($strName);
                break;
        }
        break;
    }

    #[AsInsertTag('link_close', type: OutputType::html)]
    public function replaceInsertTagClose(ResolvedInsertTag $insertTag): string
    {
        return '</a>';
    }
}
