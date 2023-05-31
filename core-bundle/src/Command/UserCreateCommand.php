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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\Locales;
use Contao\UserGroupModel;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsCommand(
    name: 'contao:user:create',
    description: 'Create a new Contao back end user.'
)]
class UserCreateCommand extends Command
{
    private array $locales;

    public function __construct(
        private ContaoFramework $framework,
        private Connection $connection,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        Locales $locales,
    ) {
        $this->locales = $locales->getEnabledLocaleIds();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'The username to create')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The full name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'The e-mail address')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The password')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'The user language (ISO 639-1 language code)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Give admin permissions to the new user')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The groups to assign the user to')
            ->addOption('change-password', null, InputOption::VALUE_NONE, 'Require user to change the password on the first back end login')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getOption('username')) {
            $username = $this->ask('Please enter the username: ', $input, $output);

            $input->setOption('username', $username);
        }

        if (null === $input->getOption('name')) {
            $name = $this->ask('Please enter the full name: ', $input, $output);

            $input->setOption('name', $name);
        }

        $emailCallback = static function ($value) {
            if (!Validator::isEmail($value)) {
                throw new \InvalidArgumentException('The e-mail address is invalid.');
            }

            return $value;
        };

        if (null === $input->getOption('email')) {
            $email = $this->ask('Please enter the e-mail address: ', $input, $output, $emailCallback);

            $input->setOption('email', $email);
        } else {
            $emailCallback($input->getOption('email'));
        }

        if (null === $input->getOption('language')) {
            $language = $this->askChoice('Please type the user language code: ', $this->locales, $input, $output);

            $input->setOption('language', $language);
        }

        $config = $this->framework->getAdapter(Config::class);
        $minLength = $config->get('minPasswordLength');
        $username = $input->getOption('username');

        $passwordCallback = static function ($value) use ($username, $minLength): string {
            if ('' === trim($value)) {
                throw new \RuntimeException('The password cannot be empty');
            }

            if (mb_strlen($value) < $minLength) {
                throw new \RuntimeException(sprintf('Please use at least %d characters.', $minLength));
            }

            if ($value === $username) {
                throw new \RuntimeException('Username and password must not be the same.');
            }

            return $value;
        };

        if (null === $input->getOption('password')) {
            $password = $this->askForPassword('Please enter the new password: ', $input, $output, $passwordCallback);

            $confirmCallback = static function (#[\SensitiveParameter] $value) use ($password): string {
                if ($password !== $value) {
                    throw new \RuntimeException('The passwords do not match.');
                }

                return $value;
            };

            $this->askForPassword('Please confirm the password: ', $input, $output, $confirmCallback);

            $input->setOption('password', $password);
        } else {
            $passwordCallback($input->getOption('password'));
        }

        if (false === $input->getOption('admin')) {
            $answer = $this->askChoice('Give user admin permissions?', ['no', 'yes'], $input, $output);

            $input->setOption('admin', 'yes' === $answer);
        }

        if (false === $input->getOption('admin') && ($options = $this->getGroups()) && 0 !== \count($options)) {
            $answer = $this->askMultipleChoice(
                'Assign which groups to the user (select multiple comma-separated)?',
                $options,
                $input,
                $output
            );

            $input->setOption('group', array_values(array_intersect_key(array_flip($options), array_flip($answer))));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (
            null === $input->getOption('username')
            || (null === $name = $input->getOption('name'))
            || (null === $email = $input->getOption('email'))
            || (null === $password = $input->getOption('password'))
        ) {
            $io->error('Please provide at least and each of: username, name, email, password');

            return Command::FAILURE;
        }

        $isAdmin = $input->getOption('admin');

        $this->persistUser(
            $username = $input->getOption('username'),
            $name,
            $email,
            $password,
            $input->getOption('language') ?? 'en',
            $isAdmin,
            $input->getOption('group'),
            $input->getOption('change-password')
        );

        $io->success(sprintf('User %s%s created.', $username, $isAdmin ? ' with admin permissions' : ''));

        return Command::SUCCESS;
    }

    private function ask(string $label, InputInterface $input, OutputInterface $output, callable|null $callback = null): string
    {
        $question = new Question($label);
        $question->setMaxAttempts(3);
        $question->setValidator($callback);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function askForPassword(string $label, InputInterface $input, OutputInterface $output, callable $callback): string
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setMaxAttempts(3);
        $question->setValidator($callback);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function askChoice(string $label, array $options, InputInterface $input, OutputInterface $output): string
    {
        $question = new ChoiceQuestion($label, $options);
        $question->setAutocompleterValues($options);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function askMultipleChoice(string $label, array $options, InputInterface $input, OutputInterface $output): array
    {
        $question = new ChoiceQuestion($label, $options);
        $question->setAutocompleterValues($options);
        $question->setMultiselect(true);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function getGroups(): array
    {
        $this->framework->initialize();

        $userGroupModel = $this->framework->getAdapter(UserGroupModel::class);
        $groups = $userGroupModel->findAll();

        if (null === $groups) {
            return [];
        }

        return $groups->fetchEach('name');
    }

    private function persistUser(string $username, string $name, string $email, string $password, string $language, bool $isAdmin = false, array|null $groups = null, bool $pwChange = false): void
    {
        $time = time();
        $hash = $this->passwordHasherFactory->getPasswordHasher(BackendUser::class)->hash($password);

        $data = [
            'tstamp' => $time,
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $hash,
            'language' => $language,
            'backendTheme' => 'flexible',
            'admin' => $isAdmin,
            'pwChange' => $pwChange,
            'dateAdded' => $time,
        ];

        if (!$isAdmin && !empty($groups)) {
            $data[$this->connection->quoteIdentifier('groups')] = serialize(array_map('strval', $groups));
        }

        $this->connection->insert('tl_user', $data, ['admin' => Types::BOOLEAN, 'pwChange' => Types::BOOLEAN]);
    }
}
