<?php

namespace Contao\CoreBundle\EventListener\DataContainer;

use Symfony\Component\Filesystem\Filesystem;

class LayoutAssets
{

    /**
     * @var string
     */
    private $jsonManifestPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $data;

    public function __construct(?string $jsonManifestPath, Filesystem $filesystem = null)
    {
        $this->jsonManifestPath = $jsonManifestPath;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getCssAssets(): array
    {
        return $this->getFilesWithExtension('.css');
    }

    public function getJsAssets(): array
    {
        return $this->getFilesWithExtension('.js');
    }

    private function getFilesWithExtension(string $extension)
    {
        if (!$this->filesystem->exists($this->jsonManifestPath)) {
            return [];
        }

        $this->load();

        return array_filter(
            $this->data,
            function ($file) use ($extension) {
                return substr($file, strlen($extension) * -1) === $extension;
            }
        );
    }

    private function load()
    {
        if (null !== $this->data) {
            return;
        }

        $json = json_decode(file_get_contents($this->jsonManifestPath), true);

        $this->data = array_keys($json);
    }
}
