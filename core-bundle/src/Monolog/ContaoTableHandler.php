<?php

namespace Contao\CoreBundle\Monolog;

use Contao\CoreBundle\EventListener\ScopeAwareTrait;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;

/**
 * ContaoLogHandler
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableHandler extends AbstractHandler
{
    use ScopeAwareTrait;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var callable
     */
    private $processor;

    /**
     * @var Statement
     */
    private $statement;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param Connection               $db
     * @param callable                 $processor
     * @param int                      $level
     * @param bool                     $bubble
     */
    public function __construct(
        ContaoFrameworkInterface $framework,
        Connection $db,
        callable $processor,
        $level = Logger::DEBUG,
        $bubble = false
    ) {
        parent::__construct($level, $bubble);

        $this->framework = $framework;
        $this->db        = $db;
        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $record)
    {
        if (!$this->canWriteToDb()) {
            return false;
        }

        try {
            /** @var \DateTime $date */
            $date     = $record['datetime'];
            $category = strtoupper(str_replace('contao_', '', $record['channel']));

            $record = call_user_func($this->processor, $record);

            $this->statement->execute(
                [
                    'tstamp'   => $date->format('U'),
                    'text'     => specialchars($record['message']),
                    'source'   => $this->isBackendScope() ? 'BE' : 'FE',
                    'action'   => $category,
                    'username' => (string) $record['extra']['username'],
                    'func'     => (string) $record['extra']['function'],
                    'ip'       => (string) $record['extra']['ip'],
                    'browser'  => (string) $record['extra']['browser'],
                ]
            );
        } catch (DBALException $e) {
            return false;
        }

        $this->executeHook($record, $category);

        return false === $this->bubble;
    }

    /**
     * @return bool
     */
    private function canWriteToDb()
    {
        if (null !== $this->statement) {
            return true;
        }

        try {
            $this->statement = $this->db->prepare('
                INSERT INTO tl_log (tstamp, source, action, username, text, func, ip, browser)
                VALUES (:tstamp, :source, :action, :username, :text, :func, :ip, :browser)
            ');
        } catch (DBALException $e) {
            // Ignore if table does not exist
            return false;
        }

        return true;
    }

    /**
     * @param array  $record
     * @param string $category
     */
    private function executeHook(array $record, $category)
    {
        // HOOK: allow to add custom loggers
        if (!$this->framework->isInitialized()
            || !isset($GLOBALS['TL_HOOKS']['addLogEntry'])
            || !is_array($GLOBALS['TL_HOOKS']['addLogEntry'])
        ) {
            return;
        }

        trigger_error(
            "\$GLOBALS['TL_HOOKS']['addLogEntry'] is deprecated in Contao 4.2 and will be removed in Contao 5.",
            E_USER_DEPRECATED
        );

        /** @var \Contao\System $system */
        $system = $this->framework->getAdapter('Contao\System');

        // Must create variable to allow modification in hook
        $text     = $record['message'];
        $function = $record['extra']['function'];

        foreach ($GLOBALS['TL_HOOKS']['addLogEntry'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}(
                $text,
                $function,
                $category
            );
        }
    }
}
