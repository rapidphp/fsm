<?php

namespace Rapid\Fsm\Tests\FakeValues\A;

use Illuminate\Database\Eloquent\Model;
use Rapid\Fsm\Traits\InteractsWithContext;

class FakeModel extends Model
{
    use InteractsWithContext;

    protected $fillable = [
        'current_state',
    ];

    protected function contextClass(): string
    {
        return FakeContext::class;
    }

    public function update(array $attributes = [], array $options = [])
    {
        $this->fill($attributes);
    }
}