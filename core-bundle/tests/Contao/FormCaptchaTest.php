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

use Contao\Config;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FormCaptcha;
use Contao\Input;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class FormCaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->set('request_stack', $stack = new RequestStack());
        $container->set('contao.routing.scope_matcher', $this->createStub(ScopeMatcher::class));
        $container->setParameter('kernel.secret', 'secret');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.cache_dir', __DIR__);

        $container->set(
            'contao.rate_limit.form_captcha_factory',
            new RateLimiterFactory(
                ['id' => 'contao.form_captcha', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '30 minutes'],
                new InMemoryStorage(),
            ),
        );

        $stack->push(new Request([], [
            'captcha_test' => '10',
            'captcha_test_hash101' => $this->getHash(10, 'proof'),
        ]));

        System::setContainer($container);
        $GLOBALS['TL_LANG']['ERR']['captcha'] = 'Invalid captcha';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([Input::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testCaptchaProofCanOnlyBeConsumedOnce(): void
    {
        $captcha = new FormCaptcha(['id' => 'test']);
        $captcha->validate();

        $this->assertFalse($captcha->hasErrors());

        $captcha = new FormCaptcha(['id' => 'test']);
        $captcha->validate();

        $this->assertTrue($captcha->hasErrors());
    }

    public function testCaptchaProofCannotBeChanged(): void
    {
        $captcha = new FormCaptcha(['id' => 'test']);
        $captcha->validate();

        $this->assertFalse($captcha->hasErrors());

        $hash = Input::post('captcha_test_hash101');
        Input::setPost('captcha_test_hash101', explode(':', $hash, 2)[0].':changed-proof');

        $captcha = new FormCaptcha(['id' => 'test']);
        $captcha->validate();

        $this->assertTrue($captcha->hasErrors());
    }

    public function testGeneratedCaptchaCanBeValidated(): void
    {
        $captcha = new FormCaptcha(['id' => 'test']);
        $sum = $captcha->sum;

        Input::setPost('captcha_test', (string) $sum);
        Input::setPost('captcha_test_hash'.($sum ** 2 + 1), $captcha->hash);

        $captcha = new FormCaptcha(['id' => 'test']);
        $captcha->validate();

        $this->assertFalse($captcha->hasErrors());
    }

    private function getHash(int $sum, string $proof): string
    {
        $time = (int) round(time() / 60 / 30);

        return hash_hmac('sha256', $sum."\0".$time."\0".$proof, 'secret').':'.$proof;
    }
}
