<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Model\Collection;
use Contao\UserModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists Contao back end users.
 *
 * @internal
 */
class UserListCommand extends Command
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:user:list')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The header fields to display in the table')
            ->addOption('admins', null, InputOption::VALUE_NONE, 'Return only amdins')
            ->setDescription('Lists Contao back end users.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('admins')) {
            $io->note('Only showing admin accounts');
        }

        $users = $this->getUsers($input->getOption('admins'));

        if (null === $users) {
            $io->error('No accounts found.');

            return 1;
        }

        $fields = $input->getOption('fields');

        if ([] === $fields) {
            $fields = ['username', 'name', 'admin', 'dateAdded'];
        }

        $rows = [];

        foreach ($users->fetchAll() as $user) {
            $rows[] = array_map(
                static function (string $field) use ($user) {
                    if (\in_array($field, ['tstamp', 'dateAdded'], true)) {
                        return $user[$field] ? date('Y-m-d H:i:s', (int) $user[$field]) : '';
                    }

                    if ('admin' === $field) {
                        return $user[$field] ? '✔' : '';
                    }

                    return $user[$field] ?? '';
                },
                $fields
            );
        }

        $io->table($fields, $rows);

        return 0;
    }

    private function getUsers(bool $onlyAdmins = false): ?Collection
    {
        $this->framework->initialize();

        /** @var UserModel $userModel */
        $userModel = $this->framework->getAdapter(UserModel::class);

        if ($onlyAdmins) {
            return $userModel->findBy('admin', '1');
        }

        return $userModel->findAll();
    }
}
