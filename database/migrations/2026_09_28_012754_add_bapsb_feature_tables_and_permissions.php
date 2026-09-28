<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('giros', 'needs_bilyet')) {
            Schema::table('giros', function (Blueprint $table) {
                $table->boolean('needs_bilyet')->default(true)->after('type');
            });

            DB::table('giros')->where('type', 'giro')->update(['needs_bilyet' => 1]);
            DB::table('giros')->where('type', 'tabungan')->update(['needs_bilyet' => 0]);
        }

        $this->extendDokumensTypeEnum();

        if (! Schema::hasTable('bapsbs')) {
            Schema::create('bapsbs', function (Blueprint $table) {
                $table->id();
                $table->string('nomor')->unique();
                $table->string('period', 7);
                $table->string('project', 20);
                $table->date('bapsb_date');
                $table->foreignId('prepared_by')->constrained('users');
                $table->string('checker1');
                $table->string('checker2');
                $table->string('approved_by')->nullable();
                $table->unsignedBigInteger('total_bg')->default(0);
                $table->unsignedBigInteger('total_cek')->default(0);
                $table->unsignedBigInteger('total_loa')->default(0);
                $table->unsignedInteger('count_bg')->default(0);
                $table->unsignedInteger('count_cek')->default(0);
                $table->unsignedInteger('count_loa')->default(0);
                $table->unsignedInteger('count_cair')->default(0);
                $table->unsignedInteger('count_void')->default(0);
                $table->string('validation_status', 20)->default('pending');
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('validated_at')->nullable();
                $table->foreignId('dokumen_id')->nullable()->constrained('dokumens')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['project', 'period']);
            });
        }

        if (! Schema::hasTable('bapsb_lines')) {
            Schema::create('bapsb_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bapsb_id')->constrained('bapsbs')->cascadeOnDelete();
                $table->foreignId('bilyet_id')->constrained('bilyets')->restrictOnDelete();
                $table->string('type', 20);
                $table->string('nomor', 50);
                $table->string('bank_account', 100);
                $table->date('bilyet_date')->nullable();
                $table->date('cair_date')->nullable();
                $table->unsignedBigInteger('amount')->default(0);
                $table->string('status', 30);
                $table->boolean('physical_present')->default(true);
                $table->string('location', 80)->nullable();
                $table->string('location_note')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['bapsb_id', 'bilyet_id']);
            });
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\BapsbPermissionsSeeder',
            '--force' => true,
        ]);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        Schema::dropIfExists('bapsb_lines');
        Schema::dropIfExists('bapsbs');

        if (Schema::hasColumn('giros', 'needs_bilyet')) {
            Schema::table('giros', function (Blueprint $table) {
                $table->dropColumn('needs_bilyet');
            });
        }

        $permissionNames = ['akses_bapsb', 'validate_bapsb_report'];
        foreach ($permissionNames as $name) {
            $permission = \Spatie\Permission\Models\Permission::where('name', $name)
                ->where('guard_name', 'web')
                ->first();
            if ($permission) {
                $permission->delete();
            }
        }

        Artisan::call('permission:cache-reset');
    }

    private function extendDokumensTypeEnum(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM dokumens WHERE Field = 'type'");
        if ($column === null) {
            return;
        }

        $typeDefinition = (string) $column->Type;
        if (! str_starts_with(strtolower($typeDefinition), 'enum(')) {
            return;
        }

        if (str_contains($typeDefinition, "'bapsb'")) {
            return;
        }

        preg_match_all("/'([^']+)'/", $typeDefinition, $matches);
        $values = $matches[1] ?? [];
        if (! in_array('bapsb', $values, true)) {
            $values[] = 'bapsb';
        }

        $enumList = implode(',', array_map(static fn (string $v): string => "'".$v."'", $values));
        $null = ((string) $column->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->Default !== null ? " DEFAULT '".$column->Default."'" : '';

        DB::statement("ALTER TABLE dokumens MODIFY COLUMN type ENUM({$enumList}) {$null}{$default}");
    }
};
