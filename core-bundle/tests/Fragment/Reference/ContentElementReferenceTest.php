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

use Contao\ContentModel;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Tests\TestCase;

class ContentElementReferenceTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $reference = new ContentElementReference(new ContentModel());

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\Reference\ContentElementReference', $reference);
        $this->assertInstanceOf('Contao\CoreBundle\Fragment\Reference\FragmentReference', $reference);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Controller\ControllerReference', $reference);
    }

    public function testCreatesTheControllerNameFromTheModelType(): void
    {
        $model = new ContentModel();
        $model->type = 'foobar';

        $reference = new ContentElementReference($model);

        $this->assertSame(ContentElementReference::TAG_NAME.'.foobar', $reference->controller);
    }

    public function testAddsTheSectionAttribute(): void
    {
        $model = new ContentModel();
        $model->type = 'foobar';

        $reference = new ContentElementReference($model);
        $this->assertSame('main', $reference->attributes['section']);

        $reference = new ContentElementReference($model, 'header');
        $this->assertSame('header', $reference->attributes['section']);

        $reference = new ContentElementReference($model, 'footer');
        $this->assertSame('footer', $reference->attributes['section']);
    }
}
