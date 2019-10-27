<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity\Module;

use Contao\CoreBundle\Entity\Module;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FormModule extends Module
{
    /**
     * @ORM\Column(name="form", type="integer", options={"unsigned": true, "default": 0})
     *
     * @var int
     */
    protected $form;

    public function getForm(): ?int
    {
        return 0 !== $this->form ? $this->form : null;
    }
}
