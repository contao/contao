<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Job;

use Contao\BackendUser;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @experimental
 */
class Jobs
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly VirtualFilesystemInterface $jobAttachmentsStorage,
        private readonly RouterInterface $router,
        private readonly ClockInterface $clock = new NativeClock(),
    ) {
    }

    public function hasAccess(Job $job): bool
    {
        if ($job->getOwner()->isSystem()) {
            return true;
        }

        $userId = $this->getContaoBackendUserId();

        if (0 === $userId) {
            return true;
        }

        return $job->getOwner()->getId() === $userId;
    }

    public function createJob(string $type): Job
    {
        $userId = $this->getContaoBackendUserId();

        if (0 === $userId) {
            return $this->createSystemJob($type);
        }

        return $this->createUserJob($type, $userId);
    }

    public function createSystemJob(string $type, bool $public = true): Job
    {
        return $this->doCreateJob($type, Owner::asSystem(), $public);
    }

    public function createUserJob(string $type, int|null $userId = null): Job
    {
        $userId ??= $this->getContaoBackendUserId();

        if (0 === $userId) {
            throw new \LogicException('Cannot create a user job without having a user ID.');
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
        $qb->setParameter('status', [Status::new->value, Status::pending->value], ArrayParameterType::STRING);

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

    /**
     * @return array<Attachment>
     */
    public function getAttachments(Job|string $jobOrUuid): array
    {
        $job = $jobOrUuid instanceof Job ? $jobOrUuid : $this->getByUuid($jobOrUuid);

        if (!$job) {
            return [];
        }

        $attachments = [];

        foreach ($this->jobAttachmentsStorage->listContents($job->getUuid())->files() as $fileItem) {
            $attachments[] = $this->convertFilesystemItemToAttachment($job, $fileItem);
        }

        return $attachments;
    }

    public function getAttachment(Job|string $jobOrUuid, string $identifier): Attachment|null
    {
        $job = $jobOrUuid instanceof Job ? $jobOrUuid : $this->getByUuid($jobOrUuid);

        if (!$job) {
            return null;
        }

        $fileItem = $this->jobAttachmentsStorage->get($this->getAttachmentIdentifier($job, $identifier));

        if (!$fileItem) {
            return null;
        }

        return $this->convertFilesystemItemToAttachment($job, $fileItem);
    }

    /**
     * @param string|resource $contents
     */
    public function addAttachment(Job $job, string $identifier, mixed $contents): void
    {
        $location = $this->getAttachmentIdentifier($job, $identifier);

        if (\is_string($contents)) {
            $this->jobAttachmentsStorage->write($location, $contents);

            return;
        }

        $this->jobAttachmentsStorage->writeStream($location, $contents);
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
                    'owner' => $job->getOwner()->getId(), // No encoding needed, integer
                    'tstamp' => (int) $job->getCreatedAt()->format('U'), // No encoding needed, integer
                    'public' => $job->isPublic(), // No encoding needed, boolean
                ],
                [
                    Types::STRING,
                    Types::STRING,
                    Types::STRING,
                    Types::INTEGER,
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
        $child = Job::new($parent->getType(), $parent->getOwner(), $this->clock->now())->withParent($parent);
        $this->persist($child);

        return $child;
    }

    public function prune(int $period): void
    {
        if ($period <= 0) {
            return;
        }

        $this->connection->executeStatement(
            'DELETE FROM tl_job WHERE tstamp < :tstamp',
            ['tstamp' => $this->clock->now()->getTimestamp() - $period],
            ['tstamp' => Types::INTEGER],
        );

        // Cleanup all directories in attachment storage that are not used anymore (also
        // cleans up orphans in case, e.g., jobs were deleted in the database manually)
        foreach ($this->jobAttachmentsStorage->listContents('')->directories() as $jobDirectory) {
            $jobUuid = $jobDirectory->getName();
            $id = $this->connection->fetchOne('SELECT id FROM tl_job WHERE uuid=?', [$jobUuid]);

            if (false === $id) {
                $this->jobAttachmentsStorage->deleteDirectory($jobDirectory->getPath());
            }
        }
    }

    private function getAttachmentIdentifier(Job $job, string $identifier): string
    {
        return $job->getUuid().'/'.$identifier;
    }

    private function convertFilesystemItemToAttachment(Job $job, FilesystemItem $filesystemItem): Attachment
    {
        return new Attachment(
            $filesystemItem,
            new TranslatableMessage('file_label.'.$filesystemItem->getName(), [], 'contao_jobs'),
            $this->router->generate(
                '_contao_jobs.download',
                ['jobUuid' => $job->getUuid(), 'identifier' => $filesystemItem->getName()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        );
    }

    /**
     * @return int 0 if no contao backend user was given
     */
    private function getContaoBackendUserId(): int
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser) {
            return (int) $user->id;
        }

        return 0;
    }

    private function doCreateJob(string $type, Owner $owner, bool $public = true): Job
    {
        $job = Job::new($type, $owner, $this->clock->now());

        if ($job->getOwner()->isSystem()) {
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

        $userid = $this->getContaoBackendUserId();

        if (0 === $userid) {
            return null;
        }

        $expr = $qb->expr();

        $qb->andWhere('j.pid = 0'); // Only parents
        $qb->andWhere(
            $expr->or(
                $expr->eq('j.owner', ':userOwner'),
                $expr->and(
                    $expr->eq('j.public', '1'),
                    $expr->eq('j.owner', ':systemOwner'),
                ),
            ),
        );
        $qb->setParameter('userOwner', $userid, ParameterType::INTEGER);
        $qb->setParameter('systemOwner', Owner::SYSTEM, ParameterType::INTEGER);
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
        $allCompleted = true;

        foreach ($children as $childRow) {
            $childJob = $this->databaseRowToDto($childRow);

            if ($childJob->isPending()) {
                $onePending = true;
                break;
            }

            if (!$childJob->isCompleted()) {
                $allCompleted = false;
            }
        }

        if ($onePending) {
            $this->persist($job->markPending());

            return;
        }

        if ($allCompleted) {
            $this->persist($job->markCompleted());
        }
    }

    private function databaseRowToDto(array $row, bool $withParent = true): Job
    {
        $job = new Job(
            $row['uuid'],
            \DateTimeImmutable::createFromFormat('U', (string) $row['tstamp']),
            Status::from($row['status']),
            StringUtil::decodeEntities($row['type']), // Decode because it's encoded for DC_Table
            new Owner((int) $row['owner']),
        );

        if ($job->getOwner()->isSystem()) {
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
