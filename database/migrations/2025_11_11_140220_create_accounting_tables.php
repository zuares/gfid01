<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daftar akun sederhana (bisa kamu seed)
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // 1101, 2101, dst (opsional)
            $t->string('name'); // Persediaan Bahan, Hutang Dagang, Kas/Bank
            $t->enum('type', [ // tipe dasar untuk normal balance
                'asset', 'liability', 'equity', 'revenue', 'expense',
            ]);
            $t->timestamps();
        });

        // Jurnal header
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // JRN-YYYYMMDD-###
            $t->date('date');
            $t->string('ref_code')->nullable(); // referensi eksternal: INV-BKU-...
            $t->string('memo')->nullable();
            $t->timestamps();
        });

        // Jurnal detail (debet/kredit)
        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('accounts');
            $t->decimal('debit', 14, 0)->default(0);
            $t->decimal('credit', 14, 0)->default(0);
            $t->text('note')->nullable();
            $t->timestamps();
        });

        // Tambahan: purchase_invoices & lines (kalau belum ada)
        // Sesuaikan jika sudah dibuat sebelumnya.
        if (!Schema::hasTable('purchase_invoices')) {
            Schema::create('purchase_invoices', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique(); // INV-BKU-YYMMDD-###
                $t->date('date');
                $t->foreignId('supplier_id')->constrained('suppliers');
                $t->foreignId('warehouse_id')->constrained('warehouses');
                $t->enum('status', ['draft', 'posted'])->default('draft');
                $t->string('note')->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('purchase_invoice_lines')) {
            Schema::create('purchase_invoice_lines', function (Blueprint $t) {
                $t->id();
                $t->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('items');
                $t->string('item_code');
                $t->decimal('qty', 12, 2);
                $t->string('unit', 16);
                $t->decimal('unit_cost', 14, 0)->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('journal_lines')) {
            Schema::drop('journal_lines');
        }

        if (Schema::hasTable('journal_entries')) {
            Schema::drop('journal_entries');
        }

        if (Schema::hasTable('accounts')) {
            Schema::drop('accounts');
        }

        // jangan drop invoices kalau sudah ada sebelum ini
        if (Schema::hasTable('purchase_invoice_lines')) {
            Schema::drop('purchase_invoice_lines');
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::drop('purchase_invoices');
        }

    }
};
