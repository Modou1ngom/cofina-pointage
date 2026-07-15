<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class MobileApiUserResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user, ?Request $request = null): array
    {
        $user->profilCollaborateurAssocie();

        return MobileApiAccountResource::toArray($user, $request);
    }
}
