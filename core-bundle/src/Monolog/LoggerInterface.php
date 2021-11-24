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

interface LoggerInterface extends ContextAwareLoggerInterface
{
    /**
     * Log a message with LogLevel::INFO and ContaoContext::ACCESS.
     */
    public function logAccess(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::CONFIGURATION.
     */
    public function logConfiguration(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::CRON.
     */
    public function logCron(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::EMAIL.
     */
    public function logEmail(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::ERROR and ContaoContext::ERROR.
     */
    public function logError(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::FILES.
     */
    public function logFiles(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::FORMS.
     */
    public function logForms(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and ContaoContext::GENERAL.
     */
    public function logGeneral(string $message, string $method = null, string $username = null): void;

    /**
     * Log a message with LogLevel::INFO and the given action name.
     */
    public function logActionName(string $action, string $message, string $method = null, string $username = null): void;

    /**
     * Add a ContaoContext with ContaoContext::ACCESS.
     */
    public function asContaoAccess(): self;

    /**
     * Add a ContaoContext with ContaoContext::CONFIGURATION.
     */
    public function asContaoConfiguration(): self;

    /**
     * Add a ContaoContext with ContaoContext::EMAIL.
     */
    public function asContaoEmail(): self;

    /**
     * Add a ContaoContext with ContaoContext::ERROR.
     */
    public function asContaoError(): self;

    /**
     * Add a ContaoContext with ContaoContext::FILES.
     */
    public function asContaoFiles(): self;

    /**
     * Add a ContaoContext with ContaoContext::FORMS.
     */
    public function asContaoForms(): self;

    /**
     * Add a ContaoContext with ContaoContext::GENERAL.
     */
    public function asContaoGeneral(): self;

    /**
     * Add a ContaoContext to the logger.
     */
    public function withContaoContext(ContaoContext $context = null): self;

    /**
     * Add a ContaoContext with the given action to the logger.
     */
    public function withContaoAction(string $action): self;

    /**
     * Add a ContaoContext with the given func to the logger.
     */
    public function withContaoFunc(string $func): self;

    /**
     * Add a ContaoContext with the given username to the logger.
     */
    public function withContaoUsername(string $username): self;
}
