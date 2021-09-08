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

use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Callback(table="tl_page", target="fields.type.options")
 */
class PageTypeOptionsListener
{
    private PageRegistry $pageRegistry;
    private Security $security;
    private ?EventDispatcherInterface $eventDispatcher;

    public function __construct(PageRegistry $pageRegistry, Security $security, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->pageRegistry = $pageRegistry;
        $this->security = $security;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DataContainer $dc): array
    {
        $options = array_unique(array_merge(array_keys($GLOBALS['TL_PTY']), $this->pageRegistry->keys()));

        if (null !== $this->eventDispatcher) {
            $options = $this->eventDispatcher
                ->dispatch(new FilterPageTypeEvent($options, $dc))
                ->getOptions()
            ;
        }

        // Allow the currently selected option and anything the user has access to
        foreach ($options as $k => $pageType) {
            if (
                $pageType !== $dc->value
                && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $pageType)
            ) {
                unset($options[$k]);
            }
        }

        return array_values($options);
    }
}
