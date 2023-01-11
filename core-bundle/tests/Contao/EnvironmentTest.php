<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EnvironmentTest extends TestCase
{
    use ExpectDeprecationTrait;

    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();

        $this->projectDir = strtr($this->getFixturesDir(), '\\', '/');

        Environment::reset();
        Environment::set('path', '/core');

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack());

        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR);

        System::setContainer($container);

        require __DIR__.'/../../contao/config/default.php';
    }

    protected function tearDown(): void
    {
        $this->restoreServerEnvGetPost();

        $this->resetStaticProperties([
            Environment::class,
            [Environment::class, ['strSapi']],
            System::class,
            Request::class,
            IpUtils::class,
        ]);

        parent::tearDown();
    }

    /**
     * @group legacy
     */
    public function testHandlesModPhp(): void
    {
        $this->setSapi('apache');

        $_SERVER = [];
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'keep-alive';
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36';
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate,sdch';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '123.45.67.89';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['DOCUMENT_ROOT'] = $this->projectDir;
        $_SERVER['SCRIPT_FILENAME'] = $this->projectDir.'/core/index.php';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['QUERY_STRING'] = 'do=test';
        $_SERVER['REQUEST_URI'] = '/core/en/academy.html?do=test';
        $_SERVER['SCRIPT_NAME'] = '/core/index.php';
        $_SERVER['PHP_SELF'] = '/core/index.php';

        $this->runTests();

        System::getContainer()->get('request_stack')->push(new Request(server: $_SERVER));

        $this->runTests();

        $_SERVER = [];

        $this->runTests();
    }

    /**
     * @group legacy
     */
    public function testHandlesCgiFcgi(): void
    {
        $this->setSapi('cgi_fcgi');

        $_SERVER = [];
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'close';
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36';
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate,sdch';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '123.45.67.89';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['DOCUMENT_ROOT'] = $this->projectDir;
        $_SERVER['SCRIPT_FILENAME'] = $this->projectDir.'/core/index.php';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['QUERY_STRING'] = 'do=test';
        $_SERVER['REQUEST_URI'] = '/core/en/academy.html?do=test';
        $_SERVER['SCRIPT_NAME'] = '/core/index.php';
        $_SERVER['PHP_SELF'] = '/core/index.php';
        $_SERVER['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $_SERVER['PATH_INFO'] = '/en/academy.html';
        $_SERVER['SCRIPT_URI'] = 'http://localhost/core/en/academy.html';
        $_SERVER['SCRIPT_URL'] = '/core/en/academy.html';

        $this->runTests();

        System::getContainer()->get('request_stack')->push(new Request(server: $_SERVER));

        $this->runTests();

        $_SERVER = [];

        $this->runTests();
    }

    /**
     * @group legacy
     */
    public function testHandlesFpmFcgi(): void
    {
        $this->setSapi('fpm_fcgi');

        $_SERVER = [];
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CONNECTION'] = 'close';
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36';
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate,sdch';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '123.45.67.89';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['DOCUMENT_ROOT'] = $this->projectDir;
        $_SERVER['SCRIPT_FILENAME'] = $this->projectDir.'/core/index.php';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['QUERY_STRING'] = 'do=test';
        $_SERVER['REQUEST_URI'] = 'http://localhost/core/en/academy.html?do=test'; // see #8661
        $_SERVER['SCRIPT_NAME'] = '/core/index.php';
        $_SERVER['PHP_SELF'] = '/core/index.php';
        $_SERVER['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $_SERVER['PATH_INFO'] = '/en/academy.html';

        $this->runTests();

        System::getContainer()->get('request_stack')->push(new Request(server: $_SERVER));

        $this->runTests();

        $_SERVER = [];

        $this->runTests();
    }

    private function runTests(): void
    {
        $this->assertSame('HTTP/1.1', Environment::get('serverProtocol'));
        $this->assertSame($this->projectDir.'/core/index.php', Environment::get('scriptFilename'));
        $this->assertSame('/core/index.php', Environment::get('scriptName'));
        $this->assertSame($this->projectDir, Environment::get('documentRoot'));
        $this->assertSame('/core/en/academy.html?do=test', Environment::get('requestUri'));
        $this->assertSame('do=test', Environment::get('queryString'));
        $this->assertSame(['de-DE', 'de', 'en-GB', 'en'], Environment::get('httpAcceptLanguage'));
        $this->assertSame(['gzip', 'deflate', 'sdch'], Environment::get('httpAcceptEncoding'));
        $this->assertSame('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36', Environment::get('httpUserAgent'));
        $this->assertSame('localhost', Environment::get('httpHost'));
        $this->assertEmpty(Environment::get('httpXForwardedHost'));

        $this->assertTrue(Environment::get('ssl'));
        $this->assertSame('https://localhost', Environment::get('url'));
        $this->assertSame('https://localhost/core/en/academy.html?do=test', Environment::get('uri'));
        $this->assertSame('123.45.67.89', Environment::get('ip'));
        $this->assertSame('127.0.0.1', Environment::get('server'));
        $this->assertSame('index.php', Environment::get('script'));
        $this->assertSame('/core/index.php', Environment::get('scriptName'));
        $this->assertSame('en/academy.html?do=test', Environment::get('request'));
        $this->assertSame('/core/en/academy.html?do=test', Environment::get('requestUri'));
        $this->assertSame('en/academy.html?do=test', Environment::get('indexFreeRequest'));
        $this->assertSame('https://localhost'.Environment::get('path').'/', Environment::get('base'));
        $this->assertFalse(Environment::get('isAjaxRequest'));
    }

    private function setSapi(string $sapi): void
    {
        $reflection = new \ReflectionClass(Environment::class);
        $property = $reflection->getProperty('strSapi');
        $property->setValue($sapi);
    }
}
