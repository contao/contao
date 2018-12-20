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
        $optIn->removeOn = strtotime('+7 days'); // FIXME: set to +3 years if we need to keep the log entry
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

                if ($model->findByPk($id)) {
                    $delete = false;
                    break;
                }
            }

            // Delete the token if there are no more related records, otherwise prolong for another 3 years
            if ($delete) {
                $token->delete();
            } else {
                $token->removeOn = strtotime('+3 years', (int) $token->removeOn);
                $token->save();
            }
        }
    }
}
