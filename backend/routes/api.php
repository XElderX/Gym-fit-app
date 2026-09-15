<?php
use App\Http\Controllers\Api\V1\{AuthController,ExerciseController,WorkoutController,StatisticsController,TemplateController,SettingsController};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function(){
    Route::post('/auth/register',[AuthController::class,'register']);
    Route::post('/auth/login',[AuthController::class,'login']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/auth/me',[AuthController::class,'me']); Route::post('/auth/logout',[AuthController::class,'logout']);
        Route::get('/workouts/active',[WorkoutController::class,'active']);
        Route::post('/workouts/{workout}/copy',[WorkoutController::class,'copy']);
        Route::apiResource('workouts',WorkoutController::class);
        Route::get('/exercises/{exercise}/history',[ExerciseController::class,'history']);
        Route::get('/exercises/{exercise}/previous',[ExerciseController::class,'previous']);
        Route::apiResource('exercises',ExerciseController::class);
        Route::get('/statistics',[StatisticsController::class,'summary']);
        Route::get('/statistics/exercises/{exercise}',[StatisticsController::class,'exercise']);
        Route::post('/templates/{template}/start',[TemplateController::class,'start']);
        Route::apiResource('templates',TemplateController::class);
        Route::patch('/settings',[SettingsController::class,'update']);
    });
});
