<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\OrderController; 

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user
    ]);
});


Route::middleware('auth:api')->group(function () {
    Route::get('/missing_warranty_orders', [OrderController::class, 'list_missing_warranty_orders']);
    Route::put('/missing_warranty_orders', [OrderController::class, 'update_missing_warranty_orders']);

    // Route::post('/tasks', [TaskController::class, 'store']); 
    // Route::delete('/tasks/{task}', [TaskController::class, 'delete']); 
});
