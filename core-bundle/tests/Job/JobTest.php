<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Job;

use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Owner;
use Contao\CoreBundle\Job\Status;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Uid\Uuid;

class JobTest extends TestCase
{
    public function testBasicGetters(): void
    {
        $job = $this->getJob();
        $this->assertSame('9ad2f29c-671b-4a1a-9a15-dabda4fd6bad', $job->getUuid());
        $this->assertSame('2025-01-01 00:00:00', $job->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(Status::NEW, $job->getStatus());
        $this->assertSame(Owner::asSystem()->getIdentifier(), $job->getOwner()->getIdentifier());
        $this->assertSame(
            [
                'uuid' => '9ad2f29c-671b-4a1a-9a15-dabda4fd6bad',
                'createdAt' => '2025-01-01 00:00:00',
                'progress' => 0.0,
                'metadata' => [],
                'errors' => [],
                'warnings' => [],
                'parent' => null,
                'children' => [],
            ],
            $job->toArray(),
        );
    }

    public function testMarkPendingChangesStatus(): void
    {
        $job = $this->getJob()->markPending();
        $this->assertSame(Status::PENDING, $job->getStatus());
    }

    public function testMarkFinishedChangesStatus(): void
    {
        $job = $this->getJob()->markFinished();
        $this->assertSame(Status::FINISHED, $job->getStatus());
    }

    public function testCanAddWarnings(): void
    {
        $job = $this->getJob();
        $this->assertFalse($job->hasWarnings());
        $warnings = ['warning-1', 'warning-2'];
        $job = $job->withWarnings($warnings);
        $this->assertSame($warnings, $job->getWarnings());
        $this->assertTrue($job->hasWarnings());
    }

    public function testWithProgressSetsCorrectValue(): void
    {
        $job = $this->getJob()->withProgress(42.0);
        $this->assertSame(42.0, $job->getProgress());
    }

    public function testWithMetadataStoresData(): void
    {
        $metadata = ['key' => 'value'];
        $job = $this->getJob()->withMetadata($metadata);
        $this->assertSame($metadata, $job->getMetadata());
    }

    public function testWithIsPublicThrowsExceptionForSystemOwner(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Job(Uuid::v4()->toRfc4122(), new \DateTimeImmutable(), Status::NEW, Owner::asSystem()))
            ->withIsPublic(true)
        ;
    }

    public function testParentChildHandling(): void
    {
        $parent = $this->getJob();
        $child = $this->getJob('5b79effc-9744-4c8a-bcb5-ed78c9c00eaa')->withParent($parent);
        $parent = $parent->withChildren([$child]);
        $this->assertSame($parent->getUuid(), $child->getParent()->getUuid());
        $this->assertSame($parent->getChildren()[0]->getUuid(), $child->getUuid());
    }

    private function getJob(string $uuid = '9ad2f29c-671b-4a1a-9a15-dabda4fd6bad'): Job
    {
        return new Job(
            $uuid,
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            Status::NEW,
            Owner::asSystem(),
        );
    }
}
