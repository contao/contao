<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm\Entity;

/**
 * TODO: laminas-code does not include docblock comment namespaces
 * TODO: we probably have to fix this in DocBlockScanner ourself
 *
 * @see https://github.com/laminas/laminas-soap/issues/8
 */
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This is a basic entity.
 *
 * Well to be honest, this was only a comment for a basic entity.
 * And this text is further describing it.
 *
 * @ORM\Entity
 * @ORM\Table(name="meep_content", indexes={@ORM\Index(name="search_idx", columns={"id", "test"})})
 * @UniqueEntity("test")
 */
abstract class Content
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text", name="test")
     */
    protected $test;

    public function getId(): ?int
    {
        return $this->id;
    }
}
