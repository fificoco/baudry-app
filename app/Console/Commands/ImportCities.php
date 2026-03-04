<?php

namespace App\Console\Commands;

use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCities extends Command
{
    protected $signature = 'cities:import
                            {--path= : Chemin vers le fichier JSON (défaut: docs/villes.json)}
                            {--fresh : Vider la table avant import}';

    protected $description = 'Importe les villes depuis le fichier JSON (docs/villes.json) vers la table cities.';

    public function handle(): int
    {
        $path = $this->option('path') ?? base_path('docs/villes.json');

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

        foreach ($chunks as $chunk) {
            $rows = array_map(fn($v) => [
                'name'        => $v['ville'],
                'postal_code' => $v['code'],
                'lat'         => $v['lat'] ?? null,
                'lng'         => $v['lng'] ?? null,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $chunk);

            // Ignore les doublons (même name + postal_code)
            City::upsert($rows, ['name', 'postal_code'], ['lat', 'lng', 'updated_at']);

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import terminé — $total villes traitées.");

        return self::SUCCESS;
    }
}
