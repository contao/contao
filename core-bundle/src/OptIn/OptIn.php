<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\OptIn;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Email;
use Contao\Model;
use Contao\OptInModel;

class OptIn implements OptInInterface
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $prefix, string $table, int $id, string $email, string $subject, string $text): string
    {
        $token = $prefix.bin2hex(random_bytes(16));

        /** @var OptInModel $model */
        $model = $this->framework->createInstance(OptInModel::class);
        $model->token = $token;
        $model->createdOn = time();
        $model->relatedTable = $table;
        $model->relatedId = $id;
        $model->email = $email;
        $model->emailSubject = $subject;
        $model->emailText = $text;
        $model->save();

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function confirm(string $token): void
    {
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$model = $adapter->findByToken($token)) {
            throw new \InvalidArgumentException(sprintf('Invalid token: %s', $token));
        }

        if ($model->confirmedOn > 0) {
            throw new \LogicException(sprintf('The token "%s" has already been confirmed', $token));
        }

        $model->confirmedOn = time();
        $model->save();
    }

    /**
     * {@inheritdoc}
     */
    public function sendMail(string $token): void
    {
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$model = $adapter->findByToken($token)) {
            throw new \InvalidArgumentException(sprintf('Invalid token: %s', $token));
        }

        if ($model->confirmedOn > 0) {
            throw new \LogicException(sprintf('The token "%s" has already been confirmed', $token));
        }

        /** @var Email $email */
        $email = $this->framework->createInstance(Email::class);
        $email->subject = $model->emailSubject;
        $email->text = $model->emailText;
        $email->sendTo($model->email);
    }

    /**
     * {@inheritdoc}
     */
    public function flagForRemoval(string $token, int $removeOn): void
    {
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$model = $adapter->findByToken($token)) {
            throw new \InvalidArgumentException(sprintf('Invalid token: %s', $token));
        }

        if (!$model->confirmedOn) {
            throw new \LogicException(sprintf('The token "%s" has not been confirmed yet', $token));
        }

        $model->removeOn = $removeOn;
        $model->save();
    }

    /**
     * {@inheritdoc}
     */
    public function purgeTokens(): void
    {
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$models = $adapter->findExpiredTokens()) {
            return;
        }

        foreach ($models as $model) {
            if (!$model->confirmedOn) {
                $this->deleteRelatedRecord($model);
            }

            $model->delete();
        }
    }

    private function deleteRelatedRecord(OptInModel $model): void
    {
        /** @var Model $adapter */
        $adapter = $this->framework->getAdapter(Model::class);
        $class = $adapter->getClassFromTable($model->relatedTable);

        /** @var Model $classAdapter */
        $classAdapter = $this->framework->getAdapter($class);

        if (!$record = $classAdapter->findByPk($model->relatedId)) {
            return;
        }

        $record->delete();
    }
}
