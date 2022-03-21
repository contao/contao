<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fragment\Reference;

use Contao\ContentModel;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Tests\TestCase;

class ContentElementReferenceTest extends TestCase
{
    public function testCreatesTheControllerNameFromTheModelType(): void
    {
        $model = $this->mockClassWithProperties(ContentModel::class, ['type' => 'foobar']);

        $reference = new ContentElementReference($model);
        $this->assertSame(ContentElementReference::TAG_NAME.'.foobar', $reference->controller);
    }

    public function testAddsTheSectionAttribute(): void
    {
        $model = $this->createMock(ContentModel::class);

        $reference = new ContentElementReference($model);
        $this->assertSame('main', $reference->attributes['section']);

        $reference = new ContentElementReference($model, 'header');
        $this->assertSame('header', $reference->attributes['section']);

        $reference = new ContentElementReference($model, 'footer');
        $this->assertSame('footer', $reference->attributes['section']);
    }
}
