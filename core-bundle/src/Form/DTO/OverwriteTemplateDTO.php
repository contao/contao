<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Form\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\PathUtil\Path;

class OverwriteTemplateDTO
{
    public const TYPE_CONTAO_TEMPLATE = 'contao';
    public const TYPE_BUNDLE_TEMPLATE = 'bundle';

    /**
     * @Assert\NotNull()
     *
     * @var string|null
     */
    private $type;

    /**
     * @Assert\NotBlank(groups={"contao"})
     *
     * @var string|null
     */
    private $sourceContao;

    /**
     * @Assert\NotBlank(groups={"contao"})
     *
     * @var string|null
     */
    private $targetDirectory;

    /**
     * @Assert\NotBlank(groups={"bundle"})
     *
     * @var string|null
     */
    private $sourceBundle;

    /**
     * @var array<string, string>
     */
    private $bundleTargetPathMapping = [];

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getSourceContao(): ?string
    {
        return $this->sourceContao;
    }

    public function setSourceContao(?string $sourceContao): void
    {
        $this->sourceContao = $sourceContao;
    }

    public function getSourceBundle(): ?string
    {
        return $this->sourceBundle;
    }

    public function setSourceBundle(?string $sourceBundle): void
    {
        $this->sourceBundle = $sourceBundle;
    }

    public function getTargetDirectory(): ?string
    {
        return $this->targetDirectory;
    }

    public function setTargetDirectory(?string $targetDirectory): void
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function setBundleTargetPathMapping(array $bundleTargetPathMapping): void
    {
        $this->bundleTargetPathMapping = $bundleTargetPathMapping;
    }

    /**
     * Get selected source file.
     */
    public function getSource(): ?string
    {
        if (self::TYPE_CONTAO_TEMPLATE === $this->type) {
            return $this->getSourceContao();
        }

        return $this->getSourceBundle();
    }

    /**
     * Get effective target file.
     */
    public function getTarget(): ?string
    {
        if (null === ($source = $this->getSource())) {
            return null;
        }

        if (self::TYPE_CONTAO_TEMPLATE === $this->type) {
            return Path::join($this->targetDirectory, Path::getFilename($source));
        }

        return $this->bundleTargetPathMapping[$source] ?? null;
    }
}
