<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Job;

use Contao\CoreBundle\Entity\Job as JobEntity;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Job\Owner;
use Contao\CoreBundle\Repository\JobRepository;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class JobsTest extends TestCase
{
    public function testCreateJob(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (JobEntity $job) => 'foobar' === $job->getOwner()))
        ;

        $jobs = $this->getJobs(null, $entityManager);
        $job = $jobs->createJob(new Owner('foobar'));

        $this->assertSame('foobar', $job->getOwner()->getIdentifier());
    }

    public function testCreateSystemJob(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static fn (JobEntity $job) => Owner::SYSTEM === $job->getOwner()))
        ;

        $jobs = $this->getJobs(null, $entityManager);
        $job = $jobs->createSystemJob();

        $this->assertSame(Owner::SYSTEM, $job->getOwner()->getIdentifier());
    }

    public function testCreateUserJobWithUserId(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('logged-in-user')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(static fn (JobEntity $job) => \in_array($job->getOwner(), ['logged-in-user', 'my-user'], true)))
        ;

        $jobs = $this->getJobs(null, $entityManager, $security);

        // Without any argument, it should take the one of security
        $job = $jobs->createUserJob();
        $this->assertSame('logged-in-user', $job->getOwner()->getIdentifier());

        // With argument, we want ours
        $job = $jobs->createUserJob('my-user');
        $this->assertSame('my-user', $job->getOwner()->getIdentifier());
    }

    public function testCreateUserJobThrowsExceptionIfNoUser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create a user job without having a user id.');

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $jobs = $this->getJobs(null, null, $security);

        $jobs->createUserJob();
    }

    private function getJobs(JobRepository|null $jobRepository = null, EntityManagerInterface|null $entityManager = null, Security|null $security = null): Jobs
    {
        return new Jobs(
            $jobRepository ?? $this->createMock(JobRepository::class),
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $security ?? $this->createMock(Security::class),
        );
    }
}
