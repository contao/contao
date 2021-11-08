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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

/**
 * @Callback(table="tl_page", target="fields.canonicalLink.load")
 * @Callback(table="tl_page", target="fields.canonicalKeepParams.load")
 */
class DisableCanonicalFieldsListener
{
    public function __invoke(string $value, DataContainer $dc): string
    {
        if ($dc->activeRecord && !$dc->activeRecord->enableCanonical) {
            $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['disabled'] = true;
        }

        return $value;
    }
}
