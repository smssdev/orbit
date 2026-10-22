<?php

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();

arch('env() is only read from config files')
    ->expect('env')
    ->not->toBeUsedIn('App');
