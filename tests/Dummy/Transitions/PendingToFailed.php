<?php

namespace Spatie\State\Tests\Dummy\Transitions;

use Spatie\State\Tests\Dummy\Payment;
use Spatie\State\Tests\Dummy\States\Failed;
use Spatie\State\Tests\Dummy\States\Pending;
use Spatie\State\Transition;

class PendingToFailed extends Transition
{
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function __invoke(Payment $payment): Payment
    {
        $this->ensureInitialState($payment, Pending::class);

        $payment->state = new Failed($payment);
        $payment->failed_at = time();
        $payment->error_message = $this->message;

        $payment->save();

        return $payment;
    }
}
