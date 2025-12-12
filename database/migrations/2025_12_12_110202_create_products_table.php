<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Product basic fields
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();

            /**
             * Product Status:
             * active   → Product is visible and available
             * inactive → Product exists but hidden (not sold, not shown)
             * deleted  → Marked as removed (soft-delete type)
             *
             * Default is "active"
             */
            $table->enum('status', ['active', 'inactive', 'deleted'])
                  ->default('active');

            /**
             * Soft delete field:
             * deleted_at will be automatically filled when calling:
             * $product->delete();
             */
            $table->softDeletes();

            /**
             * Tracking who created / updated
             * These will store user IDs (nullable if no login system yet)
             */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // created_at / updated_at timestamps
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
