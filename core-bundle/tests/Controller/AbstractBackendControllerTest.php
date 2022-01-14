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

use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment as ContaoEnvironment;
use Contao\System;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class AbstractBackendControllerTest extends TestCase
{
    public function testAddsAndMergesBackendContext(): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(): Response
            {
                return $this->render('custom_be.html.twig', ['foo' => 'bar', 'version' => 'my version']);
            }
        };

        // Legacy setup
        ContaoEnvironment::reset();
        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');

        $GLOBALS['TL_LANG']['MSC'] = [
            'version' => 'version',
            'dashboard' => 'dashboard',
            'home' => 'home',
            'learnMore' => 'learn more',
        ];

        $GLOBALS['TL_LANGUAGE'] = 'en';

        $_SERVER['HTTP_USER_AGENT'] = 'Contao/Foo';
        $_SERVER['HTTP_HOST'] = 'localhost';

        if (!\defined('TL_FILES_URL')) {
            \define('TL_FILES_URL', '');
        }

        $expectedContext = [
            'version' => 'my version',
            'headline' => 'dashboard',
            'title' => '',
            'theme' => 'flexible',
            'base' => 'http://localhost/',
            'language' => 'en',
            'host' => 'localhost',
            'charset' => 'UTF-8',
            'home' => 'home',
            'isPopup' => null,
            'learnMore' => 'learn more',
            'menu' => '<menu>',
            'headerMenu' => '<header_menu>',
            'foo' => 'bar',
        ];

        $container = $this->getContainerWithDefaultConfiguration($expectedContext);

        System::setContainer($container);
        $controller->setContainer($container);

        $this->assertSame('<custom_be_main>', $controller->fooAction()->getContent());

        // Cleanup
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_LANGUAGE'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_HOST']);
    }

    private function getContainerWithDefaultConfiguration(array $expectedContext): ContainerBuilder
    {
        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->exactly(3))
            ->method('render')
            ->willReturnMap([
                ['@ContaoCore/Backend/be_menu.html.twig', [], '<menu>'],
                ['@ContaoCore/Backend/be_header_menu.html.twig', [], '<header_menu>'],
                ['custom_be.html.twig', $expectedContext, '<custom_be_main>'],
            ])
        ;

        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('security.token_storage', $this->createMock(TokenStorageInterface::class));
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('session', $this->createMock(Session::class));
        $container->set('twig', $twig);
        $container->set('router', $this->createMock(RouterInterface::class));

        $container->setParameter('contao.resources_paths', $this->getTempDir());

        return $container;
    }
}
