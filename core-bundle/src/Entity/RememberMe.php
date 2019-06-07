<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="tl_remember_me",
 *     indexes={
 *         @ORM\Index(name="series", columns={"series"})
 *     }
 * )
 */
class RememberMe
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=88, nullable=false, options={"fixed"=true})
     */
    protected $series;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=88, nullable=false, options={"fixed"=true})
     * @ORM\Id
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastUsed;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $expires;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $class;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=200, nullable=false)
     */
    protected $username;
}
