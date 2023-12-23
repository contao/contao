<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\CspReporterController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\Filter\Filter;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\Log\LogFormatterInterface;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\Log\Logger;
use Nelmio\SecurityBundle\Controller\ContentSecurityPolicyController;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CspReporterControllerTest extends TestCase
{
    public function testThrowsNotFoundExceptionIfReportLogNotEnabled(): void
    {
        $content = json_encode(['csp-report' => []]);
        $request = Request::create('https://www.example.org/_contao/csp/report/1', content: $content);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->cspReportLog = false;

        $adapter = $this->mockAdapter(['findWithDetails']);
        $adapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $logger = $this->createMock(LoggerInterface::class);
        $logFormatter = $this->createMock(LogFormatterInterface::class);
        $nelmioLogger = new Logger($logger, $logFormatter, 'notice');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $inner = new ContentSecurityPolicyController($nelmioLogger, $eventDispatcher, new Filter());

        $this->expectException(NotFoundHttpException::class);

        $controller = new CspReporterController($framework, $inner);
        $controller($request, 1);
    }

    public function testForwardsToNelmioCspController(): void
    {
        $content = json_encode(['csp-report' => []]);
        $request = Request::create('https://www.example.org/_contao/csp/report/1', content: $content);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->cspReportLog = true;

        $adapter = $this->mockAdapter(['findWithDetails']);
        $adapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $logger = $this->createMock(LoggerInterface::class);
        $logFormatter = $this->createMock(LogFormatterInterface::class);
        $nelmioLogger = new Logger($logger, $logFormatter, 'notice');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $inner = new ContentSecurityPolicyController($nelmioLogger, $eventDispatcher, new Filter());

        $controller = new CspReporterController($framework, $inner);
        $controller($request, 1);
    }
}
