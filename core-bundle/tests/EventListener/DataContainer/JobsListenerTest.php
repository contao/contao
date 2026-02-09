<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\JobsListener;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Tests\Job\AbstractJobsTestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class JobsListenerTest extends AbstractJobsTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testInvokeWithoutRequest(): void
    {
        $listener = new JobsListener(
            $this->createStub(Jobs::class),
            $this->createStub(Security::class),
            $this->createStub(Connection::class),
            $this->getRequestStack(),
            $this->createContaoFrameworkStub(),
            $this->createStub(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testInvokeWithoutUser(): void
    {
        $listener = new JobsListener(
            $this->createStub(Jobs::class),
            $this->mockSecurity(),
            $this->createStub(Connection::class),
            $this->getRequestStack(Request::create('/')),
            $this->createContaoFrameworkStub(),
            $this->createStub(Environment::class),
        );

        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testRegularView(): void
    {
        $framework = $this->createContaoFrameworkStub([System::class => $this->createAdapterStub(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->createStub(Jobs::class),
            $this->mockSecurity(42),
            $this->createStub(Connection::class),
            $this->getRequestStack(Request::create('/contao?do=jobs')),
            $framework,
            $this->createStub(Environment::class),
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
        $framework = $this->createContaoFrameworkStub([System::class => $this->createAdapterStub(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->createStub(Jobs::class),
            $this->mockSecurity(42),
            $this->createStub(Connection::class),
            $this->getRequestStack(Request::create('/contao?do=jobs&ptable=tl_job')),
            $framework,
            $this->createStub(Environment::class),
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

    public function testLabelCallback(): void
    {
        $framework = $this->createContaoFrameworkStub([System::class => $this->createAdapterStub(['loadLanguageFile'])]);

        $jobs = $this->getJobs();
        $job = $jobs->createJob('job-type');
        $job = $job->withProgress(37.0);

        $jobs->persist($job);
        $jobs->addAttachment($job, 'foobar', 'foobar');

        $call = 0;
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->exactly(3))
            ->method('render')
            ->willReturnCallback(
                function (string $template, array $context) use (&$call, $job) {
                    ++$call;

                    if (1 === $call) {
                        $this->assertSame('@Contao/backend/jobs/progress.html.twig', $template);
                        $this->assertSame($job->getUuid(), $context['job']->getUuid());

                        return 'progress.html.twig output';
                    }

                    if (2 === $call) {
                        $this->assertSame('@Contao/backend/jobs/status.html.twig', $template);
                        $this->assertSame($job->getUuid(), $context['job']->getUuid());

                        return 'status.html.twig output';
                    }

                    if (3 === $call) {
                        $this->assertSame('@Contao/backend/jobs/attachments.html.twig', $template);
                        $this->assertSame($job->getUuid(), $context['job']->getUuid());
                        $this->assertCount(1, $context['attachments']);

                        return 'attachments.html.twig output';
                    }

                    $this->fail('render() called too many times.');
                },
            )
        ;

        $listener = new JobsListener(
            $jobs,
            $this->createStub(Security::class),
            $this->createStub(Connection::class),
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

        $columnsNew = $listener->onLabelCallback($row, 'label', $this->createStub(DC_Table::class), $columns);

        $this->assertSame('progress.html.twig output', $columnsNew[2]);
        $this->assertSame('status.html.twig output', $columnsNew[3]);
        $this->assertSame('attachments.html.twig output', $columnsNew[5]);
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
