<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BuildGeoData extends Command
{
    protected $signature = 'geodata:build
                            {--path= : Chemin du JSON source (défaut: docs/baudry/geoData.json)}
                            {--output= : Chemin du JSON de sortie (défaut: docs/baudry/geoData.json)}
                            {--departments-output= : Export optionnel des départements JSON (ex: docs/departments.json)}';

    protected $description = 'Enrichit les villes avec le département et les informations d\'arrondissement, puis génère geoData.json.';

    private const ARRONDISSEMENT_PARENT_CITIES = [
        'PARIS',
        'LYON',
        'MARSEILLE',
    ];

    private const ARRONDISSEMENT_PARENT_DEPARTMENTS = [
        '75',
        '69',
        '13',
    ];

    private const DEPARTMENTS = [
        '01' => 'Ain',
        '02' => 'Aisne',
        '03' => 'Allier',
        '04' => 'Alpes-de-Haute-Provence',
        '05' => 'Hautes-Alpes',
        '06' => 'Alpes-Maritimes',
        '07' => 'Ardèche',
        '08' => 'Ardennes',
        '09' => 'Ariège',
        '10' => 'Aube',
        '11' => 'Aude',
        '12' => 'Aveyron',
        '13' => 'Bouches-du-Rhône',
        '14' => 'Calvados',
        '15' => 'Cantal',
        '16' => 'Charente',
        '17' => 'Charente-Maritime',
        '18' => 'Cher',
        '19' => 'Corrèze',
        '20' => 'Corse',
        '21' => 'Côte-d\'Or',
        '22' => 'Côtes-d\'Armor',
        '23' => 'Creuse',
        '24' => 'Dordogne',
        '25' => 'Doubs',
        '26' => 'Drôme',
        '27' => 'Eure',
        '28' => 'Eure-et-Loir',
        '29' => 'Finistère',
        '30' => 'Gard',
        '31' => 'Haute-Garonne',
        '32' => 'Gers',
        '33' => 'Gironde',
        '34' => 'Hérault',
        '35' => 'Ille-et-Vilaine',
        '36' => 'Indre',
        '37' => 'Indre-et-Loire',
        '38' => 'Isère',
        '39' => 'Jura',
        '40' => 'Landes',
        '41' => 'Loir-et-Cher',
        '42' => 'Loire',
        '43' => 'Haute-Loire',
        '44' => 'Loire-Atlantique',
        '45' => 'Loiret',
        '46' => 'Lot',
        '47' => 'Lot-et-Garonne',
        '48' => 'Lozère',
        '49' => 'Maine-et-Loire',
        '50' => 'Manche',
        '51' => 'Marne',
        '52' => 'Haute-Marne',
        '53' => 'Mayenne',
        '54' => 'Meurthe-et-Moselle',
        '55' => 'Meuse',
        '56' => 'Morbihan',
        '57' => 'Moselle',
        '58' => 'Nièvre',
        '59' => 'Nord',
        '60' => 'Oise',
        '61' => 'Orne',
        '62' => 'Pas-de-Calais',
        '63' => 'Puy-de-Dôme',
        '64' => 'Pyrénées-Atlantiques',
        '65' => 'Hautes-Pyrénées',
        '66' => 'Pyrénées-Orientales',
        '67' => 'Bas-Rhin',
        '68' => 'Haut-Rhin',
        '69' => 'Rhône',
        '70' => 'Haute-Saône',
        '71' => 'Saône-et-Loire',
        '72' => 'Sarthe',
        '73' => 'Savoie',
        '74' => 'Haute-Savoie',
        '75' => 'Paris',
        '76' => 'Seine-Maritime',
        '77' => 'Seine-et-Marne',
        '78' => 'Yvelines',
        '79' => 'Deux-Sèvres',
        '80' => 'Somme',
        '81' => 'Tarn',
        '82' => 'Tarn-et-Garonne',
        '83' => 'Var',
        '84' => 'Vaucluse',
        '85' => 'Vendée',
        '86' => 'Vienne',
        '87' => 'Haute-Vienne',
        '88' => 'Vosges',
        '89' => 'Yonne',
        '90' => 'Territoire de Belfort',
        '91' => 'Essonne',
        '92' => 'Hauts-de-Seine',
        '93' => 'Seine-Saint-Denis',
        '94' => 'Val-de-Marne',
        '95' => 'Val-d\'Oise',
        '971' => 'Guadeloupe',
        '972' => 'Martinique',
        '973' => 'Guyane',
        '974' => 'La Réunion',
        '976' => 'Mayotte',
        '977' => 'Saint-Barthélemy',
        '978' => 'Saint-Martin',
        '984' => 'Terres australes et antarctiques françaises',
        '986' => 'Wallis-et-Futuna',
        '987' => 'Polynésie française',
        '988' => 'Nouvelle-Calédonie',
    ];

    public function handle(): int
    {
        $departmentsOutputPath = $this->option('departments-output');

        if (is_string($departmentsOutputPath) && trim($departmentsOutputPath) !== '') {
            $this->writeDepartmentsFile($departmentsOutputPath);
        }

        $sourcePath = $this->option('path') ?: base_path('docs/baudry/geoData.json');
        $outputPath = $this->option('output') ?: base_path('docs/baudry/geoData.json');

        if (! file_exists($sourcePath)) {
            $this->error("Fichier source introuvable : {$sourcePath}");
            return self::FAILURE;
        }

        $this->info('Lecture des villes...');
        $rows = json_decode((string) file_get_contents($sourcePath), true);

        if (! is_array($rows)) {
            $this->error('Le JSON source est invalide.');
            return self::FAILURE;
        }

        $total = count($rows);
        $this->info("{$total} lignes détectées. Enrichissement en cours...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $result = [];

        foreach ($rows as $row) {
            $cityName = (string) ($row['ville'] ?? '');
            $postalCode = (string) ($row['code'] ?? '');

            $departmentCode = $this->extractDepartmentCode($postalCode);
            $departmentName = $departmentCode !== null
                ? (self::DEPARTMENTS[$departmentCode] ?? ('Département '.$departmentCode))
                : null;

            $arrondissementInfo = $this->detectArrondissement($cityName, $departmentCode);

            $result[] = [
                'ville' => $cityName,
                'code' => $postalCode,
                'lat' => $row['lat'] ?? null,
                'lng' => $row['lng'] ?? null,
                'department_code' => $departmentCode,
                'department_name' => $departmentName,
                'is_arrondissement_city' => $arrondissementInfo['is_arrondissement_city'],
                'arrondissement_parent_city' => $arrondissementInfo['arrondissement_parent_city'],
                'arrondissement_number' => $arrondissementInfo['arrondissement_number'],
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $outputPath,
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->info("geoData généré : {$outputPath}");
        return self::SUCCESS;
    }

    private function writeDepartmentsFile(string $path): void
    {
        $rows = [];

        foreach (self::DEPARTMENTS as $code => $name) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ];
        }

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $path,
            json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->info("Départements exportés : {$path}");
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

    /**
     * Double check arrondissement:
     * 1) format du nom: "Paris 02", "Lyon 3", "Marseille 1er"
     * 2) cohérence département: 75, 69, 13
     */
    private function detectArrondissement(string $cityName, ?string $departmentCode): array
    {
        $normalizedName = strtoupper(trim($cityName));

        $match = [];
        $hasNumberedPattern = preg_match('/^([\p{L}\-\'\s]+?)\s+0?(\d{1,2})(?:ER)?$/iu', $cityName, $match) === 1;

        if (! $hasNumberedPattern) {
            return [
                'is_arrondissement_city' => false,
                'arrondissement_parent_city' => null,
                'arrondissement_number' => null,
            ];
        }

        $parentCity = strtoupper(trim($match[1]));
        $arrNumber = (int) $match[2];

        $isKnownParent = in_array($parentCity, self::ARRONDISSEMENT_PARENT_CITIES, true);
        $isKnownDepartment = $departmentCode !== null
            && in_array($departmentCode, self::ARRONDISSEMENT_PARENT_DEPARTMENTS, true);

        if (! $isKnownParent && ! $isKnownDepartment) {
            return [
                'is_arrondissement_city' => false,
                'arrondissement_parent_city' => null,
                'arrondissement_number' => null,
            ];
        }

        if ($arrNumber < 1 || $arrNumber > 20) {
            return [
                'is_arrondissement_city' => false,
                'arrondissement_parent_city' => null,
                'arrondissement_number' => null,
            ];
        }

        return [
            'is_arrondissement_city' => true,
            'arrondissement_parent_city' => $this->formatParentCityLabel($parentCity),
            'arrondissement_number' => $arrNumber,
        ];
    }

    private function formatParentCityLabel(string $name): string
    {
        $name = strtoupper($name);
        return ucfirst(strtolower($name));
    }
}
