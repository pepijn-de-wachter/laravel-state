<?php

namespace Spatie\State\Tests\Dummy\States;

class Canceled extends PaymentState
{
    public static $name = 'canceled';

    public function color(): string
    {
        return 'gray';
    }
}
