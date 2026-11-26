@extends('layouts.app')

@section('title', 'Standar Harga Maksimal - SIPDANA')
@section('page_title', 'Standar Harga Maksimal')

@section('content')
    <div class="space-y-4">
        {{-- Info Read-Only --}}
        <div class="px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 text-xs text-slate-600 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-teal-600"></i>
            <span>Daftar ini bersifat <b>hanya lihat (read-only)</b> sebagai acuan pemeriksaan. Penambahan atau perubahan batas harga dilakukan oleh Administrator.</span>
        </div>

        {{-- Tabel Daftar Standar Harga --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800">Daftar Standar Harga Maksimal</h2>
                <span class="text-[11px] text-slate-500">{{ $priceCaps->count() }} item</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
                            <th class="px-4 py-3">Nama Item</th>
                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-4 py-3">Harga Maksimal</th>
                            <th class="px-4 py-3">Scope</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($priceCaps as $cap)
                            <tr class="hover:bg-slate-50/70 {{ $cap->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $cap->item_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $cap->unit }}</td>
                                <td class="px-4 py-3 font-bold text-teal-700">
                                    Rp {{ number_format($cap->max_price, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold">
                                        {{ $cap->scope }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($cap->is_active)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    Belum ada standar harga maksimal yang ditetapkan Admin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection