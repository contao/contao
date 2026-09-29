<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Serializer;

use Contao\CoreBundle\Serializer\WebauthnRpEntityNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\Denormalizer\PublicKeyCredentialRpEntityDenormalizer;
use Webauthn\PublicKeyCredentialRpEntity;

class WebauthnRpEntityNormalizerTest extends TestCase
{
    public function testUsesTheRequestHostAsTheRpIdAndName(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://www.example.org/contao/login'));

        $normalizer = new WebauthnRpEntityNormalizer($requestStack, new PublicKeyCredentialRpEntityDenormalizer());
        $normalizedData = $normalizer->normalize(PublicKeyCredentialRpEntity::create());

        $this->assertSame('www.example.org', $normalizedData['id']);
    }

    public function testDoesNotSupportAnExplicitRpId(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://www.example.org/contao/login'));

        $normalizer = new WebauthnRpEntityNormalizer($requestStack, new PublicKeyCredentialRpEntityDenormalizer());

        $this->assertFalse($normalizer->supportsNormalization(PublicKeyCredentialRpEntity::create('', 'example.org')));
    }
}
