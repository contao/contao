<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\TestCase\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnRpEntityNormalizerTest extends FunctionalTestCase
{
    public function testTheNormalizerIsRegisteredWithTheSymfonySerializer(): void
    {
        static::bootKernel();

        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push(Request::create('https://www.example.org/'));

        $data = self::getContainer()->get('serializer')->normalize(PublicKeyCredentialRpEntity::create());

        $this->assertSame('www.example.org', $data['id']);
    }

    public function testTheCreationProfileContainsAnRpNode(): void
    {
        static::bootKernel();

        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push(Request::create('https://www.example.org/'));

        $options = self::getContainer()->get(PublicKeyCredentialCreationOptionsFactory::class)->create(
            'contao_backend',
            PublicKeyCredentialUserEntity::create('user', 'user', 'User'),
        );

        $data = self::getContainer()->get('serializer')->normalize($options);

        $this->assertSame('www.example.org', $data['rp']['id']);
    }
}
