<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\PackingJob;
use App\Models\PackingJobLine;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackingJobController extends Controller
{
    /**
     * LIST PACKING JOBS
     * GET /production/packing-jobs
     */
    public function index(Request $request)
    {
        $query = PackingJob::query()
            ->with(['fromWarehouse', 'toWarehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $packingJobs = $query->paginate(20);

        return view('production.packing_jobs.index', compact('packingJobs'));
    }

    /**
     * FORM CREATE
     * GET /production/packing-jobs/create
     *
     * Menampilkan daftar "bundles jahit" (sewing_return_lines)
     * yang masih punya sisa untuk dipacking (qty_ok > packed_qty).
     */
    public function create(Request $request)
    {
        // 1) Pastikan gudang WIP-FIN dan FG ada
        $fromWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WIP-FIN'],
            ['name' => 'WIP Finishing', 'type' => 'wip']
        );

        $toWarehouse = Warehouse::firstOrCreate(
            ['code' => 'FG'],
            ['name' => 'Finished Goods', 'type' => 'fg']
        );

        // 2) Ambil semua sewing_return_lines yang:
        //    - header (sewing_return) sudah POSTED
        //    - masih punya sisa = qty_ok - packed_qty > 0
        $bundleLines = SewingReturnLine::query()
            ->whereHas('sewingReturn', function ($q) {
                $q->where('status', 'posted');
            })
            ->with(['sewingReturn', 'item', 'lot'])
            ->get()
            ->filter(function (SewingReturnLine $line) {
                $qtyOk = (float) ($line->qty_ok ?? 0);
                $packedQty = (float) ($line->packed_qty ?? 0);
                $remaining = $qtyOk - $packedQty;

                return $remaining > 0;
            })
            ->values(); // reset index

        return view('production.packing_jobs.create', [
            'fromWarehouse' => $fromWarehouse,
            'toWarehouse' => $toWarehouse,
            'bundleLines' => $bundleLines,
        ]);
    }

    /**
     * STORE (buat draft packing)
     * POST /production/packing-jobs
     *
     * - Membuat header PackingJob (status = draft)
     * - Membuat detail PackingJobLine dari input user
     * - Belum pindah stok (pindah stok di method post())
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],

            'lines' => ['required', 'array'],
            'lines.*.selected' => ['nullable'], // checkbox
            'lines.*.line_id' => ['required', 'integer', 'exists:sewing_return_lines,id'],
            'lines.*.qty_pack' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Filter hanya baris yang dicentang dan qty_pack > 0
        $selectedLines = collect($data['lines'])
            ->filter(function ($row) {
                $selected = !empty($row['selected']);
                $qtyPack = isset($row['qty_pack']) ? (float) $row['qty_pack'] : 0;

                return $selected && $qtyPack > 0;
            });

        if ($selectedLines->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Tidak ada bundle yang dipilih untuk dipacking.');
        }

        // Gudang FROM/TO
        $fromWarehouse = Warehouse::where('code', 'WIP-FIN')->firstOrFail();
        $toWarehouse = Warehouse::where('code', 'FG')->firstOrFail();

        $date = $data['date'];
        $running = PackingJob::whereDate('date', $date)->count() + 1;
        $code = 'PACK-' . date('ymd', strtotime($date)) . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($data, $selectedLines, $code, $fromWarehouse, $toWarehouse) {
            // 1) Buat header
            $job = PackingJob::create([
                'code' => $code,
                'date' => $data['date'],
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 2) Buat detail
            foreach ($selectedLines as $row) {
                /** @var \App\Models\SewingReturnLine $line */
                $line = SewingReturnLine::with(['item'])->findOrFail($row['line_id']);
                $qtyPack = (float) $row['qty_pack'];

                $qtyOk = (float) ($line->qty_ok ?? 0);
                $packedQty = (float) ($line->packed_qty ?? 0);
                $remaining = $qtyOk - $packedQty;

                // Validasi: tidak boleh lebih dari sisa
                if ($qtyPack > $remaining) {
                    throw ValidationException::withMessages([
                        'lines' => ["Qty packing untuk line ID {$line->id} melebihi sisa yang tersedia ({$remaining})."],
                    ]);
                }

                PackingJobLine::create([
                    'packing_job_id' => $job->id,
                    'sewing_return_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'item_code' => $line->item_code,
                    'lot_id' => $line->lot_id,
                    'qty' => $qtyPack,
                    'unit' => $line->unit ?? 'pcs',
                ]);
            }
        });

        return redirect()
            ->route('production.packing_jobs.index')
            ->with('success', 'Draft Packing berhasil dibuat. Silakan review & posting.');
    }

    /**
     * SHOW
     * GET /production/packing-jobs/{packingJob}
     *
     * Menampilkan header + detail + info sewing_return_lines.
     */
    public function show(PackingJob $packingJob)
    {
        $packingJob->load([
            'fromWarehouse',
            'toWarehouse',
            'lines.sewingReturnLine.sewingReturn',
            'lines.sewingReturnLine.item',
            'lines.sewingReturnLine.lot',
        ]);

        return view('production.packing_jobs.show', compact('packingJob'));
    }

    /**
     * POSTING
     * POST /production/packing-jobs/{packingJob}/post
     *
     * - Pindahkan stok dari WIP-FIN → FG
     * - Update packed_qty di sewing_return_lines
     * - Ubah status header menjadi "posted"
     */
    public function post(PackingJob $packingJob)
    {
        if ($packingJob->status === 'posted') {
            return back()->with('info', 'Dokumen ini sudah diposting.');
        }

        $packingJob->load(['lines.sewingReturnLine']);

        $inventory = app(\App\Services\InventoryService::class);

        DB::transaction(function () use ($packingJob, $inventory) {
            foreach ($packingJob->lines as $line) {
                $sewLine = $line->sewingReturnLine;
                $qtyPack = (float) $line->qty;

                $qtyOk = (float) ($sewLine->qty_ok ?? 0);
                $packedQty = (float) ($sewLine->packed_qty ?? 0);
                $remaining = $qtyOk - $packedQty;

                // Safety check lagi di sini
                if ($qtyPack > $remaining) {
                    throw ValidationException::withMessages([
                        'lines' => ["Qty packing untuk line ID {$sewLine->id} melebihi sisa yang tersedia ({$remaining})."],
                    ]);
                }

                // 1) Mutasi stok: WIP-FIN → FG
                InventoryService::transferForPacking([
                    'from_warehouse_id' => $packingJob->from_warehouse_id, // WIP-FIN
                    'to_warehouse_id' => $packingJob->to_warehouse_id, // FG
                    'lot_id' => $line->lot_id,
                    'qty' => $qtyPack,
                    'unit' => $line->unit ?? 'pcs',
                    'ref_code' => $packingJob->code,
                    'note' => 'Packing FG dari hasil jahit',
                    'date' => $packingJob->date?->toDateString(),
                    // 'category'       => 'fg', // boleh di-set di sini, atau pakai default di service
                ]);

                // 2) Update packed_qty di sewing_return_lines
                $sewLine->packed_qty = $packedQty + $qtyPack;
                $sewLine->last_packed_at = now();
                $sewLine->save();
            }

            // 3) Update status header
            $packingJob->update(['status' => 'posted']);
        });

        return redirect()
            ->route('production.packing_jobs.show', $packingJob)
            ->with('success', 'Packing berhasil diposting. Stok sudah pindah ke FG.');
    }
}
