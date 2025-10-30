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

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Job\Owner;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class JobsListener
{
    public function __construct(
        private readonly Jobs $jobs,
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $contaoFramework,
    ) {
    }

    #[AsCallback(table: 'tl_job', target: 'list.operations.attachments.button')]
    public function onAttachmentsCallback(DataContainerOperation $operation): void
    {
        $uuid = $operation->getRecord()['uuid'];
        $job = $this->jobs->getByUuid($uuid);

        if (!$job) {
            $operation->hide();

            return;
        }

        $attachments = $this->jobs->getAttachments($job);
        $numberOfAttachments = \count($attachments);

        if (0 === $numberOfAttachments) {
            $operation->hide();

            return;
        }

        // TODO: we need a template and Stimulus logic to have an operation with sub
        // operations just like the [...] in the current context menu to be able to
        // display more than just one download
        $attachment = $attachments[0];

        $operation['icon'] = 'theme_import.svg';
        $operation['title'] = $attachment->getFileLabel();

        $operation->setUrl($attachment->getDownloadUrl());
    }

    #[AsCallback(table: 'tl_job', target: 'list.operations.children.button')]
    public function onChildrenCallback(DataContainerOperation $operation): void
    {
        $childCount = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_job WHERE pid = ?',
            [(string) $operation->getRecord()['id']],
        );

        if ($childCount < 1) {
            $operation->hide();
        }
    }

    #[AsCallback(table: 'tl_job', target: 'config.onload')]
    public function onLoadCallback(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $userId = $this->getContaoBackendUserId();

        if (!$request || 0 === $userId) {
            return;
        }

        $this->contaoFramework->getAdapter(System::class)->loadLanguageFile('jobs');

        // Job children view
        if ($request->query->has('ptable')) {
            $pidFilter = 'pid != 0';
            $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['mode'] = DataContainer::MODE_PARENT;
            $GLOBALS['TL_DCA']['tl_job']['list']['label']['fields'] = ['uuid', 'status'];
            $GLOBALS['TL_DCA']['tl_job']['list']['label']['format'] = '%s <span class="label-info">%s</span>';
            unset($GLOBALS['TL_DCA']['tl_job']['list']['operations']['children']);
        } else {
            $pidFilter = 'pid = 0';
        }

        $query = \sprintf('%s AND (owner = %d OR (public = 1 AND owner = %d))',
            $pidFilter,
            $userId,
            Owner::SYSTEM,
        );

        $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['filter'][] = $query;
    }

    /**
     * @return int 0 if no Contao back end user was given
     */
    private function getContaoBackendUserId(): int
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser) {
            return (int) $user->id;
        }

        return 0;
    }
}
