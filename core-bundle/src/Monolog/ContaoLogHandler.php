<?php

namespace Contao\CoreBundle\Monolog;

use Contao\CoreBundle\EventListener\ScopeAwareTrait;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * ContaoLogHandler
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoLogHandler extends AbstractProcessingHandler
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
     * @var Statement
     */
    private $statement;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param Connection               $db
     * @param int                      $level
     * @param bool                     $bubble
     */
    public function __construct(ContaoFrameworkInterface $framework, Connection $db, $level = Logger::DEBUG, $bubble = false)
    {
        parent::__construct($level, $bubble);

        $this->framework = $framework;
        $this->db        = $db;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->canWriteToDb()) {
            return;
        }

        try {
            /** @var \DateTime $date */
            $date = $record['datetime'];

            $this->statement->execute(
                [
                    'tstamp'   => (string) $date->format('U'),
                    'source'   => (string) $this->isBackendScope() ? 'BE' : 'FE',
                    'action'   => (string) $record['extra']['tl_log.action'],
                    'username' => (string) $record['extra']['tl_log.username'],
                    'text'     => (string) specialchars($record['message']),
                    'func'     => (string) $record['extra']['tl_log.func'],
                    'ip'       => (string) $record['extra']['tl_log.ip'],
                    'browser'  => (string) $record['extra']['tl_log.browser'],
                ]
            );
        } catch (DBALException $e) {
            // Fall back to PHP log if database is not available
            error_log(
                $record['formatted'],
                3,
                TL_ROOT . '/app/logs/tl_log.log'
            );
        }

        $this->executeHook($record);
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
     * @param array $record
     */
    private function executeHook(array $record)
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
        $text = $record['formatted'];
        $function = $record['extra'][ContaoLogProcessor::CONTEXT_FUNCTION];
        $category = $record['extra'][ContaoLogProcessor::CONTEXT_CATEGORY];

        foreach ($GLOBALS['TL_HOOKS']['addLogEntry'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}(
                $text,
                $function,
                $category
            );
        }
    }
}
