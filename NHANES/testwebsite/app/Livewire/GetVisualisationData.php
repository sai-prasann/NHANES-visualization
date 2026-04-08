<?php

namespace App\Livewire;


use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Data;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;



class GetVisualisationData extends Component
{

    public $variables = []; // all variable_ids (distinct)

    #[Url]
    public $varX = '';          // first variable (used as X / categories)

    #[Url]
    public $varY = '';          // second variable (used as Y numeric when needed)

    // chart UI inputs
    #[Url]
    public $chartMode = '';     

    public bool $showInsights = false;

    // chart payload
    public $chartType = null;      
    public $chartLabels = [];
    public $chartSeries = [];
    public $chartTitle = '';
    public $insightText = '';

    public function mount($chartMode = null, $varX = null, $varY = null)
    {
        
        ini_set('max_execution_time', 600);

        $this->variables = Data::select('variable_id')
            ->distinct()
            ->orderBy('variable_id')
            ->pluck('variable_id')
            ->toArray();

        // Auto-render visualization if params are provided
        $this->autoVisualizeIfReady();
    }

    public function resetVisualization()
    {
       
        $this->varX = '';
        $this->varY = '';
        $this->chartMode = '';

        // clear chart state
        $this->chartType = null;
        $this->chartLabels = [];
        $this->chartSeries = [];
        $this->chartTitle = '';
        $this->insightText = '';
    }
    private function resetInsights(): void
    {
        $this->showInsights = false;
        $this->insightText = '';
    }
    private function resetVisualizationNow(): void
    {
        $this->reset(['chartType','chartLabels','chartSeries','chartTitle']);
        $this->dispatch('render-chart', null); // JS will clear canvas
    }



    /* =========================================================
     |  Auto-render hooks
     |  (fire when inputs change; call visualize if ready)
     * =======================================================*/
    public function updatedVarX($value): void
    {
        $this->resetErrorBag('vars');
        $this->resetInsights();
        $this->resetVisualizationNow();
        $this->autoVisualizeIfReady();
    }

    public function updatedVarY($value): void
    {
        $this->resetErrorBag('vars');
        $this->resetInsights();
        $this->resetVisualizationNow();
        $this->autoVisualizeIfReady();
    }

    public function updatedChartMode($value): void
    {
        $this->resetErrorBag('vars');
        $this->resetInsights();
        $this->resetVisualizationNow();
        $this->autoVisualizeIfReady();
        $this->showInsights = false;
    }

    /** Decide if we have enough inputs for the selected chart mode */
    protected function hasMinimumInputs(): bool
    {
        switch ($this->chartMode) {
            case 'scatter':
            case 'line-binned-mean':
            case 'box':
            case 'violin':
            case 'stacked-bar':
                // need X and Y
                return filled($this->varX) && filled($this->varY);

            case 'bar-count':
            case 'histogram':
            case 'pie':
                // need only X
                return filled($this->varX);

            default:
                return false;
        }
    }

    /** Attempt to render immediately if inputs are sufficient */
    protected function autoVisualizeIfReady(): void
    {
        if ($this->hasMinimumInputs()) {
            try {
                $this->visualize();
            } catch (\Throwable $e) {
                logger()->error('viz:auto_visualize_error', ['msg' => $e->getMessage()]);
            }
        } else {
            // Clear the last chart if user removed a required input
            $this->reset(['chartType','chartLabels','chartSeries','chartTitle']);
        }
    }

    // Visualize logic
    public function visualize()
    {
        $this->reset(['chartType','chartLabels','chartSeries','chartTitle']);

        // Common validations
        if (!$this->varX) {
            $this->addError('vars', 'Please select Variable X.');
            return;
        }

        // Map chartMode to handler
        switch ($this->chartMode) {
            case 'scatter':           return $this->buildScatter();
            case 'line-binned-mean':  return $this->buildLineBinnedMean();
            case 'bar-count':         return $this->buildBarCategoryCounts();
            case 'histogram':         return $this->buildHistogram();
            case 'pie':               return $this->buildPie();
            case 'box':               return $this->buildBoxPlot();
            case 'violin':            return $this->buildViolinPlot();
            case 'stacked-bar':       return $this->buildStackedBarChart();
            default:
                $this->addError('vars', 'Unknown chart mode.');
                return;
        }
    }

