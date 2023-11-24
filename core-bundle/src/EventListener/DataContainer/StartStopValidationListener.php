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
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: 'tl_article', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_content', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_page', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_member', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_member_group', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_user', target: 'config.onbeforesubmit')]
#[AsCallback(table: 'tl_user_group', target: 'config.onbeforesubmit')]
class StartStopValidationListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(array $values, DataContainer $dc): array
    {
        $hasStart = \array_key_exists('start', $values);
        $hasStop = \array_key_exists('stop', $values);

        // Both fields were not submitted, do not validate since the user cannot change it anyway
        if (!$hasStart && !$hasStop) {
            return $values;
        }

        $start = (string) ($hasStart ? $values['start'] : $dc->getCurrentRecord()['start']);
        $stop = (string) ($hasStop ? $values['stop'] : $dc->getCurrentRecord()['stop']);

        if ('' !== $start && '' !== $stop && $stop < $start) {
            throw new \RuntimeException($this->translator->trans('ERR.startStop', [], 'contao_default'));
        }

        return $values;
    }
}
