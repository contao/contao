<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Form\Type;

use Contao\CoreBundle\Form\Type\ChangePasswordType;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Form\FormBuilder;

class ChangePasswordTypeTest extends TestCase
{
    public function testBuildForm(): void
    {
        $formType = new ChangePasswordType();

        $builder = $this->createMock(FormBuilder::class);
        $builder
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($builder)
        ;

        $formType->buildForm($builder, []);
    }
}
