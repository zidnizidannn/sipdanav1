<?php

namespace App\Http\Controllers;

use App\Models\PriceCap;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403, 'Akses khusus Administrator.');

        $search = trim((string) $request->query('q', ''));

        $priceCaps = PriceCap::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhere('scope', 'like', "%{$search}%");
            })
            ->orderByDesc('is_active')
            ->orderBy('item_name')
            ->get();

        return view('admin.dashboard', [
            'priceCaps'       => $priceCaps,
            'search'          => $search,
            'activeCapsCount' => PriceCap::where('is_active', true)->count(),
            'totalCapsCount'  => PriceCap::count(),
            'totalUsers'      => User::count(),
            'totalProposals'  => Proposal::count(),
        ]);
    }
}