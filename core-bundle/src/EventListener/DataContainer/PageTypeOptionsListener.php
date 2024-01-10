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
use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DataContainer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCallback(table: 'tl_page', target: 'fields.type.options')]
#[AsCallback(table: 'tl_user', target: 'fields.alpty.options')]
#[AsCallback(table: 'tl_user_group', target: 'fields.alpty.options')]
class PageTypeOptionsListener
{
    public function __construct(
        private readonly PageRegistry $pageRegistry,
        private readonly Security $security,
        private readonly EventDispatcherInterface|null $eventDispatcher = null,
    ) {
    }

    public function __invoke(DataContainer $dc): array
    {
        $options = array_unique([...array_keys($GLOBALS['TL_PTY']), ...$this->pageRegistry->keys()]);

        if ('tl_user' === $dc->table || 'tl_user_group' === $dc->table) {
            return array_values($options);
        }

        if ($this->eventDispatcher) {
            $options = $this->eventDispatcher
                ->dispatch(new FilterPageTypeEvent($options, $dc))
                ->getOptions()
            ;
        }

        // Allow the currently selected option and anything the user has access to
        foreach ($options as $k => $pageType) {
            if (
                $pageType !== $dc->value
                && !$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_page', new UpdateAction('tl_page', $dc->getCurrentRecord(), ['type' => $pageType]))
            ) {
                unset($options[$k]);
            }
        }

        return array_values($options);
    }
}
