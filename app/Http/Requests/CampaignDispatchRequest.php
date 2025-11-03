<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Sendportal\Base\Http\Requests\CampaignDispatchRequest as BaseRequest;

class CampaignDispatchRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['slice_enabled'] = ['nullable', 'boolean'];
        $rules['slice_size'] = ['nullable', 'integer', 'min:1'];
        $rules['slice_interval_minutes'] = ['nullable', 'integer', 'min:1'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slice_enabled' => filter_var($this->input('slice_enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}

