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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Automatically disables the copying of an email address if it already exists in
 * the target newsletter channel.
 */
#[AsCallback('tl_newsletter_recipients', 'config.onload')]
class NewsletterRecipientsCopyListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $act = $request->query->get('act');
        $sourceRecordId = $request->query->get('id');
        $targetParentId = $request->query->get('pid');
        $mode = (int) $request->query->get('mode');

        if ('copy' !== $act || 2 !== $mode || !$targetParentId || !$sourceRecordId) {
            return;
        }

        // Check if the source record has the same parent as the target
        if ($this->connection->fetchOne('SELECT TRUE FROM tl_newsletter_recipients WHERE id = ? and pid = ?', [$sourceRecordId, $targetParentId])) {
            $GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['email']['eval']['doNotCopy'] = true;
        }
    }
}
