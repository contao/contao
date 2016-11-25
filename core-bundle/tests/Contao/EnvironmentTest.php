<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\CoreBundle\Test\TestCase;
use Contao\Environment;
use Contao\System;

/**
 * Tests the Environment class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @group legacy
 */
class EnvironmentTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass()
    {
        Environment::reset();
        Environment::set('path', '/core');

        require __DIR__.'/../../src/Resources/contao/config/default.php';
        require __DIR__.'/../../src/Resources/contao/config/agents.php';
    }

    /**
     * Returns the normalized root directory.
     *
     * @return string
     */
    public function getRootDir()
    {
        return strtr(parent::getRootDir(), '\\', '/');
    }

    /**
     * Tests the mod_php environment.
     */
    public function testApache()
    {
        $this->setSapi('apache');

        $_SERVER = [
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4',
            'HTTP_X_FORWARDED_FOR' => '123.456.789.0',
            'SERVER_NAME' => 'localhost',
            'SERVER_ADDR' => '127.0.0.1',
            'DOCUMENT_ROOT' => $this->getRootDir(),
            'SCRIPT_FILENAME' => $this->getRootDir().'/core/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'QUERY_STRING' => 'do=test',
            'REQUEST_URI' => '/core/en/academy.html?do=test',
            'SCRIPT_NAME' => '/core/index.php',
            'PHP_SELF' => '/core/index.php',
        ];

        $this->runTests();
    }

    /**
     * Tests the cgi_fcgi environment.
     */
    public function testCgiFcgi()
    {
        $this->setSapi('cgi_fcgi');

        $_SERVER = [
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
            'SCRIPT_FILENAME' => $this->getRootDir().'/core/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'QUERY_STRING' => 'do=test',
            'REQUEST_URI' => '/core/en/academy.html?do=test',
            'SCRIPT_NAME' => '/core/index.php',
            'PHP_SELF' => '/core/index.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'PATH_INFO' => '/en/academy.html',
            'SCRIPT_URI' => 'http://localhost/core/en/academy.html',
            'SCRIPT_URL' => '/core/en/academy.html',
        ];

        $this->runTests();
    }

    /**
     * Tests the fpm_fcgi environment.
     */
    public function testFpmFcgi()
    {
        $this->setSapi('fpm_fcgi');

        $_SERVER = [
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
            'SCRIPT_FILENAME' => $this->getRootDir().'/core/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'QUERY_STRING' => 'do=test',
            'REQUEST_URI' => '/core/en/academy.html?do=test',
            'SCRIPT_NAME' => '/core/index.php',
            'PHP_SELF' => '/core/index.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'PATH_INFO' => '/en/academy.html',
        ];

        $this->runTests();
    }

    /**
     * Runs the actual tests.
     */
    protected function runTests()
    {
        // Environment::get('ip') needs the request stack
        System::setContainer($this->mockContainerWithContaoScopes());

        $agent = Environment::get('agent');

        $this->assertEquals('mac', $agent->os);
        $this->assertEquals('mac chrome webkit ch33', $agent->class);
        $this->assertEquals('chrome', $agent->browser);
        $this->assertEquals('ch', $agent->shorty);
        $this->assertEquals(33, $agent->version);
        $this->assertEquals('webkit', $agent->engine);
        $this->assertEquals([33, 0, 1750, 149], $agent->versions);
        $this->assertFalse($agent->mobile);

        $this->assertEquals('HTTP/1.1', Environment::get('serverProtocol'));
        $this->assertEquals($this->getRootDir().'/core/index.php', Environment::get('scriptFilename'));
        $this->assertEquals('/core/index.php', Environment::get('scriptName'));
        $this->assertEquals($this->getRootDir(), Environment::get('documentRoot'));
        $this->assertEquals('/core/en/academy.html?do=test', Environment::get('requestUri'));
        $this->assertEquals(['de-DE', 'de', 'en-GB', 'en'], Environment::get('httpAcceptLanguage'));
        $this->assertEquals(['gzip', 'deflate', 'sdch'], Environment::get('httpAcceptEncoding'));
        $this->assertEquals('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36', Environment::get('httpUserAgent'));
        $this->assertEquals('localhost', Environment::get('httpHost'));
        $this->assertEmpty(Environment::get('httpXForwardedHost'));

        $this->assertFalse(Environment::get('ssl'));
        $this->assertEquals('http://localhost', Environment::get('url'));
        $this->assertEquals('http://localhost/core/en/academy.html?do=test', Environment::get('uri'));
        $this->assertEquals('123.456.789.0', Environment::get('ip'));
        $this->assertEquals('127.0.0.1', Environment::get('server'));
        $this->assertEquals('index.php', Environment::get('script'));
        $this->assertEquals('en/academy.html?do=test', Environment::get('request'));
        $this->assertEquals('en/academy.html?do=test', Environment::get('indexFreeRequest'));
        $this->assertEquals('http://localhost'.Environment::get('path').'/', Environment::get('base'));
        $this->assertFalse(Environment::get('isAjaxRequest'));
    }

    /**
     * Overrides the SAPI value.
     *
     * @param string $sapi
     */
    private function setSapi($sapi)
    {
        $reflection = new \ReflectionClass('Contao\Environment');

        $property = $reflection->getProperty('strSapi');
        $property->setAccessible(true);
        $property->setValue($sapi);
    }
}
