<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener('<?= $event ?>')]
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
