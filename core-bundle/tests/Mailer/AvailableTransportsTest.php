<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Mailer;

use Contao\CoreBundle\Mailer\AvailableTransports;
use Contao\CoreBundle\Mailer\TransportConfig;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;

class AvailableTransportsTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    public function testAnnotatedCallbacks(): void
    {
        $service = new AvailableTransports();

        $annotationReader = new AnnotationReader();
        $annotations = $annotationReader->getMethodAnnotations(new \ReflectionMethod($service, 'getTransportOptions'));

        $this->assertCount(2, $annotations);

        [$pageCallback, $formCallback] = $annotations;

        $this->assertInstanceOf(Callback::class, $pageCallback);
        $this->assertInstanceOf(Callback::class, $formCallback);

        $this->assertSame(
            [
                'table' => 'tl_page',
                'target' => 'fields.mailerTransport.options',
                'priority' => null,
            ],
            get_object_vars($pageCallback)
        );

        $this->assertSame(
            [
                'table' => 'tl_form',
                'target' => 'fields.mailerTransport.options',
                'priority' => null,
            ],
            get_object_vars($formCallback)
        );
    }

    public function testAddsTransports(): void
    {
        $availableTransports = new AvailableTransports();
        $availableTransports->addTransport(new TransportConfig('foobar'));
        $availableTransports->addTransport(new TransportConfig('lorem', 'Lorem Ipsum <lorem.ipsum@example.org>'));

        $this->assertSame(
            [
                'foobar' => 'foobar',
                'lorem' => 'lorem (Lorem Ipsum &lt;lorem.ipsum@example.org&gt;)',
            ],
            $availableTransports->getTransportOptions()
        );

        $this->assertCount(2, $availableTransports->getTransports());
        $this->assertNotNull($availableTransports->getTransport('foobar'));
        $this->assertNotNull($availableTransports->getTransport('lorem'));
        $this->assertSame('foobar', $availableTransports->getTransport('foobar')->getName());
        $this->assertNull($availableTransports->getTransport('foobar')->getFrom());
        $this->assertSame('lorem', $availableTransports->getTransport('lorem')->getName());
        $this->assertSame('Lorem Ipsum <lorem.ipsum@example.org>', $availableTransports->getTransport('lorem')->getFrom());
    }
}
