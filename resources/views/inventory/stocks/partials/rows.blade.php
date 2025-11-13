@push('head')
    <style>
        /* tone qty & badge selaras */
        .qty-ok-num {
            color: color-mix(in srgb, var(--brand) 80%, var(--fg) 20%);
            font-weight: 700;
        }

        .qty-low {
            color: color-mix(in srgb, #1d4ed8 75%, var(--fg) 25%);
            font-weight: 700;
        }

        .qty-zero {
            color: var(--muted);
            font-weight: 700;
        }

        .qty-neg {
            color: #ef4444;
            font-weight: 700;
        }

        .badge-ok,
        .badge-low,
        .badge-warn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .74rem;
            font-weight: 700;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--fg);
        }

        .badge-ok {
            background: color-mix(in srgb, var(--brand) 12%, transparent 88%);
            border-color: color-mix(in srgb, var(--brand) 22%, var(--line) 78%);
            color: color-mix(in srgb, var(--brand) 80%, var(--fg) 20%);
        }

        .badge-low {
            background: color-mix(in srgb, #f97316 16%, transparent 84%);
            border-color: color-mix(in srgb, #f97316 32%, var(--line) 68%);
            color: #f97316;
        }

        .badge-warn {
            background: color-mix(in srgb, #ef4444 16%, transparent 84%);
            border-color: color-mix(in srgb, #ef4444 32%, var(--line) 68%);
            color: #ef4444;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }
    </style>
@endpush

@foreach ($rows as $row)
    @php
        $qty = (float) ($row->qty ?? 0);

        // tone angka
        $toneClass = $qty < 0 ? 'qty-neg' : ($qty == 0 ? 'qty-zero' : ($qty <= 5 ? 'qty-low' : 'qty-ok-num'));

        // status badge
        $badgeClass = 'badge-ok';
        $badgeText = 'Tersedia';
        $badgeIcon = 'bi bi-check2-circle';

        if ($qty < 0) {
            $badgeClass = 'badge-warn';
            $badgeText = 'Negatif';
            $badgeIcon = 'bi bi-bug-fill';
        } elseif ($qty === 0.0) {
            $badgeClass = 'badge-low';
            $badgeText = 'Habis';
            $badgeIcon = 'bi bi-exclamation-triangle-fill';
        } elseif ($qty > 0 && $qty <= 5) {
            $badgeClass = 'badge-low';
            $badgeText = 'Low';
            $badgeIcon = 'bi bi-exclamation-triangle-fill';
        }
    @endphp

    <tr data-stock-card>
        <td class="text-nowrap mono">{{ $row->warehouse->code }}</td>

        <td>
            <div class="fw-semibold" style="letter-spacing:.02em">
                <span class="text-uppercase">{{ $row->lot->item->code }}</span>
            </div>
            <div class="small" style="color:var(--muted)">{{ $row->lot->item->name }}</div>
        </td>

        <td class="mono text-nowrap">{{ $row->lot->code }}</td>

        {{-- Qty dengan tone konsisten tema --}}
        <td class="text-end mono {{ $toneClass }}">
            {{ number_format($qty, 2, ',', '.') }}
        </td>

        <td class="text-nowrap">{{ $row->unit }}</td>

        <td>
            <span class="{{ $badgeClass }}">
                <i class="{{ $badgeIcon }}"></i> {{ $badgeText }}
            </span>
        </td>

        <td class="small text-nowrap" style="color:var(--muted)">
            {{ optional($row->updated_at)->format('Y-m-d H:i') ?? '-' }}
        </td>
    </tr>
@endforeach
