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
use webignition\RobotsTxt\Inspector\Inspector;
use webignition\RobotsTxt\Record\Record;

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
        $this->contaoFramework->initialize();

        $file = $event->getFile();
        $records = $file->getRecords();

        $inspector = new Inspector($file);
        $directiveList = $inspector->getDirectives(); // get all directives for user-agent:*

        // If no directive for user-agent: * exists we add the record
        if (0 === $directiveList->getLength()) {
            $record = new Record();
            $this->addContaoDisallowDirectivesToRecord($record);
            $file->addRecord($record);
        }

        foreach ($records as $record) {
            $this->addContaoDisallowDirectivesToRecord($record);
        }

        // Find all matching root pages
        /** @var PageModel $pageModel */
        $pageModel = $this->contaoFramework->getAdapter(PageModel::class);
        $rootPages = $pageModel->findPublishedByHostname($event->getRootPage()->dns);

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

    private function addContaoDisallowDirectivesToRecord(Record $record): void
    {
        $directiveList = $record->getDirectiveList();

        $directive1 = new Directive('Disallow', '/contao$');
        $directive2 = new Directive('Disallow', '/contao?');
        $directive3 = new Directive('Disallow', '/contao/');

        $directiveList->add($directive1);
        $directiveList->add($directive2);
        $directiveList->add($directive3);
    }
}
