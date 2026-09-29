<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\JobsController;
use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Tests\Job\AbstractJobsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class JobsControllerTest extends AbstractJobsTestCase
{
    #[DataProvider('pollIntervalProvider')]
    public function testPollingOnlyIncludesRecentlyCompletedJobs(int $interval, int $window): void
    {
        $clock = new MockClock();
        $jobs = $this->getJobs($this->mockSecurity(1), $clock);
        $pendingJob = $jobs->createUserJob('my-type')->markPending();
        $jobs->persist($pendingJob);

        $oldJob = $jobs->createUserJob('my-type')->markCompleted();
        $jobs->persist($oldJob);

        $clock->modify('+1 second');
        $recentJob = $jobs->createUserJob('my-type')->markCompleted();
        $jobs->persist($recentJob);
        $clock->modify('+'.$window.' seconds');

        $request = Request::create('/contao/jobs/pending', parameters: ['range' => $interval]);
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/jobs/update_running_jobs.stream.html.twig',
                $this->callback(
                    function (array $parameters) use ($pendingJob, $recentJob): bool {
                        $this->assertEqualsCanonicalizing(
                            [$pendingJob->getUuid(), $recentJob->getUuid()],
                            array_map(static fn (Job $job): string => $job->getUuid(), $parameters['jobs']),
                        );

                        return true;
                    },
                ),
            )
            ->willReturn('stream response')
        ;

        $controller = $this->getController($jobs, $request, $twig);
        $response = $controller->latestJobsAction($request);
        $this->assertSame('stream response', $response->getContent());

        $request->headers->set('If-None-Match', $response->getEtag());
        $this->assertSame(Response::HTTP_NOT_MODIFIED, $controller->latestJobsAction($request)->getStatusCode());
    }

    public static function pollIntervalProvider(): iterable
    {
        yield 'default interval' => [5000, 5];
        yield 'maximum interval' => [60000, 60];
        yield 'round up fractional seconds' => [5500, 6];
    }

    private function getController(Jobs $jobs, Request $request, Environment $twig): JobsController
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('twig', $twig);

        $controller = new JobsController($jobs);
        $controller->setContainer($container);

        return $controller;
    }
}
