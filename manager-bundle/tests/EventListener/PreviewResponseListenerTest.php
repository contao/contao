<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\ManagerBundle\EventListener\PreviewResponseListener;
use Contao\TestCase\ContaoTestCase;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PreviewResponseListenerTest extends ContaoTestCase
{
    #[DataProvider('requestProvider')]
    public function testAppliesHeadersOnlyToTheMainPreviewResponse(bool $preview, int $type): void
    {
        $request = Request::create('/');
        $request->attributes->set('_preview', $preview);

        $response = new Response();
        $response->headers->set(TagHeaderFormatter::DEFAULT_HEADER_NAME, 'preview-tag');
        $response->setSharedMaxAge(500);

        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, $type, $response);

        new PreviewResponseListener()($event);

        if ($preview && HttpKernelInterface::MAIN_REQUEST === $type) {
            $this->assertSame('noindex', $response->headers->get('X-Robots-Tag'));
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $this->assertFalse($response->headers->has(TagHeaderFormatter::DEFAULT_HEADER_NAME));
        } else {
            $this->assertFalse($response->headers->has('X-Robots-Tag'));
            $this->assertSame('500', $response->headers->getCacheControlDirective('s-maxage'));
            $this->assertSame('preview-tag', $response->headers->get(TagHeaderFormatter::DEFAULT_HEADER_NAME));
        }
    }

    public static function requestProvider(): iterable
    {
        yield 'preview' => [true, HttpKernelInterface::MAIN_REQUEST];
        yield 'normal' => [false, HttpKernelInterface::MAIN_REQUEST];
        yield 'subrequest' => [true, HttpKernelInterface::SUB_REQUEST];
    }
}
