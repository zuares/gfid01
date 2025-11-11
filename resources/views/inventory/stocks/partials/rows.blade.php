@foreach ($rows as $row)
    @php
        $qty = (float) ($row->qty ?? 0);
        $toneClass = $qty < 0 ? 'qty-neg' : ($qty == 0 ? 'qty-zero' : ($qty <= 5 ? 'qty-low' : 'qty-ok-num'));
        $isLow = $qty <= 0;
    @endphp
    <tr data-stock-card>
        <td class="text-nowrap">{{ $row->warehouse->code }}</td>

        <td>
            <div class="fw-semibold text-dark">{{ $row->lot->item->code }}</div>
            <div class="small text-muted">{{ $row->lot->item->name }}</div>
        </td>

        <td class="mono text-nowrap">{{ $row->lot->code }}</td>

        {{-- Qty dengan tone warna navy/biru/merah --}}
        <td class="text-end mono {{ $toneClass }}">
            {{ number_format($qty, 2, ',', '.') }}
        </td>

        <td class="text-nowrap">{{ $row->unit }}</td>

        <td>
            @if ($isLow)
                <span class="badge-low">Habis</span>
            @else
                <span class="badge-ok">Tersedia</span>
            @endif
        </td>

        <td class="text-muted small text-nowrap">
            {{ optional($row->updated_at)->format('Y-m-d H:i') ?? '-' }}
        </td>
    </tr>
@endforeach
