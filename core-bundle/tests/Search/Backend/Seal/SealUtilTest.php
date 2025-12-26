<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Seal;

use CmsIg\Seal\Reindex\ReindexConfig as SealReindexConfig;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\CoreBundle\Search\Backend\Seal\SealReindexProvider;
use Contao\CoreBundle\Search\Backend\Seal\SealUtil;
use PHPUnit\Framework\TestCase;

class SealUtilTest extends TestCase
{
    public function testInternalConfigToSealConfig(): void
    {
        $reindexConfig = new ReindexConfig();
        $sealReindexConfig = SealUtil::internalReindexConfigToSealReindexConfig($reindexConfig);

        $this->assertSame([], $sealReindexConfig->getIdentifiers());
        $this->assertNull($sealReindexConfig->getDateTimeBoundary());

        $dateTime = new \DateTime();
        $reindexConfig = $reindexConfig->limitToDocumentsNewerThan($dateTime);
        $sealReindexConfig = SealUtil::internalReindexConfigToSealReindexConfig($reindexConfig);

        $this->assertSame([], $sealReindexConfig->getIdentifiers());
        $this->assertSame($dateTime, $sealReindexConfig->getDateTimeBoundary());

        $reindexConfig = $reindexConfig->limitToDocumentIds(new GroupedDocumentIds([
            'foobar' => ['42', '99'],
            'other' => ['12'],
        ]));

        $sealReindexConfig = SealUtil::internalReindexConfigToSealReindexConfig($reindexConfig);

        $this->assertSame(['foobar__42', 'foobar__99', 'other__12'], $sealReindexConfig->getIdentifiers());
        $this->assertSame(SealReindexProvider::getIndex(), $sealReindexConfig->getIndex());
    }

    public function testSealConfigToInternalConfig(): void
    {
        $sealReindexConfig = new SealReindexConfig();
        $reindexConfig = SealUtil::sealReindexConfigToInternalReindexConfig($sealReindexConfig);

        $this->assertTrue($reindexConfig->getLimitedDocumentIds()->isEmpty());
        $this->assertNull($reindexConfig->getUpdateSince());

        $dateTime = new \DateTime();
        $sealReindexConfig = $sealReindexConfig->withDateTimeBoundary($dateTime);
        $reindexConfig = SealUtil::sealReindexConfigToInternalReindexConfig($sealReindexConfig);

        $this->assertTrue($reindexConfig->getLimitedDocumentIds()->isEmpty());
        $this->assertSame($dateTime, $reindexConfig->getUpdateSince());

        $sealReindexConfig = $sealReindexConfig->withIdentifiers(['foobar__42', 'foobar__99', 'other__12']);
        $reindexConfig = SealUtil::sealReindexConfigToInternalReindexConfig($sealReindexConfig);

        $this->assertSame(
            [
                'foobar' => ['42', '99'],
                'other' => ['12'],
            ],
            $reindexConfig->getLimitedDocumentIds()->toArray(),
        );
    }

    public function testGetGlobalDocumentId(): void
    {
        $this->assertSame('foobar__42', SealUtil::getGlobalDocumentId('foobar', '42'));
    }

    public function testConvertProviderDocumentForSearchIndex(): void
    {
        $document = new Document('42', 'foobar', 'searchable');
        $document = $document->withMetadata(['meta' => 'data']);
        $document = $document->withTags(['tag1', 'tag2']);

        $this->assertSame(
            [
                'id' => 'foobar__42',
                'type' => 'foobar',
                'searchableContent' => 'searchable',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'document' => '{"id":"42","type":"foobar","searchableContent":"searchable","tags":["tag1","tag2"],"metadata":{"meta":"data"}}',
            ],
            SealUtil::convertProviderDocumentForSearchIndex($document),
        );
    }
}
