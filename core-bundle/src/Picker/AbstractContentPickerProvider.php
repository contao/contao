<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractContentPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security, Connection $connection)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'content' === $context && $this->security->isGranted('contao_user.modules', $this->getBackendModule());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        if (!is_numeric($config->getValue())) {
            return false;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('ptable')
            ->from('tl_content')
            ->where($qb->expr()->eq('id', $config->getValue()))
        ;

        if ($qb->execute()->fetchColumn() === $this->getParentTable()) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_content';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        $value = $config->getValue();
        if ($fieldType = $config->getExtra('fieldType')) {
            $attributes['fieldType'] = $fieldType;
        }

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($value) {
            $intval = static function ($val) {
                return (int) $val;
            };

            $attributes['value'] = array_map($intval, explode(',', $value));
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => $this->getBackendModule()];

        if (null !== $config) {
            $qb = $this->connection->createQueryBuilder();
            $qb->select('pid, ptable')->from('tl_content')->where($qb->expr()->eq('id', $config->getValue()));
            $data = $qb->execute()->fetch();

            if ($data['ptable'] === $this->getParentTable()) {
                $params['table'] = 'tl_content';
                $params['id'] = $data['pid'];
            }
        }

        return $params;
    }

    abstract protected function getBackendModule(): string;

    abstract protected function getParentTable(): string;
}
