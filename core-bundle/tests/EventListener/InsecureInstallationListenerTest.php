<?php
/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;


use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Event\ContaoFrameworkBootEvent;
use Contao\CoreBundle\EventListener\Framework\ValidateInstallationListener;
use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the ValidateInstallationListener class.
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class ValidateInstallationListenerTest extends TestCase
{

    /**
     * Tests the validateInstallation() method.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @expectedException \Contao\CoreBundle\Exception\InsecureInstallationException
     */
    public function testValidateInstallation()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        $listener = new InsecureInstallationListener();

        $request = new Request();

        $request->server->add([
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_CONNECTION' => 'close',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4',
            'HTTP_X_FORWARDED_FOR' => '123.456.789.0',
            'SERVER_NAME' => 'localhost',
            'SERVER_ADDR' => '127.0.0.1',
            'DOCUMENT_ROOT' => $this->getRootDir(),
            'SCRIPT_FILENAME' => $this->getRootDir() . '/foo/web/app_dev.php',
            'ORIG_SCRIPT_FILENAME' => '/var/run/localhost.fcgi',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'QUERY_STRING' => 'do=test',
            'REQUEST_URI' => '/web/app_dev.php?do=test',
            'SCRIPT_NAME' => '/foo/web/app_dev.php',
            'ORIG_SCRIPT_NAME' => '/php.fcgi',
            'PHP_SELF' => '/foo/web/app_dev.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'ORIG_PATH_INFO' => '/foo/web/app_dev.php',
            'ORIG_PATH_TRANSLATED' => $this->getRootDir() . '/foo/web/app_dev.php',
        ]);

        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');

        $kernel->getContainer()->enterScope(ContaoCoreBundle::SCOPE_BACKEND);

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        $listener->onKernelRequest($event);
    }
}
