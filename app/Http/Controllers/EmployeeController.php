<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // Get all employees
    public function index()
{
    $employees = Employee::all();

    return response()->json([
        'success' => true,
        'count' => $employees->count(),
        'employees' => $employees
    ]);
}
   

    // Create a new employee
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $employee = Employee::create($validated);

        return response()->json($employee, 201);
    }

    // Get one employee
    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    // Update an employee
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|string|max:255',
        ]);

        $employee->update($validated);

        return response()->json($employee);
    }

    // Delete an employee
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully'
        ]);
    }
}