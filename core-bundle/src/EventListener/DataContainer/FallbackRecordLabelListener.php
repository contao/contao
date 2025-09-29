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

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\DcaLoader;
use Contao\StringUtil;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener(priority: -1)]
class FallbackRecordLabelListener
{
    public function __construct(private readonly TranslatorInterface&TranslatorBagInterface $translator)
    {
    }

    public function __invoke(DataContainerRecordLabelEvent $event): void
    {
        if (null !== $event->getLabel() || !str_starts_with($event->getIdentifier(), 'contao.db.')) {
            return;
        }

        [, , $table, $id] = explode('.', $event->getIdentifier()) + [null, null, null, null];

        if (!$table || !$id) {
            return;
        }

        (new DcaLoader($table))->load();

        $defaultSearchField = $GLOBALS['TL_DCA'][$table]['list']['sorting']['defaultSearchField'] ?? null;

        if ($defaultSearchField && ($label = $event->getData()[$defaultSearchField] ?? null)) {
            $event->setLabel(trim(StringUtil::decodeEntities(strip_tags((string) $label))));
        } else {
            $messageDomain = "contao_$table";

            if ($this->translator->getCatalogue()->has("$table.edit.1", $messageDomain)) {
                $label = $this->translator->trans("$table.edit.1", [$event->getData()['id']], $messageDomain);
            } elseif ($this->translator->getCatalogue()->has("$table.edit", $messageDomain)) {
                $label = $this->translator->trans("$table.edit", [$event->getData()['id']], $messageDomain);
            } else {
                $label = $this->translator->trans('DCA.edit.1', [$event->getData()['id']], 'contao_default');
            }

            $event->setLabel($label);
        }
    }
}
