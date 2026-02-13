<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pickupBin', function (Blueprint $table) {
            $table->id()->primary();
            $table->char('binNumber')->nullable();
            $table->decimal('yards')->nullable();
            $table->char('company')->nullable();
            $table->char('location')->nullable();
            $table->integer('orderID')->nullable();
        });

        Schema::create('pickupProduct', function (Blueprint $table) {
            $table->id()->primary();
            $table->char('name')->nullable();
            $table->char('uom')->nullable();
            $table->char('company')->nullable();
            $table->integer('orderID')->nullable();
        });

        Schema::create('pickupSorting', function (Blueprint $table) {
            $table->id()->primary();
            $table->char('user')->nullable();
            $table->decimal('units')->nullable();
            $table->char('product')->nullable();
            $table->date('date')->nullable();
            $table->integer('picked_timestamp')->unsigned()->nullable();
            $table->char('company')->useCurrent();
            $table->integer('status')->nullable()->default(1);
            $table->integer('idempotency')->unsigned()->nullable()->default(0);
        });
        Schema::create('pickupSortingProduct', function (Blueprint $table) {
            $table->id()->primary();
            $table->char('name')->nullable();
            $table->integer('orderID')->nullable();
        });
        Schema::create('pickupUnit', function (Blueprint $table) {
            $table->id()->primary();
            $table->char('user')->nullable();
            $table->decimal('units')->nullable();
            $table->char('uom')->nullable();
            $table->char('product')->nullable();
            $table->decimal('length')->nullable();
            $table->decimal('width')->nullable();
            $table->decimal('height')->nullable();
            $table->char('bin')->nullable();
            $table->date('date')->nullable();
            $table->integer('picked_timestamp')->unsigned()->nullable();
            $table->char('company')->nullable();
            $table->char('destination')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->integer('idempotency')->unsigned()->nullable()->default(0);
            $table->string('comment')->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
