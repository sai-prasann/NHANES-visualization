<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Seeder;
use App\Models\Dataset;

class DatasetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pythonScript = base_path ('scripts/scraper.py');
        $command = "python \"{$pythonScript}\" 2>&1";
        exec("python scripts/scraper.py", $output, $returnVar);
        // Check if there was an error in executing the command
        if ($returnVar !== 0) {
            Log::error(message: "Error executing Python script: " . implode("\n", $output));
            echo 'scraper.py FAILED output:' . PHP_EOL . implode(PHP_EOL, $output);
        }

        $datasets = json_decode($output[0], true);
        for ($i = 0; $i < count($datasets); $i++) {
            $d = new Dataset;
            $d->id = $datasets[$i]['id'];
            $d->years = $datasets[$i]['years'];
            $d->component = $datasets[$i]['component'];
            $d->description = $datasets[$i]['description'];
            $d->docs_url = $datasets[$i]['docs_url'];
            $d->data_url = $datasets[$i]['data_url'];
            $d->is_available = $datasets[$i]['is_available'];
            $d->code = $datasets[$i]['code'];
            $d->save();
        }
    }
}
