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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class ToggleNodesLabelListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(string $table): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // Ignore if not in the back end
        if (!$request instanceof Request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        if (!isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes'])) {
            return;
        }

        // Return if there is a custom label
        if (isset($GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'])) {
            return;
        }

        $href = $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['href'] ?? null;

        if ('ptg=all' !== $href && 'tg=all' !== $href) {
            return;
        }

        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return;
        }

        $sessionBag = $session->getBag('contao_backend');

        if (!$sessionBag instanceof AttributeBagInterface) {
            return;
        }

        $session = $sessionBag->all();
        $node = $table.'_tree';

        if (DataContainer::MODE_TREE_EXTENDED === (int) ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? 0)) {
            $node = $table.'_'.($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? '').'_tree';
        }

        if (empty($session[$node]) || !\is_array($session[$node]) || 1 !== (int) current($session[$node])) {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['expandNodes'];
        } else {
            $GLOBALS['TL_DCA'][$table]['list']['global_operations']['toggleNodes']['label'] = &$GLOBALS['TL_LANG']['DCA']['collapseNodes'];
        }
    }
}
