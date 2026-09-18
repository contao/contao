<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\Security;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;

class VirtualBackendUserFactory
{
    /**
     * @var array<string, mixed>|null
     */
    private array|null $cachedDefaults = null;

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function createForGroupId(int $groupId): BackendUser
    {
        return BackendUser::createFromData([
            ...$this->getDefaultUserDataFromDca(),
            'id' => 0,
            'username' => '__contao_backend_search_group_'.$groupId,
            'name' => '__contao_backend_search_group_'.$groupId,
            'admin' => false,
            'inherit' => 'group',
            'groups' => [$groupId],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultUserDataFromDca(): array
    {
        if (null !== $this->cachedDefaults) {
            return $this->cachedDefaults;
        }

        $this->framework->initialize();
        $this->framework->getAdapter(Controller::class)->loadDataContainer('tl_user');

        $defaults = [];

        foreach ($GLOBALS['TL_DCA']['tl_user']['fields'] ?? [] as $field => $config) {
            if (!\array_key_exists('default', $config)) {
                continue;
            }

            $value = $config['default'];

            if (\is_callable($value)) {
                $value = $value();
            }

            if (\is_array($value) || \is_object($value)) {
                $value = serialize($value);
            }

            $defaults[$field] = $value;
        }

        return $this->cachedDefaults = $defaults;
    }
}
