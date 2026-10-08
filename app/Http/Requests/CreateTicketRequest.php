<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public static function ticketRules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', 'string', Rule::in(Ticket::PRIORITIES)],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::ticketRules();
    }
}
