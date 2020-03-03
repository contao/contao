<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm\Extension;

use Contao\CoreBundle\Orm\Annotation\Extension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Extension("Contao\CoreBundle\Orm\Entity\Content")
 */
trait Meep
{
    /**
     * @ORM\Column(type="integer", name="meep")
     */
    private $meep;

    public function getMeep()
    {
        return $this->meep;
    }

    public function setMeep($meep)
    {
        $this->meep = $meep;
        return $this;
    }
}
