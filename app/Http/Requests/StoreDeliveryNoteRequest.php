<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_date' => ['required', 'date_format:Y-m-d'],
            'producer' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_date.required' => 'Indica la data del DDT.',
            'document_date.date_format' => 'Inserisci una data valida per il DDT.',
            'producer.string' => 'Inserisci un nome produttore valido.',
            'producer.max' => 'Il nome del produttore non può superare 255 caratteri.',
            'document_number.string' => 'Inserisci un numero documento valido.',
            'document_number.max' => 'Il numero documento non può superare 100 caratteri.',
            'pdf.required' => 'Seleziona il PDF del DDT da caricare.',
            'pdf.file' => 'Il file selezionato non è valido.',
            'pdf.mimes' => 'Il DDT deve essere un file PDF.',
            'pdf.extensions' => 'Il file deve avere estensione .pdf.',
            'pdf.max' => 'Il PDF non può superare 10 MB.',
            'pdf.uploaded' => 'Caricamento non riuscito. Seleziona di nuovo il PDF e riprova.',
        ];
    }
}
