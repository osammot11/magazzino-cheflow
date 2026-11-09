<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_date' => '2026-11-09',
            'producer' => fake()->company(),
            'document_number' => fake()->numerify('DDT-####'),
            'original_name' => 'documento.pdf',
            'file_path' => 'delivery-notes/'.fake()->uuid().'.pdf',
            'file_size' => 1000,
        ];
    }
}
