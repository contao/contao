<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Widget;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @template T of ContaoUser
 *
 * @extends UserFactoryInterface<T>
 */
abstract class AbstractUserFactory implements UserFactoryInterface, ResetInterface
{
    /**
     * @var array<string, mixed>|null
     */
    private array|null $cachedDefaults = null;

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ContaoUser<T>
     */
    public function createWithDefaults(array $data): ContaoUser
    {
        return $this->create(array_merge($this->getDefaultDataFromDca(), $data));
    }

    public function reset(): void
    {
        $this->cachedDefaults = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultDataFromDca(): array
    {
        if (null !== $this->cachedDefaults) {
            return $this->cachedDefaults;
        }

        $this->framework->initialize();
        $this->framework->getAdapter(Controller::class)->loadDataContainer($this->getTable());

        $defaults = [];

        foreach ($GLOBALS['TL_DCA'][$this->getTable()]['fields'] ?? [] as $field => $config) {
            if (\array_key_exists('default', $config)) {
                $value = $config['default'];

                if (\is_callable($value)) {
                    $value = $value();
                }
            } elseif (\is_array($config['sql']) && \array_key_exists('default', $config['sql'])) {
                $value = $config['sql']['default'];
            } elseif (isset($config['sql'])) {
                $value = Widget::getEmptyValueByFieldType($config['sql']);
            } else {
                continue;
            }

            if (\is_array($value) || \is_object($value)) {
                $value = serialize($value);
            }

            $defaults[$field] = $value;
        }

        return $this->cachedDefaults = $defaults;
    }
}
