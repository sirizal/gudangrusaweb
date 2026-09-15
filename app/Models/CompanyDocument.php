<?php

namespace App\Models;

use App\Enums\CompanyDocumentType;
use Database\Factories\CompanyDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'document_type',
    'document_number',
    'name',
    'start_date',
    'end_date',
    'file_path',
])]
class CompanyDocument extends Model
{
    /** @use HasFactory<CompanyDocumentFactory> */
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => CompanyDocumentType::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
