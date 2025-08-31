<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    /**
     * Display a listing of all available permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all(['id', 'name', 'guard_name']);

        return response()->json([
            'data' => $permissions,
        ]);
    }
}
