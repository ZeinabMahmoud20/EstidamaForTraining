<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    use HasFactory;
    protected $table = 'certificate_templates';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }
}
