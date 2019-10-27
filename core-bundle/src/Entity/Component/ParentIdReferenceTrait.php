<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity\Component;

trait ParentIdReferenceTrait
{
    /**
     * @ORM\Column(name="pid", type="integer", options={"unsigned": true})
     *
     * @var int
     */
    protected $parentId;

    public function getParentId(): ?int
    {
        return 0 !== $this->parentId ? $this->parentId : null;
    }
}
