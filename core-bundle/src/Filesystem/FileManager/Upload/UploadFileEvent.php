<?php
declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Upload;

use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class UploadFileEvent
{
    private string $filenameWithoutExtension;
    private string $extension;
    private $deniedReasons = [];

    public function __construct(string $name)
    {
        $this->filenameWithoutExtension = Path::getFilenameWithoutExtension($name);
        $this->extension = Path::getExtension($name, true);
    }

    public function getFilenameWithoutExtension(): string
    {
        return $this->filenameWithoutExtension;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getFilename(): string
    {
        return $this->filenameWithoutExtension . '.' . $this->extension;
    }

    public function isDenied(): bool
    {
        return !empty($this->deniedReasons);
    }

    public function getDenyReasons(): array
    {
        return array_filter($this->deniedReasons);
    }

    public function setFilenameWithoutExtension(string $name): void
    {
        $this->filenameWithoutExtension = $name;
    }

    public function deny(string|null $reason = null): void
    {
        $this->deniedReasons[] = $reason;
    }
}
