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
class HtmlModule extends Module
{
    /**
     * @ORM\Column(name="html", type="text", length=Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_TEXT, options={"default": ""})
     *
     * @var string
     */
    protected $html = '';

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getCustomTemplate(): string
    {
        return $this->customTemplate;
    }
}
