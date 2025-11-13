{{-- resources/views/production/external/receive.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container py-3">
        <h4>Penerimaan External: {{ $t->code }}</h4>
        <div class="mb-2 text-muted">Status saat ini: <span class="badge text-bg-secondary">{{ $t->status }}</span></div>

        <form method="post" action="{{ route('production.external.receive.store', $t->id) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control">
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>LOT</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Dikirim</th>
                            <th>Sudah Terima</th>
                            <th>Terima Sekarang</th>
                            <th>Defect Sekarang</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($t->lines as $ln)
                            <tr>
                                <td>{{ $ln->lot_id }}</td>
                                <td>{{ $ln->item_id }}</td>
                                <td>{{ $ln->unit }}</td>
                                <td>{{ number_format($ln->qty, 4) }}</td>
                                <td>{{ number_format($ln->received_qty, 4) }}</td>
                                <td style="width:160px">
                                    <input type="hidden" name="lines[{{ $ln->id }}][transfer_line_id]"
                                        value="{{ $ln->id }}">
                                    <input type="number" step="0.0001" name="lines[{{ $ln->id }}][received_qty]"
                                        class="form-control" placeholder="0">
                                </td>
                                <td style="width:160px">
                                    <input type="number" step="0.0001" name="lines[{{ $ln->id }}][defect_qty]"
                                        class="form-control" placeholder="0">
                                </td>
                                <td><input type="text" name="lines[{{ $ln->id }}][note]" class="form-control">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-success">Post Penerimaan</button>
            </div>
        </form>
    </div>
@endsection
