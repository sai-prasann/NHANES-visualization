<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;
use App\Models\Dataset;
use App\Models\Data;


class VisualizationController extends Controller
{
    public function index()
    {
        $datasets = Dataset::where("is_available", 1)->get();
        $variables = Data::select("dataset_id", "variable_id")
                            ->distinct()
                            ->limit(100)
                            ->get();

        return view('visualization.index', ['datasets' => $datasets, 'variables' => $variables]);
    }

    public function showChart(Request $request)
    {
        dd($request);

        //Validate that the data to be plotted exists
        $validatedData = $request->validate([
            'dataset_id' => 'required|string',
            'variable_id' => 'required|string',
        ]);

        $chartName = $validatedData["variable_id"] . "Chart";
        $dataLabel = $validatedData["variable_id"] . " by SEQN";
        
        //Blood cadmium level mmol code LBDBCDSI
        //Blood lead level mmol code LBDBPBSI 
        //Blood mercury level mmol code LBDTHGSI
        //Suffered from stroke questionnaire MCQ160F
        // $structured_data2 = $this->getScatterData("LBDTHGSI");

        $structured_data1 = $this->getScatterData($validatedData["variable_id"]);
        $chart = Chartjs::build()
            ->name($chartName)
            ->type("scatter")
            ->size(["width" => 400, "height" => 200])
            ->datasets([
                [
                    "label" => $dataLabel,
                    "backgroundColor" => "rgba(38, 185, 154, 0.31)",
                    "borderColor" => "rgba(38, 185, 154, 0.7)",
                    "data" => $structured_data1
                ],
                // [   
                //     "label" => "Blood Mercury (umol/L) by SEQN",
                //     "backgroundColor" => "rgba(255, 99, 132, 0.31)",
                //     "borderColor" => "rgba(255, 99, 132, 0.7)",
                //     "data" => $structured_data2
                // ]
            ],
                )
            ->options([
                'aspect_ratio' => 1,
                'animation' => false,
                'hover' => false,
                'scales' => [
                    'x' => [
                        'type' => 'linear',
                        'position' => 'bottom'
                    ]
                ],
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $validatedData["variable_id"]
                    ]
                ]
            ]);

        return view("visualization.chart", compact("chart"));
    }

    public function getScatterData($variable_id) {
        $data = DB::table("data")
        ->select( "SEQN", "value")
        ->where("variable_id", "=", $variable_id)
        ->limit(500)
        ->get()
        ->toArray();

        $structuredData = array_map(function($x) {
            return ["x" => $x->SEQN, "y" => $x->value];
        }, $data);
        return $structuredData;
    }
}
//     private $excludedTables = [
//         'users', 'migrations', 'cache', 'cache_locks', 'datasets', 'failed_jobs', 'jobs', 
//         'job_batches', 'model_has_permissions', 'model_has_roles', 
//         'password_reset_tokens', 'permissions', 'personal_access_tokens', 
//         'roles', 'role_has_permissions', 'sessions'
//     ];

//     public function display(Request $request): View
//     {
//         $tables = $this->getAllTables();
//         $datasets = $this->getDatasetInfo();
//         $matchedTables = $this->matchTablesWithDatasets($tables, $datasets);

//         $selectedTables = $request->input('tables', []);
//         $rowLimit = (int) $request->input('rowVisualisationCount', 0);
//         $totalRecords = $selectedTables ? DB::table($selectedTables[0])->count() : 0;
        
//         $headers = $this->getHeadersForTables($selectedTables);
//         $tableRows = $this->getRandomRowsForTables($selectedTables, $rowLimit);

//         // Fetch header descriptions from the database
//         $headerDescriptions = $this->getHeaderDescriptions();

//         // Determine column types
//         $columnTypes = [];
//         foreach ($headers as $table => $columns) {
//             foreach ($columns as $column) {
//                 $columnTypes[$table][$column] = $this->determineColumnType($table, $column);
//             }
//         }

