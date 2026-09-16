<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisualizationController;
use App\Http\Controllers\DashboardController;
use App\Livewire\GetVisualisationData;

Route::get('/', function () {
    return view('index');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
->middleware(['auth', 'verified'])
->name('dashboard');

Route::patch('/dashboard/{id}/update-availability', [DashboardController::class, 'updateAvailability'])
->name('update.availability');

Route::post('/dataset/download/{id}', [DashboardController::class, 'downloadAndConvert'])
->middleware(['auth', 'verified'])
->name('dataset.download');

Route::get('visualization', [VisualizationController::class, 'index'])
->middleware(['auth', 'verified'])
->name('visualization.index');

Route::post('/visualization/chart', [VisualizationController::class, 'showChart'])
->middleware(['auth', 'verified'])
->name('visualization.showChart');

Route::post('visualization/visualize', [VisualizationController::class, 'visualize'])
->middleware(['auth', 'verified'])
->name('visualization.visualize');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::post('/visualization/chart-data', [VisualizationController::class, 'getChartData'])->name('visualization.getChartData');
Route::get('/insights', [VisualizationController::class, 'insights'])->name('visualization.insights');
require __DIR__.'/auth.php';

Route::get('/visualization/{varX}/{varY?}/{chartMode}', GetVisualisationData::class)
     ->name('visualization');


