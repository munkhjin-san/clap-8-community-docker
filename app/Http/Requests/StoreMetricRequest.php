<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetricRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label_ja'        => ['required','max:191','unique:project_metrics,label_ja'],
            'kind'            => ['required','in:input,derived'],
            'value_type'      => ['required','in:amount,rate,currency'],
            'line'            => ['nullable','in:sales,expense,profit,profit_rate'],
            'is_active'       => ['sometimes','boolean'],
            'sort_order'      => ['sometimes','integer'],
            'scenario_label_ja' => ['sometimes','nullable','max:191'],
            'expression'      => ['nullable','string'],  // required if kind=derived; validated in controller
            'sub_metrics'     => ['sometimes','array'],
            'sub_metrics.*.expression' => ['required','string'],
            'sub_metrics.*.sort_order' => ['sometimes','integer'],
        ];
    }
}
