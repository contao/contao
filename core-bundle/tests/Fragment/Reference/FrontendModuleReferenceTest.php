<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\Reference;

use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleModel;

class FrontendModuleReferenceTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $reference = new FrontendModuleReference(new ModuleModel());

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\Reference\FrontendModuleReference', $reference);
        $this->assertInstanceOf('Contao\CoreBundle\Fragment\Reference\FragmentReference', $reference);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Controller\ControllerReference', $reference);
    }

    public function testCreatesTheControllerNameFromTheModelType(): void
    {
        $model = new ModuleModel();
        $model->type = 'foobar';

        $reference = new FrontendModuleReference($model);

        $this->assertSame(FrontendModuleReference::TAG_NAME.'.foobar', $reference->controller);
    }

    public function testAddsTheSectionAttribute(): void
    {
        $model = new ModuleModel();
        $model->type = 'foobar';

        $reference = new FrontendModuleReference($model);
        $this->assertSame('main', $reference->attributes['section']);

        $reference = new FrontendModuleReference($model, 'header');
        $this->assertSame('header', $reference->attributes['section']);

        $reference = new FrontendModuleReference($model, 'footer');
        $this->assertSame('footer', $reference->attributes['section']);
    }
}
