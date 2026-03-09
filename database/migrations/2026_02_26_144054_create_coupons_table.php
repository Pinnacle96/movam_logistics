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
        Schema::create('coupons', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code')->unique();
            $blueprint->enum('type', ['fixed', 'percentage']);
            $blueprint->decimal('value', 10, 2);
            $blueprint->decimal('min_order_amount', 10, 2)->default(0);
            $blueprint->decimal('max_discount_amount', 10, 2)->nullable();
            $blueprint->timestamp('starts_at')->nullable();
            $blueprint->timestamp('expires_at')->nullable();
            $blueprint->integer('usage_limit')->nullable();
            $blueprint->integer('used_count')->default(0);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->foreignId('merchant_id')->nullable()->constrained()->onDelete('cascade'); // Null means platform-wide
            $blueprint->timestamps();
        });

        Schema::create('coupon_user', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            $blueprint->timestamp('used_at');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_user');
        Schema::dropIfExists('coupons');
    }
};
