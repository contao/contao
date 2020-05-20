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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\PageModel;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class PageCallbackListener implements ServiceAnnotationInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @Callback(table="tl_page", target="config.onload")
     */
    public function adjustAutoforwardSubpalette(DataContainer $dc): void
    {
        if ('tl_page' !== $dc->table) {
            return;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        if (null !== ($page = $pageAdapter->findByPk($dc->id)) && \in_array($page->type, ['error_401', 'error_403'], true)) {
            PaletteManipulator::create()
                ->removeField('redirect')
                ->applyToSubpalette('autoforward', 'tl_page')
            ;
        }
    }
}
