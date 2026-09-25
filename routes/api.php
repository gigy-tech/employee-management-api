<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================================================
// AUTHENTICATION
// ==========================================================================

// Admin / existing user login
Route::post('/login', [AuthController::class, 'login']);

// Guest / view-only login
Route::post('/guest-login', [AuthController::class, 'guestLogin']);


// ==========================================================================
// EXISTING EMPLOYEE EMAIL VERIFICATION
// ==========================================================================

// Step 1: Verify existing employee email and send OTP
Route::post('/verify-employee-email', [AuthController::class, 'verifyEmployeeEmail']);

// Step 2: Verify OTP for existing employee
Route::post('/verify-employee-otp', [AuthController::class, 'verifyEmployeeOtp']);

// Step 3: Set password for existing employee
Route::post('/set-password', [AuthController::class, 'setPassword']);


// ==========================================================================
// EMPLOYEE SELF-REGISTRATION
// ==========================================================================

// Step 1: Employee enters their details and receives an OTP
Route::post('/register', [AuthController::class, 'registerEmployee']);

// Step 2: Employee verifies the OTP sent to their email
Route::post('/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp']);

// Step 3: Employee creates a password and the system creates
// both the Employee and User records automatically
Route::post('/complete-registration', [AuthController::class, 'completeRegistration']);


// ==========================================================================
// EMPLOYEE MANAGEMENT
// ==========================================================================

Route::apiResource('employees', EmployeeController::class);


// ==========================================================================
// PROTECTED ROUTES
// ==========================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ----------------------------------------------------------------------
    // EVENTS
    // ----------------------------------------------------------------------

    Route::apiResource('events', EventController::class);


    // ----------------------------------------------------------------------
    // MEETINGS
    // ----------------------------------------------------------------------

    Route::apiResource('meetings', MeetingController::class);


    // ----------------------------------------------------------------------
    // ATTENDANCE
    // ----------------------------------------------------------------------

    Route::apiResource('attendances', AttendanceController::class);


    // ----------------------------------------------------------------------
    // AUTHENTICATION
    // ----------------------------------------------------------------------

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Get currently logged-in user
    Route::get('/user', [AuthController::class, 'user']);
});