<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('it stays decoupled from the consuming application')
    ->expect('SmartDato\CblLogistica')
    ->not->toUse([
        'App',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Http',
        'Illuminate\Support\Facades\Cache',
    ]);
