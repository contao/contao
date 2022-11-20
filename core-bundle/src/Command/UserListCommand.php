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
    protected static $defaultName = 'contao:user:list';
    protected static $defaultDescription = 'Lists Contao back end users.';

    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

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
            case 'text':
                trigger_deprecation('contao/core-bundle', '4.13', 'Using --format=text is deprecated and will be removed in Contao 5. Use --format=txt instead.');
                // no break

            case 'txt':
                if (!$users || 0 === $users->count()) {
                    $io->note('No accounts found.');

                    return 0;
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

        return 0;
    }

    private function getUsers(bool $onlyAdmins = false): ?Collection
    {
        $this->framework->initialize();

        $userModel = $this->framework->getAdapter(UserModel::class);

        if ($onlyAdmins) {
            return $userModel->findBy('admin', '1');
        }

        return $userModel->findAll();
    }

    private function formatTableRows(Collection $users, array &$columns): array
    {
        if ([] === $columns) {
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
                $columns
            );
        }

        return $rows;
    }

    private function formatJson(?Collection $users, array $columns): array
    {
        if (!$users) {
            return [];
        }

        if ([] === $columns) {
            return $users->fetchAll();
        }

        $data = [];

        foreach ($users->fetchAll() as $user) {
            $data[] = array_filter(
                $user,
                static fn ($key) => \in_array($key, $columns, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $data;
    }
}
