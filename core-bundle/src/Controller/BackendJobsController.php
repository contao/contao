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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @experimental
 */
class BackendJobsController extends AbstractBackendController
{
    public function __construct(private readonly Jobs $jobs)
    {
    }

    #[Route(
        '%contao.backend.route_prefix%/jobs',
        name: 'contao_backend_jobs',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
    )]
    public function allJobsAction(): Response
    {
        return $this->render('@Contao/backend/jobs/index.html.twig', [
            'title' => 'Jobs',
            'headline' => 'Jobs',
            'jobs' => $this->jobs->findMine(),
            'dateTimeFormat' => 'Y-m-d H:i:s', // TODO: System settings but why is this not available globally in Twig?
        ]);
    }

    #[Route(
        '%contao.backend.route_prefix%/jobs/pending',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
    )]
    public function latestJobsAction(): JsonResponse
    {
        // TODO: This should become a Turbo stream, I guess?
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
