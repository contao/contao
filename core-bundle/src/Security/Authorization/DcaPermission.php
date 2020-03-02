<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authorization;

class DcaPermission
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string|null
     */
    private $id;

    public function __construct(string $table, string $id = null)
    {
        $this->table = $table;
        $this->id = $id;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
