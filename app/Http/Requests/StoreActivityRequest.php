<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_activities') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:activities,code'],
            'name' => ['required', 'string', 'max:255'],
            'periode' => ['required', 'string', 'max:20'],
            'project' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'mode' => ['required', Rule::in(['reklasifikasi', 'tanpa_reklasifikasi'])],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'status' => ['nullable', Rule::in(['open', 'closed'])],
            'anggaran_ids' => ['nullable', 'array'],
            'anggaran_ids.*' => ['integer', 'exists:anggarans,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kegiatan',
            'periode' => 'periode',
            'account_id' => 'akun kegiatan',
            'anggaran_ids' => 'RAB',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') === 'reklasifikasi' && ! $this->filled('account_id')) {
                $validator->errors()->add('account_id', 'Akun kegiatan wajib diisi untuk mode reklasifikasi.');
            }

            if ($this->filled('account_id')) {
                $account = Account::query()->find($this->input('account_id'));
                if ($account && $account->type !== 'expense') {
                    $validator->errors()->add('account_id', 'Akun kegiatan harus bertipe expense.');
                }
            }
        });
    }
}
