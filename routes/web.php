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
    return view('layouts.main');
})->middleware('auth');

Route::get('/ntl', function () {
    return view('layouts.ntl');
})->middleware('auth');

Route::get('/sentiment', function () {
    return view('layouts.sentiment');
})->name('sentiment')->middleware('auth');

Route::get('/dashboard', function () {
    return view('layouts.main');
})->middleware(['auth'])->name('dashboard');

Route::get('/task/download', function () {
    return response()->download(public_path('tasks/топширик.docx'));
})->middleware(['auth'])->name('task.download');

Route::get('table', [PageController::class, 'table']);

require __DIR__.'/auth.php';
