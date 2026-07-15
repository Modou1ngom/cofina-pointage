<?php

namespace App\Http\Controllers;

use App\Models\Profil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user && ($user->isRh() || $user->isSuperAdmin())) {
            return redirect()->route('pointage.rh.presence.recuperation-pointages');
        }

        $statsProfils = null;
        if ($user && $user->isAdmin()) {
            $statsProfils = [
                'total' => Profil::query()->count(),
                'actifs' => Profil::query()->where('actif', true)->count(),
                'inactifs' => Profil::query()->where('actif', false)->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'statsProfils' => $statsProfils,
            'userRole' => $this->getUserRole($user),
        ]);
    }

    private function getUserRole($user): string
    {
        if (! $user) {
            return 'guest';
        }

        if ($user->isAdmin()) {
            return 'admin';
        }
        if ($user->isRh()) {
            return 'rh';
        }

        return 'user';
    }
}
