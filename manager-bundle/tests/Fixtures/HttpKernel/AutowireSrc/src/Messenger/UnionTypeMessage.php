<?php

declare(strict_types=1);

namespace App\Messenger;

class UnionTypeMessage
{
    public function __construct(int|string $id)
    {
    }
}
