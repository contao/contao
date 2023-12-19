<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\CspReporterLoader;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CspReporterLoaderTest extends TestCase
{
    public function testSupportsTheContaoCspReporterRoute(): void
    {
        $loader = new CspReporterLoader(true, '/_contao/csp/report');

        $this->assertTrue($loader->supports('.', 'contao_csp_reporter'));
    }

    public function testUsesTheCorrectPath(): void
    {
        $loader = new CspReporterLoader(true, '/_contao/csp/report');

        $route = $loader->load('.', 'contao_csp_reporter')->get('contao_csp_reporter');

        $this->assertNotNull($route);
        $this->assertSame('/_contao/csp/report', $route->getPath());
        $this->assertSame('nelmio_security.csp_reporter_controller::indexAction', $route->getDefault('_controller'));
        $this->assertSame([Request::METHOD_POST], $route->getMethods());
    }

    public function testDoesNotAddRouteIfDisabled(): void
    {
        $loader = new CspReporterLoader(false, '/_contao/csp/report');

        $route = $loader->load('.', 'contao_csp_reporter')->get('contao_csp_reporter');

        $this->assertNull($route);
    }

    public function testDoesNotAddRouteWithoutPath(): void
    {
        $loader = new CspReporterLoader(true, null);

        $route = $loader->load('.', 'contao_csp_reporter')->get('contao_csp_reporter');

        $this->assertNull($route);
    }
}
