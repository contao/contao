<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inspector;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ContaoInspectorNodeVisitorTest extends TestCase
{
    public function testHashLowPriority(): void
    {
        $inspectorNodeVisitor = new InspectorNodeVisitor(
            new NullAdapter(),
            $this->createMock(Environment::class),
        );

        $this->assertSame(128, $inspectorNodeVisitor->getPriority());
    }

    public function testAnalyzesSlots(): void
    {
        $environment = new Environment(
            new ArrayLoader(['template.html.twig' => '{% slot B %}{% endslot %}{% block foo %}{% slot A %}body{% endslot %}{% slot A %}{% endslot %}{% endblock %}']),
        );

        $cache = new ArrayAdapter();

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor($cache, $environment),
            ),
        );

        $inspector = new Inspector($environment, $cache, $filesystemLoader);
        $information = $inspector->inspectTemplate('template.html.twig');

        $this->assertSame(['A', 'B'], $information->getSlots());
    }
}
