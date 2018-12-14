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
        $model->validUntil = strtotime('+24 hours');
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
}