    public function getInsights()
    {
        if (empty($this->chartSeries) || !is_array($this->chartSeries[0]) || count($this->chartSeries[0]) === 0) {
            $this->insightText = 'No chart data available for insights.';
            $this->showInsights = false;
            return;
        }
        
        $this->insightText = $this->generateInsight($this->chartType, $this->chartLabels, $this->chartSeries);
        $this->showInsights = true;
    }

    protected function generateInsight($type, $labels, $series): string
    {
        if (!$this->varX || !$this->chartSeries) {
            return "No insight available.";
        }
        $xCat = $this->isCategorical($this->varX);
        $yCat = $this->varY ? $this->isCategorical($this->varY) : null;
        if ($xCat && $yCat) {
            return $this->buildCategoricalVsCategoricalInsight();
        } elseif ($xCat && !$yCat) {
            return $this->buildCategoryWiseNumericInsight();
        } elseif (!$xCat && !$yCat) {
            return $this->buildCorrelationInsight();
        }
        return "Insight not available for this variable combination.";
    }

    protected function computeChiSquarePValue(array $groups): float
    {

       // Build contingency table
       $xCats = array_keys($groups);
       $yCatsAll = [];
       foreach ($groups as $xc => $ycounts) {
            foreach ($ycounts as $yc => $cnt) {
                if (!in_array($yc, $yCatsAll)) {
                    $yCatsAll[] = $yc;
                }
            }
        }
        sort($yCatsAll);

        // Fill observed matrix
        $observed = [];
        foreach ($xCats as $xc) {
            $row = [];
            foreach ($yCatsAll as $yc) {
                $row[] = $groups[$xc][$yc] ?? 0;
            }
            $observed[] = $row;
        }

        // Compute Chi-square statistic + p-value
        [$chi2, $p] = $this->chiSquareFromObserved($observed);
        return $p;
    }

