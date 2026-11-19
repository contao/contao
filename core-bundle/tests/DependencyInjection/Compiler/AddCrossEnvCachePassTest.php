<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddCrossEnvCachePass;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\DependencyInjection\CachePoolPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddCrossEnvCachePassTest extends TestCase
{
    public function testFallbackSharesTheAppCacheStorage(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->setParameter('kernel.container_class', 'TestContainer');
        $container->register('cache.app', FilesystemAdapter::class)
            ->setArguments(['', 0, $this->getTempDir()])
            ->addTag('cache.pool')
            ->setPublic(true)
        ;
        $container->setAlias('test.cross_env', 'contao.cache.cross_env')->setPublic(true);
        $container->addCompilerPass(new CachePoolPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 32);
        $container->addCompilerPass(new AddCrossEnvCachePass());
        $container->compile();

        $appCache = $container->get('cache.app');
        $appCache->save($appCache->getItem('test')->set('shared value'));

        $this->assertSame('shared value', $container->get('test.cross_env')->getItem('test')->get());
    }

    #[DataProvider('provideExistingCachePools')]
    public function testPreservesAnExistingCachePool(bool $alias): void
    {
        $container = new ContainerBuilder();
        $container->register('cache.app', NullAdapter::class);

        $poolId = $alias ? 'custom.cache' : 'contao.cache.cross_env';
        $container->register($poolId, ArrayAdapter::class)->setPublic(true);

        if ($alias) {
            $container->setAlias('contao.cache.cross_env', $poolId)->setPublic(true);
        }

        $container->addCompilerPass(new AddCrossEnvCachePass());
        $container->compile();

        $pool = $container->get('contao.cache.cross_env');
        $pool->save($pool->getItem('test')->set('custom value'));

        $this->assertSame('custom value', $pool->getItem('test')->get());
    }

    public static function provideExistingCachePools(): iterable
    {
        yield 'definition' => [false];
        yield 'alias' => [true];
    }
}
