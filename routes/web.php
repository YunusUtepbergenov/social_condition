<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/mood');
})->middleware('auth');

Route::get('/mood', function () {
    return view('pages.mood');
})->middleware('auth')->name('mood');

Route::get('/protests', function () {
    return view('pages.protests');
})->middleware('auth')->name('protests');

Route::get('/indicators', function () {
    return view('pages.indicators');
})->middleware('auth')->name('indicators');

Route::get('/clusters', function () {
    return view('pages.clusters');
})->middleware('auth')->name('clusters');

Route::get('/sentiment', function () {
    return redirect('/sentiment/mood');
})->middleware('auth');

Route::get('/sentiment/mood', function () {
    return view('pages.sentiment-mood');
})->middleware('auth')->name('sentiment.mood');

Route::get('/sentiment/survey', function () {
    return view('pages.sentiment-survey');
})->middleware('auth')->name('sentiment.survey');

Route::get('/mahallas', function () {
    return view('layouts.mahallas');
})->middleware('auth');

Route::get('/districts/{district}', [PageController::class, 'districts'])->name('districts')->middleware('auth');

Route::get('/dashboard', function () {
    return redirect('/mood');
})->middleware(['auth'])->name('dashboard');

Route::get('/task/download', function () {
    return response()->download(public_path('tasks/топширик.docx'));
})->middleware(['auth'])->name('task.download');

Route::get('table', [PageController::class, 'table']);

require __DIR__.'/auth.php';
