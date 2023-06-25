<?php

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\DataContainer\BuilderTemplate\DataContainerBuilderTemplateInterface;
use Contao\DataContainer;
use InvalidArgumentException;

class DataContainerBuilder implements DataContainerBuilderInterface
{
    private string $name;
    private array $dca;
    /** 
     * @var array<string, DataContainerBuilderTemplateInterface>
     */
    private array $templates = [];

    /** 
     * @param DataContainerBuilderTemplateInterface[] $templates
     */
    public function __construct(string $name, iterable $templates = [])
    {
        $this->name = $name;
        $this->dca = $GLOBALS['TL_DCA'][$name] ?? [];

        foreach ($templates as $template) {
            $this->templates[$template->getName()] = $template;
        }
    }

    public function applyTemplate(string ...$templates): self
    {
        $merged = [];

        foreach ($templates as $name) {
            if (!isset($this->templates[$name])) {
                throw new InvalidArgumentException(sprintf('Could not find template "%s"', $name));
            }

            $merged = array_replace_recursive($merged, $this->templates[$name]->getConfig());
        }

        $this->dca = array_replace_recursive($merged, $this->dca);

        return $this;
    }

    public function setDriver(string $driver): self
    {
        $this->dca['config']['dataContainer'] = $driver;

        return $this;
    }

    public function setParentTable(string $table): self
    {
        return $this;
    }

    public function enableVersioning(bool $versioning = true): self
    {
        if ($versioning) {
            $this->dca['config']['enableVersioning'] = $versioning;
        } else {
            unset($this->dca['config']['enableVersioning']);
        }

        return $this;
    }

    public function addChildTable(string $table): self
    {
        $this->dca['config']['ctable'][] = $table;

        return $this;
    }

    public function removeChildTable(string $table): self
    {
        $idx = array_search($table, $this->dca['config']['ctable'], true);

        if (false !== $idx) {
            unset($this->dca['config']['ctable'][$idx]);
        }

        return $this;
    }

    public function addIndex(string $field, string $type = DataContainer::INDEX_SECONDARY): self
    {
        $this->dca['config']['sql']['keys'][$field] = $type;

        return $this;
    }

    public function removeIndex(string $field): self
    {
        unset($this->dca['config']['sql']['keys'][$field]);

        return $this;
    }

    public function addField(string $field, array $config): self
    {
        $this->dca['fields'][$field] = $config;

        return $this;
    }

    public function removeField(string $field): self
    {
        unset($this->dca['fields'][$field]);

        return $this;
    }

    public function addGlobalOperation(string $operation, array $config): self
    {
        return $this;
    }

    public function removeGlobalOperation(string $operation): self
    {
        return $this;
    }

    public function addOperation(string $operation, ?array $config = null): self
    {
        return $this;
    }

    public function removeOperation(string $operation): self
    {
        return $this;
    }

    public function setListMode(int $mode): self
    {
        return $this;
    }

    public function addListField(string $field): self
    {
        return $this;
    }

    public function addListHeaderField(string $field): self
    {
        return $this;
    }

    public function setListPanelLayout(string $layout): self
    {
        return $this;
    }

    public function setPalette(string $name, string $palette): self
    {
        return $this;
    }

    public function removePalette(string $name): self
    {
        return $this;
    }

    public function setSubPalette(string $name, string $palette): self
    {
        return $this;
    }

    public function removeSubPalette(string $name): self
    {
        return $this;
    }

    public function addSelector(string $name): self
    {
        return $this;
    }

    public function removeSelector(string $name): self
    {
        return $this;
    }

    public function create(bool $returnOnly = false): array
    {
        if (!$returnOnly) {
            $GLOBALS['TL_DCA'][$this->name] = $this->dca;
        }

        return $this->dca;
    }
}
