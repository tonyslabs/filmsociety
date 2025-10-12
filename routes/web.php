<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StremioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\WatchController;

Route::get('/watch/{type}/{id}', [WatchController::class, 'index'])->name('watch');

Route::get('/watch/{type}/{id}/play/{index}', [WatchController::class, 'play'])->name('watch.play');



Route::get('/buscar', [SearchController::class, 'index'])->name('buscar');


Route::get('/explorar', [ExploreController::class, 'index'])->name('explorar');
Route::get('/peliculas', [ExploreController::class, 'peliculas'])->name('peliculas');
Route::get('/series', [ExploreController::class, 'series'])->name('series');
Route::get('/explorar/load-more', [ExploreController::class, 'loadMore'])->name('explorar.loadmore');


Route::get('/proxy/stream', [WatchController::class, 'proxy']);



Route::get('/', [HomeController::class, 'index'])->name('home');
