<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\WebhookFieldListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Webhook\SecretEncryption;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebhookFieldListenerTest extends TestCase
{
    public function testSavesValidUrl(): void
    {
        $listener = $this->createListener();

        $this->assertSame('https://example.com/webhook', $listener->saveUrl('https://example.com/webhook'));
    }

    public function testRejectsInvalidUrl(): void
    {
        $listener = $this->createListener();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook destinations must use HTTPS and must not contain credentials.');

        $listener->saveUrl('http://example.com/webhook');
    }

    public function testEncryptsOutgoingSecret(): void
    {
        $encryption = new SecretEncryption('app-secret');
        $listener = $this->createListenerWithEncryption($encryption);
        $dataContainer = $this->createStub(DataContainer::class);

        $encrypted = $listener->saveSecret('private-secret', $dataContainer);

        $this->assertNotSame('private-secret', $encrypted);
        $this->assertSame('private-secret', $encryption->decrypt($encrypted));
    }

    public function testKeepsExistingOutgoingSecretEncrypted(): void
    {
        $encryption = new SecretEncryption('app-secret');
        $listener = $this->createListenerWithEncryption($encryption);
        $encrypted = $encryption->encrypt('private-secret');
        $dataContainer = $this->createStub(DataContainer::class);

        $this->assertSame($encrypted, $listener->saveSecret($encrypted, $dataContainer));
    }

    public function testMasksSecret(): void
    {
        $listener = $this->createListener();

        $this->assertSame('********', $listener->loadSecret('encrypted-secret'));
        $this->assertSame('', $listener->loadSecret(''));
    }

    public function testPreservesOutgoingSecretWhenPlaceholderIsSubmitted(): void
    {
        $encryption = new SecretEncryption('app-secret');
        $listener = $this->createListenerWithEncryption($encryption);
        $encrypted = $encryption->encrypt('private-secret');
        $dataContainer = $this->createStub(DataContainer::class);
        $dataContainer
            ->method('getCurrentRecord')
            ->willReturn(['secret' => $encrypted])
        ;

        $this->assertSame($encrypted, $listener->saveSecret('********', $dataContainer));
    }

    public function testClearsOutgoingSecretWhenEmptyValueIsSubmitted(): void
    {
        $listener = $this->createListenerWithEncryption(new SecretEncryption('app-secret'));
        $dataContainer = $this->createStub(DataContainer::class);

        $this->assertSame('', $listener->saveSecret('', $dataContainer));
    }

    public function testClearsIncomingSecretWhenEmptyValueIsSubmitted(): void
    {
        $listener = $this->createListenerWithEncryption(new SecretEncryption('app-secret'));
        $dataContainer = $this->createStub(DataContainer::class);

        $this->assertSame('', $listener->saveIncomingSecret('', $dataContainer));
    }

    public function testPreservesIncomingSecretWhenPlaceholderIsSubmitted(): void
    {
        $encryption = new SecretEncryption('app-secret');
        $listener = $this->createListenerWithEncryption($encryption);
        $encrypted = $encryption->encrypt('private-secret');
        $dataContainer = $this->createStub(DataContainer::class);
        $dataContainer
            ->method('getCurrentRecord')
            ->willReturn(['secret' => $encrypted])
        ;

        $this->assertSame($encrypted, $listener->saveIncomingSecret('********', $dataContainer));
    }

    public function testStoresAndConsumesGeneratedSecret(): void
    {
        $session = $this->mockSession();
        $request = Request::create('/');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = $this->createListener($requestStack);
        $reflection = new \ReflectionClass($listener);
        $property = $reflection->getProperty('generatedSecret');
        $property->setValue($listener, 'secret');

        $listener->storeGeneratedSecret($this->createStub(DataContainer::class));

        $this->assertSame('secret', $session->get('contao.webhook_secret'));
    }

    private function createListener(RequestStack|null $requestStack = null): WebhookFieldListener
    {
        $reflection = new \ReflectionClass(WebhookFieldListener::class);

        if (!$requestStack) {
            return $reflection->newInstanceWithoutConstructor();
        }

        $listener = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('requestStack');
        $property->setValue($listener, $requestStack);

        return $listener;
    }

    private function createListenerWithEncryption(SecretEncryption $encryption): WebhookFieldListener
    {
        $reflection = new \ReflectionClass(WebhookFieldListener::class);
        $listener = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('encryption');
        $property->setValue($listener, $encryption);

        return $listener;
    }
}
