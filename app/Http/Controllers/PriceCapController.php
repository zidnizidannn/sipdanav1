<?php

namespace App\Http\Controllers;

use App\Models\PriceCap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PriceCapController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isChecker(), 403, 'Hanya Checker yang dapat mengelola Standar Harga Maksimal.');

        $priceCaps = PriceCap::orderBy('item_name')->get();

        return view('price-caps.index', compact('priceCaps', 'user'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isChecker(), 403);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:150', 'unique:price_caps,item_name'],
            'unit'      => ['required', 'string', 'max:30'],
            'max_price' => ['required', 'integer', 'min:1'],
            'scope'     => ['required', Rule::in(['ALL', 'ORG', 'YAYASAN'])],
        ]);

        PriceCap::create([
            ...$validated,
            'is_active'  => true,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('price-caps.index')
            ->with('success', "Standar harga '{$validated['item_name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, PriceCap $priceCap)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isChecker(), 403);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:150', Rule::unique('price_caps', 'item_name')->ignore($priceCap->id)],
            'unit'      => ['required', 'string', 'max:30'],
            'max_price' => ['required', 'integer', 'min:1'],
            'scope'     => ['required', Rule::in(['ALL', 'ORG', 'YAYASAN'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $priceCap->update([
            'item_name' => $validated['item_name'],
            'unit'      => $validated['unit'],
            'max_price' => $validated['max_price'],
            'scope'     => $validated['scope'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('price-caps.index')
            ->with('success', "Standar harga '{$priceCap->item_name}' berhasil diperbarui.");
    }

    public function destroy(PriceCap $priceCap)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isChecker(), 403);

        $name = $priceCap->item_name;
        $priceCap->delete();

        return redirect()
            ->route('price-caps.index')
            ->with('success', "Standar harga '{$name}' berhasil dihapus.");
    }
}