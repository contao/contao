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
use webignition\RobotsTxt\Directive\UserAgentDirective;
use webignition\RobotsTxt\Inspector\Inspector;
use webignition\RobotsTxt\Record\Record;

/**
 * @internal
 */
class RobotsTxtListener
{
    private ContaoFramework $contaoFramework;
    private string $routePrefix;

    public function __construct(ContaoFramework $contaoFramework, string $routePrefix = '/contao')
    {
        $this->contaoFramework = $contaoFramework;
        $this->routePrefix = $routePrefix;
    }

    public function __invoke(RobotsTxtEvent $event): void
    {
        $this->contaoFramework->initialize();

        $file = $event->getFile();
        $inspector = new Inspector($file, '*');

        // Get all directives for user-agent: *
        $directiveList = $inspector->getDirectives();

        // If no directive for user-agent: * exists, we add the record
        if (0 === $directiveList->getLength()) {
            $record = new Record();

            $userAgentDirectiveList = $record->getUserAgentDirectiveList();
            $userAgentDirectiveList->add(new UserAgentDirective('*'));

            $file->addRecord($record);
        }

        $records = $file->getRecords();

        foreach ($records as $record) {
            $directiveList = $record->getDirectiveList();
            $directiveList->add(new Directive('Disallow', $this->routePrefix.'/'));
            $directiveList->add(new Directive('Disallow', '/_contao/'));
        }

        $pageModel = $this->contaoFramework->getAdapter(PageModel::class);

        // Only fetch the fallback page because there can only be one sitemap per host
        $rootPage = $pageModel->findPublishedFallbackByHostname($event->getRootPage()->dns);

        if (null === $rootPage) {
            return;
        }

        $sitemap = sprintf(
            '%s%s/sitemap.xml',
            $rootPage->useSSL ? 'https://' : 'http://',
            $rootPage->dns ?: $event->getRequest()->server->get('HTTP_HOST')
        );

        $event->getFile()->getNonGroupDirectives()->add(new Directive('Sitemap', $sitemap));
    }
}
