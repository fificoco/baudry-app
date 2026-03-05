<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCities extends Command
{
    protected $signature = 'cities:import
                            {--path= : Chemin vers le fichier JSON (défaut: docs/geoData.json)}
                            {--fresh : Vider la table avant import}';

    protected $description = 'Importe les villes depuis le fichier JSON (docs/geoData.json) vers la table cities.';

    public function handle(): int
    {
        $path = $this->resolveImportPath($this->option('path'));

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Vidage de la table cities...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            City::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info("Lecture du fichier JSON...");
        $json    = file_get_contents($path);
        $villes  = json_decode($json, true);
        $total   = count($villes);

        $this->info("$total villes détectées. Import en cours...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $chunkSize = 500;
        $chunks    = array_chunk($villes, $chunkSize);
        $now       = now();
        $departmentIdByCode = Department::query()->pluck('id', 'code')->all();

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($v) use ($now, &$departmentIdByCode) {
                $postalCode = (string) $v['code'];
                $departmentCode = $this->normalizeDepartmentCode((string) ($v['department_code'] ?? ''))
                    ?? $this->extractDepartmentCode($postalCode);
                $departmentName = $this->normalizeDepartmentName((string) ($v['department_name'] ?? ''));
                $departmentId = null;

                if ($departmentCode) {
                    if (! array_key_exists($departmentCode, $departmentIdByCode)) {
                        $department = Department::query()->create([
                            'code' => $departmentCode,
                            'name' => $departmentName ?? ('Département '.$departmentCode),
                            'is_active' => true,
                        ]);

                        $departmentIdByCode[$departmentCode] = $department->id;
                    }

                    if ($departmentName !== null) {
                        Department::query()
                            ->where('id', $departmentIdByCode[$departmentCode])
                            ->where('name', '!=', $departmentName)
                            ->update(['name' => $departmentName]);
                    }

                    $departmentId = $departmentIdByCode[$departmentCode];
                }

                return [
                    'name'        => $v['ville'],
                    'postal_code' => $postalCode,
                    'department_id' => $departmentId,
                    'lat'         => $v['lat'] ?? null,
                    'lng'         => $v['lng'] ?? null,
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }, $chunk);

            // Ignore les doublons (même name + postal_code)
            City::upsert($rows, ['name', 'postal_code'], ['department_id', 'lat', 'lng', 'updated_at']);

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import terminé — $total villes traitées.");

        return self::SUCCESS;
    }

    private function resolveImportPath(?string $optionPath): string
    {
        if (is_string($optionPath) && trim($optionPath) !== '') {
            return $optionPath;
        }

        $preferred = base_path('docs/geoData.json');
        if (file_exists($preferred)) {
            return $preferred;
        }

        $legacy = base_path('docs/villes.json');
        if (file_exists($legacy)) {
            $this->warn('Fichier docs/geoData.json absent. Fallback sur docs/villes.json.');
            return $legacy;
        }

        return $preferred;
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

    private function normalizeDepartmentCode(string $departmentCode): ?string
    {
        $normalized = strtoupper(trim($departmentCode));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeDepartmentName(string $departmentName): ?string
    {
        $normalized = trim($departmentName);

        return $normalized !== '' ? $normalized : null;
    }
}
