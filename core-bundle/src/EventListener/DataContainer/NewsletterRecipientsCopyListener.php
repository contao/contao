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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Automatically disables the copying of an email address if it already exists in the target newsletter channel.
 *
 * @Callback(table="tl_newsletter_recipients", target="config.onload")
 */
class NewsletterRecipientsCopyListener
{
    private RequestStack $requestStack;
    private Connection $connection;

    public function __construct(RequestStack $requestStack, Connection $connection)
    {
        $this->requestStack = $requestStack;
        $this->connection = $connection;
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

        $email = $this->connection->fetchOne('SELECT email FROM tl_newsletter_recipients WHERE id = ?', [$sourceRecordId]);

        // Check if the email already exists in the target
        if ($this->connection->fetchOne('SELECT TRUE FROM tl_newsletter_recipients WHERE email = ? AND pid = ?', [$email, $targetParentId])) {
            $GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['email']['eval']['doNotCopy'] = true;
        }
    }
}
