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
     * @internal
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function create(string $prefix, string $email, array $related): OptInTokenInterface
    {
        if ($prefix) {
            $prefix = rtrim($prefix, '-');
        }

        if (\strlen($prefix) > 6) {
            throw new \InvalidArgumentException('The token prefix must not be longer than 6 characters');
        }

        $token = bin2hex(random_bytes(12));

        if ($prefix) {
            $token = $prefix.'-'.substr($token, \strlen($prefix) + 1);
        }

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

    public function find(string $identifier): OptInTokenInterface|null
    {
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$model = $adapter->findByToken($identifier)) {
            return null;
        }

        return new OptInToken($model, $this->framework);
    }

    public function purgeTokens(): void
    {
        $adapter = $this->framework->getAdapter(OptInModel::class);

        if (!$tokens = $adapter->findExpiredTokens()) {
            return;
        }

        $adapter = $this->framework->getAdapter(Model::class);

        foreach ($tokens as $token) {
            $delete = true;

            // If the token has been confirmed, check if the related records still exist
            if ($token->confirmedOn) {
                $related = $token->getRelatedRecords();

                foreach ($related as $table => $id) {
                    /** @var class-string<Model> $class */
                    $class = $adapter->getClassFromTable($table);

                    /** @var Model $model */
                    $model = $this->framework->getAdapter($class);

                    if (null !== $model->findMultipleByIds($id)) {
                        $delete = false;
                        break;
                    }
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
