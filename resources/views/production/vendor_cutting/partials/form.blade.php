@extends('layouts.app')

@section('title', 'Vendor Cutting • Input Cutting')

@section('content')
    <div class="container my-4">

        <h3 class="fw-bold mb-4">Vendor Cutting — Input Cutting</h3>

        {{-- Info LOT Terpilih --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="small text-muted">LOT Terpilih</div>
                <div class="fw-bold fs-5 mono">{{ $lot->code }}</div>

                <div>{{ $item->code }} — {{ $item->name }}</div>
                <div class="mono">
                    Qty: {{ number_format($line->qty, 2) }} {{ $line->unit }}
                </div>
            </div>
        </div>


        {{-- ===================== --}}
        {{-- FORM CUTTING --}}
        {{-- ===================== --}}
        <form action="" method="POST">
            @csrf
            <input type="hidden" name="lot_id" value="{{ $lot->id }}">

            {{-- Operator --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    Operator
                </div>
                <div class="card-body">
                    <label class="form-label mb-2">Pilih Operator</label>
                    <select name="operator_code" class="form-select" required>
                        <option value="">— Pilih —</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->code }}">{{ $e->code }} — {{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>


            {{-- Hasil Cutting --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    Hasil Cutting (Barang Setengah Jadi)
                </div>

                <div class="card-body">

                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Item</th>
                                <th>Nama</th>
                                <th style="width: 120px;" class="text-end">Qty</th>
                                <th style="width: 80px;">UoM</th>
                            </tr>
                        </thead>
                        <tbody>

                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td>
                                        <select name="results[{{ $i }}][item_id]" class="form-select">
                                            <option value="">— Pilih —</option>

                                        </select>
                                    </td>
                                    <td class="text-muted small">(nama otomatis)</td>
                                    <td>
                                        <input type="number" step="0.01" name="results[{{ $i }}][qty]"
                                            class="form-control text-end">
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $i }}][uom]" class="form-control"
                                            value="pcs">
                                    </td>
                                </tr>
                            @endfor

                        </tbody>
                    </table>

                </div>
            </div>


            {{-- Submit --}}
            <div class="text-end">
                <button class="btn btn-primary px-4">Simpan Cutting</button>
            </div>

        </form>

    </div>
@endsection
