<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Page;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\PageModel;

class AdjustAutoforwardSubpaletteListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(DataContainer $dc): void
    {
        if ('tl_page' !== $dc->table || !$dc->id) {
            return;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        if (null !== ($page = $pageAdapter->findByPk($dc->id)) && 'error_403' === $page->type) {
            $GLOBALS['TL_DCA'][$dc->table]['subpalettes']['autoforward'] = str_replace(',redirect', '', $GLOBALS['TL_DCA'][$dc->table]['subpalettes']['autoforward']);
        }
    }
}
