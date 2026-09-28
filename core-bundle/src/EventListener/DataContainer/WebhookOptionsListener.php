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
use Contao\CoreBundle\Webhook\WebhookEventRegistry;
use Contao\CoreBundle\Webhook\WebhookReceiverRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookOptionsListener
{
    public function __construct(
        private readonly WebhookReceiverRegistry $receivers,
        private readonly WebhookEventRegistry $events,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'fields.receiver.options')]
    public function receiverOptions(): array
    {
        $options = [];

        foreach (array_keys($this->receivers->all()) as $name) {
            $options[$name] = $this->translator->trans('WHR.'.$name, [], 'contao_default');
        }

        return $options;
    }

    #[AsCallback(table: 'tl_webhook_outgoing', target: 'fields.events.options')]
    public function eventOptions(): array
    {
        $options = [];

        foreach ($this->events->all() as $metadata) {
            $options[$metadata['name']] = $this->translator->trans('WHK.'.$metadata['name'], [], 'contao_default');
        }

        return $options;
    }
}
