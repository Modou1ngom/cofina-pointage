<?php

namespace App\Http\Controllers\Api\Pointrust;

use App\Http\Controllers\Controller;
use App\Support\MobileApiAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->profilCollaborateurAssocie();

        return response()->json(MobileApiAccountResource::toArray($user, $request));
    }
}
