<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ProjectMetric;

class UpdateMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeMetric = $this->route('metric');
        $metricId = $routeMetric instanceof ProjectMetric
            ? $routeMetric->getKey()
            : (int) ($routeMetric ?? 0);

        return [
            'label_ja'        => ['required','max:191', Rule::unique('project_metrics', 'label_ja')->ignore($metricId)],
            'kind'            => ['required','in:input,derived'],
            'value_type'      => ['required','in:amount,rate,currency'],
            'line'            => ['nullable','in:sales,expense,profit,profit_rate'],
            'is_active'       => ['sometimes','boolean'],
            'sort_order'      => ['sometimes','integer'],
            'scenario_label_ja' => ['sometimes','nullable','max:191'],
            'expression'      => ['nullable','string'],
            'sub_metrics'     => ['sometimes','array'],
            'sub_metrics.*.id' => ['sometimes','integer'],
            'sub_metrics.*.expression' => ['required','string'],
            'sub_metrics.*.sort_order' => ['sometimes','integer'],
        ];
    }
}
