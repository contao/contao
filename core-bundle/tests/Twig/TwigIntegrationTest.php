<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\FormTextField;
use Contao\FrontendTemplate;
use Contao\System;
use Contao\TemplateLoader;
use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->mkdir(Path::join($this->getTempDir(), 'templates'));

        $GLOBALS['TL_FFL'] = [
            'text' => FormTextField::class,
        ];

        $GLOBALS['TL_LANG']['MSC'] = [
            'mandatory' => 'mandatory',
            'global' => 'global',
        ];
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'templates'));

        TemplateLoader::reset();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_FFL'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([ContaoFramework::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersWidgets(): void
    {
        $content = "{{ strClass }}\n{{ strLabel }} {{ this.label }}\n {{ getErrorAsString }}";

        // Setup legacy framework and environment
        (new Filesystem())->touch(Path::join($this->getTempDir(), 'templates/form_textfield.html5'));
        TemplateLoader::addFile('form_textfield', 'templates');

        $environment = new Environment(new ArrayLoader(['@Contao/form_textfield.html.twig' => $content]));
        $environment->addExtension(new ContaoExtension($environment, $this->createMock(ContaoFilesystemLoader::class)));

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('twig', $environment);
        $container->set(ContextFactory::class, new ContextFactory());

        System::setContainer($container);

        // Render widget
        $textField = new FormTextField(['class' => 'my_class', 'label' => 'foo']);
        $textField->addError('bar');

        $this->assertSame("my_class error\nfoo foo\n bar", $textField->parse());
    }

    public function testRendersTwigTemplateWithLegacyParent(): void
    {
        (new Filesystem())->dumpFile(
            Path::join($this->getTempDir(), 'templates/legacy_template.html5'),
            <<<'EOF'
                <?php
                    echo $this->value;
                    echo ',test1';
                    $this->block('a');
                    echo ',test2';
                    $this->endblock('a');
                    echo ',test3';
                    $this->block('b');
                    echo ',test4';
                    $this->block('b1');
                    echo ',test5';
                    $this->endblock('b1');
                    echo ',test6';
                    $this->endblock('b');
                    echo ',test7';
                EOF,
        );

        TemplateLoader::addFile('legacy_template', 'templates');

        (new Filesystem())->dumpFile(
            Path::join($this->getTempDir(), 'templates/twig_template.html.twig'),
            <<<'EOF'
                {% extends "@Contao/legacy_template.html5" %}
                {% block a %}<<{{ parent() }}>>{% endblock %}
                {% block b1 %}{{ parent() }},({{ value }}){% endblock %}
                EOF,
        );

        $templateLocator = new TemplateLocator(
            $this->getTempDir(),
            [],
            [],
            $themeNamespace = new ThemeNamespace(),
            $this->createMock(Connection::class),
        );

        $filesystemLoader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace, $this->getTempDir());

        $environment = new Environment($filesystemLoader);
        $environment->addExtension(new ContaoExtension($environment, $filesystemLoader));

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('twig', $environment);
        $container->set(ContextFactory::class, new ContextFactory());

        System::setContainer($container);

        $template = new FrontendTemplate('twig_template');
        $template->setData(['value' => 'value']);

        $obLevel = ob_get_level();
        $this->assertSame('value,test1<<,test2>>,test3,test4,test5,(value),test6,test7', $template->parse());
        $this->assertSame($obLevel, ob_get_level());
    }
}
