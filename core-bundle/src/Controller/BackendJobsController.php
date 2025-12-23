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

use Contao\CoreBundle\Job\Jobs;
use Symfony\Component\HttpFoundation\Request;
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
        '%contao.backend.route_prefix%/jobs/pending',
        name: '_contao_jobs_pending.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function latestJobsAction(Request $request): Response
    {
        return $this->render('@Contao/backend/jobs/show_running_jobs.stream.html.twig', [
            'jobs' => $this->jobs->findMyRecent($request->query->getInt('range')),
        ]);
    }

    #[Route(
        '%contao.backend.route_prefix%/jobs/download/{jobUuid}/{identifier}',
        name: '_contao_jobs.download',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
    )]
    public function downloadJobAttachment(string $jobUuid, string $identifier): Response
    {
        $job = $this->jobs->getByUuid($jobUuid);

        if (!$job || !$this->jobs->hasAccess($job)) {
            throw $this->createNotFoundException();
        }

        $attachment = $this->jobs->getAttachment($jobUuid, $identifier);

        if (!$attachment) {
            throw $this->createNotFoundException();
        }

        return $attachment->toStreamedResponse();
    }
}
