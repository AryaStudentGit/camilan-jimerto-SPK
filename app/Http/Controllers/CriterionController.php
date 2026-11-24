<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use Illuminate\Http\Request;

class CriterionController extends Controller
{
    public function index()
    {
        $criteria = Criterion::orderBy('id')->get();
        return view('criteria.index', compact('criteria'));
    }

    public function update(Request $request, int $id)
    {
        $criterion = Criterion::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attribute_type' => 'required|in:benefit,cost',
            'unit_or_scale' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $criterion->update($validated);
        return back()->with('success', "Kriteria {$criterion->code} berhasil diperbarui.");
    }
}

