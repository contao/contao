<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\EventListener;

use Contao\CoreBundle\Event\MemberActivationMailEvent;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class MemberActivationMailListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(MemberActivationMailEvent $event): void
    {
        $newsletter = StringUtil::deserialize($event->getMember()->newsletter, true);

        if (empty($newsletter)) {
            return;
        }

        $channels = $this->connection->fetchFirstColumn(
            'SELECT title FROM tl_newsletter_channel WHERE id IN (?)',
            [$newsletter],
            [Types::SIMPLE_ARRAY]
        );

        if (!$channels) {
            return;
        }

        $event->addSimpleToken('channels', implode("\n", $channels));
    }
}
