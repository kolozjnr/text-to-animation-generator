<?php

use App\Providers\AppServiceProvider;
use App\Providers\DocumentServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    DocumentServiceProvider::class,
    FortifyServiceProvider::class,
    RepositoryServiceProvider::class,
];
