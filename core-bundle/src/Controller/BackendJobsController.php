<?php

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Job\FindCriteria;
use Contao\CoreBundle\Job\Jobs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BackendJobsController extends AbstractController
{
    public function __construct(private Jobs $jobs, private )
    {

    }

    public function allJobsAction(Request $request): JsonResponse
    {
        $findCriteria = new FindCriteria();
        $findCriteria->limit = min($request->query->get('limit', 20), 20);
        $findCriteria->offset = $request->query->get('offset', 1);
        $findCriteria->owner

        // TODO: just twig directly?
        $jobs = [];
        foreach ($this->jobs->findByCriteria() as $item) {

        }
    }

    public function latestJobsAction(): JsonResponse
    {


    }
}
