<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = ['document_date', 'producer', 'document_number', 'original_name', 'file_path', 'file_size'];

    protected function casts(): array
    {
        return ['document_date' => 'date', 'file_size' => 'integer'];
    }

    public function downloadName(): string
    {
        return 'ddt-'.$this->document_date->format('Y-m-d').'-'.$this->id.'.pdf';
    }
}
