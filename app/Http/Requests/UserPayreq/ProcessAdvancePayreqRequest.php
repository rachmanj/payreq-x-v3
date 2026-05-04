<?php

namespace App\Http\Requests\UserPayreq;

use App\Models\Payreq;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcessAdvancePayreqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if (! Gate::allows('rab_select')) {
            $this->merge([
                'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
                'allocations' => [],
            ]);
        }

        $amountInput = $this->input('amount');
        if (is_string($amountInput)) {
            $this->merge([
                'amount' => str_replace(',', '', $amountInput),
            ]);
        }

        $allocations = $this->input('allocations', []);
        if (! is_array($allocations)) {
            return;
        }

        $normalized = [];
        foreach ($allocations as $row) {
            if (! is_array($row)) {
                continue;
            }
            $am = $row['amount'] ?? null;
            if (is_string($am)) {
                $row['amount'] = str_replace(',', '', $am);
            }
            $normalized[] = $row;
        }
        $this->merge(['allocations' => $normalized]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'button_type' => ['required', Rule::in(['create', 'edit', 'create_submit', 'edit_submit'])],
            'employee_id' => ['required', 'exists:users,id'],
            'payreq_type' => ['required', Rule::in(['advance'])],
            'payreq_no' => ['required', 'string', 'max:100'],
            'project' => ['required', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'remarks' => ['required', 'string'],
            'budget_link_mode' => ['required', Rule::in([PayreqBudgetLinkMode::LEGACY, PayreqBudgetLinkMode::MULTI_ALLOCATION])],
            'lot_no' => ['nullable', 'string', 'max:255'],
            'rab_id' => ['nullable'],
        ];

        if ($this->input('budget_link_mode') === PayreqBudgetLinkMode::MULTI_ALLOCATION) {
            $rules['amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['allocations'] = ['required', 'array', 'min:1'];
            $rules['allocations.*.anggaran_id'] = ['required', 'exists:anggarans,id'];
            $rules['allocations.*.amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['allocations.*.remarks'] = ['nullable', 'string', 'max:500'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['allocations'] = ['nullable', 'array'];
            $rules['rab_id'] = ['nullable', 'exists:anggarans,id'];
        }

        if (in_array($this->input('button_type'), ['edit', 'edit_submit'], true)) {
            $rules['payreq_id'] = ['required', 'exists:payreqs,id'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((int) $this->input('employee_id') !== (int) Auth::id()) {
                $validator->errors()->add('employee_id', 'Invalid request.');
            }

            $button = $this->input('button_type');
            if (in_array($button, ['edit', 'edit_submit'], true)) {
                $payreq = Payreq::find($this->input('payreq_id'));
                if (! $payreq) {
                    return;
                }
                if ((int) $payreq->user_id !== (int) Auth::id()) {
                    $validator->errors()->add('payreq_id', 'You cannot edit this payment request.');
                }
                $lockedMode = $payreq->budget_link_mode ?? PayreqBudgetLinkMode::LEGACY;
                $requestedMode = $this->input('budget_link_mode');
                if ($requestedMode !== $lockedMode) {
                    $validator->errors()->add('budget_link_mode', 'Budget form type cannot be changed after the draft was created.');
                }
            }

            if ($this->input('budget_link_mode') !== PayreqBudgetLinkMode::MULTI_ALLOCATION) {
                return;
            }

            $allocations = $this->input('allocations', []);
            $sumRows = collect($allocations)->sum(fn ($row) => (float) ($row['amount'] ?? 0));

            $header = (float) $this->input('amount');

            if (round(abs($sumRows - $header), 2) > 0.009) {
                $validator->errors()->add(
                    'allocations',
                    'Sum of allocation row amounts must equal the payreq total amount.'
                );
            }
        });
    }
}
