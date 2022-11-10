<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version408;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\File;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\IntegerType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class Version480Update extends AbstractMigration
{
    private Connection $connection;
    private Filesystem $filesystem;
    private ContaoFramework $framework;
    private string $projectDir;

    /**
     * @var array<string>
     */
    private array $resultMessages = [];

    public function __construct(Connection $connection, Filesystem $filesystem, ContaoFramework $framework, string $projectDir)
    {
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->framework = $framework;
        $this->projectDir = $projectDir;
    }

    public function getName(): string
    {
        return 'Contao 4.8.0 Update';
    }

    public function shouldRun(): bool
    {
        return $this->shouldRunMediaelement()
            || $this->shouldRunSkipIfDimensionsMatch()
            || $this->shouldRunImportantPart()
            || $this->shouldRunMinKeywordLength()
            || $this->shouldRunContextLength()
            || $this->shouldRunDefaultImageDensities()
            || $this->shouldRunRememberMe();
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();
        $this->resultMessages = [];

        if ($this->shouldRunMediaelement()) {
            $this->runMediaelement();
        }

        if ($this->shouldRunSkipIfDimensionsMatch()) {
            $this->runSkipIfDimensionsMatch();
        }

        if ($this->shouldRunImportantPart()) {
            $this->runImportantPart();
        }

        if ($this->shouldRunMinKeywordLength()) {
            $this->runMinKeywordLength();
        }

        if ($this->shouldRunContextLength()) {
            $this->runContextLength();
        }

        if ($this->shouldRunDefaultImageDensities()) {
            $this->runDefaultImageDensities();
        }

        if ($this->shouldRunRememberMe()) {
            $this->runRememberMe();
        }

        return $this->createResult(true, $this->resultMessages ? implode("\n", $this->resultMessages) : null);
    }

    public function shouldRunMediaelement(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        if (!isset($columns['jquery'], $columns['scripts'])) {
            return false;
        }

        if (
            !$this->connection->fetchOne("
                SELECT EXISTS(
                    SELECT id
                    FROM tl_layout
                    WHERE
                        jquery LIKE '%\"j_mediaelement\"%'
                        OR scripts LIKE '%\"js_mediaelement\"%'
                )
            ")
        ) {
            // Early return without initializing the framework
            return false;
        }

        $this->framework->initialize();

        $controller = $this->framework->getAdapter(Controller::class);

        foreach (['jquery' => 'j_mediaelement', 'scripts' => 'js_mediaelement'] as $column => $templateName) {
            if (\array_key_exists($templateName, $controller->getTemplateGroup(explode('_', $templateName)[0].'_'))) {
                // Do not delete scripts that still exist
                continue;
            }

            if (
                $this->connection->fetchOne("
                    SELECT EXISTS(
                        SELECT id
                        FROM tl_layout
                        WHERE
                            $column LIKE '%\"$templateName\"%'
                    )
                ")
            ) {
                return true;
            }
        }

        return false;
    }

    public function runMediaelement(): void
    {
        $this->framework->initialize();

        $controller = $this->framework->getAdapter(Controller::class);
        $jTemplateExists = \array_key_exists('j_mediaelement', $controller->getTemplateGroup('j_'));
        $jsTemplateExists = \array_key_exists('js_mediaelement', $controller->getTemplateGroup('js_'));

        $rows = $this->connection->fetchAllAssociative('
            SELECT
                id, jquery, scripts
            FROM
                tl_layout
        ');

        // Remove the "j_mediaelement" and "js_mediaelement" templates
        foreach ($rows as $row) {
            if ($row['jquery'] && !$jTemplateExists) {
                $jquery = StringUtil::deserialize($row['jquery']);

                if (\is_array($jquery) && false !== ($i = array_search('j_mediaelement', $jquery, true))) {
                    unset($jquery[$i]);

                    $this->connection->executeStatement(
                        'UPDATE tl_layout SET jquery = :jquery WHERE id = :id',
                        ['jquery' => serialize(array_values($jquery)), 'id' => $row['id']]
                    );
                }
            }

            if ($row['scripts'] && !$jsTemplateExists) {
                $scripts = StringUtil::deserialize($row['scripts']);

                if (\is_array($scripts) && false !== ($i = array_search('js_mediaelement', $scripts, true))) {
                    unset($scripts[$i]);

                    $this->connection->executeStatement(
                        'UPDATE tl_layout SET scripts = :scripts WHERE id = :id',
                        ['scripts' => serialize(array_values($scripts)), 'id' => $row['id']]
                    );
                }
            }
        }
    }

    public function shouldRunSkipIfDimensionsMatch(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_image_size'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_image_size');

        return !isset($columns['skipifdimensionsmatch']);
    }

    public function runSkipIfDimensionsMatch(): void
    {
        $this->connection->executeStatement("
            ALTER TABLE
                tl_image_size
            ADD
                skipIfDimensionsMatch char(1) NOT NULL default ''
        ");

        // Enable the "skipIfDimensionsMatch" option for existing image sizes (backwards compatibility)
        $this->connection->executeStatement("
            UPDATE
                tl_image_size
            SET
                skipIfDimensionsMatch = '1'
        ");
    }

    public function shouldRunImportantPart(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_files'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_files');

        if (
            !isset(
                $columns['path'],
                $columns['importantpartx'],
                $columns['importantparty'],
                $columns['importantpartwidth'],
                $columns['importantpartheight']
            )
        ) {
            return false;
        }

        if ($columns['importantpartx']->getType() instanceof IntegerType) {
            return true;
        }

        return (bool) $this->connection->fetchOne('
            SELECT EXISTS(
                SELECT id
                FROM tl_files
                WHERE
                    importantPartX > 1.00001
                    OR importantPartY > 1.00001
                    OR importantPartWidth > 1.00001
                    OR importantPartHeight > 1.00001
            )
        ');
    }

    public function runImportantPart(): void
    {
        $compareValue = 1;

        // If the columns are of type integer, we can safely convert all images even if they are only set to 1
        if ($this->connection->createSchemaManager()->listTableColumns('tl_files')['importantpartx']->getType() instanceof IntegerType) {
            $compareValue = 0;
        }

        $this->connection->executeStatement('
            ALTER TABLE
                tl_files
            CHANGE
                importantPartX importantPartX DOUBLE PRECISION UNSIGNED DEFAULT 0 NOT NULL,
            CHANGE
                importantPartY importantPartY DOUBLE PRECISION UNSIGNED DEFAULT 0 NOT NULL,
            CHANGE
                importantPartWidth importantPartWidth DOUBLE PRECISION UNSIGNED DEFAULT 0 NOT NULL,
            CHANGE
                importantPartHeight importantPartHeight DOUBLE PRECISION UNSIGNED DEFAULT 0 NOT NULL
        ');

        $files = $this->connection->fetchAllAssociative("
            SELECT
                id, path, importantPartX, importantPartY, importantPartWidth, importantPartHeight
            FROM
                tl_files
            WHERE
                importantPartWidth > $compareValue OR importantPartHeight > $compareValue
        ");

        // Convert the important part to relative values as fractions
        foreach ($files as $file) {
            $path = Path::join($this->projectDir, $file['path']);

            if (!$this->filesystem->exists($path) || is_dir($path)) {
                $imageSize = [];
            } else {
                $imageSize = (new File($file['path']))->imageViewSize;
            }

            $updateData = ['id' => $file['id']];

            if (empty($imageSize[0]) || empty($imageSize[1])) {
                if (
                    (float) $file['importantPartX'] + (float) $file['importantPartWidth'] <= 1.00001
                    && (float) $file['importantPartY'] + (float) $file['importantPartHeight'] <= 1.00001
                ) {
                    continue;
                }

                $updateData['x'] = 0;
                $updateData['y'] = 0;
                $updateData['width'] = 0;
                $updateData['height'] = 0;

                $this->resultMessages[] = sprintf(
                    'Deleted invalid important part [%s,%s,%s,%s] from image "%s".',
                    $file['importantPartX'],
                    $file['importantPartY'],
                    $file['importantPartWidth'],
                    $file['importantPartHeight'],
                    $file['path']
                );
            } else {
                $updateData['x'] = min(1, $file['importantPartX'] / $imageSize[0]);
                $updateData['y'] = min(1, $file['importantPartY'] / $imageSize[1]);
                $updateData['width'] = min(1 - $updateData['x'], $file['importantPartWidth'] / $imageSize[0]);
                $updateData['height'] = min(1 - $updateData['y'], $file['importantPartHeight'] / $imageSize[1]);
            }

            $this->connection->executeStatement(
                '
                    UPDATE
                        tl_files
                    SET
                        importantPartX = :x,
                        importantPartY = :y,
                        importantPartWidth = :width,
                        importantPartHeight = :height
                    WHERE
                        id = :id
                ',
                $updateData
            );
        }

        // If there are still invalid values left, reset them
        $this->connection->executeStatement('
            UPDATE
                tl_files
            SET
                importantPartX = 0,
                importantPartY = 0,
                importantPartWidth = 0,
                importantPartHeight = 0
            WHERE
                importantPartX > 1.00001
                OR importantPartY > 1.00001
                OR importantPartWidth > 1.00001
                OR importantPartHeight > 1.00001
        ');
    }

    public function shouldRunMinKeywordLength(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        return !isset($columns['minkeywordlength']);
    }

    public function runMinKeywordLength(): void
    {
        $this->connection->executeStatement('
            ALTER TABLE
                tl_module
            ADD
                minKeywordLength smallint(5) unsigned NOT NULL default 4 AFTER contextLength
        ');

        // Disable the minimum keyword length for existing modules (backwards compatibility)
        $this->connection->executeStatement("
            UPDATE
                tl_module
            SET
                minKeywordLength = 0
            WHERE
                type = 'search'
        ");
    }

    public function shouldRunContextLength(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        return isset($columns['contextlength'], $columns['totallength']);
    }

    public function runContextLength(): void
    {
        $this->connection->executeStatement("
            ALTER TABLE
                tl_module
            CHANGE
                contextLength contextLength varchar(64) NOT NULL default ''
        ");

        $rows = $this->connection->fetchAllAssociative("
            SELECT
                id, contextLength, totalLength
            FROM
                tl_module
            WHERE
                type = 'search'
        ");

        // Consolidate the search context fields
        foreach ($rows as $row) {
            if (!empty($row['contextLength']) && !is_numeric($row['contextLength'])) {
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE tl_module SET contextLength = :context WHERE id = :id',
                [
                    'id' => $row['id'],
                    'context' => serialize([$row['contextLength'], $row['totalLength']]),
                ]
            );
        }

        $this->connection->executeStatement('ALTER TABLE tl_module DROP COLUMN totalLength');
    }

    public function shouldRunDefaultImageDensities(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout', 'tl_theme'])) {
            return false;
        }

        $columnsLayout = $schemaManager->listTableColumns('tl_layout');
        $columnsTheme = $schemaManager->listTableColumns('tl_theme');

        return !isset($columnsLayout['defaultimagedensities']) && isset($columnsTheme['defaultimagedensities']);
    }

    public function runDefaultImageDensities(): void
    {
        $this->connection->executeStatement("
            ALTER TABLE
                tl_layout
            ADD
                defaultImageDensities varchar(255) NOT NULL default ''
        ");

        // Move the default image densities to the page layout
        $this->connection->executeStatement('
            UPDATE
                tl_layout l
            SET
                defaultImageDensities = (SELECT defaultImageDensities FROM tl_theme t WHERE t.id = l.pid)
        ');
    }

    public function shouldRunRememberMe(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_remember_me'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_remember_me');

        return !isset($columns['id']);
    }

    public function runRememberMe(): void
    {
        // Since rememberme is broken in Contao 4.7 and there are no valid
        // cookies out there, we can simply drop the old table here and let the
        // install tool create the new one
        if ($this->connection->createSchemaManager()->tablesExist(['tl_remember_me'])) {
            $this->connection->executeStatement('DROP TABLE tl_remember_me');
        }
    }
}
