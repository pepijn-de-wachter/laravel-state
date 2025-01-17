<?php

namespace Spatie\State\Tests;

use Spatie\State\Tests\Dummy\AutoDetectStates\AbstractState;
use Spatie\State\Tests\Dummy\AutoDetectStates\StateA;
use Spatie\State\Tests\Dummy\DummyModel;
use Spatie\State\Tests\Dummy\Payment;
use Spatie\State\Tests\Dummy\States\Created;
use Spatie\State\Tests\Dummy\States\Paid;
use Spatie\State\Tests\Dummy\States\PaidWithoutName;
use Spatie\State\Tests\Dummy\States\Pending;
use Spatie\State\Tests\Dummy\Transitions\CreatedToPending;
use Spatie\State\Tests\Dummy\WrongState;
use TypeError;

class StateTest extends TestCase
{
    public function state_with_name_is_saved_with_its_class_name()
    {
        $payment = Payment::create([
            'state' => PaidWithoutName::class,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => PaidWithoutName::getMorphClass(),
        ]);

        $this->assertInstanceOf(PaidWithoutName::class, $payment->state);
    }

    /** @test */
    public function state_is_properly_serialized()
    {
        $payment = Payment::create();

        $this->assertInstanceOf(Created::class, $payment->state);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => Created::getMorphClass(),
        ]);

        $payment->state = new Pending($payment);

        $payment->save();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => Pending::getMorphClass(),
        ]);
    }

    /** @test */
    public function create_with_state()
    {
        $payment = Payment::create([
            'state' => Paid::class,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => Paid::getMorphClass(),
        ]);

        $this->assertInstanceOf(Paid::class, $payment->state);
    }

    /** @test */
    public function transitions_can_be_performed()
    {
        $payment = Payment::create();

        $createdToPending = new CreatedToPending();

        $payment = $createdToPending($payment);

        $payment->refresh();

        $this->assertInstanceOf(Pending::class, $payment->state);
    }

    /** @test */
    public function only_states_of_the_correct_type_are_allowed_via_create()
    {
        $this->expectException(TypeError::class);

        Payment::create([
            'state' => new WrongState()
        ]);
    }

    /** @test */
    public function only_states_of_the_correct_type_are_allowed_via_setter()
    {
        $payment = Payment::create();

        $this->expectException(TypeError::class);

        $payment->state = new WrongState();

        $payment->save();
    }

    /** @test */
    public function state_from_concrete_class()
    {
        $payment = Payment::create();

        $payment->state = new Paid($payment);

        $payment->save();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => Paid::getMorphClass(),
        ]);
    }

    /** @test */
    public function state_from_class_name()
    {
        $payment = Payment::create([
            'state' => Paid::class,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => Paid::getMorphClass(),
        ]);
    }

    /** @test */
    public function state_with_morphed_class_name()
    {
        $payment = Payment::create([
            'state' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => 'paid',
        ]);
    }

    /** @test */
    public function state_with_morphed_class_name_from_class_name()
    {
        $payment = Payment::create([
            'state' => Paid::class,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => 'paid',
        ]);
    }

    /** @test */
    public function state_with_morphed_class_name_from_concrete_class()
    {
        $payment = new Payment();

        $payment->state = new Paid($payment);

        $payment->save();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'state' => 'paid',
        ]);
    }

    /** @test */
    public function is_one_of()
    {
        $payment = new Payment();

        $this->assertTrue($payment->state->isOneOf(
            Created::class
        ));

        $this->assertTrue($payment->state->isOneOf(
            new Created($payment),
        ));

        $this->assertTrue($payment->state->isOneOf(
            'created',
        ));

        $this->assertFalse($payment->state->isOneOf(
            Paid::class
        ));
    }

    /** @test */
    public function equals()
    {
        $createdA = new Created(new Payment());
        $createdB = new Created(new Payment());
        $paid = new Paid(new Payment());

        $this->assertTrue($createdA->equals($createdB));
        $this->assertTrue($createdA->equals(Created::class));
        $this->assertTrue($createdA->equals('created'));

        $this->assertFalse($createdA->equals($paid));
    }

    /** @test */
    public function resolve_state_without_explicit_mapping()
    {
        $state = AbstractState::find('a');

        $this->assertInstanceOf(StateA::class, $state);
    }
}
