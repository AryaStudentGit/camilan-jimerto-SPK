<?php

namespace App\Services;

use App\Models\AhpComparison;
use App\Models\AhpWeight;
use App\Models\CalculationRun;
use App\Models\Criterion;
use Illuminate\Support\Collection;

class AhpService
{
    /**
     * Random Consistency Index (RI) table for matrix sizes 1 to 10.
     * n=5 -> 1.12
     */
    public const RI_TABLE = [
        1 => 0.00,
        2 => 0.00,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

    /**
     * Calculate AHP weights and consistency metrics from pairwise comparisons.
     *
     * @param array<string, float> $pairwiseValues Key format: "c1_c2" (e.g. "1_2" => 3.0)
     * @param Collection<int, Criterion> $criteria
     * @return array
     */
    public function calculate(array $pairwiseValues, Collection $criteria): array
    {
        $n = $criteria->count();
        $criterionIds = $criteria->pluck('id')->values()->all();

        // 1. Build initial pairwise comparison matrix A (n x n)
        $matrix = [];
        foreach ($criterionIds as $i => $idI) {
            $matrix[$idI] = [];
            foreach ($criterionIds as $j => $idJ) {
                if ($idI === $idJ) {
                    $matrix[$idI][$idJ] = 1.0;
                } elseif (isset($pairwiseValues["{$idI}_{$idJ}"])) {
                    $val = (float) $pairwiseValues["{$idI}_{$idJ}"];
                    $matrix[$idI][$idJ] = $val > 0 ? $val : 1.0;
                } elseif (isset($pairwiseValues["{$idJ}_{$idI}"])) {
                    $val = (float) $pairwiseValues["{$idJ}_{$idI}"];
                    $matrix[$idI][$idJ] = $val > 0 ? (1.0 / $val) : 1.0;
                } else {
                    $matrix[$idI][$idJ] = 1.0;
                }
            }
        }

        // 2. Compute column sums
        $columnSums = [];
        foreach ($criterionIds as $idJ) {
            $sum = 0.0;
            foreach ($criterionIds as $idI) {
                $sum += $matrix[$idI][$idJ];
            }
            $columnSums[$idJ] = $sum > 0 ? $sum : 1.0;
        }

        // 3. Normalize matrix and compute priority weights (row averages)
        $normalizedMatrix = [];
        $weights = [];
        foreach ($criterionIds as $idI) {
            $rowSum = 0.0;
            $normalizedMatrix[$idI] = [];
            foreach ($criterionIds as $idJ) {
                $normVal = $matrix[$idI][$idJ] / $columnSums[$idJ];
                $normalizedMatrix[$idI][$idJ] = $normVal;
                $rowSum += $normVal;
            }
            $weights[$idI] = $n > 0 ? ($rowSum / $n) : (1.0 / $n);
        }

        // Ensure weights sum strictly to 1.0
        $sumWeights = array_sum($weights);
        if ($sumWeights > 0) {
            foreach ($weights as $idI => $w) {
                $weights[$idI] = round($w / $sumWeights, 6);
            }
        }

        // 4. Consistency Ratio (CR) calculation
        // Weighted Sum Vector (WSV): A * w
        $wsv = [];
        $cv = []; // Consistency Vector
        foreach ($criterionIds as $idI) {
            $sum = 0.0;
            foreach ($criterionIds as $idJ) {
                $sum += $matrix[$idI][$idJ] * $weights[$idJ];
            }
            $wsv[$idI] = $sum;
            $cv[$idI] = $weights[$idI] > 0 ? ($sum / $weights[$idI]) : 0.0;
        }

        // Lambda Max
        $lambdaMax = $n > 0 ? (array_sum($cv) / $n) : 0.0;

        // Consistency Index (CI)
        $ci = $n > 1 ? (($lambdaMax - $n) / ($n - 1)) : 0.0;
        if ($ci < 0) {
            $ci = 0.0; // avoid minor precision negative drift
        }

        // Random Index (RI)
        $ri = self::RI_TABLE[$n] ?? 1.12;

        // Consistency Ratio (CR)
        $cr = $ri > 0 ? ($ci / $ri) : 0.0;
        $isConsistent = ($cr <= 0.10001);

        return [
            'matrix' => $matrix,
            'column_sums' => $columnSums,
            'normalized_matrix' => $normalizedMatrix,
            'weights' => $weights,
            'wsv' => $wsv,
            'cv' => $cv,
            'lambda_max' => round($lambdaMax, 6),
            'consistency_index' => round($ci, 6),
            'consistency_ratio' => round($cr, 6),
            'random_index' => $ri,
            'is_consistent' => $isConsistent,
        ];
    }

    /**
     * Save or update AHP comparison matrix and weights into a CalculationRun.
     */
    public function saveCalculation(CalculationRun $run, array $pairwiseValues, Collection $criteria): array
    {
        $result = $this->calculate($pairwiseValues, $criteria);

        // Update run metadata
        $run->update([
            'lambda_max' => $result['lambda_max'],
            'consistency_index' => $result['consistency_index'],
            'consistency_ratio' => $result['consistency_ratio'],
            'is_consistent' => $result['is_consistent'],
        ]);

        // Save comparisons
        $criterionIds = $criteria->pluck('id')->values()->all();
        $n = count($criterionIds);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $id1 = $criterionIds[$i];
                $id2 = $criterionIds[$j];
                $val = $result['matrix'][$id1][$id2] ?? 1.0;

                AhpComparison::updateOrCreate(
                    [
                        'calculation_run_id' => $run->id,
                        'criterion_1_id' => $id1,
                        'criterion_2_id' => $id2,
                    ],
                    [
                        'comparison_value' => round($val, 4),
                    ]
                );
            }
        }

        // Save weights
        foreach ($result['weights'] as $critId => $weight) {
            AhpWeight::updateOrCreate(
                [
                    'calculation_run_id' => $run->id,
                    'criterion_id' => $critId,
                ],
                [
                    'weight' => $weight,
                ]
            );
        }

        return $result;
    }
}

