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

use Contao\DataContainer;
use Contao\DC_Table;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class ToggleNodesLabelListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(string $table): void
    {
        if (
            DC_Table::class !== DataContainer::getDriverForTable($table)
            || !isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes'])
            || isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'])
            || 'ptg=all' !== ($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['href'] ?? null)
        ) {
            return;
        }

        $session = ($request = $this->requestStack->getCurrentRequest()) ? $request->getSession() : null;

        if (null === $session) {
            return;
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $session->getBag('contao_backend');
        $session = $sessionBag->all();

        $node = $table.'_tree';

        if (6 === (int) ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? 0)) {
            $node = $table.'_'.($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? '').'_tree';
        }

        if (empty($session[$node]) || !\is_array($session[$node]) || 1 !== (int) current($session[$node])) {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['expandNodes'];
        } else {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['collapseNodes'];
        }
    }
}
