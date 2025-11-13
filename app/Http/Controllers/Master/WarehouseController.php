<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * List gudang (index)
     */
    public function index()
    {
        $rows = Warehouse::orderBy('code')->paginate(30);

        return view('master.warehouses.index', compact('rows'));
    }

    /**
     * Form create gudang
     */
    public function create()
    {
        return view('master.warehouses.create');
    }

    /**
     * Simpan gudang baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
        ]);

        // Biar konsisten: code huruf besar
        $data['code'] = strtoupper($data['code']);

        Warehouse::create($data);

        return redirect()
            ->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    /**
     * Form edit gudang
     */
    public function edit(Warehouse $warehouse)
    {
        return view('master.warehouses.edit', compact('warehouse'));
    }

    /**
     * Update gudang
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:255',
        ]);

        $data['code'] = strtoupper($data['code']);

        $warehouse->update($data);

        return redirect()
            ->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil diperbarui.');
    }

    /**
     * Hapus gudang
     */
    public function destroy(Warehouse $warehouse)
    {
        // nanti kalau sudah dipakai di inventory, bisa diganti ke "tidak boleh hapus kalau ada relasi"
        $warehouse->delete();

        return redirect()
            ->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}
