<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm\Collector;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use RegexIterator;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use Symfony\Component\Finder\SplFileInfo;

class ExtensionCollector
{
    private $paths;
    private $cached;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function collect(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $extensions = [];
        $includedFiles = [];

        foreach ($this->paths as $path) {
            $searchPath = sprintf('%s/Extension', $path);

            if (!is_dir($searchPath)) {
                continue;
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($searchPath, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . preg_quote('.php') . '$/i',
                RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_traits();

        foreach ($declared as $traitName) {
            $rc = new ReflectionClass($traitName);
            $sourceFile = $rc->getFileName();

            if (!\in_array($sourceFile, $includedFiles)) {
                continue;
            }

            $extensions[] = $traitName;
        }

        $this->cached = $extensions;

        return $extensions;
    }
}
