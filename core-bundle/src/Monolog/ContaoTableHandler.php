<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Monolog;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class ContaoTableHandler extends AbstractProcessingHandler
{
    /**
     * @param \Closure(): Connection $connection
     */
    public function __construct(
        private readonly \Closure $connection,
        $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);
        $record->formatted = $this->getFormatter()->format($record);

        if (!isset($record->extra['contao']) || !$record->extra['contao'] instanceof ContaoContext) {
            return false;
        }

        try {
            $this->write($record);
        } catch (\Exception) {
            return false;
        }

        return !$this->bubble;
    }

    protected function write(LogRecord $record): void
    {
        /** @var ContaoContext $context */
        $context = $record->extra['contao'];

        ($this->connection)()->insert('tl_log', [
            'tstamp' => $record->datetime->format('U'),
            'text' => StringUtil::specialchars((string) $record->formatted),
            'source' => (string) $context->getSource(),
            'action' => (string) $context->getAction(),
            'username' => (string) $context->getUsername(),
            'func' => $context->getFunc(),
            'browser' => StringUtil::specialchars((string) $context->getBrowser()),
            'uri' => StringUtil::specialchars($context->getUri() ?? ''),
            'page' => $context->getPageId() ?? 0,
        ]);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter('%message%');
    }
}
