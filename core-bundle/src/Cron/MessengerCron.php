<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;

#[AsCronJob('minutely')]
class MessengerCron extends AbstractConsoleCron
{
    public function __construct(string $consolePath, private int $numberOfWorkers = 0)
    {
        parent::__construct($consolePath);
    }

    public function __invoke(string $scope): void
    {
        if (Cron::SCOPE_WEB === $scope || $this->numberOfWorkers < 1) {
            return;
        }

        $processes = [];

        for ($i = 0; $i < $this->numberOfWorkers; ++$i) {
            $process = $this->createProcess(
                'messenger:consume',
                '--time-limit=60', // Minutely cronjob running for one minute max
                'contao_prio_high',
                'contao_prio_normal',
                'contao_prio_low'
            );
            $process->setTimeout(65);

            // Start the job asynchronously
            $process->start();
            $processes[] = $process;
        }

        // Now we need to sleep to keep the parent process open. Otherwise, this script will end and thus kill
        // our child processes.
        // All jobs run for 60 seconds, so we don't need to check every second yet
        sleep(55);

        // Now we check every second if all processes are done
        while (true) {
            $allDone = true;

            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $allDone = false;
                    break;
                }
            }

            if ($allDone) {
                break;
            }

            sleep(1);
        }
    }
}
