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

/**
 * @internal
 */
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

    public function __invoke(RobotsTxtEvent $event): void
    {
        $this->contaoFramework->initialize();

        $file = $event->getFile();
        $inspector = new Inspector($file);

        // Get all directives for user-agent: *
        $directiveList = $inspector->getDirectives();

        // If no directive for user-agent: * exists, we add the record
        if (0 === $directiveList->getLength()) {
            $record = new Record();
            $this->addContaoDisallowDirectivesToRecord($record);
            $file->addRecord($record);
        }

        $records = $file->getRecords();

        foreach ($records as $record) {
            $this->addContaoDisallowDirectivesToRecord($record);
        }

        /** @var PageModel $pageModel */
        $pageModel = $this->contaoFramework->getAdapter(PageModel::class);
        $rootPages = $pageModel->findPublishedRootPages(['dns' => $event->getRootPage()->dns]);

        // Generate the sitemaps
        foreach ($rootPages as $rootPage) {
            if (!$rootPage->createSitemap) {
                continue;
            }

            $sitemap = sprintf(
                '%s%s/share/%s.xml',
                $rootPage->useSSL ? 'https://' : 'http://',
                $rootPage->dns ?: $event->getRequest()->server->get('HTTP_HOST'),
                $rootPage->sitemapName
            );

            $event->getFile()->getNonGroupDirectives()->add(new Directive('Sitemap', $sitemap));
        }
    }

    private function addContaoDisallowDirectivesToRecord(Record $record): void
    {
        $directiveList = $record->getDirectiveList();
        $directiveList->add(new Directive('Disallow', '/contao/'));
    }
}
