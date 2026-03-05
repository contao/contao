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

use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DC_Table;

/**
 * @internal
 */
class CudPermissionListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ResourceFinderInterface $resourceFinder,
    ) {
    }

    #[AsHook('loadDataContainer', priority: -255)]
    public function addDefaultPermissions(string $table): void
    {
        // Only handle DC_Table
        if (!is_a($GLOBALS['TL_DCA'][$table]['config']['dataContainer'] ?? null, DC_Table::class, true)) {
            return;
        }

        // Do not overwrite existing permissions
        if (isset($GLOBALS['TL_DCA'][$table]['config']['permissions'])) {
            return;
        }

        // Only add DCAs with at least one editable field
        if (!$this->hasEditableFields($table)) {
            return;
        }

        $GLOBALS['TL_DCA'][$table]['config']['permissions'] = ['create', 'update', 'delete'];
    }

    /**
     * @return array<string, list<string>>
     */
    #[AsCallback('tl_user', 'fields.cud.options')]
    #[AsCallback('tl_user_group', 'fields.cud.options')]
    public function getCudOptions(): array
    {
        $this->loadDcaFiles();

        $options = [];

        foreach ($GLOBALS['TL_DCA'] as $table => $dca) {
            if (\is_array($dca['config']['permissions'] ?? null) && [] !== $dca['config']['permissions']) {
                $options[$table] = $dca['config']['permissions'];
            }
        }

        return $options;
    }

    private function loadDcaFiles(): void
    {
        $processed = [];
        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        foreach ($files as $file) {
            if (\in_array($file->getBasename(), $processed, true)) {
                continue;
            }

            $processed[] = $file->getBasename();

            $controllerAdapter->loadDataContainer($file->getBasename('.php'));
        }
    }

    private function hasEditableFields(string $table): bool
    {
        foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $field) {
            if (isset($field['inputType'])) {
                return true;
            }
        }

        return false;
    }
}
