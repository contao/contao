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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\MockClock;

class JobsTest extends AbstractJobsTestCase
{
    public function testCreateChildJob(): void
    {
        $jobs = $this->getJobs($this->mockSecurity(42));
        $parent = $jobs->createJob('job-type');

        $child = $jobs->createChildJob($parent);

        $this->assertSame($parent, $child->getParent());
    }

    #[DataProvider('createJobProvider')]
    public function testCreateJob(bool $userLoggedIn): void
    {
        $jobs = $this->getJobs($this->mockSecurity($userLoggedIn ? 42 : null));
        $job = $jobs->createJob('job-type');

        $this->assertSame($userLoggedIn ? 42 : Owner::SYSTEM, $job->getOwner()->getId());
    }

    #[DataProvider('withProgressFromAmountsProvider')]
    public function testWithProgressFromFixedAmounts(int|null $total, int $amount, float $expectedProgress): void
    {
        $jobs = $this->getJobs($this->mockSecurity());
        $job = $jobs->createJob('job-type');
        $job = $job->withProgressFromAmounts($amount, $total);
        $this->assertSame($expectedProgress, $job->getProgress());
    }

    public function testWithProgressFromUnknownTotal(): void
    {
        $jobs = $this->getJobs($this->mockSecurity());
        $job = $jobs->createJob('job-type');
        $job = $job->withProgressFromAmounts(100);
        $this->assertGreaterThan(0, $job->getProgress());
        $this->assertLessThan(100, $job->getProgress());
    }

    public static function withProgressFromAmountsProvider(): iterable
    {
        yield 'basic 25%' => [200, 50, 25.0];
        yield 'zero total returns same' => [0, 50, 0.0];
        yield 'cap to 100%' => [100, 200, 100.0];
        yield 'not negative' => [100, -50, 0.0];
        yield 'exact 100%' => [100, 100, 100.0];
        yield 'fractional percentage' => [3, 1, 100 / 3];
    }

    public static function createJobProvider(): iterable
    {
        yield 'No logged in user' => [false];
        yield 'Logged in back end user' => [true];
    }

    public function testCreateSystemJob(): void
    {
        $jobs = $this->getJobs();
        $job = $jobs->createSystemJob('my-type');

        $this->assertSame(Owner::SYSTEM, $job->getOwner()->getId());
    }

    public function testCreateUserJobThrowsExceptionIfNoUser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create a user job without having a user ID.');

