<?php

namespace App\Services;

use App\Models\CalculationRun;
use App\Models\Criterion;
use App\Models\Product;
use App\Models\ProductAssessment;
use App\Models\SawResult;
use Illuminate\Support\Collection;

class SawService
{
    /**
     * Calculate SAW normalization, preference scores, rankings, and priority tiers.
     *
     * @param CalculationRun $run
     * @param array<int, float>|null $overrideWeights Optional custom weights [criterion_id => weight] for what-if simulation.
     * @return array Detailed calculation result
     */
    public function calculate(CalculationRun $run, ?array $overrideWeights = null): array
    {
        // 1. Fetch criteria and products
        $criteria = Criterion::all()->keyBy('id');
        $products = Product::where('is_active', true)->orderBy('id')->get()->keyBy('id');

        // 2. Fetch AHP weights (or use overrideWeights)
        if ($overrideWeights !== null && count($overrideWeights) > 0) {
            $weights = $overrideWeights;
        } else {
            $weights = $run->weights()->pluck('weight', 'criterion_id')->toArray();
            // If weights not set in database, fallback to equal weighting
            if (empty($weights)) {
                $n = $criteria->count();
                $eq = $n > 0 ? (1.0 / $n) : 0.2;
                foreach ($criteria as $critId => $crit) {
                    $weights[$critId] = $eq;
                }
            }
        }

        // Normalize weights sum to 1.0 if needed
        $totalWeight = array_sum($weights);
        if ($totalWeight > 0) {
            foreach ($weights as $cId => $w) {
                $weights[$cId] = $w / $totalWeight;
            }
        }

        // 3. Fetch product assessments
        $assessments = ProductAssessment::where('calculation_run_id', $run->id)
            ->get()
            ->groupBy('product_id');

        // Build raw matrix X: [product_id][criterion_id] => raw_value
        $rawMatrix = [];
        foreach ($products as $pId => $product) {
            $rawMatrix[$pId] = [];
            foreach ($criteria as $cId => $criterion) {
                $item = $assessments->get($pId)?->firstWhere('criterion_id', $cId);
                $rawMatrix[$pId][$cId] = $item ? (float) $item->raw_value : 0.0;
            }
        }

        // 4. Find max and min for each criterion
        $maxValues = [];
        $minValues = [];
        foreach ($criteria as $cId => $criterion) {
            $colValues = [];
            foreach ($products as $pId => $product) {
                $colValues[] = $rawMatrix[$pId][$cId] ?? 0.0;
            }
            $maxValues[$cId] = count($colValues) > 0 ? max($colValues) : 0.0;
            // For min value in cost criteria, find min among non-zero or absolute min
            $filtered = array_filter($colValues, fn($v) => $v > 0);
            $minValues[$cId] = count($filtered) > 0 ? min($filtered) : (count($colValues) > 0 ? min($colValues) : 0.0);
        }

        // 5. Normalization Matrix R and Preference Score V
        $normalizedMatrix = [];
        $weightedMatrix = [];
        $scores = [];
        $breakdowns = [];

        foreach ($products as $pId => $product) {
            $normalizedMatrix[$pId] = [];
            $weightedMatrix[$pId] = [];
            $vScore = 0.0;

            foreach ($criteria as $cId => $criterion) {
                $x = $rawMatrix[$pId][$cId] ?? 0.0;
                $weight = $weights[$cId] ?? 0.0;

                if ($criterion->attribute_type === 'benefit') {
                    $denom = $maxValues[$cId] ?? 0.0;
                    $r = ($denom > 0) ? ($x / $denom) : 0.0;
                } else { // cost
                    $numer = $minValues[$cId] ?? 0.0;
                    $r = ($x > 0) ? ($numer / $x) : 0.0;
                }

                $r = round($r, 6);
                $c = round($r * $weight, 6);

                $normalizedMatrix[$pId][$cId] = $r;
                $weightedMatrix[$pId][$cId] = $c;
                $vScore += $c;
            }

            $vScore = round($vScore, 6);
            $scores[$pId] = $vScore;
        }

        // 6. Ranking (Sort descending by score)
        arsort($scores, SORT_NUMERIC);
        $rankings = [];
        $rank = 1;
        foreach ($scores as $pId => $score) {
            // Determine tier: Rank 1-10 Utama, 11-20 Menengah, 21-30 Rendah
            if ($rank <= 10) {
                $tier = 'utama';
                $tierLabel = 'Prioritas Utama (Produksi Rutin)';
                $tierBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            } elseif ($rank <= 20) {
                $tier = 'menengah';
                $tierLabel = 'Prioritas Menengah (Kapasitas Sisa)';
                $tierBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            } else {
                $tier = 'rendah';
                $tierLabel = 'Prioritas Rendah (Make-to-Order)';
                $tierBadge = 'bg-rose-100 text-rose-800 border-rose-300';
            }

            // Generate narrative analysis
            $product = $products[$pId];
            $strongestCrit = null;
            $weakestCrit = null;
            $maxContr = -1;
            $minContr = 999;

            foreach ($criteria as $cId => $crit) {
                $contr = $weightedMatrix[$pId][$cId];
                if ($contr > $maxContr) {
                    $maxContr = $contr;
                    $strongestCrit = $crit;
                }
                if ($contr < $minContr) {
                    $minContr = $contr;
                    $weakestCrit = $crit;
                }
            }

            $narrative = $this->generateNarrative($product, $rank, $tier, $score, $strongestCrit, $weakestCrit);

            $rankings[] = [
                'product_id' => $pId,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'final_score' => $score,
                'rank' => $rank,
                'priority_tier' => $tier,
                'tier_label' => $tierLabel,
                'tier_badge' => $tierBadge,
                'raw_values' => $rawMatrix[$pId],
                'normalized_values' => $normalizedMatrix[$pId],
                'weighted_values' => $weightedMatrix[$pId],
                'narrative' => $narrative,
            ];

            $rank++;
        }

        return [
            'criteria' => $criteria,
            'products' => $products,
            'weights' => $weights,
            'max_values' => $maxValues,
            'min_values' => $minValues,
            'raw_matrix' => $rawMatrix,
            'normalized_matrix' => $normalizedMatrix,
            'weighted_matrix' => $weightedMatrix,
            'rankings' => $rankings,
        ];
    }

