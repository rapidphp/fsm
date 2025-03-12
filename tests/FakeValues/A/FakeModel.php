<?php

namespace Rapid\Fsm\Tests\FakeValues\A;

use Illuminate\Database\Eloquent\Model;

class FakeModel extends Model
{
    protected $fillable = [
        'current_state',
    ];

    public function update(array $attributes = [], array $options = [])
    {
        $this->fill($attributes);
    }
}