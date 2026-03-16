<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\CityCoordinateCorrection;
use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCities extends Command
{
    protected $signature = 'cities:import
                            {--path= : Chemin vers le fichier JSON ou CSV (défaut: docs/communes_hameaux_france.csv)}
                            {--communes-only : Importer uniquement les lignes CSV de type commune}
                            {--fresh : Vider la table avant import}';

    protected $description = 'Importe les villes depuis un fichier JSON (geoData) ou CSV (communes/hameaux) vers la table cities.';

    public function handle(): int
    {
        $path = $this->resolveImportPath($this->option('path'));

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Vidage des tables cities et city_coordinate_corrections...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            CityCoordinateCorrection::truncate();
            City::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $now       = now();
        $departmentIdByCode = Department::query()->pluck('id', 'code')->all();

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $total = $this->importFromCsv($path, $now, $departmentIdByCode);
            $this->info("Import terminé — $total lignes CSV traitées.");
            return self::SUCCESS;
        }

        $total = $this->importFromJson($path, $now, $departmentIdByCode);
        $this->info("Import terminé — $total villes traitées.");

        return self::SUCCESS;
    }

    private function importFromJson(string $path, $now, array &$departmentIdByCode): int
    {
        $this->info("Lecture du fichier JSON...");
        $json = file_get_contents($path);
        $villes = json_decode((string) $json, true);

        if (! is_array($villes)) {
            $this->error('Le JSON est invalide ou vide.');
            return 0;
        }

        $total = count($villes);
        $this->info("$total villes détectées. Import en cours...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $chunkSize = 500;
        $chunks = array_chunk($villes, $chunkSize);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $v) {
                $row = $this->buildUpsertRow(
                    (string) ($v['ville'] ?? ''),
                    (string) ($v['code'] ?? ''),
                    $v['lat'] ?? null,
                    $v['lng'] ?? null,
                    (string) ($v['department_code'] ?? ''),
                    (string) ($v['department_name'] ?? ''),
                    $now,
                    $departmentIdByCode
                );

                if ($row !== null) {
                    $rows[] = $row;
                }
            }

            if ($rows !== []) {
                City::upsert($rows, ['name', 'postal_code'], ['department_id', 'lat', 'lng', 'updated_at']);
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();

        return $total;
    }

    private function importFromCsv(string $path, $now, array &$departmentIdByCode): int
    {
        $this->info("Lecture du fichier CSV...");
        $communesOnly = (bool) $this->option('communes-only');

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error("Impossible d'ouvrir le fichier CSV : {$path}");
            return 0;
        }

        $headers = fgetcsv($handle, 0, ';');
        if (! is_array($headers)) {
            fclose($handle);
            $this->error('Le CSV ne contient pas d\'en-têtes valides.');
            return 0;
        }

        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[trim((string) $header)] = $index;
        }

        $required = ['nom_standard', 'code_postal'];
        foreach ($required as $requiredHeader) {
            if (! array_key_exists($requiredHeader, $headerMap)) {
                fclose($handle);
                $this->error("En-tête CSV manquant : {$requiredHeader}");
                return 0;
            }
        }

        if ($communesOnly && ! array_key_exists('place', $headerMap)) {
            fclose($handle);
            $this->error("En-tête CSV manquant : place (requis pour filtrer les communes)");
            return 0;
        }

        $chunkSize = 500;
        $buffer = [];
        $processed = 0;
        $skippedNonCommunes = 0;

        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if ($communesOnly) {
                $place = mb_strtolower($this->csvValue($line, $headerMap, 'place'));
                if ($place !== 'commune') {
                    $skippedNonCommunes++;
                    continue;
                }
            }

            $name = $this->csvValue($line, $headerMap, 'nom_standard');
            $postalCode = $this->csvValue($line, $headerMap, 'code_postal');
            $lat = $this->csvFloat($line, $headerMap, 'latitude_mairie');
            $lng = $this->csvFloat($line, $headerMap, 'longitude_mairie');
            $departmentCode = $this->csvValue($line, $headerMap, 'dep_code');
            $departmentName = $this->csvValue($line, $headerMap, 'dep_nom');

            $row = $this->buildUpsertRow(
                $name,
                $postalCode,
                $lat,
                $lng,
                $departmentCode,
                $departmentName,
                $now,
                $departmentIdByCode
            );

            if ($row === null) {
                continue;
            }

            $buffer[] = $row;

            if (count($buffer) >= $chunkSize) {
                City::upsert($buffer, ['name', 'postal_code'], ['department_id', 'lat', 'lng', 'updated_at']);
                $processed += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            City::upsert($buffer, ['name', 'postal_code'], ['department_id', 'lat', 'lng', 'updated_at']);
            $processed += count($buffer);
        }

        fclose($handle);

        if ($communesOnly) {
            $this->info("Lignes non-communes ignorées : {$skippedNonCommunes}");
        }

        return $processed;
    }

    private function buildUpsertRow(
        string $name,
        string $postalCode,
        mixed $lat,
        mixed $lng,
        string $departmentCode,
        string $departmentName,
        $now,
        array &$departmentIdByCode
    ): ?array {
        $name = $this->normalizeCityName($name);
        $postalCode = trim($postalCode);

        if ($name === '' || $postalCode === '') {
            return null;
        }

        $normalizedDepartmentCode = $this->normalizeDepartmentCode($departmentCode)
            ?? $this->extractDepartmentCode($postalCode);
        $normalizedDepartmentName = $this->normalizeDepartmentName($departmentName);
        $departmentId = null;

        if ($normalizedDepartmentCode) {
            if (! array_key_exists($normalizedDepartmentCode, $departmentIdByCode)) {
                $department = Department::query()->create([
                    'code' => $normalizedDepartmentCode,
                    'name' => $normalizedDepartmentName ?? ('Département '.$normalizedDepartmentCode),
                    'is_active' => true,
                ]);

                $departmentIdByCode[$normalizedDepartmentCode] = $department->id;
            }

            if ($normalizedDepartmentName !== null) {
                Department::query()
                    ->where('id', $departmentIdByCode[$normalizedDepartmentCode])
                    ->where('name', '!=', $normalizedDepartmentName)
                    ->update(['name' => $normalizedDepartmentName]);
            }

            $departmentId = $departmentIdByCode[$normalizedDepartmentCode];
        }

        return [
            'name' => $name,
            'postal_code' => $postalCode,
            'department_id' => $departmentId,
            'lat' => $lat,
            'lng' => $lng,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function normalizeCityName(string $name): string
    {
        $normalized = str_replace('-', ' ', trim($name));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function csvValue(array $line, array $headerMap, string $header): string
    {
        if (! array_key_exists($header, $headerMap)) {
            return '';
        }

        return trim((string) ($line[$headerMap[$header]] ?? ''));
    }

    private function csvFloat(array $line, array $headerMap, string $header): ?float
    {
        $value = $this->csvValue($line, $headerMap, $header);

        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function resolveImportPath(?string $optionPath): string
    {
        if (is_string($optionPath) && trim($optionPath) !== '') {
            return $optionPath;
        }

        return base_path('docs/communes_hameaux_france.csv');
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
