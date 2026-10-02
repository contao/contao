<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

use Contao\CoreBundle\DataContainer\VirtualFieldsHandler;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model;
use Contao\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class ContaoUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly UserFactoryInterface $userFactory,
        private readonly VirtualFieldsHandler $virtualFieldsHandler,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $this->framework->initialize();

        $data = $this->getUserData('username', $identifier);

        if (null !== $data) {
            return $this->userFactory->create($data);
        }

        throw new UserNotFoundException(\sprintf('Could not find user "%s"', $identifier));
    }

    public function loadUserById(int $id): User
    {
        $data = $this->getUserData('id', $id);

        if (null !== $data) {
            return $this->userFactory->create($data);
        }

        throw new UserNotFoundException(\sprintf('Could not find user "%s"', $id));
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(\sprintf('Unsupported class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * @param class-string<User> $class
     */
    public function supportsClass(string $class): bool
    {
        return $this->userFactory->supportsClass($class);
    }

    /**
     * @param User $user
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(\sprintf('Unsupported class "%s".', $user::class));
        }

        $this->connection->update(
            $this->userFactory->getTable(),
            ['password' => $newHashedPassword],
            ['id' => $user->id],
        );
    }

    public function getUserData(string $field, int|string $value): array|null
    {
        $table = $this->userFactory->getTable();
        $data = $this->connection->fetchAssociative("SELECT * FROM $table WHERE $field=?", [$value]);

        if (false === $data) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = Model::getClassFromTable($table);

        if (class_exists($modelClass)) {
            foreach ($data as $k => $v) {
                $data[$k] = $modelClass::convertToPhpValue($k, $v);
            }
        }

        return $this->virtualFieldsHandler->expandFields($data, $table);
    }
}
