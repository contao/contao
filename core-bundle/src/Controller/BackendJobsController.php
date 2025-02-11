<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Jobs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @experimental
 */
class BackendJobsController extends AbstractController
{
    public function __construct(private Jobs $jobs)
    {
    }

    #[Route(
        '%contao.backend.route_prefix%/jobs',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
    )]
    public function allJobsAction(): JsonResponse
    {
        return $this->createResponse($this->jobs->findMine());
    }

    #[Route(
        '%contao.backend.route_prefix%/jobs/pending',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
    )]
    public function latestJobsAction(): JsonResponse
    {
        return $this->createResponse($this->jobs->findMyPending());
    }

    /**
     * @param array<Job> $jobs
     */
    private function createResponse(array $jobs): JsonResponse
    {
        return new JsonResponse(array_map(static fn (Job $job) => $job->toArray(), $jobs));
    }
}
