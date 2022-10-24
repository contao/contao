<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

declare(strict_types=1);

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

    public function __invoke(string $table)
    {
        if (
            DataContainer::getDriverForTable($table) !== DC_Table::class
            || !isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes'])
            || isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'])
            || 'ptg=all' !== ($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['href'] ?? null)
        ) {
            return;
        }

        try {
            $session = $this->requestStack->getCurrentRequest()->getSession();
        } catch (SessionNotFoundException $e) {
            return;
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $session->getBag('contao_backend');
        $session = $sessionBag->all();

        $node = $table . '_tree';

        if ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] == 6) {
            $node = $table . '_' . ($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? '') . '_tree';
        }

        if (empty($session[$node]) || !\is_array($session[$node]) || current($session[$node]) != 1) {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['expandNodes'];
        } else {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['collapseNodes'];
        }
    }
}
