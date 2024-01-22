<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\Csp;

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class CspHandlerTest extends TestCase
{
    public function testGeneratesNonce(): void
    {
        $cspHandler = $this->getCspHandler();
        $nonce = $cspHandler->getNonce('script-src');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertNotNull($nonce);
        $this->assertStringContainsString('nonce-'.$nonce, $response->headers->get('Content-Security-Policy'));
    }

    public function testDoesNotGenerateNonceIfNoDirectiveSet(): void
    {
        $cspHandler = $this->getCspHandler(['style-src' => "'self'"]);
        $nonce = $cspHandler->getNonce('script-src');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertNull($nonce);
        $this->assertStringNotContainsString('script-src', $response->headers->get('Content-Security-Policy'));
    }

    public function testGeneratesHash(): void
    {
        $cspHandler = $this->getCspHandler();
        $cspHandler->addHash('script-src', 'doSomething();');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringContainsString("script-src 'self' 'sha384-", $response->headers->get('Content-Security-Policy'));
    }

    public function testDoesNotGenerateHashIfNoDirectiveSet(): void
    {
        $cspHandler = $this->getCspHandler(['style-src' => "'self'"]);
        $cspHandler->addHash('script-src', 'doSomething();');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertStringNotContainsString('script-src', $response->headers->get('Content-Security-Policy'));
    }

    public function testAddsSource(): void
    {
        $cspHandler = $this->getCspHandler(['default-src' => "'self' foobar.com", 'frame-src' => "'self'"]);
        $cspHandler->addSource('frame-src', 'www.youtube.com');
        $cspHandler->addSource('img-src', 'data:');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("default-src 'self' foobar.com; frame-src 'self' www.youtube.com; img-src 'self' foobar.com data:", $response->headers->get('Content-Security-Policy'));
    }

    public function testDoesNotAddSource(): void
    {
        $cspHandler = $this->getCspHandler(['style-src' => "'self'"]);
        $cspHandler->addSource('frame-src', 'foobar.com');

        $response = new Response();
        $cspHandler->applyHeaders($response);

        $this->assertSame("style-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testChecksIfDirectiveOrFallbackIsSet(): void
    {
        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertNotNull($cspHandler->getDirective('script-src'));

        $cspHandler = $this->getCspHandler(['default-src' => "'self'"]);
        $this->assertNull($cspHandler->getDirective('script-src', false));
    }

    public function testAppliesHeaders(): void
    {
        $response = new Response();

        $cspHandler = $this->getCspHandler();
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy'));

        $response = new Response();

        $cspHandler->setReportOnly(true);
        $cspHandler->applyHeaders($response);

        $this->assertSame("script-src 'self'", $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function testCspExceedsMaximumLengthAndCannotBeReduced(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/^The generated Content Security Policy header exceeds 20 bytes. It is highly unlikely.+/');

        $response = new Response();
        $cspHandler = $this->getCspHandler(
            [
                'default-src' => "'self'",
                'style-src' => "'self'",
                'script-src' => "'self'",
            ],
            20,
        );

        for ($i = 0; $i < 5; ++$i) {
            $cspHandler->addHash('style-src', bin2hex(random_bytes(20)));
        }

        $cspHandler->applyHeaders($response);
    }

    /**
     * @dataProvider cspExceedsMaximumLengthIsProperlyReducedProvider
     */
    public function testCspExceedsMaximumLengthIsProperlyReduced(int $maxHeaderLength, array $styleHashes, array $scriptHashes, string $expectedLogMessage, string $expectedCspHeader): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects('' === $expectedLogMessage ? $this->never() : $this->once())
            ->method('critical')
            ->with($expectedLogMessage)
        ;

        $response = new Response();
        $cspHandler = $this->getCspHandler(['default-src' => "'self'"], $maxHeaderLength, $logger);

        foreach ($styleHashes as $styleHash) {
            $cspHandler->addHash('style-src', $styleHash);
        }

        foreach ($scriptHashes as $scriptHash) {
            $cspHandler->addHash('script-src', $scriptHash);
        }

        $cspHandler->applyHeaders($response);

        $this->assertSame($expectedCspHeader, $response->headers->get('Content-Security-Policy'));
    }

    public function cspExceedsMaximumLengthIsProperlyReducedProvider(): \Generator
    {
        yield 'All hashes fit into the header, nothing should be reduced' => [
            4096,
            [
                'style-d9813b22',
                'style-194c5b63',
                'style-87ef01e8',
            ],
            [
                'script-4bef1ebb',
                'script-ebdca00f',
                'script-096ccf1e',
            ],
            '',
            "default-src 'self'; script-src 'self' 'sha384-WiH70QJ//IwHzEEazDLD3ib/HCBX/w5DVK7iLcwNctCgC1gny3WZhpHYVc6MjM9p' 'sha384-2C5rO1BSGnvaxlQR9JaYInkYkxIBgqypaFa5W2Ers/2Uo+TF7HaOLeGTGhv2OFqL' 'sha384-H4X1/C0N5XroCPXG9EsrS7uyO3Gr2++QGudhEO0BrXuRgO83gfa1jegPJfs9Pr7f'; style-src 'self' 'sha384-6ygZ/e3B2JsYp8JUU2hp06kb4zU3aV3N9xgzeKjSvx0RhCkYBh3hG3gS9+iaa/Pc' 'sha384-SfjF7vhH3AekJ2O/KUjiHQg3wZ1GvMZKSio0AbFkBjoq8JmX2VeOp++Jg+j6DmSZ' 'sha384-6EpNvRkpzXKc0/GqaxbbUjVVMY7ihAahgNJolwHhuXbgncyGzHtMDoKymvNwecrl'",
        ];

        yield 'Not all hashes fit in the header. Should reduce the last style hash' => [
            480,
            [
                'style-d9813b22',
                'style-194c5b63',
                'style-87ef01e8',
            ],
            [
                'script-4bef1ebb',
                'script-ebdca00f',
                'script-096ccf1e',
            ],
            'Allowed CSP header size of 480 bytes exhausted (tried to write 499 bytes). Removed style-src hashes: sha384-6EpNvRkpzXKc0/GqaxbbUjVVMY7ihAahgNJolwHhuXbgncyGzHtMDoKymvNwecrl. Removed script-src hashes: none.',
            "default-src 'self'; script-src 'self' 'sha384-WiH70QJ//IwHzEEazDLD3ib/HCBX/w5DVK7iLcwNctCgC1gny3WZhpHYVc6MjM9p' 'sha384-2C5rO1BSGnvaxlQR9JaYInkYkxIBgqypaFa5W2Ers/2Uo+TF7HaOLeGTGhv2OFqL' 'sha384-H4X1/C0N5XroCPXG9EsrS7uyO3Gr2++QGudhEO0BrXuRgO83gfa1jegPJfs9Pr7f'; style-src 'self' 'sha384-6ygZ/e3B2JsYp8JUU2hp06kb4zU3aV3N9xgzeKjSvx0RhCkYBh3hG3gS9+iaa/Pc' 'sha384-SfjF7vhH3AekJ2O/KUjiHQg3wZ1GvMZKSio0AbFkBjoq8JmX2VeOp++Jg+j6DmSZ'",
        ];

        yield 'None of the style hashes fit' => [
            350,
            [
                'style-d9813b22',
                'style-194c5b63',
                'style-87ef01e8',
            ],
            [
                'script-4bef1ebb',
                'script-ebdca00f',
                'script-096ccf1e',
            ],
            'Allowed CSP header size of 350 bytes exhausted (tried to write 499 bytes). Removed style-src hashes: sha384-6EpNvRkpzXKc0/GqaxbbUjVVMY7ihAahgNJolwHhuXbgncyGzHtMDoKymvNwecrl, sha384-SfjF7vhH3AekJ2O/KUjiHQg3wZ1GvMZKSio0AbFkBjoq8JmX2VeOp++Jg+j6DmSZ, sha384-6ygZ/e3B2JsYp8JUU2hp06kb4zU3aV3N9xgzeKjSvx0RhCkYBh3hG3gS9+iaa/Pc. Removed script-src hashes: none.',
            "default-src 'self'; script-src 'self' 'sha384-WiH70QJ//IwHzEEazDLD3ib/HCBX/w5DVK7iLcwNctCgC1gny3WZhpHYVc6MjM9p' 'sha384-2C5rO1BSGnvaxlQR9JaYInkYkxIBgqypaFa5W2Ers/2Uo+TF7HaOLeGTGhv2OFqL' 'sha384-H4X1/C0N5XroCPXG9EsrS7uyO3Gr2++QGudhEO0BrXuRgO83gfa1jegPJfs9Pr7f'; style-src 'self'",
        ];

        yield 'Not all of the script hashes fit either' => [
            200,
            [
                'style-d9813b22',
                'style-194c5b63',
                'style-87ef01e8',
            ],
            [
                'script-4bef1ebb',
                'script-ebdca00f',
                'script-096ccf1e',
            ],
            'Allowed CSP header size of 200 bytes exhausted (tried to write 499 bytes). Removed style-src hashes: sha384-6EpNvRkpzXKc0/GqaxbbUjVVMY7ihAahgNJolwHhuXbgncyGzHtMDoKymvNwecrl, sha384-SfjF7vhH3AekJ2O/KUjiHQg3wZ1GvMZKSio0AbFkBjoq8JmX2VeOp++Jg+j6DmSZ, sha384-6ygZ/e3B2JsYp8JUU2hp06kb4zU3aV3N9xgzeKjSvx0RhCkYBh3hG3gS9+iaa/Pc. Removed script-src hashes: sha384-H4X1/C0N5XroCPXG9EsrS7uyO3Gr2++QGudhEO0BrXuRgO83gfa1jegPJfs9Pr7f, sha384-2C5rO1BSGnvaxlQR9JaYInkYkxIBgqypaFa5W2Ers/2Uo+TF7HaOLeGTGhv2OFqL.',
            "default-src 'self'; script-src 'self' 'sha384-WiH70QJ//IwHzEEazDLD3ib/HCBX/w5DVK7iLcwNctCgC1gny3WZhpHYVc6MjM9p'; style-src 'self'",
        ];

        yield 'None of the hashes fit' => [
            100,
            [
                'style-d9813b22',
                'style-194c5b63',
                'style-87ef01e8',
            ],
            [
                'script-4bef1ebb',
                'script-ebdca00f',
                'script-096ccf1e',
            ],
            'Allowed CSP header size of 100 bytes exhausted (tried to write 499 bytes). Removed style-src hashes: sha384-6EpNvRkpzXKc0/GqaxbbUjVVMY7ihAahgNJolwHhuXbgncyGzHtMDoKymvNwecrl, sha384-SfjF7vhH3AekJ2O/KUjiHQg3wZ1GvMZKSio0AbFkBjoq8JmX2VeOp++Jg+j6DmSZ, sha384-6ygZ/e3B2JsYp8JUU2hp06kb4zU3aV3N9xgzeKjSvx0RhCkYBh3hG3gS9+iaa/Pc. Removed script-src hashes: sha384-H4X1/C0N5XroCPXG9EsrS7uyO3Gr2++QGudhEO0BrXuRgO83gfa1jegPJfs9Pr7f, sha384-2C5rO1BSGnvaxlQR9JaYInkYkxIBgqypaFa5W2Ers/2Uo+TF7HaOLeGTGhv2OFqL, sha384-WiH70QJ//IwHzEEazDLD3ib/HCBX/w5DVK7iLcwNctCgC1gny3WZhpHYVc6MjM9p.',
            "default-src 'self'; script-src 'self' ; style-src 'self'",
        ];
    }

    private function getCspHandler(array $directives = ['script-src' => "'self'"], int $maxHeaderLength = 4096, LoggerInterface|null $logger = null): CspHandler
    {
        $directiveSet = new DirectiveSet(new PolicyManager());
        $directiveSet->setDirectives($directives);
        $directiveSet->setLevel1Fallback(false);

        return new CspHandler($directiveSet, $maxHeaderLength, $logger);
    }
}
