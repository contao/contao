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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\PageModel;

/**
 * @Callback(table="tl_page", target="fields.canonicalLink.load")
 * @Callback(table="tl_page", target="fields.canonicalKeepParams.load")
 */
class DisableCanonicalFieldsListener
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(string $value, DataContainer $dc): string
    {
        if (!$dc->id) {
            return $value;
        }

        /** @var PageModel $adapter */
        $adapter = $this->framework->getAdapter(PageModel::class);

        if (($page = $adapter->findWithDetails($dc->id)) && !$page->enableCanonical) {
            $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['disabled'] = true;
        }

        return $value;
    }
}
