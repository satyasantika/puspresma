<?php

arch('app/Enums hanya berisi enum')
    ->expect('App\Enums')
    ->toBeEnums();

arch('Actions tidak memakai facade Request')
    ->expect('App\Actions')
    ->not->toUse(['Illuminate\Support\Facades\Request', 'Request']);

arch('tidak ada dd, dump, atau ray di app')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'dump_server'])
    ->not->toBeUsed();
