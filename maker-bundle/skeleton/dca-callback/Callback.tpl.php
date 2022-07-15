<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>

#[AsCallback(table: '<?= $table ?>', target: '<?= $target ?>')]
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
