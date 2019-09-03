<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Event\RobotsTxtEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use webignition\RobotsTxt\Directive\Directive;

class RobotsTxtListener
{
    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    public function __construct(ContaoFramework $contaoFramework)
    {
        $this->contaoFramework = $contaoFramework;
    }

    public function onRobotsTxt(RobotsTxtEvent $event): void
    {
        $file = $event->getFile();

        $records = $file->getRecords();

        // Disallow /contao for every directive
        foreach ($records as $record) {
            $record->getDirectiveList()->add(new Directive('Disallow', '/contao'));
        }

        // Find all matching root pages
        $rootPages = $this->contaoFramework->getAdapter(PageModel::class)
            ->findPublishedByHostname($event->getRootPage()->dns)
        ;

        // Generate the sitemaps
        foreach ($rootPages as $rootPage) {
            if (!$rootPage->createSitemap) {
                continue;
            }

            $sitemap = sprintf('%s/share/%s.xml',
                ($rootPage->useSSL ? 'https://' : 'http://').($rootPage->dns ?: $event->getRequest()->server->get('HTTP_HOST')),
                $rootPage->sitemapName
            );

            $event->getFile()->getNonGroupDirectives()->add(new Directive('Sitemap', $sitemap));
        }
    }
}
