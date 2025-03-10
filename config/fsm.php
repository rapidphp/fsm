<?php

return [

    /*
    |--------------------------------------------------------------------------
    |
    |--------------------------------------------------------------------------
    |
    |
    |
    */
    'debug' => env('FSM_DEBUG', env('APP_DEBUG', false)),

    'compare' => env('FSM_COMPARE', \Rapid\Fsm\FsmManager::INSTANCE_OF),

    'authorize' => [
        'status' => env('FSM_AUTHORIZE_STATUS', 403),
    ],

];