    /**
     * Calculate and persist SAW results to `saw_results` table.
     */
    public function calculateAndSave(CalculationRun $run, ?array $overrideWeights = null): array
    {
        $result = $this->calculate($run, $overrideWeights);

        foreach ($result['rankings'] as $item) {
            SawResult::updateOrCreate(
                [
                    'calculation_run_id' => $run->id,
                    'product_id' => $item['product_id'],
                ],
                [
                    'final_score' => $item['final_score'],
                    'rank' => $item['rank'],
                    'priority_tier' => $item['priority_tier'],
                ]
            );
        }

        return $result;
    }

    /**
     * Generate automatic Indonesian narrative explaining product ranking reasons.
     */
    private function generateNarrative(
        Product $product,
        int $rank,
        string $tier,
        float $score,
        ?Criterion $strongest,
        ?Criterion $weakest
    ): string {
        $pName = $product->name;
        $tierText = match ($tier) {
            'utama' => 'Prioritas Utama (Produksi Rutin harian/mingguan untuk mencegah kelangkaan stok pasar)',
            'menengah' => 'Prioritas Menengah (Diproduksi dengan memanfaatkan kapasitas produksi sisa)',
            'rendah' => 'Prioritas Rendah (Make-to-Order atau hanya diproduksi saat ada pesanan khusus)',
        };

        $strongText = $strongest ? "didukung dominan oleh {$strongest->name} ({$strongest->code})" : 'berkinerja seimbang';
        $weakText = $weakest ? "faktor penahan utama berada pada {$weakest->name} ({$weakest->code})" : '';

        return "Produk {$pName} menempati Peringkat #{$rank} dengan nilai preferensi {$score}. Masuk dalam kategori {$tierText}. Peringkat ini {$strongText}. {$weakText}.";
    }
}

