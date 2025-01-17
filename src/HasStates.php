<?php

namespace Spatie\State;

use Illuminate\Database\Eloquent\Model;
use TypeError;

trait HasStates
{
    public static function bootHasStates(): void
    {
        /** @var \Spatie\State\State $expectedStateClass */
        $serialiseState = function (string $field, string $expectedStateClass) {
            return function (Model $model) use ($field, $expectedStateClass) {
                $value = $model->getAttribute($field);

                if ($value === null) {
                    return;
                }

                $stateClass = $expectedStateClass::resolveStateClass($value);

                if (! is_subclass_of($stateClass, State::class)) {
                    throw new TypeError("State field `{$field}` value must extend from `" . State::class . "`, instead got `{$stateClass}`");
                }

                if (! is_subclass_of($stateClass, $expectedStateClass)) {
                    throw new TypeError("State field `{$field}` expects state to be of type `{$expectedStateClass}`, instead got `{$stateClass}`");
                }

                $model->setAttribute(
                    $field,
                    State::resolveStateName($value)
                );
            };
        };

        /** @var \Spatie\State\State $expectedStateClass */
        $unserialiseState = function (string $field, string $expectedStateClass) {
            return function (Model $model) use ($field, $expectedStateClass) {
                $stateClass = $expectedStateClass::resolveStateClass($model->getAttribute($field));

                $model->setAttribute(
                    $field,
                    new $stateClass($model)
                );
            };
        };

        foreach (self::resolveStateFields() as $field => $expectedStateClass) {
            static::retrieved($unserialiseState($field, $expectedStateClass));
            static::created($unserialiseState($field, $expectedStateClass));
            static::saved($unserialiseState($field, $expectedStateClass));

            static::updating($serialiseState($field, $expectedStateClass));
            static::creating($serialiseState($field, $expectedStateClass));
            static::saving($serialiseState($field, $expectedStateClass));
        }
    }

    private static function resolveStateFields(): array
    {
        return (new static)->states ?? [];
    }
}
