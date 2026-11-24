@extends('layouts.app')

@section('title', 'Revisi Pengajuan #' . $proposal->code . ' - SIPDANA')
@section('page_title', 'Revisi Pengajuan')

@section('content')
    @php
        $rejectedCount = $proposal->items->where('is_checked', false)->count();
        $initialItems = old('items', $proposal->items->map(fn($it) => [
            'price_cap_id'  => $it->price_cap_id ?? '',
            'name'          => $it->name,
            'spec'          => $it->spec ?? '',
            'qty'           => $it->qty,
            'unit'          => $it->unit,
            'unit_price'    => $it->unit_price,
            'is_checked'    => (bool) $it->is_checked,
            'revision_note' => $it->revision_note ?? '',
        ])->values()->toArray());
    @endphp

    <form action="{{ route('proposals.resubmit', $proposal) }}" method="POST" enctype="multipart/form-data" class="max-w-4xl mx-auto space-y-5">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between">
            <a href="{{ route('proposals.show', $proposal) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali ke Detail
            </a>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                #{{ $proposal->code }}
            </span>
        </div>

        {{-- Banner Info Revisi --}}
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-950">
            <div class="font-bold text-sm text-rose-900">
                Perlu Revisi ({{ $rejectedCount }} Item)
            </div>
            <p class="text-xs text-rose-800 mt-1 leading-relaxed">
                Perbaiki item yang ditandai merah sesuai catatan pemeriksa di bawah ini, kemudian klik <b>Ajukan Ulang</b> agar kembali diperiksa.
            </p>
        </div>

        {{-- Informasi Umum --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-xs text-slate-800">Revisi Pengajuan Dana</h3>
                <span class="text-[11px] text-slate-500">Perbaikan Anggaran</span>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengajuan *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $proposal->title) }}" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>

                <div>
                    <label for="submission_date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                    <input type="date" id="submission_date" name="submission_date"
                           value="{{ old('submission_date', \Carbon\Carbon::parse($proposal->submission_date)->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>

                <div class="md:col-span-3">
                    <label for="disbursement_account" class="block text-xs font-semibold text-slate-700 mb-1">Rekening Pencairan *</label>
                    <input type="text" id="disbursement_account" name="disbursement_account"
                           value="{{ old('disbursement_account', $proposal->disbursement_account) }}" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Kebutuhan</label>
                    <textarea id="description" name="description" rows="2"
                              class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">{{ old('description', $proposal->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Lampiran (Opsional)</label>
                    <label class="cursor-pointer px-3 py-3.5 rounded-lg border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 truncate block text-center transition">
                        <span id="attachmentLabel" class="truncate">
                            {{ $proposal->attachment_path ? basename($proposal->attachment_path) : 'Pilih berkas baru...' }}
                        </span>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                               onchange="document.getElementById('attachmentLabel').textContent = this.files[0] ? this.files[0].name : 'Pilih berkas baru...'">
                    </label>
                </div>
            </div>
        </div>

        {{-- Rincian Item --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-xs text-slate-800">Rincian Item Anggaran (<span id="itemCountDisplay">0</span>)</h3>
                <button type="button" onclick="addItemRow()"
                        class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                    + Tambah Item
                </button>
            </div>

            <div class="p-5 space-y-3" id="itemsContainer"></div>

            <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="text-[11px] text-slate-500 font-medium">Total Keseluruhan</div>
                    <div class="text-xl font-bold text-slate-900" id="grandTotalDisplay">Rp 0</div>
                </div>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
                    <a href="{{ route('proposals.show', $proposal) }}"
                       class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                        Ajukan Ulang
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const PRICE_CAPS = @json($priceCaps);
    const SATUAN_OPTIONS = ['Unit', 'Paket', 'Pcs', 'Box', 'Set', 'Kegiatan', 'Bulan', 'Orang', 'Rim', 'Meter', 'Lot'];
    let itemsState = @json(array_values($initialItems));

    function formatRupiah(num) {
        return 'Rp ' + (Number(num) || 0).toLocaleString('id-ID');
    }

    function findCap(capId) {
        if (!capId) return null;
        return PRICE_CAPS.find(c => String(c.id) === String(capId)) || null;
    }

    function escapeAttr(val) {
        return String(val ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderItems() {
        const container = document.getElementById('itemsContainer');
        document.getElementById('itemCountDisplay').textContent = itemsState.length;

        container.innerHTML = itemsState.map((item, idx) => {
            const cap = findCap(item.price_cap_id);
            if (cap) {
                item.name = cap.item_name;
                item.unit = cap.unit;
                item.unit_price = Number(cap.max_price);
            }

            const qty = Number(item.qty) || 0;
            const price = Number(item.unit_price) || 0;
            const lineTotal = qty * price;
            const isProblematic = item.is_checked === false;
            const unitList = SATUAN_OPTIONS.includes(item.unit) ? SATUAN_OPTIONS : [item.unit, ...SATUAN_OPTIONS];

            return `
                <div class="p-4 rounded-xl border transition ${isProblematic ? 'bg-rose-50/50 border-rose-300' : 'bg-slate-50/50 border-slate-200'}">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold ${isProblematic ? 'text-rose-700' : 'text-slate-700'}">
                                Item #${idx + 1} ${isProblematic ? '&mdash; Perlu Direvisi' : ''}
                            </span>
                            <select name="items[${idx}][price_cap_id]" onchange="handleCapChange(${idx}, this.value)"
                                    class="px-2.5 py-1 rounded-md border border-slate-300 bg-white text-[11px] font-medium text-slate-700 focus:outline-none focus:border-teal-600">
                                <option value="">— Item Bebas (Non-Standar) —</option>
                                ${PRICE_CAPS.map(c => `
                                    <option value="${c.id}" ${String(item.price_cap_id) === String(c.id) ? 'selected' : ''}>
                                        [Standar] ${escapeAttr(c.item_name)} (Fix ${formatRupiah(c.max_price)}/${escapeAttr(c.unit)})
                                    </option>
                                `).join('')}
                            </select>
                            ${cap ? `<span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-teal-800 border border-teal-200">Harga Fix: ${formatRupiah(cap.max_price)}/${escapeAttr(cap.unit)}</span>` : ''}
                        </div>

                        ${itemsState.length > 1 ? `
                            <button type="button" onclick="removeItemRow(${idx})" class="text-xs font-semibold text-rose-600 hover:underline">
                                Hapus
                            </button>
                        ` : ''}
                    </div>

                    ${isProblematic && item.revision_note ? `
                        <div class="mb-3 p-2.5 rounded-lg bg-rose-100/80 border border-rose-200 text-xs text-rose-900">
                            <span class="font-bold">Catatan Pemeriksa:</span> "${escapeAttr(item.revision_note)}"
                        </div>
                    ` : ''}

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-4">
                            <label class="block text-[11px] font-medium text-slate-500 mb-1">Nama Item *</label>
                            <input type="text" name="items[${idx}][name]" value="${escapeAttr(item.name)}" required
                                   ${cap ? 'readonly tabindex="-1"' : ''}
                                   oninput="itemsState[${idx}].name = this.value"
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600 ${cap ? 'bg-slate-100 text-slate-600 cursor-not-allowed' : 'bg-white'}">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-medium text-slate-500 mb-1">Spesifikasi</label>
                            <input type="text" name="items[${idx}][spec]" value="${escapeAttr(item.spec)}"
                                   oninput="itemsState[${idx}].spec = this.value"
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:border-teal-600">
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-[11px] font-medium text-slate-500 mb-1">Qty *</label>
                            <input type="number" name="items[${idx}][qty]" min="1" step="1" value="${qty}" required
                                   oninput="updateNumeric(${idx}, 'qty', this.value)"
                                   class="w-full px-2 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-center focus:outline-none focus:border-teal-600">
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-[11px] font-medium text-slate-500 mb-1">Satuan</label>
                            ${cap ? `
                                <input type="text" name="items[${idx}][unit]" value="${escapeAttr(cap.unit)}" readonly tabindex="-1"
                                       class="w-full px-2 py-1.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-600 text-xs text-center cursor-not-allowed">
                            ` : `
                                <select name="items[${idx}][unit]" onchange="itemsState[${idx}].unit = this.value"
                                        class="w-full px-1.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:border-teal-600">
                                    ${unitList.map(u => `<option value="${escapeAttr(u)}" ${item.unit === u ? 'selected' : ''}>${escapeAttr(u)}</option>`).join('')}
                                </select>
                            `}
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-medium text-slate-500 mb-1">Harga Satuan (Rp) *</label>
                            ${cap ? `
                                <input type="hidden" name="items[${idx}][unit_price]" value="${cap.max_price}">
                                <input type="text" value="${formatRupiah(cap.max_price)}" readonly tabindex="-1"
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-600 text-xs font-semibold cursor-not-allowed">
                            ` : `
                                <input type="number" name="items[${idx}][unit_price]" min="1" step="1" value="${price}" required
                                       oninput="updateNumeric(${idx}, 'unit_price', this.value)"
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold focus:outline-none focus:border-teal-600">
                            `}
                            <div class="mt-1 text-right">
                                <span id="lineTotal-${idx}" class="text-[11px] font-bold text-slate-700">${formatRupiah(lineTotal)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        recalcGrandTotal();
    }

    function handleCapChange(idx, capId) {
        const cap = findCap(capId);
        itemsState[idx].price_cap_id = capId;
        if (cap) {
            itemsState[idx].name = cap.item_name;
            itemsState[idx].unit = cap.unit;
            itemsState[idx].unit_price = Number(cap.max_price);
        }
        renderItems();
    }

    function updateNumeric(idx, field, val) {
        itemsState[idx][field] = Number(val) || 0;
        const item = itemsState[idx];
        const lineEl = document.getElementById(`lineTotal-${idx}`);
        if (lineEl) lineEl.textContent = formatRupiah((Number(item.qty) || 0) * (Number(item.unit_price) || 0));
        recalcGrandTotal();
    }

    function recalcGrandTotal() {
        const grand = itemsState.reduce((sum, it) => sum + ((Number(it.qty) || 0) * (Number(it.unit_price) || 0)), 0);
        document.getElementById('grandTotalDisplay').textContent = formatRupiah(grand);
    }

    function addItemRow() {
        itemsState.push({ price_cap_id: '', name: '', spec: '', qty: 1, unit: 'Unit', unit_price: 0, is_checked: true, revision_note: '' });
        renderItems();
    }

    function removeItemRow(idx) {
        if (itemsState.length <= 1) return;
        itemsState.splice(idx, 1);
        renderItems();
    }

    renderItems();
</script>
@endpush