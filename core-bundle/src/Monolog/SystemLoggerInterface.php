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

interface SystemLoggerInterface
{
    public function access(string $message, string $method = null): void;

    public function configuration(string $message, string $method = null): void;

    public function cron(string $message, string $method = null): void;

    public function email(string $message, string $method = null): void;

    public function error(string $message, string $method = null): void;

    public function files(string $message, string $method = null): void;

    public function forms(string $message, string $method = null): void;

    public function general(string $message, string $method = null): void;

    public function info(string $message, string $method = null): void;

    public function log(string $action, string $message, string $method = null, string $level = null): void;
}
