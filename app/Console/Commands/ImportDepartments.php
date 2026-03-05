<?php

namespace App\Console\Commands;

use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDepartments extends Command
{
    protected $signature = 'departments:import
                            {--path= : Chemin vers le fichier JSON (défaut: docs/departments.json)}
                            {--fresh : Vider la table avant import}';

    protected $description = 'Importe les départements depuis docs/departments.json vers la table departments.';

    public function handle(): int
    {
        $path = $this->resolveImportPath($this->option('path'));

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows)) {
            $this->error('Le JSON est invalide.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Vidage de la table departments...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Department::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $payload[] = [
                'code' => $code,
                'name' => $name,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->warn('Aucune ligne valide à importer.');
            return self::SUCCESS;
        }

        Department::upsert($payload, ['code'], ['name', 'is_active', 'updated_at']);

        $this->info(count($payload).' départements importés/actualisés depuis '.$path);

        return self::SUCCESS;
    }

    private function resolveImportPath(?string $optionPath): string
    {
        if (is_string($optionPath) && trim($optionPath) !== '') {
            return $optionPath;
        }

        return base_path('docs/departments.json');
    }
}