        $jobs = $this->getJobs($this->mockSecurity());
        $jobs->createUserJob('job-type');
    }

    public function testEncodesAndDecodesDataCorrectlyForDCTable(): void
    {
        $jobs = $this->getJobs();
        $job = $jobs->createUserJob('strange > type', 42);
        $job = $jobs->getByUuid($job->getUuid());

        $this->assertSame('strange > type', $job->getType());
    }

    public function testFindingMyNewOrPendingRestrictsCorrectly(): void
    {
        $securityUser1 = $this->mockSecurity(1);
        $securityUser2 = $this->mockSecurity(2);

        $jobsUser1 = $this->getJobs($securityUser1);
        $jobsUser2 = $this->getJobs($securityUser2);

        $uuid1 = $jobsUser1->createUserJob('my-type')->getUuid();
        $uuid2 = $jobsUser1->createSystemJob('my-type')->getUuid();
        $uuid3 = $jobsUser1->createSystemJob('my-type', false)->getUuid();
        $uuid4 = $jobsUser2->createUserJob('my-type')->getUuid();

        $this->assertContains($uuid1, $this->jobsToUuids($jobsUser1->findMyNewOrPending()));
        $this->assertContains($uuid2, $this->jobsToUuids($jobsUser1->findMyNewOrPending()));
        $this->assertNotContains($uuid3, $this->jobsToUuids($jobsUser1->findMyNewOrPending()));
        $this->assertNotContains($uuid4, $this->jobsToUuids($jobsUser1->findMyNewOrPending()));

        $this->assertNotContains($uuid1, $this->jobsToUuids($jobsUser2->findMyNewOrPending()));
        $this->assertNotContains($uuid2, $this->jobsToUuids($jobsUser2->findMyNewOrPending()));
        $this->assertNotContains($uuid3, $this->jobsToUuids($jobsUser2->findMyNewOrPending()));
        $this->assertContains($uuid4, $this->jobsToUuids($jobsUser2->findMyNewOrPending()));
    }

    public function testStatusOfChildrenUpdatesParentJobStatus(): void
    {
        $jobs = $this->getJobs($this->mockSecurity(42));
        $parentJob = $jobs->createUserJob('my-type');
        $childJob1 = $jobs->createUserJob('my-type')->withMetadata(['child-1']);
        $childJob2 = $jobs->createUserJob('my-type')->withMetadata(['child-2']);

        $parentJob = $parentJob->withChildren([$childJob1, $childJob2]);
        $jobs->persist($parentJob);

        $parentJob = $jobs->getByUuid($parentJob->getUuid());
        $childJob1 = $jobs->getByUuid($childJob1->getUuid());
        $childJob2 = $jobs->getByUuid($childJob2->getUuid());

        $this->assertNull($parentJob->getParent());
        $this->assertSame($parentJob->getUuid(), $childJob1->getParent()->getUuid());
        $this->assertSame($parentJob->getUuid(), $childJob2->getParent()->getUuid());

        $this->assertSame(Status::new, $jobs->getByUuid($parentJob->getUuid())->getStatus());
        $this->assertSame(Status::new, $jobs->getByUuid($childJob1->getUuid())->getStatus());
        $this->assertSame(Status::new, $jobs->getByUuid($childJob2->getUuid())->getStatus());

        $childJob2 = $childJob2->markPending();
        $jobs->persist($childJob2);

        $parentJob = $jobs->getByUuid($parentJob->getUuid());
        $childJob1 = $jobs->getByUuid($childJob1->getUuid());
        $childJob2 = $jobs->getByUuid($childJob2->getUuid());

        $this->assertSame(Status::pending, $jobs->getByUuid($parentJob->getUuid())->getStatus());
        $this->assertSame(Status::new, $jobs->getByUuid($childJob1->getUuid())->getStatus());
        $this->assertSame(Status::pending, $jobs->getByUuid($childJob2->getUuid())->getStatus());

        $childJob2 = $childJob2->markCompleted();
        $jobs->persist($childJob2);

        $parentJob = $jobs->getByUuid($parentJob->getUuid());
        $childJob1 = $jobs->getByUuid($childJob1->getUuid());
        $childJob2 = $jobs->getByUuid($childJob2->getUuid());

        $this->assertSame(Status::pending, $jobs->getByUuid($parentJob->getUuid())->getStatus());
        $this->assertSame(Status::new, $jobs->getByUuid($childJob1->getUuid())->getStatus());
        $this->assertSame(Status::completed, $jobs->getByUuid($childJob2->getUuid())->getStatus());

        $childJob1 = $childJob1->markPending();
        $jobs->persist($childJob1);

        $parentJob = $jobs->getByUuid($parentJob->getUuid());
        $childJob1 = $jobs->getByUuid($childJob1->getUuid());
        $childJob2 = $jobs->getByUuid($childJob2->getUuid());

        $this->assertSame(Status::pending, $jobs->getByUuid($parentJob->getUuid())->getStatus());
        $this->assertSame(Status::pending, $jobs->getByUuid($childJob1->getUuid())->getStatus());
        $this->assertSame(Status::completed, $jobs->getByUuid($childJob2->getUuid())->getStatus());

        $childJob1 = $childJob1->markCompleted();
        $jobs->persist($childJob1);

        $parentJob = $jobs->getByUuid($parentJob->getUuid());
        $childJob1 = $jobs->getByUuid($childJob1->getUuid());
        $childJob2 = $jobs->getByUuid($childJob2->getUuid());

        $this->assertSame(Status::completed, $jobs->getByUuid($parentJob->getUuid())->getStatus());
        $this->assertSame(Status::completed, $jobs->getByUuid($childJob1->getUuid())->getStatus());
        $this->assertSame(Status::completed, $jobs->getByUuid($childJob2->getUuid())->getStatus());
    }

    public function testHasAccessWithNoLoggedInUser(): void
    {
        $jobs = $this->getJobs($this->mockSecurity());
        $job = Job::new('type', new Owner(42));

        $this->assertTrue($jobs->hasAccess($job));
    }

    public function testHasAccessForSystemOwnerIsAlwaysTrue(): void
    {
        $jobs = $this->getJobs();
        $job = Job::new('type', Owner::asSystem());

        $this->assertTrue($jobs->hasAccess($job));
    }

    public function testHasAccessWithMatchingOwner(): void
    {
        $jobs = $this->getJobs($this->mockSecurity(42));
        $job = Job::new('type', new Owner(42));

        $this->assertTrue($jobs->hasAccess($job));
    }

    public function testHasAccessWithDifferentOwnerIsFalse(): void
    {
        $jobs = $this->getJobs($this->mockSecurity(1));
        $job = Job::new('type', new Owner(42));

        $this->assertFalse($jobs->hasAccess($job));
    }

    public function testAttachments(): void
    {
        $jobs = $this->getJobs();
        $this->assertCount(0, $jobs->getAttachments('i-do-not-exist'));

        $job = $jobs->createUserJob('type', 42);
        $jobs->addAttachment($job, 'my-attachment-key', 'foobar');
        $jobs->addAttachment($job, 'my-other-attachment-key', 'foobar');

        $this->assertNull($jobs->getAttachment($job, 'i-do-not-exist'));

        $attachment = $jobs->getAttachment($job, 'my-attachment-key');
        $this->assertSame('my-attachment-key', $attachment->getFilesystemItem()->getName());
        $this->assertCount(2, iterator_to_array($this->vfs->listContents($job->getUuid())));
        $this->assertCount(2, $jobs->getAttachments($job->getUuid()));
    }

    public function testPrune(): void
    {
        // Add job for 2 days ago
        $jobs = $this->getJobs(null, new MockClock(new \DateTimeImmutable('-2 days')));
        $job1 = $jobs->createUserJob('type', 1);
        $jobs->addAttachment($job1, 'my-attachment-key', 'foobar');

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'foobar');
        rewind($stream);
        $jobs->addAttachment($job1, 'my-other-attachment-key', $stream);

        $this->assertNotNull($jobs->getByUuid($job1->getUuid()));
        $this->assertCount(1, iterator_to_array($this->vfs->listContents('')));

        // Add job now
        $jobs = $this->getJobs();
        $job2 = $jobs->createUserJob('type', 2);
        $jobs->addAttachment($job2, 'my-attachment-key', 'foobar');

        $this->assertNotNull($jobs->getByUuid($job2->getUuid()));
        $this->assertCount(2, iterator_to_array($this->vfs->listContents('')));

        // Prune now
        $jobs->prune(86400);

        // Job one must now be deleted as well as its attachments
        $this->assertNull($jobs->getByUuid($job1->getUuid()));
        $this->assertCount(0, iterator_to_array($this->vfs->listContents($job1->getUuid())));
        $this->assertCount(1, iterator_to_array($this->vfs->listContents('')));

        // But job 2 must still exist
        $this->assertNotNull($jobs->getByUuid($job2->getUuid()));
        $this->assertCount(1, iterator_to_array($this->vfs->listContents('')));
    }

    /**
     * @param array<Job> $jobs
     *
     * @return array<string>
     */
    private function jobsToUuids(array $jobs): array
    {
        return array_map(static fn (Job $job) => $job->getUuid(), $jobs);
    }
}
