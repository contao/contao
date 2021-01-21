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
use Doctrine\DBAL\Connection;
use Contao\CoreBundle\ServiceAnnotation\Callback;

/**
 * @internal
 *
 * @Callback(table="tl_content", target="fields.customTpl.save")
 * @Callback(table="tl_module", target="fields.customTpl.save"
 */
class ResetCustomTemplateListener
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check if we need to reset the template
     *
     * @param mixed $varValue
     *
     * @return mixed
     */
    public function __invoke($varValue, DataContainer $dc)
    {
        if ($dc->activeRecord->type !== $varValue) {
            $GLOBALS['TL_DCA'][$dc->table]['onsubmit_callback'][] = function (DataContainer $dc) {
                $this->resetTemplate($dc);
            };
        }

        return $varValue;
    }

    /**
     * Reset the template if the element type has changed
     */
    private function resetTemplate(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->connection->update(
            $dc->table,
            ['customTpl' => ''],
            ['id' => $dc->id]
        );
    }
}
