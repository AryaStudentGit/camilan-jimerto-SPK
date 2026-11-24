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
        // 2. criteria
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // C1 - C5
            $table->string('name');
            $table->enum('attribute_type', ['benefit', 'cost']);
            $table->string('unit_or_scale');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A1 - A30
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. monthly_sales_recaps
        Schema::create('monthly_sales_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('year');
            $table->unsignedTinyInteger('month'); // 1 - 12
            $table->integer('total_units_sold')->default(0);
            $table->decimal('total_revenue', 15, 2)->nullable()->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'year', 'month']);
        });

        // 5. calculation_runs
        Schema::create('calculation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('start_period', 7); // YYYY-MM
            $table->string('end_period', 7);   // YYYY-MM
            $table->decimal('lambda_max', 8, 6)->nullable();
            $table->decimal('consistency_index', 8, 6)->nullable();
            $table->decimal('consistency_ratio', 8, 6)->nullable();
            $table->boolean('is_consistent')->default(true);
            $table->boolean('is_active_dashboard')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. ahp_comparisons
        Schema::create('ahp_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained('calculation_runs')->cascadeOnDelete();
            $table->foreignId('criterion_1_id')->constrained('criteria')->cascadeOnDelete();
            $table->foreignId('criterion_2_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('comparison_value', 10, 4); // 1-9 or reciprocal 1/9
            $table->timestamps();

            $table->unique(['calculation_run_id', 'criterion_1_id', 'criterion_2_id'], 'ahp_comp_unique');
        });

        // 7. ahp_weights
        Schema::create('ahp_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained('calculation_runs')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('weight', 8, 6);
            $table->timestamps();

            $table->unique(['calculation_run_id', 'criterion_id']);
        });

        // 8. product_assessments
        Schema::create('product_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained('calculation_runs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('raw_value', 12, 2)->default(0); // stores 150 values (30 prods x 5 crit)
            $table->timestamps();

            $table->unique(['calculation_run_id', 'product_id', 'criterion_id'], 'prod_assess_unique');
        });

        // 9. saw_results
        Schema::create('saw_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained('calculation_runs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('final_score', 8, 6)->default(0);
            $table->unsignedInteger('rank')->default(1);
            $table->enum('priority_tier', ['utama', 'menengah', 'rendah'])->default('menengah');
            $table->timestamps();

            $table->unique(['calculation_run_id', 'product_id']);
        });

        // Additional table: sales_transactions (storing dataset rows for drill-down and dynamic audits)
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('row_id')->nullable(); // ID_Baris
            $table->string('invoice_no')->nullable(); // ID_Nota
            $table->date('transaction_date')->nullable();
            $table->integer('year');
            $table->unsignedTinyInteger('month');
            $table->string('month_period')->nullable();
            $table->string('day_name')->nullable();
            $table->string('original_note_no')->nullable();
            $table->string('original_store_name')->nullable();
            $table->string('standard_store_name')->nullable();
            $table->string('district')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('original_item_name');
            $table->string('sku_code')->nullable();
            $table->string('standard_product_name')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_category')->nullable();
            $table->integer('qty_original')->default(0);
            $table->integer('qty_clean')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_written', 12, 2)->default(0);
            $table->decimal('total_valid', 12, 2)->default(0);
            $table->decimal('clean_turnover', 12, 2)->default(0);
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->string('spelling_status')->nullable();
            $table->text('spelling_notes')->nullable();
            $table->text('audit_notes')->nullable();
            $table->string('photo_source')->nullable();
            $table->timestamps();

            $table->index(['year', 'month', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
        Schema::dropIfExists('saw_results');
        Schema::dropIfExists('product_assessments');
        Schema::dropIfExists('ahp_weights');
        Schema::dropIfExists('ahp_comparisons');
        Schema::dropIfExists('calculation_runs');
        Schema::dropIfExists('monthly_sales_recaps');
        Schema::dropIfExists('products');
        Schema::dropIfExists('criteria');
    }
};

