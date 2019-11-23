<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\CalendarBundle\ContaoManager\Plugin as CalendarBundlePlugin;
use Contao\CommentsBundle\ContaoManager\Plugin as CommentsBundlePlugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoManager\Plugin as CoreBundlePlugin;
use Contao\FaqBundle\ContaoManager\Plugin as FaqBundlePlugin;
use Contao\InstallationBundle\ContaoManager\Plugin as InstallationBundlePlugin;
use Contao\ListingBundle\ContaoManager\Plugin as ListingBundlePlugin;
use Contao\ManagerBundle\Command\DebugPluginsCommand;
use Contao\ManagerBundle\ContaoManager\Plugin as ManagerBundlePlugin;
use Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin as FixturesPlugin;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\PluginLoader;
use Contao\NewsBundle\ContaoManager\Plugin as NewsBundlePlugin;
use Contao\NewsletterBundle\ContaoManager\Plugin as NewsletterBundlePlugin;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class DebugPluginsCommandTest extends ContaoTestCase
{
    public function testNameAndArguments(): void
    {
        $command = new DebugPluginsCommand($this->getKernel([]));

        $this->assertSame('debug:plugins', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('name'));
        $this->assertTrue($command->getDefinition()->hasOption('bundles'));
    }

    /**
     * @dataProvider commandOutputProvider
     */
    public function testCommandOutput(array $plugins, array $bundles, array $arguments, string $expectedOutput)
    {
        $command = new DebugPluginsCommand($this->getKernel($plugins, $bundles));

        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);

        $this->assertSame($expectedOutput, $commandTester->getDisplay());
    }

    public function commandOutputProvider(): ?\Generator
    {
        yield 'lists the plugins' => [
            [
                'contao/core-bundle' => new CoreBundlePlugin(),
                'contao/calendar-bundle' => new CalendarBundlePlugin(),
                'contao/comments-bundle' => new CommentsBundlePlugin(),
                'contao/faq-bundle' => new FaqBundlePlugin(),
                'contao/installation-bundle' => new InstallationBundlePlugin(),
                'contao/listing-bundle' => new ListingBundlePlugin(),
                'contao/news-bundle' => new NewsBundlePlugin(),
                'contao/newsletter-bundle' => new NewsletterBundlePlugin(),
                'contao/manager-bundle' => new ManagerBundlePlugin(),
            ],
            [],
            [],
            <<<'OUTPUT'

Contao Manager plugins with their package name
==============================================

 ------------------------------------------------ ---------------------------- -------- --------- -------- ----------- ----------- ----- 
  Plugin class                                     Composer package             Features / Plugin Interfaces                             
 ------------------------------------------------ ---------------------------- -------- --------- -------- ----------- ----------- ----- 
                                                                                Bundle   Routing   Config   Extension   Dependent   API  
 ------------------------------------------------ ---------------------------- -------- --------- -------- ----------- ----------- ----- 
  Contao\CoreBundle\ContaoManager\Plugin           contao/core-bundle           ✔        ✔                                               
  Contao\CalendarBundle\ContaoManager\Plugin       contao/calendar-bundle       ✔                                                        
  Contao\CommentsBundle\ContaoManager\Plugin       contao/comments-bundle       ✔                                                        
  Contao\FaqBundle\ContaoManager\Plugin            contao/faq-bundle            ✔                                                        
  Contao\InstallationBundle\ContaoManager\Plugin   contao/installation-bundle   ✔        ✔                                               
  Contao\ListingBundle\ContaoManager\Plugin        contao/listing-bundle        ✔                                                        
  Contao\NewsBundle\ContaoManager\Plugin           contao/news-bundle           ✔                                                        
  Contao\NewsletterBundle\ContaoManager\Plugin     contao/newsletter-bundle     ✔                                                        
  Contao\ManagerBundle\ContaoManager\Plugin        contao/manager-bundle        ✔        ✔         ✔        ✔           ✔           ✔    
 ------------------------------------------------ ---------------------------- -------- --------- -------- ----------- ----------- ----- 


OUTPUT
        ];

        yield 'correctly shows the test plugin' => [
            [
                'foo/bar-bundle' => new FixturesPlugin()
            ],
            [],
            [],
            <<<'OUTPUT'

Contao Manager plugins with their package name
==============================================

 ---------------------------------------------------------- ------------------ -------- --------- -------- ----------- ----------- ----- 
  Plugin class                                               Composer package   Features / Plugin Interfaces                             
 ---------------------------------------------------------- ------------------ -------- --------- -------- ----------- ----------- ----- 
                                                                                Bundle   Routing   Config   Extension   Dependent   API  
 ---------------------------------------------------------- ------------------ -------- --------- -------- ----------- ----------- ----- 
  Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin   foo/bar-bundle                                 ✔                            
 ---------------------------------------------------------- ------------------ -------- --------- -------- ----------- ----------- ----- 


OUTPUT
        ];

        yield 'describe bundles of plugin by package name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => 'contao/core-bundle', '--bundles' => true],
            <<<'OUTPUT'

Bundles registered by plugin "Contao\CoreBundle\ContaoManager\Plugin"
=====================================================================

 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Bundle                                                                 Replaces   Load after                                                Environment  
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Knp\Bundle\MenuBundle\KnpMenuBundle                                                                                                         All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Knp\Bundle\TimeBundle\KnpTimeBundle                                                                                                         All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Scheb\TwoFactorBundle\SchebTwoFactorBundle                                                                                                  All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle                                                                                           All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Terminal42\ServiceAnnotationBundle\Terminal42ServiceAnnotationBundle                                                                        All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Contao\CoreBundle\ContaoCoreBundle                                     core       Symfony\Bundle\FrameworkBundle\FrameworkBundle            All          
                                                                                    Symfony\Bundle\SecurityBundle\SecurityBundle                           
                                                                                    Symfony\Bundle\TwigBundle\TwigBundle                                   
                                                                                    Symfony\Bundle\MonologBundle\MonologBundle                             
                                                                                    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle                     
                                                                                    Doctrine\Bundle\DoctrineBundle\DoctrineBundle                          
                                                                                    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle                
                                                                                    Knp\Bundle\MenuBundle\KnpMenuBundle                                    
                                                                                    Knp\Bundle\TimeBundle\KnpTimeBundle                                    
                                                                                    Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle                  
                                                                                    Nelmio\CorsBundle\NelmioCorsBundle                                     
                                                                                    Nelmio\SecurityBundle\NelmioSecurityBundle                             
                                                                                    Scheb\TwoFactorBundle\SchebTwoFactorBundle                             
                                                                                    Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle                      
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 


OUTPUT
        ];

        yield 'describe bundles of plugin by package name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => CoreBundlePlugin::class, '--bundles' => true],
            <<<'OUTPUT'

