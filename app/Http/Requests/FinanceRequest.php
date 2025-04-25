<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // adjust if you have auth logic
        return true;
    }

    public function rules(): array
    {
        return [
            'projects'                  => 'required|array',
            'projects.*'                => 'integer|exists:project_records,id',
            'interval'                  => 'required|array',
            'interval.startYear'        => 'required|integer',
            'interval.startMonth'       => 'required|integer|between:1,12',
            'interval.endYear'          => 'required|integer',
            'interval.endMonth'         => 'required|integer|between:1,12',
        ];
    }

    public function messages(): array
    {
        return [
            'projects.required' => '少なくとも1つのプロジェクトを選択してください。',
            // …customize as you like
        ];
    }

    /** @return array<int,int> */
    public function getProjectIds(): array
    {
        return $this->input('projects', []);
    }

    /** @return array{startYear:int, startMonth:int, endYear:int, endMonth:int} */
    public function getInterval(): array
    {
        return $this->input('interval');
    }
}
