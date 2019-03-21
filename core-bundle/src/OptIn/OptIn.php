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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model;
use Contao\OptInModel;

class OptIn implements OptInInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $prefix, string $email, array $related): OptInTokenInterface
    {
        if (\strlen($prefix) > 6) {
            throw new \InvalidArgumentException('The token prefix must not be longer than 6 characters');
        }

        $token = bin2hex(random_bytes(12));

        if ($prefix) {
            $token = $prefix.substr($token, \strlen($prefix));
        }

        /** @var OptInModel $optIn */
        $optIn = $this->framework->createInstance(OptInModel::class);
        $optIn->tstamp = time();
        $optIn->token = $token;
        $optIn->createdOn = time();

        // The token is required to remove unconfirmed subscriptions after 24 hours, so
        // keep it for 3 days to make sure it is not purged before the subscription
        $optIn->removeOn = strtotime('+3 days');
        $optIn->email = $email;
        $optIn->save();

        $optIn->setRelatedRecords($related);

        return new OptInToken($optIn, $this->framework);
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
        /** @var OptInModel $adapter */
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$tokens = $adapter->findExpiredTokens()) {
            return;
        }

        /** @var Model $adapter */
        $adapter = $this->framework->getAdapter(Model::class);

        foreach ($tokens as $token) {
            $delete = true;
            $related = $token->getRelatedRecords();

            foreach ($related as $table => $id) {
                /** @var Model $model */
                $model = $this->framework->getAdapter($adapter->getClassFromTable($table));

                // Check if the related records still exist
                if (null !== $model->findMultipleByIds($id)) {
                    $delete = false;
                    break;
                }
            }

            if ($delete) {
                $token->delete();
            } else {
                // Prolong the token for another 3 years if the related records still exist
                $token->removeOn = strtotime('+3 years', (int) $token->removeOn);
                $token->save();
            }
        }
    }
}
