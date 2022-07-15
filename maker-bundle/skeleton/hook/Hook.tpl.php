<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>

#[AsHook("<?= $hook ?>")]
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