    protected function chiSquareFromObserved(array $obs): array
    {
        // Using Pearson's chi-square test

        $rows = count($obs);
        $cols = count($obs[0] ?? []);
        $rowTotals = array_fill(0, $rows, 0);
        $colTotals = array_fill(0, $cols, 0);
        $total = 0;

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $rowTotals[$i] += $obs[$i][$j];
                $colTotals[$j] += $obs[$i][$j];
                $total += $obs[$i][$j];
            }
        }

        // Compute expected
        $expected = [];
        for ($i = 0; $i < $rows; $i++) {
            $expected[$i] = [];
            for ($j = 0; $j < $cols; $j++) {
                $expected[$i][$j] = ($rowTotals[$i] * $colTotals[$j]) / max(1, $total);
            }
        }

        // Compute chi2
        $chi2 = 0.0;
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $o = $obs[$i][$j];
                $e = $expected[$i][$j];
                if ($e > 0) {
                    $chi2 += (($o - $e) ** 2) / $e;
                }
            }
        }

        // Degrees of freedom
        $df = ($rows - 1) * ($cols - 1);

        // compute p-value from chi2 distribution
        $p = $this->chiSquaredPValue($chi2, $df);

        return [$chi2, $p];
    }

    protected function chiSquaredPValue($chi2, $df): float
    {
        // Using the chi-square survival function approximation (upper tail probability)
        if ($chi2 <= 0 || $df < 1) return 1.0;

        // Compute using the regularized incomplete gamma function approximation
        $k = $df * 0.5;
        $x = $chi2 * 0.5;

        $sum = $term = exp(-$x);
        for ($i = 1; $i < 100; $i++) {
            $term *= $x / ($k + $i);
            $sum += $term;
            if ($term < 1e-10) break;
        }

        $gamma = $this->gamma($k);

        return min(1.0, 1.0 - ($sum * pow($x, $k - 1) / $gamma));
    }

    protected function gamma($z)
    {
        // Lanczos approximation for the gamma function
        $g = 7;
        $p = [
            0.99999999999980993, 676.5203681218851, -1259.1392167224028,
            771.32342877765313, -176.61502916214059, 12.507343278686905,
            -0.13857109526572012, 9.9843695780195716e-6, 1.5056327351493116e-7
        ];

        if ($z < 0.5) {
            return M_PI / (sin(M_PI * $z) * $this->gamma(1 - $z));
        }

        $z -= 1;
        $x = $p[0];
        for ($i = 1; $i < count($p); $i++) {
            $x += $p[$i] / ($z + $i);
        }

        $t = $z + $g + 0.5;
        return sqrt(2 * M_PI) * pow($t, $z + 0.5) * exp(-$t) * $x;
    }

    protected function computeOddsRatio(array $groups): array
    {
        $results = [];

        // helper to calculate OR for any two groups
        $calc = function($g1, $g2) use ($groups) {
            if (!isset($groups[$g1], $groups[$g2])) {
                return null;
            }

            $a = $groups[$g1]['Cancer'] ?? 0;
            $b = $groups[$g1]['No Cancer'] ?? 0;
            $c = $groups[$g2]['Cancer'] ?? 0;
            $d = $groups[$g2]['No Cancer'] ?? 0;

            if ($a <= 0 || $b <= 0 || $c <= 0 || $d <= 0) {
                return null; // avoid division by zero or empty groups
            }

            // Odds Ratio
            $or = ($a * $d) / ($b * $c);

            // Standard Error of log(OR)
            $seLogOR = sqrt((1/$a) + (1/$b) + (1/$c) + (1/$d));
            $z = 1.96; // for 95% CI
            $logOR = log($or);

            // Confidence Interval
            $ciLower = exp($logOR - $z * $seLogOR);
            $ciUpper = exp($logOR + $z * $seLogOR);

            return [
                'or' => $or,
                'ci' => [$ciLower, $ciUpper]
            ];
        };

        
        $results["Smoker vs Non Smoker"]   = $calc('Smoker', 'Non Smoker');
        $results["Smoker vs Don't know"]   = $calc('Smoker', "Don't know");
        $results["Non Smoker vs Don't know"] = $calc('Non Smoker', "Don't know");

        return $results;
    }

    protected function buildCategoricalVsCategoricalInsight(): string
    {
        $x = Data::where('variable_id', $this->varX)->pluck('value', 'SEQN');
        $y = Data::where('variable_id', $this->varY)->pluck('value', 'SEQN');
        
        $groups = [];
        foreach ($x as $seqn => $xval) {
            if (!isset($y[$seqn])) continue;

            $xLabel = $this->mapLabel($this->varX, $xval);

            $yval = $y[$seqn];
            $yLabel = $this->mapLabel($this->varY, $yval);

            $groups[$xLabel][$yLabel] ??= 0;
            $groups[$xLabel][$yLabel]++;
        }

        $p = $this->computeChiSquarePValue($groups);
        $pFormatted = number_format($p, 4);

        $insight = "Chi-square Test p-value: {$pFormatted}\n\n";
        foreach ($groups as $xval => $yCounts) {
            $total = array_sum($yCounts);
            $insight .= "Group '{$xval}' (N={$total}):\n";
            foreach ($yCounts as $yval => $count) {
                $pct = round(($count / $total) * 100, 1);
                $insight .= "• {$yval}: {$count} ({$pct}%)\n";
            }
            $insight .= "\n";
        }

        $ors = $this->computeOddsRatio($groups);
        if (!empty($ors)) {
            $insight .= "\nOdds Ratios:\n";
            foreach ($ors as $label => $res) {
                if ($res === null) {
                    $insight .= "• {$label}: Undefined (not enough data)\n";
                } else {
                    $or = number_format($res['or'], 2);
                    $ciLow = number_format($res['ci'][0], 2);
                    $ciHigh = number_format($res['ci'][1], 2);

                    $insight .= "• {$label}: OR = {$or} (95% CI: {$ciLow}–{$ciHigh})";

                    [$g1, $g2] = explode(" vs ", $label);
                    if ($res['or'] > 1) {
                        $insight .= " → {$g1} has higher odds of cancer.\n";
                    } elseif ($res['or'] < 1) {
                        $insight .= " → {$g2} has higher odds of cancer.\n";
                    } else {
                        $insight .= " → Both groups have the same odds.\n";
                    }
                }
            }
        }
        return $insight;
    }

    protected function buildCategoryWiseNumericInsight(): string
    {
        $x = Data::where('variable_id', $this->varX)->pluck('value', 'SEQN');
        $y = Data::where('variable_id', $this->varY)->pluck('value', 'SEQN');
        $groups = [];
        foreach ($x as $seqn => $cat) {
            if (!isset($y[$seqn]) || !is_numeric($y[$seqn])) continue;
            $groups[$cat][] = (float) $y[$seqn];
        }
        $out = "";
        foreach ($groups as $cat => $nums) {
            $mean = round(array_sum($nums)/count($nums), 2);
            $out .= "Group '{$cat}': Mean {$this->varY} = {$mean}\n";
        }
        return $out;
    }
    
    protected function buildCorrelationInsight(): string
    {
        $x = Data::where('variable_id', $this->varX)->pluck('value', 'SEQN');
        $y = Data::where('variable_id', $this->varY)->pluck('value', 'SEQN');
        $xArr = []; $yArr = [];
        foreach ($x as $seqn => $vx) {
            if (!isset($y[$seqn]) || !is_numeric($vx) || !is_numeric($y[$seqn])) continue;
            $xArr[] = (float) $vx;
            $yArr[] = (float) $y[$seqn];
        }
        if (count($xArr) < 10) return "Not enough data to assess correlation.";
        $r = $this->pearsonCorrelation($xArr, $yArr);
        $roundedR = round($r, 3);

        $isBMIvsGlucose = (
            in_array($this->varX, ['BMXBMI', 'LBXGLU']) &&
            in_array($this->varY, ['BMXBMI', 'LBXGLU'])
        );

        $insight = "Pearson Correlation between {$this->varX} and {$this->varY}: {$roundedR}\n\n";

        if ($isBMIvsGlucose) {
            // Determine which is BMI and which is Glucose
            $bmiData = $this->varX === 'BMXBMI' ? $x : $y;
            $glucoseData = $this->varX === 'BMXBMI' ? $y : $x;

            $obeseCount = 0;
            $obeseWithDiabetes = 0;

            foreach ($bmiData as $seqn => $bmi) {
                $glucose = $glucoseData[$seqn] ?? null;
                if (!is_numeric($bmi) || !is_numeric($glucose)) continue;

                if ((float) $bmi >= 30) {
                    $obeseCount++;
                    if ((float) $glucose >= 126) {
                        $obeseWithDiabetes++;
                    }
                }
            }

            $rate = $obeseCount > 0 ? round(($obeseWithDiabetes / $obeseCount) * 100, 1) : 0;

            $insight .= <<<TEXT
                BMI Categories (BMXBMI):
                - Underweight: < 18.5
                - Normal: 18.5-24.9
                - Overweight: 25-29.9
                - Obese: ≥ 30

                Fasting Glucose (LBXGLU) Levels (mg/dL):
                - Normal: < 100
                - Prediabetes: 100-125
                - Diabetes: ≥ 126

                Among obese participants, approx. {$rate}% have glucose levels in the diabetic range.
                TEXT;
        }
        return $insight;
    }


    /** ---------- SCATTER (X vs Y) ---------- */
    protected function buildScatter(): void
    {
        if (!$this->varX || !$this->varY) {
            $this->addError('vars', 'Scatter needs both X and Y (numeric).');
            return;
        }
        // One query for both variables, keyed by SEQN
        $rows = Data::select('SEQN', 'variable_id', 'value')
        ->whereIn('variable_id', [$this->varX, $this->varY])
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN'); // SEQN => Collection of rows (X and/or Y)
        
        $points = [];
        foreach ($rows as $seqn => $group) {
            // get X and Y values within this participant
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = optional($group->firstWhere('variable_id', $this->varY))->value;
            
            if (is_numeric($vx) && is_numeric($vy)) {
                $points[] = ['x' => (float) $vx, 'y' => (float) $vy];
            }
        }
        logger('viz:fetched', [
            'mode' => 'scatter',
            'varX' => $this->varX, 'varY' => $this->varY,
            'seqn_groups' => $rows->count(),
            'points' => count($points),
        ]);
        if (empty($points)) {
            $this->addError('vars', 'No numeric pairs found for these variables.');
            return;
        }
        if (count($points) > 5000) {
            $points = array_slice($points, 0, 5000);
        }
        
        $this->emitChart('scatter', [], [$points], "{$this->varX} vs {$this->varY} (Scatter)");
    }

    /** ---------- LINE (binned mean of Y vs X) ---------- */
    protected function buildLineBinnedMean(): void
    {
        if (!$this->varX || !$this->varY) {
            $this->addError('vars', 'Line (binned mean) needs both X and Y numeric.');
            return;
        }
        // One query for BOTH variables, then group by SEQN (participant)
        $rows = Data::select('SEQN', 'variable_id', 'value')
        ->whereIn('variable_id', [$this->varX, $this->varY])
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN'); // SEQN => collection of rows (X and/or Y)
        
        // Build numeric (x,y) pairs per participant
        $pairs = [];
        foreach ($rows as $seqn => $group) {
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = optional($group->firstWhere('variable_id', $this->varY))->value;
            if (is_numeric($vx) && is_numeric($vy)) {
                $pairs[] = [(float) $vx, (float) $vy];
            }
        }
        
        logger('viz:fetched', [
            'mode' => 'line-binned-mean',
            'varX' => $this->varX,
            'varY' => $this->varY,
            'seqn_groups' => $rows->count(),
            'numeric_pairs' => count($pairs),
        ]);
        if (empty($pairs)) {
            $this->addError('vars', 'No numeric pairs found for these variables.');
            return;
        }
        
        // Bin X into ~20 bins and average Y per bin
        $binCount = 20;
        $xs = array_column($pairs, 0);
        $min = min($xs);
        $max = max($xs);
        
        if ($max == $min) {
            $this->addError('vars', 'X has no variance to bin.');
            return;
        }
        $width = ($max - $min) / $binCount;
        $bins = array_fill(0, $binCount, ['sum' => 0.0, 'n' => 0, 'center' => 0.0]);
        for ($i = 0; $i < $binCount; $i++) {
            $bins[$i]['center'] = $min + ($i + 0.5) * $width; // bin centers for labels
        }
        
        foreach ($pairs as [$vx, $vy]) {
            $idx = (int) floor(($vx - $min) / $width);
            if ($idx < 0) $idx = 0;
            if ($idx >= $binCount) $idx = $binCount - 1; // include max
            $bins[$idx]['sum'] += $vy;
            $bins[$idx]['n']   += 1;
        }
        
        $labels = [];
        $means  = [];
        foreach ($bins as $b) {
            if ($b['n'] > 0) {
                $labels[] = round($b['center'], 2);
                $means[]  = $b['sum'] / $b['n'];
            }
        }
        if (empty($labels)) {
            $this->addError('vars', 'Not enough data to build line.');
            return;
        }
        $this->emitChart('line', $labels, [$means], "{$this->varY} mean vs {$this->varX} (binned)");
    }

    /** ---------- BAR (category counts of X) ---------- */
    protected function buildBarCategoryCounts(): void
    {
        if (!$this->varX) {
            $this->addError('vars', 'Select Variable X.');
            return;
        }
        // Fetch both X (and Y if selected) so SEQN overlap is respected
        $vars = [$this->varX];
        if ($this->varY) $vars[] = $this->varY;
        $rows = Data::select('SEQN','variable_id','value')
        ->whereIn('variable_id', $vars)
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN');
        $cats = [];
        foreach ($rows as $seqn => $group) {
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = $this->varY ? optional($group->firstWhere('variable_id', $this->varY))->value : null;
            
            // If Y is selected, only include SEQNs that have both X and Y
            if ($this->varY && $vy === null) continue;
            if ($vx !== null && $vx !== '') {
                $cats[] = (string)$vx;
            }
        }
        if (empty($cats)) {
            $this->addError('vars', 'No categories found for X.');
            return;
        }
        // Count frequency per category
        $freq = array_count_values($cats);
        arsort($freq);
        $freq = array_slice($freq, 0, 30, true); // top 30 categories
        $labels = array_keys($freq);
        $series = array_values($freq);
        
        $title = $this->varY
        ? "{$this->varX} — Category Counts (paired with {$this->varY})"
        : "{$this->varX} — Category Counts";
        
        $this->emitChart('bar', $labels, [$series], $title);
    }

    /** ---------- HISTOGRAM (X only) ---------- */
    protected function buildHistogram(): void
    {
        if (!$this->varX) {
            $this->addError('vars', 'Select Variable X.');
            return;
        }
        $vars = [$this->varX];
        if ($this->varY) $vars[] = $this->varY; // ensure SEQN overlap
        $rows = Data::select('SEQN','variable_id','value')
        ->whereIn('variable_id', $vars)
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN');
        
        $nums = [];
        foreach ($rows as $seqn => $group) {
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = $this->varY ? optional($group->firstWhere('variable_id', $this->varY))->value : null;
            if ($this->varY && $vy === null) continue; // skip if Y required but missing
            if (is_numeric($vx)) $nums[] = (float)$vx;
        }
        if (empty($nums)) {
            $this->addError('vars', 'X must be numeric for histogram.');
            return;
        }
        sort($nums);
        $binCount = max(10, min(40, (int) sqrt(count($nums))));
        $min = min($nums); $max = max($nums);
        $w = ($max - $min) / $binCount;
        $bins = array_fill(0, $binCount, 0);
        
        foreach ($nums as $x) {
            $idx = (int) floor(($x - $min) / $w);
            if ($idx < 0) $idx = 0;
            if ($idx >= $binCount) $idx = $binCount - 1;
            $bins[$idx]++;
        }
        $labels = [];
        for ($i=0;$i<$binCount;$i++){
            $lo = $min + $i*$w; $hi = $lo + $w;
            $labels[] = round($lo,2).'–'.round($hi,2);
        }
        $this->emitChart('bar', $labels, [$bins], "{$this->varX} — Histogram");
    }

    /** ---------- PIE (category shares of X) ---------- */
    protected function buildPie(): void
    {
        if (!$this->varX) {
            $this->addError('vars', 'Select Variable X.');
            return;
        }
        $vars = [$this->varX];
        if ($this->varY) $vars[] = $this->varY;
        $rows = Data::select('SEQN','variable_id','value')
        ->whereIn('variable_id', $vars)
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN');
        
        $cats = [];
        foreach ($rows as $seqn => $group) {
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = $this->varY ? optional($group->firstWhere('variable_id', $this->varY))->value : null;
            if ($this->varY && $vy === null) continue;
            if ($vx !== null && $vx !== '') $cats[] = (string)$vx;
        }
        if (empty($cats)) {
            $this->addError('vars', 'No categories for X.');
            return;
        }
        $freq = array_count_values($cats);
        arsort($freq);
        $freq = array_slice($freq, 0, 20, true);
        $labels = array_keys($freq);
        $series = array_values($freq);
        
        $this->emitChart('pie', $labels, [$series], "{$this->varX} — Category Share");
    }

    /** ---------- BOX PLOT (Y by X categories) ---------- */
    protected function buildBoxPlot(): void
    {
        if (!$this->varY) {
            $this->addError('vars', 'Box plot needs X (categories) and Y (numeric).');
            return;
        }
        $x = Data::where('variable_id', $this->varX)->whereNotNull('value')->pluck('value', 'SEQN');
        $y = Data::where('variable_id', $this->varY)->whereNotNull('value')->pluck('value', 'SEQN');

        logger('viz:fetched', ['mode'=>'box','varX'=>$this->varX,'x_count'=>$x->count(),'varY'=>$this->varY,'y_count'=>$y->count()]);

        // group Y values by X category using SEQN alignment
        $groups = [];
        foreach ($x as $seqn => $cat) {
            if (!isset($y[$seqn])) continue;
            $vy = $y[$seqn];
            if (!is_numeric($vy)) continue;
            $catKey = (string)$cat;
            $groups[$catKey] ??= [];
            $groups[$catKey][] = (float)$vy;
        }

        if (empty($groups)) {
            $this->addError('vars', 'No numeric Y values found aligned by SEQN for X categories.');
            return;
        }

        // keep top 15 categories by count
        uasort($groups, fn($a,$b) => count($b) <=> count($a));
        $groups = array_slice($groups, 0, 15, true);

        $labels = [];
        $series = []; // each item: [min, q1, median, q3, max] for the plugin
        foreach ($groups as $cat => $arr) {
            sort($arr);
            $labels[] = $cat;
            $series[] = $this->fiveNumberSummary($arr);
        }

        $this->emitChart('boxplot', $labels, [$series], "{$this->varY} by {$this->varX} (Box plot)");
    }

    /** ---------- VIOLIN PLOT (Y by X categories) ---------- */
    protected function buildViolinPlot(): void
    {
        if (!$this->varX || !$this->varY) {
            $this->addError('vars', 'Violin plot needs both X and Y (numeric).');
            return;
        }
        $rows = Data::select('SEQN', 'variable_id', 'value')
        ->whereIn('variable_id', [$this->varX, $this->varY])
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN'); // Group by participant
        
        $groups = [];
        foreach ($rows as $seqn => $group) {
            $vx = optional($group->firstWhere('variable_id', $this->varX))->value;
            $vy = optional($group->firstWhere('variable_id', $this->varY))->value;
            
            if (!is_numeric($vy)) continue;
            
            $cat = (string) $vx;
            if ($cat === '' || $cat === null) continue;
            $groups[$cat] ??= [];
            $groups[$cat][] = (float) $vy;
        }
        if (empty($groups)) {
            $this->addError('vars', 'No valid SEQN-matched numeric Y values found for X categories.');
            return;
        }
        // Sort & limit top categories
        uasort($groups, fn($a, $b) => count($b) <=> count($a));
        $groups = array_slice($groups, 0, 15, true);
        $labels = array_keys($groups);
        $series = array_values($groups); // Violin plugin expects array of arrays
        
        $this->emitChart('violin', $labels, [$series], "{$this->varY} by {$this->varX} (Violin plot)");
    }

    protected function mapLabel(string $variable, $value): string
    {
        $value = (string) $value;

        if (in_array($variable, ['BMXBMI', 'LBXGLU'])) {
            $floatVal = (float) $value;

            if ($variable === 'BMXBMI') {
                return match (true) {
                    $floatVal < 18.5 => 'Underweight',
                    $floatVal < 25 => 'Normal',
                    $floatVal < 30 => 'Overweight',
                    default => 'Obese',
                };
            }

            if ($variable === 'LBXGLU') {
                return match (true) {
                    $floatVal < 100 => 'Normal',
                    $floatVal < 126 => 'Prediabetes',
                    default => 'Diabetes',
                };
            }
        }

        $map = [
            'SMQ020' => [ 
                '1' => 'Smoker',
                '2' => 'Non Smoker',
                '7' => 'Refused',
                '9' => "Don't know",
                '0' => "Missing",
            ],
            'MCQ220' => [ 
                '1' => 'Cancer',
                '2' => 'No Cancer',
                '7' => 'Refused',
                '9' => "Don't know",
                '0' => "Missing",
            ],
        ];
        return $map[$variable][$value] ?? $value;
    }

    /** ---------- StackedBar (X vs Y) ---------- */
    protected function buildStackedBarChart(): void
    {
        if (!$this->varX || !$this->varY) {
            $this->addError('vars', 'Grouped Bar Chart needs both X and Y.');
            return;
        }
        // Fetch X and Y for each SEQN
        $rows = Data::select('SEQN', 'variable_id', 'value')
        ->whereIn('variable_id', [$this->varX, $this->varY])
        ->whereNotNull('value')
        ->orderBy('SEQN')
        ->get()
        ->groupBy('SEQN');
        
        $groupCounts = []; // $groupCounts[X][Y] = count
        
        foreach ($rows as $seqn => $group) {
            $x = optional($group->firstWhere('variable_id', $this->varX))->value;
            $y = optional($group->firstWhere('variable_id', $this->varY))->value;
            
            if ($x === null || $y === null) continue;
            $xLabel = $this->mapLabel($this->varX, $x);
            $yLabel = $this->mapLabel($this->varY, $y);

            if ($this->varY === 'MCQ220' && !in_array($yLabel, ['Cancer', 'No Cancer'])) {
                continue;
            }

            $groupCounts[$xLabel][$yLabel] = ($groupCounts[$xLabel][$yLabel] ?? 0) + 1;
        }
        
        if (empty($groupCounts)) {
            $this->addError('vars', 'No data found for grouped bar chart.');
            return;
        }
        
        // Build series
        $labels = array_filter(array_keys($groupCounts), fn($label) => $label !== '0');
        $cancerSeries = [];
        $noCancerSeries = [];
        
        foreach ($labels as $label) {
            $counts = $groupCounts[$label];
            $total = array_sum($counts);
            
            $cancer = $counts['Cancer'] ?? 0;
            $noCancer = $counts['No Cancer'] ?? 0;
            
            $cancerPct = $total > 0 ? round(($cancer / $total) * 100, 2) : 0;
            $noCancerPct = $total > 0 ? round(($noCancer / $total) * 100, 2) : 0;
            
            $cancerSeries[] = $cancerPct;
            $noCancerSeries[] = $noCancerPct;
        }
        
        $this->emitChart('stacked-bar', $labels, [
            $cancerSeries,
            $noCancerSeries
        ], "{$this->varX} vs {$this->varY} (Cancer %)");
    }


    /** Five-number summary [min, q1, median, q3, max] */
    protected function fiveNumberSummary(array $sorted): array
    {
        $n = count($sorted);
        if ($n === 0) return [0,0,0,0,0];

        $median = $this->quantile($sorted, 0.5);
        $q1     = $this->quantile($sorted, 0.25);
        $q3     = $this->quantile($sorted, 0.75);

        return [
            $sorted[0],
            $q1,
            $median,
            $q3,
            $sorted[$n-1],
        ];
    }

    /** Linear interpolation quantile on a pre-sorted array */
    protected function quantile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 1) return $sorted[0];
        $idx = ($n - 1) * $p;
        $lo = (int) floor($idx);
        $hi = (int) ceil($idx);
        if ($lo === $hi) return (float)$sorted[$lo];
        $h = $idx - $lo;
        return (1 - $h) * $sorted[$lo] + $h * $sorted[$hi];
    }

    protected function isCategorical(string $var): bool
    {
        $values = Data::where('variable_id', $var)
        ->whereNotNull('value')
        ->limit(100)
        ->pluck('value');
        
        // If most values are strings or <10 unique numeric values => treat as categorical
        $numeric = $values->filter(fn($v) => is_numeric($v));
        return $numeric->count() < 90 || $values->unique()->count() < 10;
    }


    protected function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n === 0 || $n !== count($y)) return 0;
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        $num = 0; $denX = 0; $denY = 0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $num += $dx * $dy;
            $denX += $dx ** 2;
            $denY += $dy ** 2;
        }
        if ($denX == 0 || $denY == 0) return 0;
        return $num / sqrt($denX * $denY);
    }


    /** Emit chart payload to the browser + log */
    protected function emitChart(string $type, array $labels, array $series, string $title): void
    {
        $this->chartType   = $type;
        $this->chartLabels = $labels;
        $this->chartSeries = $series;
        $this->chartTitle  = $title;

        logger('viz:final', [
            'type' => $this->chartType,
            'labels_n' => count($this->chartLabels),
            'series0_count' => isset($this->chartSeries[0]) ? count($this->chartSeries[0]) : 0,
        ]);

        $this->dispatch('render-chart', [
            'type'   => $this->chartType,
            'labels' => $this->chartLabels,
            'series' => $this->chartSeries,
            'title'  => $this->chartTitle,
        ]);
    }

    public function render()
    {
        return view('livewire.get-visualisation-data');
    }
}