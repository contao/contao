<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class GenerateDescriptorEvent extends Event
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

    /** @var array */
    private $options;

    /**
     * @param string $table
     * @param array $data
     * @param array $options
     */
    public function __construct(string $table, array $data, array $options = [])
    {
        $this->table = $table;
        $this->data = $data;
        $this->options = $options;
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
    public function setDescriptor(string $descriptor = null): void
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

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}