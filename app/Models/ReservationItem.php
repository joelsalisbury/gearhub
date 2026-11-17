<?php

namespace App\Models;

use Database\Factories\ReservationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reservation_id', 'gear_item_id', 'quantity'])]
class ReservationItem extends Model
{
    /** @use HasFactory<ReservationItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function gearItem(): BelongsTo
    {
        return $this->belongsTo(GearItem::class);
    }
}