//         return view('visualization.display', compact(
//             'matchedTables',
//             'selectedTables',
//             'headers',
//             'headerDescriptions',
//             'tableRows',
//             'rowLimit',
//             'totalRecords',
//             'columnTypes'
//         ));
//     }

//     private function getHeaderDescriptions(): array
//     {
//         return DB::table('headers_description')
//             ->select(DB::raw('UPPER(doc_code) as doc_code'), 'description')
//             ->get()
//             ->keyBy('doc_code')
//             ->toArray();
//     }

//     public function getChartData(Request $request): JsonResponse
//     {
//         $data = $request->validate([
//             'selected_headers' => 'required|array',
//             'selected_rows' => 'required|array',
//             'chart_type' => 'required|string'
//         ]);

//         $selectedHeaders = $data['selected_headers'];
//         $selectedRows = $data['selected_rows'];
//         $chartType = $data['chart_type'];

//         $chartData = $this->generateChartData($selectedHeaders, $selectedRows, $chartType);
//         $stats = $this->calculateStatistics($selectedRows, $selectedHeaders);

//         return response()->json([
//             'chart' => $chartData,
//             'stats' => $stats,
//             'layout' => $chartData['layout'] ?? []
//         ]);
//     }

//     private function generateChartData(array $selectedHeaders, array $selectedRows, string $chartType): array
//     {
//         $xColumn = explode('.', $selectedHeaders[0])[1] ?? null;
//         $yColumn = explode('.', $selectedHeaders[1])[1] ?? null;

//         if (is_null($xColumn) || is_null($yColumn)) {
//             \Log::warning('Invalid column format', ['xColumn' => $xColumn, 'yColumn' => $yColumn]);
//             return ['error' => 'Invalid column format'];
//         }

//         $chartData = [
//             'type' => $chartType
//         ];
//         switch ($chartType) {
//             case "box":
//                 $chartData = [
//                     'type' => 'box',
//                     'x' => array_column($selectedRows, $xColumn),
//                     'y' => array_column($selectedRows, $yColumn),
//                 ];
//                 break;
//             case "bar":
//                 $chartData = [
//                     'type' => 'bar',
//                     'x' => array_column($selectedRows, $xColumn),
//                     'y' => array_column($selectedRows, $yColumn),
//                 ];
//                 break;
//             case "line":
//                 $chartData = [
//                     'type' => 'scatter',
//                     'mode'=> 'lines',
//                     'x' => array_column($selectedRows, $xColumn),
//                     'y' => array_column($selectedRows, $yColumn),
//                 ];
//                 break;
//             case "pie":
//                 $chartData = [
//                     'type' => 'pie',
//                     'labels' => array_column($selectedRows, $xColumn),
//                     'values' => array_column($selectedRows, $yColumn),
//                 ];
//                 break;
//             case "scatter":
//                 $chartData = [
//                     'type' => 'scatter',
//                     'mode' => 'markers',
//                     'x' => array_column($selectedRows, $xColumn),
//                     'y' => array_column($selectedRows, $yColumn),
//                 ];
//                 break;
//             default:
//                 $chartData = [];
//         }
//         return $chartData;
//     }

//     private function calculateStatistics(array $rows, array $headers): array
//     {
//         $xColumn = explode('.', $headers[0])[1] ?? null;
//         $yColumn = explode('.', $headers[1])[1] ?? null;

//         if (is_null($xColumn) || is_null($yColumn)) {
//             \Log::warning('Invalid column format for statistics', ['xColumn' => $xColumn, 'yColumn' => $yColumn]);
//             return ['error' => 'Invalid column format for statistics'];
//         }

//         $xValues = array_column($rows, $xColumn);
//         $yValues = array_column($rows, $yColumn);

//         return [
//             'mean' => $this->mean($yValues),
//             'median' => $this->median($yValues),
//             'stdDev' => $this->stdDev($yValues),
//             'correlation' => $this->correlation($xValues, $yValues),
//         ];
//     }

//     private function mean(array $values): float
//     {
//         return array_sum($values) / count($values);
//     }

//     private function median(array $values): float
//     {
//         sort($values);
//         $count = count($values);
//         $middle = floor($count / 2);

