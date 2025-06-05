<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @experimental
 */
class Jobs
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
    ) {
    }

    public function createJob(string $type): Job
    {
        $userId ??= $this->security->getUser()?->getUserIdentifier();

        if (null === $userId) {
            return $this->createSystemJob($type);
        }

        return $this->createUserJob($type, $userId);
    }

    public function createSystemJob(string $type): Job
    {
        return $this->doCreateJob($type, Owner::asSystem());
    }

    public function createUserJob(string $type, string|null $userId = null): Job
    {
        $userId ??= $this->security->getUser()?->getUserIdentifier();

        if (null === $userId) {
            throw new \LogicException('Cannot create a user job without having a user id.');
        }

        return $this->doCreateJob($type, new Owner($userId));
    }

    /**
     * @return array<Job>
     */
    public function findMyNewOrPending(): array
    {
        $qb = $this->buildQueryBuilderForMine();

        if (!$qb) {
            return [];
        }

        $qb->andWhere('j.status IN (:status)');
        $qb->setParameter('status', [Status::NEW->value, Status::PENDING->value], ArrayParameterType::STRING);

        return $this->queryWithQueryBuilder($qb);
    }

    public function getByUuid(string $uuid): Job|null
    {
        $jobData = $this->connection->fetchAssociative('SELECT * FROM tl_job WHERE uuid=?', [$uuid]);

        if (false === $jobData) {
            return null;
        }

        return $this->databaseRowToDto($jobData);
    }

    public function persist(Job $job): void
    {
        $existingJob = $this->getByUuid($job->getUuid());

        if (null === $existingJob) {
            $this->connection->insert(
                'tl_job',
                [
                    'uuid' => $job->getUuid(),
                    'type' => $job->getType(),
                    'status' => $job->getStatus()->value,
                    'owner' => $job->getOwner()->getIdentifier(),
                    'tstamp' => (int) $job->getCreatedAt()->format('U'),
                    'public' => $job->isPublic(),
                ],
                [
                    Types::STRING,
                    Types::STRING,
                    Types::STRING,
                    Types::STRING,
                    Types::INTEGER,
                    Types::BOOLEAN,
                ],
            );
        }

        // Update job data
        $row = [];
        $row['pid'] = 0;
        $row['status'] = $job->getStatus()->value;
        $row['jobData'] = json_encode(
            [
                'metadata' => $job->getMetadata(),
                'progress' => $job->getProgress(),
                'errors' => $job->getErrors(),
                'warnings' => $job->getWarnings(),
            ],
            JSON_THROW_ON_ERROR,
        );

        $parent = $job->getParent();

        if ($parent) {
            $this->persist($parent);
            $row['pid'] = $this->connection->fetchOne('SELECT id FROM tl_job WHERE uuid=?', [$parent->getUuid()]) ?? 0;
        }

        $this->connection->update('tl_job', $row, ['uuid' => $job->getUuid()], [Types::INTEGER, Types::STRING, Types::STRING]);

        if ($parent) {
            // If this job is a child, update the status of the parent if required.
            $this->updateStatusBasedOnChildren($job->getParent());
        }
    }

    public function createChild(Job $job): Job
    {
        $child = Job::new($job->getType(), $job->getOwner())->withParent($job);
        $this->persist($child);

        return $child;
    }

    private function doCreateJob(string $type, Owner $owner): Job
    {
        // TODO: job factories or so?
        $job = Job::new($type, $owner);
        $this->persist($job);

        return $job;
    }

    private function buildQueryBuilderForMine(): QueryBuilder|null
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_job', 'j')
        ;

        $userid = $this->security->getUser()?->getUserIdentifier();

        if (null === $userid) {
            return null;
        }

        $expr = $qb->expr();

        $qb->andWhere('j.pid = 0'); // Only parents
        $qb->andWhere(
            $expr->or(
                $expr->eq('j.owner', ':userOwner'),
                $expr->and(
                    $expr->eq('j.public', true),
                    $expr->eq('j.owner', ':systemOwner'),
                ),
            ),
        );
        $qb->setParameter('userOwner', $userid);
        $qb->setParameter('systemOwner', Owner::SYSTEM);
        $qb->orderBy('j.tstamp', 'DESC');

        return $qb;
    }

    /**
     * @return array<Job>
     */
    private function queryWithQueryBuilder(QueryBuilder $queryBuilder): array
    {
        $jobs = [];

        foreach ($queryBuilder->fetchAllAssociative() as $jobRow) {
            $jobs[] = $this->databaseRowToDto($jobRow);
        }

        return $jobs;
    }

    private function updateStatusBasedOnChildren(Job $job): void
    {
        $id = $this->connection->fetchOne('SELECT id FROM tl_job WHERE uuid=?', [$job->getUuid()]);

        if (false === $id) {
            return;
        }

        $children = $this->connection->fetchAllAssociative('SELECT * FROM tl_job WHERE pid=?', [$id], [Types::INTEGER]);

        $onePending = false;
        $allFinished = true;

        foreach ($children as $childRow) {
            $childJob = $this->databaseRowToDto($childRow);

            if (Status::PENDING === $childJob->getStatus()) {
                $onePending = true;
                break;
            }

            if (Status::FINISHED !== $childJob->getStatus()) {
                $allFinished = false;
            }
        }

        if ($onePending) {
            $this->persist($job->markPending());

            return;
        }

        if ($allFinished) {
            $this->persist($job->markFinished());
        }
    }

    private function databaseRowToDto(array $row): Job
    {
        $job = new Job(
            $row['uuid'],
            \DateTimeImmutable::createFromFormat('U', (string) $row['tstamp']),
            Status::from($row['status']),
            $row['type'],
            new Owner($row['owner']),
        );

        $jobData = json_decode($row['jobData'] ?? '{}', true);

        // TODO: FIX ME $children = array_map(static fn (Job $child) =>
        // $child->toDto(false), $this->getChildren()->toArray());

        if (Owner::SYSTEM === $job->getOwner()->getIdentifier()) {
            $job = $job->withIsPublic((bool) $row['public']);
        }

        return $job
            ->withProgress($jobData['progress'] ?? 0)
            ->withWarnings($jobData['warnings'] ?? [])
            ->withErrors($jobData['errors'] ?? [])
            ->withMetadata($jobData['metadata'] ?? [])
            // ->withParent($withParent ? $this->getParent()?->toDto() : null)
            // ->withChildren($children)
        ;
    }
}
