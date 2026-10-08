<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', 'string', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', 'required', 'string', Rule::in(Ticket::PRIORITIES)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(['subject', 'description', 'status', 'priority'])) {
                $validator->errors()->add('ticket', 'Au moins un champ doit être fourni.');
            }
        }];
    }
}
