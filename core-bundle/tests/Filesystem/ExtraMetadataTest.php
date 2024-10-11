<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImportantPart;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ExtraMetadataTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testSetAndGetValues(): void
    {
        $data = [
            'foo' => 42,
            'localized' => new MetadataBag([
                'en' => new Metadata(['bar' => 'baz']),
            ]),
            'importantPart' => new ImportantPart(x: 0.5, width: .5),
        ];

        $extraMetadata = new ExtraMetadata($data);

        $this->assertSame(42, $extraMetadata->get('foo'));
        $this->assertSame(42, $extraMetadata['foo']);
        $this->assertTrue(isset($extraMetadata['foo']));

        $this->assertSame('baz', $extraMetadata->get('localized')->get('en')->get('bar'));
        $this->assertSame('baz', $extraMetadata->getLocalized()->get('en')->get('bar'));

        $this->assertSame(0.5, $extraMetadata->get('importantPart')->getX());
        $this->assertSame(0.5, $extraMetadata->getImportantPart()->getX());

        $this->assertNull($extraMetadata->get('non-existent'));
        $this->assertFalse(isset($extraMetadata['non-existent']));

        $this->assertSame($data, $extraMetadata->all());
    }

    /**
     * @group legacy
     */
    public function testTriggersDeprecationWhenInitializingWithMetadataKey(): void
    {
        $localizedMetadata = new MetadataBag([]);

        $this->expectDeprecation('%sUsing the key "metadata" to set localized metadata has been deprecated%s');

        $extraMetadata = new ExtraMetadata([
            'metadata' => $localizedMetadata,
        ]);

        $this->assertSame($localizedMetadata, $extraMetadata->getLocalized());
    }

    /**
     * @group legacy
     */
    public function testTriggersDeprecationWhenAccessingMetadataKey(): void
    {
        $localizedMetadata = new MetadataBag([]);

        $extraMetadata = new ExtraMetadata([
            'localized' => $localizedMetadata,
        ]);

        $this->expectDeprecation('%sUsing the key "metadata" to get localized metadata has been deprecated%s');

        $this->assertSame($localizedMetadata, $extraMetadata->get('metadata'));
    }
}
