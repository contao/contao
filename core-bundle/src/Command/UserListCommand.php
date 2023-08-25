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
use Contao\Model\Collection;
use Contao\UserModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:user:list',
    description: 'Lists Contao back end users.',
)]
class UserListCommand extends Command
{
    public function __construct(private readonly ContaoFramework $framework)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('column', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The columns display in the table')
            ->addOption('admins', null, InputOption::VALUE_NONE, 'Return only admins')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, json)', 'txt')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('admins') && 'json' !== $input->getOption('format')) {
            $io->note('Only showing admin accounts');
        }

        $users = $this->getUsers($input->getOption('admins'));
        $columns = $input->getOption('column');

        switch ($input->getOption('format')) {
            case 'txt':
                if (!$users || 0 === $users->count()) {
                    $io->note('No accounts found.');

                    return Command::SUCCESS;
                }

                $rows = $this->formatTableRows($users, $columns);

                $io->table($columns, $rows);
                break;

            case 'json':
                $data = $this->formatJson($users, $columns);

                $io->write(json_encode($data, JSON_THROW_ON_ERROR));
                break;

            default:
                throw new \LogicException('Invalid format: '.$input->getOption('format'));
        }

        return Command::SUCCESS;
    }

    /**
     * @return Collection<UserModel>|null
     */
    private function getUsers(bool $onlyAdmins = false): Collection|null
    {
        $this->framework->initialize();

        $userModel = $this->framework->getAdapter(UserModel::class);

        if ($onlyAdmins) {
            return $userModel->findBy('admin', true);
        }

        return $userModel->findAll();
    }

    /**
     * @param Collection<UserModel> $users
     */
    private function formatTableRows(Collection $users, array &$columns): array
    {
        if (!$columns) {
            $columns = ['username', 'name', 'admin', 'dateAdded', 'lastLogin'];
        }

        $rows = [];

        foreach ($users as $user) {
            $rows[] = array_map(
                static function (string $field) use ($user) {
                    $check = '\\' === \DIRECTORY_SEPARATOR ? '1' : "\xE2\x9C\x94";

                    if (\in_array($field, ['tstamp', 'dateAdded', 'lastLogin'], true)) {
                        return $user->{$field} ? date('Y-m-d H:i:s', (int) $user->{$field}) : '';
                    }

                    if (\in_array($field, ['admin', 'pwChange', 'disable', 'useTwoFactor', 'locked'], true)) {
                        return $user->{$field} ? $check : '';
                    }

                    return $user->{$field} ?? '';
                },
                $columns,
            );
        }

        return $rows;
    }

    /**
     * @param Collection<UserModel>|null $users
     */
    private function formatJson(Collection|null $users, array $columns): array
    {
        if (!$users) {
            return [];
        }

        if (!$columns) {
            $columns = ['username', 'name', 'admin', 'dateAdded', 'lastLogin'];
        }

        $data = [];

        foreach ($users->fetchAll() as $user) {
            $data[] = array_filter(
                $user,
                static fn ($key) => \in_array($key, $columns, true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        return $data;
    }
}
