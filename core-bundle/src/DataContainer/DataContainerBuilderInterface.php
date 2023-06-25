<?php

declare(strict_types=1);

namespace Contao\CoreBundle\DataContainer;

use Contao\DataContainer;

interface DataContainerBuilderInterface
{
    /**
     * @param string $templates one ore more templates to apply
     */
    public function applyTemplate(string ...$templates): self;

    public function setDriver(string $driver): self;

    public function setParentTable(string $table): self;

    public function enableVersioning(bool $versioning = true): self;

    public function addChildTable(string $table): self;

    public function removeChildTable(string $table): self;

    /**
     * @param One $type of DataContainer::INDEX_*
     */
    public function addIndex(string $field, string $type = DataContainer::INDEX_SECONDARY): self;

    public function removeIndex(string $field): self;

    public function addField(string $field, array $config): self;

    public function removeField(string $field): self;

    public function addGlobalOperation(string $operation, array $config): self;

    public function removeGlobalOperation(string $operation): self;

    public function addOperation(string $operation, array|null $config = null): self;

    public function removeOperation(string $operation): self;

    /**
     * Defines the way the records are displayed in the Contao back end.
     *
     * @param One $mode of DataContainer::MODE_*
     */
    public function setListMode(int $mode): self;

    public function addListField(string $field): self;

    /**
     * Adds a header field for DataContainer::MODE_PARENT.
     * This is a reference to a field in the parent data container.
     */
    public function addListHeaderField(string $field): self;

    public function setListPanelLayout(string $layout): self;

    public function setPalette(string $name, string $palette): self;

    public function removePalette(string $name): self;

    public function setSubPalette(string $name, string $palette): self;

    public function removeSubPalette(string $name): self;

    public function addSelector(string $name): self;

    public function removeSelector(string $name): self;

    /**
     * Stores the Data Container Array in $GLOBALS['TL_DCA'] and returns the array.
     *
     * @param Only $returnOnly returns the Data Container Array and does not write to $GLOBALS['TL_DCA']
     */
    public function create(bool $returnOnly = false): array;
}
