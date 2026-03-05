<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('postal_code')->constrained('departments')->nullOnDelete();
            $table->index('department_id');
        });

        $cityRows = DB::table('cities')->select('id', 'postal_code')->get();

        $codeToDepartmentId = [];

        foreach ($cityRows as $city) {
            $departmentCode = $this->extractDepartmentCode((string) $city->postal_code);

            if (! $departmentCode) {
                continue;
            }

            if (! array_key_exists($departmentCode, $codeToDepartmentId)) {
                $existingDepartment = DB::table('departments')->where('code', $departmentCode)->first();

                if ($existingDepartment) {
                    $codeToDepartmentId[$departmentCode] = (int) $existingDepartment->id;
                } else {
                    $departmentId = DB::table('departments')->insertGetId([
                        'code' => $departmentCode,
                        'name' => 'Département '.$departmentCode,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $codeToDepartmentId[$departmentCode] = (int) $departmentId;
                }
            }

            DB::table('cities')
                ->where('id', $city->id)
                ->update(['department_id' => $codeToDepartmentId[$departmentCode]]);
        }
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }

    private function extractDepartmentCode(string $postalCode): ?string
    {
        $normalized = strtoupper(trim($postalCode));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(97|98)\d{1,3}$/', $normalized) === 1) {
            return substr($normalized, 0, 3);
        }

        return substr($normalized, 0, min(2, strlen($normalized)));
    }
};
