<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\InsertTags;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Date;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * Provides a {{date_format::*::*}} Insert Tag. The second parameter is the date
 * string to be parsed or a UNIX timestamp while the third parameter is the date
 * format. If the third parameter is ommitted, the datimFormat page or config setting
 * will be used.
 *
 * Usage:
 *
 *   {{date_format::2020-05-26 12:30:35::H:i, d.m.Y}}
 *
 * Result:
 *
 *   12:30, 26.05.2020
 *
 * @internal
 *
 * @Hook("replaceInsertTags")
 */
class DateFormatListener implements ServiceAnnotationInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
    }

    /**
     * @return string|bool
     */
    public function __invoke(string $insertTag)
    {
        $tag = explode('::', $insertTag);

        if ('date_format' !== $tag[0]) {
            return false;
        }

        if (empty($tag[1])) {
            return '';
        }

        $timestamp = is_numeric($tag[1]) ? (int) $tag[1] : strtotime($tag[1]);

        if (false === $timestamp) {
            return $tag[1];
        }

        /** @var Date $dateAdapter */
        $dateAdapter = $this->framework->getAdapter(Date::class);

        return $dateAdapter->parse($tag[2] ?? $this->getDateFormat(), $timestamp);
    }

    /**
     * Returns the datimFormat setting from the current front end page or the back end config.
     */
    private function getDateFormat(): string
    {
        $attributes = $this->requestStack->getCurrentRequest()->attributes;

        if ($attributes->has('pageModel') && ($page = $attributes->get('pageModel')) instanceof PageModel) {
            /** @var PageModel $page */
            return $page->datimFormat;
        }

        /** @var Config $configAdapter */
        $configAdapter = $this->framework->getAdapter(Config::class);

        return $configAdapter->get('datimFormat');
    }
}
