<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Entity;

use Contao\CoreBundle\Entity\Module;
use Contao\StringUtil;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FaqModule extends Module
{
    /**
     * @ORM\Column(name="faq_categories", type="blob", length=Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_BLOB, nullable=true)
     *
     * @var string
     */
    protected $categories = '';

    /**
     * @ORM\Column(name="faq_readerModule", type="integer", options={"unsigned": true, "default": 0})
     *
     * @var int
     */
    protected $readerModule;

    /**
     * @return int[]
     */
    public function getCategories(): array
    {
        return StringUtil::deserialize($this->categories, true);
    }

    public function getReaderModule(): int
    {
        return $this->readerModule;
    }
}
