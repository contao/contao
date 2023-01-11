<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inheritance;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\DynamicUseTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;

class DynamicUseTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new DynamicUseTokenParser($this->createMock(TemplateHierarchyInterface::class));

        $this->assertSame('use', $tokenParser->getTag());
    }

    public function testHandlesContaoUses(): void
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/use');

        $templateLocator = new TemplateLocator(
            $projectDir,
            ['FooBundle' => ContaoModuleBundle::class],
            ['FooBundle' => ['path' => Path::join($projectDir, 'bundle')]],
            $themeNamespace = new ThemeNamespace(),
            $this->createMock(Connection::class)
        );

        $loader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace, $projectDir);

        $warmer = new ContaoFilesystemLoaderWarmer(
            $loader,
            $templateLocator,
            $projectDir,
            'cache',
            'prod',
            $this->createMock(Filesystem::class)
        );

        $warmer->warmUp('');

        $environment = new Environment($loader);

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $loader,
                $this->createMock(ContaoCsrfTokenManager::class)
            )
        );

        // A component is adjusted by overwriting the component's template
        // (here by adding the item "ice" and turning apples into pineapples).
        // The changes should be visible wherever the component is used like in
        // this element template:
        $this->assertSame(
            <<<'HTML'
                <h1>Summer menu</h1>
                    <h2>Fruit shake</h2>
                    <b>Ingredients:</b>
                    <ul>
                        <li>pineapple and banana</li>
                        <li>milk</li>
                        <li>ice</li>
                    </ul>
                HTML,
            trim($environment->render('@Contao/element/menu.html.twig'))
        );

        // The rendered template overwrites blocks of a component used by the
        // extended base template and another component used within this
        // component. The adjustments (adding the item "secret sauce" and
        // adding "from a tin" to the inner component's "apple" block) should
        // be output, but only in this template:
        $this->assertSame(
            <<<'HTML'
                <h1>How to make a fruit shake</h1>
                    <h2>Fruit shake</h2>
                    <b>Ingredients:</b>
                    <ul>
                        <li>pineapple (from a tin) and banana</li>
                        <li>milk</li>
                        <li>ice</li>

                        <!-- only in the recipe -->
                        <li>secret sauce (don't tell anyone)</li>
                    </ul>

                <p>Put everything in a blender and mix for 30 seconds.</p>
                HTML,
            trim($environment->render('@Contao/element/recipe.html.twig'))
        );
    }
}
