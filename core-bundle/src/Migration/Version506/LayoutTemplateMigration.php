<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version506;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;

class LayoutTemplateMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFilesystemLoader $filesystemLoader,
        private readonly Filesystem $filesystem,
        private readonly Environment $twig,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        if (!\array_key_exists('type', $columns) || !\array_key_exists('template', $columns)) {
            return false;
        }

        $test = $this->connection->fetchOne("SELECT TRUE FROM tl_layout WHERE type='modern' AND template='layout/default' LIMIT 1");

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        $this->connection->update(
            'tl_layout',
            [
                'template' => 'page/layout',
            ],
            [
                'type' => 'modern',
                'template' => 'layout/default',
            ],
        );

        $error = false;

        foreach ($this->getOldTemplatePaths() as $oldPath) {
            $newPath = Path::join(Path::getDirectory($oldPath), '../page/layout.html.twig');

            try {
                if (!$this->filesystem->exists($targetDirectory = Path::getDirectory($newPath))) {
                    $this->filesystem->mkdir($targetDirectory);
                }

                $this->filesystem->dumpFile(
                    $newPath,
                    $this->updateTemplateContent($this->filesystem->readFile($oldPath)),
                );

                $this->filesystem->remove($oldPath);
                $this->twig->removeCache($oldPath);
            } catch (IOException) {
                $error = true;
            }
        }

        $this->filesystemLoader->warmUp(true);

        return $this->createResult(!$error);
    }

    private function getOldTemplatePaths(): \Generator
    {
        foreach ($this->filesystemLoader->getInheritanceChains()['layout/default'] ?? [] as $path => $logicalName) {
            if (\in_array(ContaoTwigUtil::parseContaoName($logicalName)[0], ['Contao_App', 'Contao_Global'], true)) {
                yield $path;
            }
        }
    }

    private function updateTemplateContent(string $content): string
    {
        return str_replace(
            '@Contao/layout/default.html.twig',
            '@Contao/page/layout.html.twig',
            $content,
        );
    }
}
