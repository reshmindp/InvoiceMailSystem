<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['api'])->group(function(){

Route::post('/generate-invoices', [InvoiceController::class, 'generate']);
Route::get('/status/{batchId}', [InvoiceController::class, 'status']);

});

