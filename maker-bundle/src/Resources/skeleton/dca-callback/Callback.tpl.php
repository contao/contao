<?= "<?php\n"; ?>

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>

/**
 * @Callback(table="<?= $table; ?>", target="<?= $target; ?>")
 */
class <?= $className . "\n"; ?>
{
    <?= $signature . "\n"; ?>
    {
        // Do something …
    }
}
