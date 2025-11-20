<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\JobsListener;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Tests\Job\AbstractJobsTestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class JobsListenerTest extends AbstractJobsTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAttachmentsCallback(): void
    {
        $security = $this->mockSecurity(42);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with('_contao_jobs.download')
            ->willReturn('https://contao.org/contao/jobs/download')
        ;

        $jobs = $this->getJobs($security, new MockClock(), $router);
        $job = $jobs->createJob('job-type');

        $listener = new JobsListener(
            $jobs,
            $security,
            $this->createMock(Connection::class),
            $this->getRequestStack(),
            $this->mockContaoFramework(),
            $this->createMock(Environment::class),
        );

        $operation = new DataContainerOperation(
            'attachments',
            ['label' => 'attachments', 'title' => 'attachments'],
            ['uuid' => 'i-do-not-exist'],
            $this->createMock(DataContainer::class),
        );

        $listener->onAttachmentsCallback($operation);
        $this->assertSame('', $operation->getHtml());

        $operation = new DataContainerOperation(
            'attachments',
            ['label' => 'attachments', 'title' => 'attachments'],
            ['uuid' => $job->getUuid()],
            $this->createMock(DataContainer::class),
        );

        $listener->onAttachmentsCallback($operation);
        $this->assertSame('', $operation->getHtml());

        $jobs->addAttachment($job, 'foobar', 'foobar');
        $jobs->addAttachment($job, 'foobar2', 'foobar2');

        $listener->onAttachmentsCallback($operation);
        $this->assertSame('https://contao.org/contao/jobs/download', $operation->getUrl());
        $this->assertSame('theme_import.svg', $operation['icon']);
    }

    public function testInvokeWithoutRequest(): void
    {
        $listener = new JobsListener(
            $this->createMock(Jobs::class),
            $this->createMock(Security::class),
            $this->createMock(Connection::class),
            $this->getRequestStack(),
            $this->mockContaoFramework(),
            $this->createMock(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testInvokeWithoutUser(): void
    {
        $listener = new JobsListener(
            $this->createMock(Jobs::class),
            $this->mockSecurity(),
            $this->createMock(Connection::class),
            $this->getRequestStack(Request::create('/')),
            $this->mockContaoFramework(),
            $this->createMock(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testRegularView(): void
    {
        $framework = $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->createMock(Jobs::class),
            $this->mockSecurity(42),
            $this->createMock(Connection::class),
            $this->getRequestStack(Request::create('/contao?do=jobs')),
            $framework,
            $this->createMock(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'filter' => [
                            'pid = 0 AND (owner = 42 OR (public = 1 AND owner = 0))',
                        ],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_job'],
        );
    }

    public function testChildView(): void
    {
        $framework = $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->createMock(Jobs::class),
            $this->mockSecurity(42),
            $this->createMock(Connection::class),
            $this->getRequestStack(Request::create('/contao?do=jobs&ptable=tl_job')),
            $framework,
            $this->createMock(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'mode' => DataContainer::MODE_PARENT,
                        'filter' => [
                            'pid != 0 AND (owner = 42 OR (public = 1 AND owner = 0))',
                        ],
                    ],
                    'label' => [
                        'fields' => [
                            'uuid',
                            'status',
                        ],
                        'format' => '%s <span class="label-info">%s</span>',
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_job'],
        );
    }

    public function testProgress(): void
    {
        $framework = $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);

        $jobs = $this->getJobs();
        $job = $jobs->createJob('job-type');
        $job = $job->withProgress(37.0);

        $jobs->persist($job);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('@Contao/backend/jobs/_progress.html.twig', ['progress' => 37.0])
            ->willReturn('the resulting twig output')
        ;

        $listener = new JobsListener(
            $jobs,
            $this->createMock(Security::class),
            $this->createMock(Connection::class),
            $this->getRequestStack(Request::create('/contao?do=jobs')),
            $framework,
            $twig,
        );

        $row = ['id' => 42, 'uuid' => $job->getUuid()];

        $columns = [
            '2025-10-30 13:10',
            'Rebuild the back end search index',
            null,
            'Completed',
            'Kevin Jones',
        ];

        $columnsNew = $listener->onLabelCallback($row, 'label', $this->createMock(DC_Table::class), $columns);

        $this->assertSame('the resulting twig output', $columnsNew[2]);
    }

    private function getRequestStack(Request|null $request = null): RequestStack
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        return $requestStack;
    }
}
