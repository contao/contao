<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authorization\DcaSubject;

class ParentSubject extends RootSubject
{
    /**
     * @var string
     */
    private $parentId;

    /**
     * @var string
     */
    private $parentTable;

    public function __construct(string $table, string $parentId, string $parentTable)
    {
        parent::__construct($table);

        $this->parentId = $parentId;
        $this->parentTable = $parentTable;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function getParentTable(): string
    {
        return $this->parentTable;
    }
}
