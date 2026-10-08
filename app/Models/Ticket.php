<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject', 'description', 'status', 'priority'])]
class Ticket extends Model
{
    public const STATUSES = ['open', 'pending', 'resolved', 'closed'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
