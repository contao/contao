<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\DC_Table;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

class ContentElementTypeListener implements ResetInterface
{
    private array $cache = [];

    public function __construct(private readonly Security $security)
    {
    }

    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function setDefault(DC_Table $dc): void
    {
        $defaultType = $GLOBALS['TL_DCA']['tl_content']['fields']['type']['sql']['default'] ?? null;
        $allowedTypes = array_merge(...array_values($this->getAllowedElements($dc->parentTable, $dc->currentPid)));

        if (!\in_array($defaultType, $allowedTypes, true)) {
            $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = $allowedTypes[0] ?? '';
        }

        if ([] === $allowedTypes) {
            $GLOBALS['TL_DCA']['tl_content']['config']['notCreatable'] = true;
        }
    }

    #[AsCallback(table: 'tl_content', target: 'fields.type.options')]
    public function getOptions(DC_Table $dc): array
    {
        return $this->getAllowedElements($dc->parentTable, $dc->currentPid);
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * Return elements that can be created in the current context (e.g. nested fragments).
     */
    private function getAllowedElements(string $ptable, int $pid): array
    {
        $cacheKey = $ptable.'.'.$pid;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $groups = [];

        foreach ($GLOBALS['TL_CTE'] as $k => $v) {
            foreach (array_keys($v) as $vv) {
                $action = new CreateAction('tl_content', [
                    'ptable' => $ptable,
                    'pid' => $pid,
                    'type' => $vv,
                ]);

                if ($this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_content', $action)) {
                    $groups[$k][] = $vv;
                }
            }
        }

        return $this->cache[$cacheKey] = $groups;
    }
}
