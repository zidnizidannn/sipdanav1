@extends('layouts.app')

@section('title', 'Standar Harga Maksimal - SIPDANA')
@section('page_title', 'Standar Harga Maksimal')

@section('content')
    @php
        $scopeLabels = [
            'ALL'     => ['label' => 'Semua (Org & Yayasan)', 'cls' => 'bg-teal-50 text-teal-800 border-teal-200'],
            'ORG'     => ['label' => 'Organisasi Saja',       'cls' => 'bg-sky-50 text-sky-800 border-sky-200'],
            'YAYASAN' => ['label' => 'Yayasan / Sekolah',     'cls' => 'bg-amber-50 text-amber-800 border-amber-200'],
        ];
    @endphp

    <div class="space-y-5">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-xs font-medium text-slate-500">Pengaturan Batas Atas Anggaran</p>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">Master Standar Harga Maksimal</h2>
            </div>
            <a href="{{ route('proposals.index') }}" class="px-4 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold transition self-start sm:self-auto">
                &larr; Kembali ke Dashboard
            </a>
        </div>

        {{-- Form Tambah Standar Harga Baru --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-xs text-slate-800">Tambah Standar Harga Baru</h3>
            </div>
            <form action="{{ route('price-caps.store') }}" method="POST" class="p-5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                @csrf
                <div class="sm:col-span-4">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Nama Item Standar *</label>
                    <input type="text" name="item_name" value="{{ old('item_name') }}" required
                           placeholder="Contoh: Makan Siang Panitia"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Satuan *</label>
                    <input type="text" name="unit" value="{{ old('unit', 'Orang') }}" required
                           placeholder="Orang / Paket / Pcs"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Harga Maks (Rp) *</label>
                    <input type="number" name="max_price" value="{{ old('max_price') }}" min="1" step="1" required
                           placeholder="25000"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold focus:outline-none focus:border-teal-600">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Berlaku Untuk *</label>
                    <select name="scope" required
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:border-teal-600">
                        <option value="ALL" @selected(old('scope') === 'ALL')>Semua</option>
                        <option value="ORG" @selected(old('scope') === 'ORG')>Organisasi</option>
                        <option value="YAYASAN" @selected(old('scope') === 'YAYASAN')>Yayasan / Sekolah</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                        + Simpan
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabel Daftar Standar Harga --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm">Daftar Standar Harga ({{ $priceCaps->count() }})</h3>
            </div>

            @if($priceCaps->isEmpty())
                <div class="p-10 text-center text-xs text-slate-400">
                    Belum ada standar harga maksimal yang ditambahkan.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500">
                                <th class="py-3 px-4">Nama Item</th>
                                <th class="py-3 px-4">Satuan</th>
                                <th class="py-3 px-4 text-right">Batas Harga Maksimal</th>
                                <th class="py-3 px-4">Cakupan</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($priceCaps as $cap)
                                @php $sc = $scopeLabels[$cap->scope] ?? ['label' => $cap->scope, 'cls' => 'bg-slate-100 text-slate-700 border-slate-200']; @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $cap->item_name }}</td>
                                    <td class="py-3.5 px-4 text-slate-600">{{ $cap->unit }}</td>
                                    <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                                        Rp {{ number_format($cap->max_price, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $sc['cls'] }}">
                                            {{ $sc['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($cap->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                                        <button type="button"
                                                onclick="openEditCapModal({{ $cap->id }}, @js($cap->item_name), @js($cap->unit), {{ $cap->max_price }}, @js($cap->scope), {{ $cap->is_active ? 'true' : 'false' }})"
                                                class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold transition">
                                            Edit
                                        </button>
                                        <form action="{{ route('price-caps.destroy', $cap) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus standar harga {{ $cap->item_name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-semibold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Edit Standar Harga --}}
    <div id="editCapModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full overflow-hidden border border-slate-200 fade-in">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Edit Standar Harga Maksimal</h3>
                <button type="button" onclick="closeEditCapModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="editCapForm" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Item *</label>
                    <input type="text" id="edit_item_name" name="item_name" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan *</label>
                        <input type="text" id="edit_unit" name="unit" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Maks (Rp) *</label>
                        <input type="number" id="edit_max_price" name="max_price" min="1" step="1" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-semibold focus:outline-none focus:border-teal-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku Untuk *</label>
                    <select id="edit_scope" name="scope" required
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:border-teal-600">
                        <option value="ALL">Semua (Organisasi &amp; Yayasan)</option>
                        <option value="ORG">Organisasi Saja</option>
                        <option value="YAYASAN">Yayasan / Sekolah Saja</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-slate-300 text-teal-600">
                    <span>Aktif (tampil di pilihan form pengajuan)</span>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeEditCapModal()" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const baseUpdateUrl = @json(url('/price-caps'));

    function openEditCapModal(id, name, unit, maxPrice, scope, isActive) {
        document.getElementById('editCapForm').action = `${baseUpdateUrl}/${id}`;
        document.getElementById('edit_item_name').value = name;
        document.getElementById('edit_unit').value = unit;
        document.getElementById('edit_max_price').value = maxPrice;
        document.getElementById('edit_scope').value = scope;
        document.getElementById('edit_is_active').checked = isActive;
        document.getElementById('editCapModal').classList.remove('hidden');
    }

    function closeEditCapModal() {
        document.getElementById('editCapModal').classList.add('hidden');
    }
</script>
@endpush