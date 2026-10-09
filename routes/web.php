<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Todas as rotas web direcionam para a SPA Vue 3.
| O roteamento de páginas é feito pelo Vue Router no frontend.
|
*/

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
