<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Merchant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('business_name');
        });

        // Fill existing slugs
        $merchants = Merchant::all();
        foreach ($merchants as $merchant) {
            $slug = Str::slug($merchant->business_name);
            // Ensure unique
            $originalSlug = $slug;
            $count = 1;
            while (Merchant::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $merchant->slug = $slug;
            $merchant->save();
        }

        // Make slug required and unique
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
