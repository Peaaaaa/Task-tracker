
<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TaskNotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\FocusSessionController;

// Task routes
Route::apiResource('tasks', TaskController::class);

Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle'])
    ->name('tasks.toggle');

// Push notification routes
Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'publicKey']);
Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);

// Notification Center routes
Route::get('/notifications', [TaskNotificationController::class, 'index']);
Route::patch('/notifications/{id}/read', [TaskNotificationController::class, 'markAsRead']);
Route::patch('/notifications/read-all', [TaskNotificationController::class, 'markAllAsRead']);

// Task Attachment routes
Route::get('/tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

// Goal routes
Route::apiResource('goals', GoalController::class);

// Milestone routes
Route::get('/goals/{goal}/milestones', [MilestoneController::class, 'index']);
Route::post('/goals/{goal}/milestones', [MilestoneController::class, 'store']);

Route::get('/milestones/{milestone}', [MilestoneController::class, 'show']);
Route::put('/milestones/{milestone}', [MilestoneController::class, 'update']);
Route::patch('/milestones/{milestone}', [MilestoneController::class, 'update']);
Route::delete('/milestones/{milestone}', [MilestoneController::class, 'destroy']);

// Focus Session routes
Route::get('/focus-sessions', [FocusSessionController::class, 'index']);
Route::post('/focus-sessions', [FocusSessionController::class, 'store']);