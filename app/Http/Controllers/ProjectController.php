<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $projects = Project::query()
            ->where('tenant_id', $tenantId)
            ->select(['id', 'name', 'tenant_id'])
            ->paginate(10);

        return response()->json($projects);
    }
}