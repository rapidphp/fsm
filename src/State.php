<?php

namespace Rapid\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @template T of Model
 */
class State
{
    public ?Context $parent = null;

    public function setParent(?Context $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @var T
     */
    public Model $record;

    public function setRecord(Model $record): void
    {
        $this->record = $record;
    }

    /**
     * @param array $attributes
     * @return T
     */
    public function createRecord(array $attributes): Model
    {
        if (isset($this->parent->record)) {
            $attributes['parent_type'] = $this->parent->record->getMorphClass();
            $attributes['parent_id'] = $this->parent->record->getKey();
        }

        $this->setRecord(
            $record = static::model()::create($attributes),
        );

        if (isset($this->parent->record)) {
            $record->setRelation('parent', $this->parent->record);
        }

        return $record;
    }

    public function deleteRecord(): ?bool
    {
        if (isset($this->record)) {
            $ok = $this->record->delete();
            unset($this->record);

            return $ok;
        }

        return null;
    }

    /**
     * @return ?T
     */
    public function loadRecord(): ?Model
    {
        if (static::model() === null || !isset($this->parent->record)) {
            return null;
        }

        $record = $this->parent->record->morphOne(static::model(), 'parent')->latest('id')->first();

        if ($record === null) {
            unset($this->record);
            return null;
        }

        $this->setRecord($record);

        return $record;
    }


    protected static string $model;
    protected static string $modelKey;

    /**
     * @return null|class-string<T>|class-string<Model>
     */
    public static function model(): ?string
    {
        return static::$model ?? null;
    }

    public static function modelKey(): string
    {
        if (isset(static::$modelKey)) {
            return static::$modelKey;
        }

        if ($model = static::model()) {
            return (new $model)->getKeyName();
        }

        return 'id';
    }


    public static function bootOnContext(string $context): void
    {
    }

    public function onEnter(): void
    {
    }

    public function onLeave(): void
    {
    }

    public function onLoad(): void
    {
    }

    public function onReload(): void
    {
    }

    public static function suffixUri(): string
    {
        return Str::kebab(class_basename(static::class));
    }
}
