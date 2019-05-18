<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DescriptorGenerationEvent extends Event
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $descriptor;

    /**
     * UndoLabelGenerationEvent constructor.
     * @param array $data
     */
    public function __construct(string $table, array $data)
    {
        $this->table = $table;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param string $descriptor
     */
    public function setDescriptor(string $descriptor): void
    {
        $this->descriptor = $descriptor;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

}
