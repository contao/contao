<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Job;

use Contao\StringUtil;
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
        $userId = $this->security->getUser()?->getUserIdentifier();

        if (null === $userId) {
            return $this->createSystemJob($type);
        }

        return $this->createUserJob($type, $userId);
    }

    public function createSystemJob(string $type, bool $public = true): Job
    {
        return $this->doCreateJob($type, Owner::asSystem(), $public);
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

    public function persist(Job $job, bool $applyRecursive = true): void
    {
        $existingJob = $this->getByUuid($job->getUuid());

        if (!$existingJob) {
            // Need to encode HTML entities here for Contao's DC_Table
            $this->connection->insert(
                'tl_job',
                [
                    'uuid' => $job->getUuid(), // No encoding needed, UUID
                    'type' => StringUtil::specialchars($job->getType()),
                    'status' => $job->getStatus()->value, // No encoding needed, enum
                    'owner' => StringUtil::specialchars($job->getOwner()->getIdentifier()),
                    'tstamp' => (int) $job->getCreatedAt()->format('U'), // No encoding needed, integer
                    'public' => $job->isPublic(), // No encoding needed, boolean
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
        $row['pid'] = 0; // No encoding needed, integer
        $row['status'] = $job->getStatus()->value; // No encoding needed, enum
        $row['jobData'] = json_encode(
            [
                'metadata' => $job->getMetadata(),
                'progress' => $job->getProgress(),
                'errors' => $job->getErrors(),
                'warnings' => $job->getWarnings(),
            ],
            // No encoding needed because this data is not output anywhere at the moment,
            // make sure to adjust when adding this to the output!
            JSON_THROW_ON_ERROR,
        );

        $parent = $job->getParent();

        if ($parent && $applyRecursive) {
            $this->persist($parent, false);
            $row['pid'] = $this->connection->fetchOne('SELECT id FROM tl_job WHERE uuid=?', [$parent->getUuid()]) ?? 0;
        }

        if ($applyRecursive) {
            foreach ($job->getChildren() as $child) {
                $child = $child->withParent($job);
                $this->persist($child);
            }
        }

        $this->connection->update('tl_job', $row, ['uuid' => $job->getUuid()], [Types::INTEGER, Types::STRING, Types::STRING]);

        if ($parent && $applyRecursive) {
            // If this job is a child, update the status of the parent if required.
            $this->updateStatusBasedOnChildren($job->getParent());
        }
    }

    public function createChildJob(Job $parent): Job
    {
        $child = Job::new($parent->getType(), $parent->getOwner())->withParent($parent);
        $this->persist($child);

        return $child;
    }

    private function doCreateJob(string $type, Owner $owner, bool $public = true): Job
    {
        $job = Job::new($type, $owner);

        if (Owner::SYSTEM === $job->getOwner()->getIdentifier()) {
            $job = $job->withIsPublic($public);
        }

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

    private function databaseRowToDto(array $row, bool $withParent = true): Job
    {
        $job = new Job(
            $row['uuid'],
            \DateTimeImmutable::createFromFormat('U', (string) $row['tstamp']),
            Status::from($row['status']),
            StringUtil::decodeEntities($row['type']), // Decode because it's encoded for DC_Table
            new Owner(StringUtil::decodeEntities($row['owner'])), // Decode because it's encoded for DC_Table
        );

        if (Owner::SYSTEM === $job->getOwner()->getIdentifier()) {
            $job = $job->withIsPublic((bool) $row['public']);
        }

        if (0 === $row['pid']) {
            $children = [];

            foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_job WHERE id=?', [$row['pid']], [Types::INTEGER]) as $jobRow) {
                $children[] = $this->databaseRowToDto($jobRow, false);
            }
            $job = $job->withChildren($children);
        } elseif ($withParent) {
            $parentData = $this->connection->fetchAssociative('SELECT * FROM tl_job WHERE id=?', [$row['pid']]);
            if (false !== $parentData) {
                $job = $job->withParent($this->databaseRowToDto($parentData, false));
            }
        }

        $jobData = json_decode($row['jobData'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        return $job
            ->withProgress($jobData['progress'] ?? 0)
            ->withWarnings($jobData['warnings'] ?? [])
            ->withErrors($jobData['errors'] ?? [])
            ->withMetadata($jobData['metadata'] ?? [])
        ;
    }
}
