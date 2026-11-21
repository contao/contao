<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="foo_translation")
 */
class FooTranslation
{
    public function __construct(
        /**
         * @ORM\Id
         * @ORM\ManyToOne(targetEntity=Foo::class)
         */
        private Foo $record,
        /**
         * @ORM\Id
         * @ORM\Column(type="string")
         */
        private string $locale,
    ) {
    }
}
