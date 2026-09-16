<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use App\Helpers\ArrayHelpers;
use App\Models\Data;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('datasets');

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('years', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('component', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('code', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply filter for component
        if ($request->has('component')) {
            $component = $request->input('component');
            if ($component === 'demographics') {
                $query->where('component', "demographics");
            } elseif ($component === 'dietary') {
                $query->where('component', 'dietary');
            } elseif ($component === 'examination') {
                $query->where('component', 'examination');
            } elseif ($component === 'laboratory') {
                $query->where('component', 'laboratory');
            } elseif ($component === 'questionnaire') {
                $query->where('component', 'questionnaire');
            }
        }

        // Apply filter for availability
        if ($request->has('availability')) {
            $availability = $request->input('availability');
            if ($availability === 'available') {
                $query->where('is_available', true);
            } elseif ($availability === 'not-available') {
                $query->where('is_available', false);
            }
        }

        $datasets = $query->paginate(10)->appends($request->except('page'));

        return view('dashboard', compact('datasets'));
    }

    public function updateAvailability(Request $request, $id)
    {
        $request->validate([
            'is_available' => 'required|boolean',
        ]);

        DB::table('datasets')
            ->where('id', $id)
            ->update(['is_available' => $request->is_available]);

        return redirect()->route('dashboard')->with('success', 'Availability status updated successfully.');
    }

    public function downloadAndConvert($id)
    {
        Log::info("Starting downloadAndConvert for dataset ID: $id");

        $dataset = DB::table('datasets')->find($id);

        try {
            $pythonScript = base_path('scripts/xpt_to_sql.py');

            if (!file_exists($pythonScript)) {
                throw new \Exception('XPT to SQL conversion script not found.');
            }

            $command = 'cd ' . escapeshellarg(storage_path('app'))
                . ' && python ' . escapeshellarg($pythonScript)
                . ' ' . escapeshellarg($dataset->data_url) . ' 2>&1';
            exec($command, $output, $returnVar);

            $fullOutput = implode("\n", $output);

            if ($returnVar !== 0) {
                throw new \Exception('Failed to process dataset: ' . $fullOutput);
            }

           $filepath = storage_path('app/dataset.csv');

            $generate_row = function($id, $row) {
                return [
                    'dataset_id' => $id,
                    'SEQN' => $row[0],
                    "variable_id" => $row[1],
                    "value" => $row[2]
                ];
            };

            # Set SQL session to non strict
            DB::statement("SET SESSION sql_mode=''");
            DB::statement("SET FOREIGN_KEY_CHECKS=0");
            DB::statement("ALTER TABLE data DISABLE KEYS");
            foreach (ArrayHelpers::chunk_file($filepath, $id, $generate_row, 1000) as $chunk) {
                Data::insert($chunk);
            }
            DB::statement("ALTER TABLE data ENABLE KEYS");
            DB::statement("SET FOREIGN_KEY_CHECKS=1");

            # Remove csv file
            File::delete($filepath);

            DB::table('datasets')
            ->where('id', $id)
            ->update(['is_available' => true]);

            return response()->json(['success' => 'Dataset processed and uploaded successfully.']);
        } catch (\Exception $e) {

            // Mark dataset as not available in case of failure
            DB::table('datasets')
                ->where('id', $id)
                ->update(['is_available' => false]);

            return response()->json(['error' => 'Failed to process the dataset: ' . $e->getMessage()], 500);
        }
    }
}