//         if ($count % 2) {
//             return $values[$middle];
//         }

//         return ($values[$middle - 1] + $values[$middle]) / 2;
//     }

//     private function stdDev(array $values): float
//     {
//         $mean = $this->mean($values);
//         $squaredDiffs = array_map(fn($value) => ($value - $mean) ** 2, $values);
//         $variance = array_sum($squaredDiffs) / count($values);
//         return sqrt($variance);
//     }

//     private function correlation(array $xValues, array $yValues): float
//     {
//         $n = count($xValues);
//         $sumX = array_sum($xValues);
//         $sumY = array_sum($yValues);
//         $sumX2 = array_sum(array_map(fn($x) => $x ** 2, $xValues));
//         $sumY2 = array_sum(array_map(fn($y) => $y ** 2, $yValues));
//         $sumXY = array_sum(array_map(fn($x, $y) => $x * $y, $xValues, $yValues));

//         $numerator = $n * $sumXY - $sumX * $sumY;
//         $denominator = sqrt(($n * $sumX2 - $sumX ** 2) * ($n * $sumY2 - $sumY ** 2));

//         return $denominator == 0 ? 0 : $numerator / $denominator;
//     }

//     private function getAllTables(): array
//     {
//         $tables = DB::select('SHOW TABLES');
//         $tableNames = array_map(fn($table) => reset($table), $tables);

//         return array_values(array_filter($tableNames, fn($tableName) => !in_array($tableName, $this->excludedTables)));
//     }

//     private function getDatasetInfo(): array
//     {
//         return DB::table('datasets')
//             ->select('data_url', 'description', 'years')
//             ->get()
//             ->toArray();
//     }

//     private function matchTablesWithDatasets(array $tables, array $datasets): array
//     {
//         $matchedTables = [];
//         foreach ($tables as $table) {
//             foreach ($datasets as $dataset) {
//                 if (stripos($dataset->data_url, $table) !== false) {
//                     $matchedTables[$table] = [
//                         'code' => $table,
//                         'description' => $dataset->description,
//                         'years' => $dataset->years
//                     ];
//                     break;
//                 }
//             }
//         }
//         return $matchedTables;
//     }

//     private function getHeadersForTables(array $tables): array
//     {
//         return array_reduce($tables, function($carry, $table) {
//             $carry[$table] = $this->getTableColumns($table);
//             return $carry;
//         }, []);
//     }

//     private function getTableColumns($table): array
//     {
//         return DB::getSchemaBuilder()->getColumnListing($table);
//     }

//     private function getRandomRowsForTables(array $tables, int $rowLimit): array
//     {   
//         return array_reduce($tables, function($carry, $table) use ($rowLimit) {
//             $carry[$table] = DB::table($table)
//                 ->inRandomOrder()
//                 ->limit($rowLimit)
//                 ->get()
//                 ->toArray();
//             return $carry;
//         }, []);
//     }

//     private function determineColumnType($table, $column)
//     {
//         $values = DB::table($table)->distinct()->pluck($column);
//         return $values->count() > 10 ? 'continuous' : 'discrete';
//     }

//     public function insights(Request $request): View
//     {
//         $selectedHeaders = $request->input('selected_headers', []);
//         $chartType = $request->input('chart_type', 'scatter');
//         $rowLimit = (int) $request->input('rowVisualisationCount', 0);
//         $selectedTables = $request->input('tables', []);

//         $headers = $this->getHeadersForTables($selectedTables);
//         $tableRows = $this->getRandomRowsForTables($selectedTables, $rowLimit);

//         $columnTypes = [];
//         foreach ($headers as $table => $columns) {
//             foreach ($columns as $column) {
//                 $columnTypes[$table][$column] = $this->determineColumnType($table, $column);
//             }
//         }

//         $stats = $this->calculateStatistics(
//             array_merge(...array_values($tableRows)),
//             $selectedHeaders
//         );

//         return view('visualization.insights', compact('selectedTables', 'selectedHeaders', 'rowLimit', 'stats', 'chartType'));
//     }
// }
