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

use Contao\CoreBundle\Doctrine\ORM\ExtendableEntity\ExtendableEntity;
use Contao\CoreBundle\Entity\Component\DcaDefault;
use Contao\CoreBundle\Entity\Component\ParentIdReferenceTrait;
use Contao\StringUtil;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tl_module")
 * @ORM\Entity()
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *   "form" = "Contao\CoreBundle\Entity\Module\FormModule",
 *   "html" = "Contao\CoreBundle\Entity\Module\HtmlModule"
 * })
 */
abstract class Module extends DcaDefault implements ExtendableEntity
{
    use ParentIdReferenceTrait;

    /**
     * @ORM\Column(name="name", options={"default": ""})
     *
     * @var string
     */
    protected $name = '';

    /**
     * @ORM\Column(name="headline", options={"default": "a:2:{s:5:\""value\"";s:0:\""\"";s:4:\""unit\"";s:2:\""h2\"";}"})
     *
     * @var string
     */
    protected $headline = '';

    /**
     * @ORM\Column(name="protected", length=1, options={"fixed": true, "default": ""})
     *
     * @var string
     */
    protected $isProtected = '';

    /**
     * @ORM\Column(name="groups", type="blob", length=Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_BLOB, nullable=true)
     *
     * @var string
     */
    protected $allowedGroups = '';

    /**
     * @ORM\Column(name="guests", length=1, options={"fixed": true, "default": ""})
     *
     * @var string
     */
    protected $isOnlyVisibleToGuests = '';

    /**
     * @ORM\Column(name="customTpl", length=64, options={"default": ""})
     *
     * @var string
     */
    protected $customTemplate;

    /**
     * @ORM\Column(name="cssID", options={"default": ""})
     *
     * @var string
     */
    protected $cssId = '';

    public function getType(): string
    {
        return (static::class)::TYPE;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isProtected(): bool
    {
        return '1' === $this->isProtected;
    }

    public function isOnlyVisibleToGuests(): bool
    {
        return '1' === $this->isOnlyVisibleToGuests;
    }

    /**
     * @return int[]
     */
    public function getAllowedGroups(): array
    {
        return StringUtil::deserialize($this->allowedGroups, true);
    }

    public function getCssId(): string
    {
        return $this->cssId;
    }
}
