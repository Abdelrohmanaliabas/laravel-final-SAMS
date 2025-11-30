<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\TeacherResource;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $teachers = User::where('role', 'teacher')
                ->with('taughtGroups.center')
                ->withCount('taughtGroups')
                ->paginate(15);
            return $this->success(
                data: $teachers,
                message: 'Teachers retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to retrieve teachers.',
                status: 500,
                errors: $e->getMessage(),
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->authorize('create', User::class);

            $user = User::create($request->validated());

            return $this->success(
                data: $user,
                message: 'Teacher created successfully.'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to create teacher.',
                status: 500,
                errors: $e->getMessage(),
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try {
            if ($user->role !== 'teacher') {
                return $this->error(
                    message: 'This User Not Teacher',
                    status: 404
                );
            }

            $user->load(['taughtGroups.center', 'taughtGroups.students']);

            return $this->success(
                data: $user,
                message: 'Teacher retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to retrieve teacher.',
                status: 500,
                errors: $e->getMessage(),
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $this->authorize('update', $user);
            $user->update($request->validated());

            return $this->success(
                data: $user,
                message: 'Teacher updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to update teacher.',
                status: 500,
                errors: $e->getMessage(),
            );
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $this->authorize('delete', $user);

            $user->delete();

            return $this->success(
                message: 'Teacher deleted successfully.',
                status: 204
            );
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to delete teacher.',
                status: 500,
                errors: $e->getMessage(),
            );
        }
    }
}
