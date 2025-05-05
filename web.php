# routes/web.php


use App\Http\Controllers\ValidationDemoController;

Route::get('/validasi-demo', function () {
    return view('validation_demo');
});

Route::post('/validasi-demo', [ValidationDemoController::class, 'store'])->name('validate.demo');
