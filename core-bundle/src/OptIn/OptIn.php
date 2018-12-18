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
use Contao\Model\Collection;
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
    public function create(string $prefix, string $email, string $table, int $id): OptInTokenInterface
    {
        if (\strlen($prefix) > 6) {
            throw new \InvalidArgumentException('The token prefix must not be longer than 6 characters');
        }

        $token = bin2hex(random_bytes(12));

        if ($prefix) {
            $token = $prefix.substr($token, \strlen($prefix));
        }

        /** @var OptInModel $model */
        $model = $this->framework->createInstance(OptInModel::class);
        $model->tstamp = time();
        $model->token = $token;
        $model->createdOn = time();
        $model->email = $email;
        $model->relatedTable = $table;
        $model->relatedId = $id;
        $model->save();

        return new OptInToken($model, $this->framework);
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?OptInTokenInterface
    {
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$model = $adapter->findByToken($identifier)) {
            return null;
        }

        return new OptInToken($model, $this->framework);
    }

    /**
     * {@inheritdoc}
     */
    public function purgeTokens(): void
    {
        // Remove expired tokens
        if ($tokens = OptInModel::findExpiredTokens()) {
            foreach ($tokens as $token) {
                $token->delete();
            }
        }

        // Flag confirmed tokens without related record for removal
        if ($tokens = OptInModel::findConfirmedTokensWithoutRelatedRecord()) {
            foreach ($tokens as $token) {
                $token->removeOn = strtotime('+3 years');
                $token->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWithRelatedRecord(Collection $models): void
    {
        foreach ($models as $model) {
            if ($tokens = OptInModel::findByEmailAndRelatedRecord($model->email, $model->getTable(), $model->id)) {
                foreach ($tokens as $token) {
                    $token->delete();
                }
            }

            $model->delete();
        }
    }
}
