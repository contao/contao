<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

<?php if (PHP_VERSION_ID >= 80000): ?>
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
<?php else: ?>
use Contao\CoreBundle\ServiceAnnotation\Callback;
<?php endif; ?>
<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>

<?php if (PHP_VERSION_ID >= 80000): ?>
#[AsCallback("<?= $table ?>", "<?= $target ?>")]
<?php else: ?>
/**
 * @Callback(table="<?= $table ?>", target="<?= $target ?>")
 */
<?php endif; ?>
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
