<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $query = PublicHoliday::query()->orderBy('date');

        if (! empty($validated['start_date'])) {
            $query->where('date', '>=', $validated['start_date']);
        }

        if (! empty($validated['end_date'])) {
            $query->where('date', '<=', $validated['end_date']);
        }

        return response()->json(
            $query->get(['id', 'date', 'holiday_name'])
        );
    }
}