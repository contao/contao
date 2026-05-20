<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'prepare_for_output_encoding')]
#[Entity]
#[Index(name: 'performed_migration', columns: ['performed_migration'])]
class PrepareForOutputEncoding
{
    #[Id]
    #[Column(name: 'table_name')]
    public string $tableName;

    #[Id]
    #[Column(name: 'column_name')]
    public string $columnName;

    #[Column(name: 'encoding_options')]
    public array $encodingOptions;

    #[Column(name: 'performed_migration')]
    public bool $performedMigration;
}
