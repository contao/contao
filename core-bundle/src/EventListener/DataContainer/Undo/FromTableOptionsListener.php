<?php declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Undo;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;

class FromTableOptionsListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /** @Callback(table="tl_undo", target="fields.options.fromTable") */
    public function __invoke()
    {
        $tables = $this->connection->executeQuery('SELECT DISTINCT ' . $this->connection->quoteIdentifier('fromTable') . ' FROM tl_undo');

        if (0 === $tables->rowCount()) {
            return array();
        }

        $options = array();

        foreach ($tables->fetchFirstColumn() as $table) {
            $options[] = $table;
        }

        return $options;
    }

}
