<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeOtp;
use App\Models\PendingEmployeeRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Login
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $employee = Employee::where('email', $user->email)->first();

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $employee?->department,
                'position' => $employee?->position,
                'role' => $user->role,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Guest / View-Only Login
    |--------------------------------------------------------------------------
    */

    public function guestLogin()
    {
        $user = User::where('email', 'guest@county.go.ke')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'View Only User',
                'email' => 'guest@county.go.ke',
                'password' => str()->random(40),
                'role' => 'user',
            ]);
        }

        $token = $user->createToken('guest-token')->plainTextToken;

        return response()->json([
            'message' => 'Guest login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Employee Email Verification
    |--------------------------------------------------------------------------
    */

    public function verifyEmployeeEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));

        $employee = Employee::where('email', $email)
            ->where('status', 'active')
            ->first();

        if (!$employee) {
            return response()->json([
                'message' => 'This government email is not registered or the account is inactive.'
            ], 404);
        }

        // Invalidate previous unused OTPs
        EmployeeOtp::where('employee_id', $employee->id)
            ->where('used', false)
            ->update([
                'used' => true
            ]);

        // Generate a 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        EmployeeOtp::create([
            'employee_id' => $employee->id,
            'otp' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(10),
            'used' => false,
        ]);

        // Send OTP
        Mail::raw(
            "Your Taita Taveta County verification OTP is: {$otp}\n\n"
            . "This OTP expires in 10 minutes.\n\n"
            . "If you did not request this code, please ignore this email.",
            function (Message $message) use ($employee) {
                $message
                    ->to($employee->email)
                    ->subject('Government Employee Verification OTP');
            }
        );

        return response()->json([
            'message' => 'Government email verified. OTP has been sent to your government email.',
            'employee_id' => $employee->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Employee OTP Verification
    |--------------------------------------------------------------------------
    */

    public function verifyEmployeeOtp(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'otp' => 'required|digits:6',
        ]);

        $employee = Employee::find($request->employee_id);

        if (!$employee || $employee->status !== 'active') {
            return response()->json([
                'message' => 'Employee account is invalid or inactive.'
            ], 404);
        }

        $otpRecord = EmployeeOtp::where('employee_id', $employee->id)
            ->where('used', false)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'No valid OTP found. Please request a new OTP.'
            ], 401);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            $otpRecord->update([
                'used' => true
            ]);

            return response()->json([
                'message' => 'OTP has expired. Please request a new OTP.'
            ], 401);
        }

        if (!Hash::check($request->otp, $otpRecord->otp)) {
            return response()->json([
                'message' => 'Invalid OTP.'
            ], 401);
        }

        // OTP can only be used once
        $otpRecord->update([
            'used' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create / Update User Automatically
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $employee->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => str()->random(40),
                'role' => $employee->role,
            ]);
        } else {
            $user->update([
                'name' => $employee->name,
                'role' => $employee->role,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum Token
        |--------------------------------------------------------------------------
        */

        $token = $user
            ->createToken('government-employee-token')
            ->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully.',
            'token' => $token,
            'password_set' => (bool) $employee->password_set,

            'user' => [
                'id' => $user->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'department' => $employee->department,
                'position' => $employee->position,
                'role' => $employee->role,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Self Registration - Send OTP
    |--------------------------------------------------------------------------
    */

    public function registerEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($request->email));

        /*
        |--------------------------------------------------------------------------
        | Check Existing Employee
        |--------------------------------------------------------------------------
        */

        if (Employee::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'An employee account with this email already exists. Please use employee login.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Existing User
        |--------------------------------------------------------------------------
        */

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'An account with this email already exists. Please log in.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Previous Pending Registration
        |--------------------------------------------------------------------------
        */

        PendingEmployeeRegistration::where('email', $email)->delete();

        /*
        |--------------------------------------------------------------------------
        | Generate OTP
        |--------------------------------------------------------------------------
        */

        $otp = (string) random_int(100000, 999999);

        $registration = PendingEmployeeRegistration::create([
            'name' => trim($request->name),
            'email' => $email,
            'phone' => $request->phone,
            'department' => $request->department,
            'position' => $request->position,
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
            'verified_at' => null,
            'password_hash' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Send Registration OTP
        |--------------------------------------------------------------------------
        */

        Mail::raw(
            "Welcome to the Taita Taveta County Events & Meetings Management System.\n\n"
            . "Your employee registration verification code is: {$otp}\n\n"
            . "This code expires in 10 minutes.\n\n"
            . "If you did not request this registration, please ignore this email.",
            function (Message $message) use ($email) {
                $message
                    ->to($email)
                    ->subject('Employee Registration Verification OTP');
            }
        );

        return response()->json([
            'message' => 'Registration started. An OTP has been sent to your email.',
            'registration_id' => $registration->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Registration OTP
    |--------------------------------------------------------------------------
    */

    public function verifyRegistrationOtp(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|integer',
            'otp' => 'required|digits:6',
        ]);

        $registration = PendingEmployeeRegistration::find(
            $request->registration_id
        );

        if (!$registration) {
            return response()->json([
                'message' => 'Registration not found. Please register again.'
            ], 404);
        }

        if ($registration->verified_at) {
            return response()->json([
                'message' => 'This registration has already been verified.'
            ], 409);
        }

        if (Carbon::now()->greaterThan($registration->otp_expires_at)) {
            $registration->delete();

            return response()->json([
                'message' => 'OTP has expired. Please register again.'
            ], 401);
        }

        if (!Hash::check($request->otp, $registration->otp_hash)) {
            return response()->json([
                'message' => 'Invalid OTP.'
            ], 401);
        }

        $registration->update([
            'verified_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Email verified successfully.',
            'registration_id' => $registration->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Employee Registration
    |--------------------------------------------------------------------------
    */

    public function completeRegistration(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|integer',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $registration = PendingEmployeeRegistration::find(
            $request->registration_id
        );

        if (!$registration) {
            return response()->json([
                'message' => 'Registration not found. Please register again.'
            ], 404);
        }

        if (!$registration->verified_at) {
            return response()->json([
                'message' => 'Please verify your email before creating your account.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Make Sure Email Has Not Been Registered Meanwhile
        |--------------------------------------------------------------------------
        */

        if (Employee::where('email', $registration->email)->exists()) {
            return response()->json([
                'message' => 'An employee account with this email already exists.'
            ], 409);
        }

        if (User::where('email', $registration->email)->exists()) {
            return response()->json([
                'message' => 'A user account with this email already exists.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Employee + User Together
        |--------------------------------------------------------------------------
        */

        $result = DB::transaction(function () use ($registration, $request) {

            $employee = Employee::create([
                'name' => $registration->name,
                'email' => $registration->email,
                'phone' => $registration->phone,
                'department' => $registration->department,
                'position' => $registration->position,

                // Employee cannot choose their own role.
                'role' => 'employee',

                'status' => 'active',

                'password' => Hash::make($request->password),

                'password_set' => true,
            ]);

            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email,

                // User model automatically hashes this.
                'password' => $request->password,

                'role' => 'employee',
            ]);

            return [
                'employee' => $employee,
                'user' => $user,
            ];
        });

        $employee = $result['employee'];
        $user = $result['user'];

        /*
        |--------------------------------------------------------------------------
        | Remove Pending Registration
        |--------------------------------------------------------------------------
        */

        $registration->delete();

        /*
        |--------------------------------------------------------------------------
        | Automatically Log In
        |--------------------------------------------------------------------------
        */

        $token = $user
            ->createToken('government-employee-token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Registration completed successfully.',
            'token' => $token,

            'user' => [
                'id' => $user->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'department' => $employee->department,
                'position' => $employee->position,
                'role' => $employee->role,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Set Password
    |--------------------------------------------------------------------------
    */

    public function setPassword(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $employee = Employee::find($request->employee_id);

        if (!$employee || $employee->status !== 'active') {
            return response()->json([
                'message' => 'Employee account is invalid or inactive.'
            ], 404);
        }

        $employee->update([
            'password' => Hash::make($request->password),
            'password_set' => true,
        ]);

        $user = User::where('email', $employee->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => $request->password,
                'role' => $employee->role,
            ]);
        } else {
            $user->update([
                'password' => $request->password,
                'role' => $employee->role,
            ]);
        }

        $token = $user
            ->createToken('government-employee-token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Password created successfully.',
            'token' => $token,

            'user' => [
                'id' => $user->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'department' => $employee->department,
                'position' => $employee->position,
                'role' => $employee->role,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    public function user(Request $request)
    {
        $user = $request->user();

        $employee = Employee::where('email', $user->email)->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $employee?->department,
                'position' => $employee?->position,
                'role' => $user->role,
            ],
        ]);
    }
}