Bundles registered by plugin "Contao\CoreBundle\ContaoManager\Plugin"
=====================================================================

 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Bundle                                                                 Replaces   Load after                                                Environment  
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Knp\Bundle\MenuBundle\KnpMenuBundle                                                                                                         All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Knp\Bundle\TimeBundle\KnpTimeBundle                                                                                                         All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Scheb\TwoFactorBundle\SchebTwoFactorBundle                                                                                                  All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle                                                                                           All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Terminal42\ServiceAnnotationBundle\Terminal42ServiceAnnotationBundle                                                                        All          
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 
  Contao\CoreBundle\ContaoCoreBundle                                     core       Symfony\Bundle\FrameworkBundle\FrameworkBundle            All          
                                                                                    Symfony\Bundle\SecurityBundle\SecurityBundle                           
                                                                                    Symfony\Bundle\TwigBundle\TwigBundle                                   
                                                                                    Symfony\Bundle\MonologBundle\MonologBundle                             
                                                                                    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle                     
                                                                                    Doctrine\Bundle\DoctrineBundle\DoctrineBundle                          
                                                                                    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle                
                                                                                    Knp\Bundle\MenuBundle\KnpMenuBundle                                    
                                                                                    Knp\Bundle\TimeBundle\KnpTimeBundle                                    
                                                                                    Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle                  
                                                                                    Nelmio\CorsBundle\NelmioCorsBundle                                     
                                                                                    Nelmio\SecurityBundle\NelmioSecurityBundle                             
                                                                                    Scheb\TwoFactorBundle\SchebTwoFactorBundle                             
                                                                                    Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle                      
 ---------------------------------------------------------------------- ---------- --------------------------------------------------------- ------------- 


OUTPUT
        ];

        yield 'describe bundles' => [
            [],
            [new ContaoCoreBundle()],
            ['--bundles' => true],
            <<<'OUTPUT'

Available registered bundles in loading order
=============================================

 ------------------ ------------------------------------------ 
  Bundle name        Contao Resources path                     
 ------------------ ------------------------------------------ 
  ContaoCoreBundle   contao/core-bundle/src/Resources/contao/  
 ------------------ ------------------------------------------ 


OUTPUT
        ];
    }

    public function testCannotDescribePluginBundlesIfInterfaceIsNotImplemented()
    {
        $command = new DebugPluginsCommand($this->getKernel(['foo/bar-bundle' => new FixturesPlugin()]));

        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['name' => 'foo/bar-bundle', '--bundles' => true]);

        $this->assertSame(-1, $result);
        $this->assertSame(
            <<<'OUTPUT'

 [ERROR] The plugin "Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin" does not register bundles.               
         (It does not implement the "Contao\ManagerPlugin\Bundle\BundlePluginInterface" interface.)                     


OUTPUT
,
            $commandTester->getDisplay()
        );
    }

    public function testGeneratesErrorIfPluginDoesNotExist()
    {
        $command = new DebugPluginsCommand($this->getKernel(['foo/bar-bundle' => new FixturesPlugin()]));

        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['name' => 'foo/baz-bundle', '--bundles' => true]);

        $this->assertSame(-1, $result);
        $this->assertSame(
            <<<'OUTPUT'

 [ERROR] No plugin with class or package name "foo/baz-bundle" was found                                                


OUTPUT
            ,
            $commandTester->getDisplay()
        );
    }

    private function getKernel(array $plugins, array $bundles = []): ContaoKernel
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects(0 === count($plugins) ? $this->never() : $this->once())
            ->method('getInstances')
            ->willReturn($plugins)
        ;

        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $kernel
            ->expects($this->any())
            ->method('getPluginLoader')
            ->willReturn($pluginLoader)
        ;

        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->willReturn($bundles)
        ;

        $kernel
            ->expects($this->any())
            ->method('getProjectDir')
            ->willReturn(dirname(__DIR__, 4))
        ;

        $container->set('kernel', $kernel);

        return $kernel;
    }
}
