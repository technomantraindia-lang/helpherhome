<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_code', 'registration_number', 'registration_date', 'name', 'mobile_number', 'alternate_mobile_number', 'email', 'address_line_1', 'address_line_2', 'location', 'city', 'state', 'pincode', 'country', 'status', 'notes', 'created_by', 'updated_by'])]
class Customer extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Customer $customer): void {
            if ($customer->isDirty('customer_code')) {
                $customer->customer_code = $customer->getOriginal('customer_code');
            }
        });
    }

    public function requirements(): HasMany { return $this->hasMany(CustomerRequirement::class); }
    public function assignments(): HasMany { return $this->hasMany(Assignment::class); }
    public function replacementRequests(): HasMany { return $this->hasMany(ReplacementRequest::class); }
    public function agreements(): HasMany { return $this->hasMany(Agreement::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function paymentReceipts(): HasMany { return $this->hasMany(PaymentReceipt::class); }
    public function generatedDocuments(): HasMany { return $this->hasMany(CustomerGeneratedDocument::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    protected function casts(): array
    {
        return ['registration_date' => 'date', 'status' => CustomerStatus::class];
    }
}
