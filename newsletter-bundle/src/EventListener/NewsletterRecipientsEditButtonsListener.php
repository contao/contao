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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

/**
 * Removes the "Save and duplicate" button since you cannot have duplicate email
 * addresses in the same newsletter channel.
 */
#[AsCallback('tl_newsletter_recipients', 'edit.buttons')]
class NewsletterRecipientsEditButtonsListener
{
    public function __invoke(array $buttons): array
    {
        unset($buttons['saveNduplicate']);

        return $buttons;
    }
}
