<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryNoteRequest;
use App\Models\DeliveryNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $search = $filters['q'] ?? '';
        $date = $filters['date'] ?? '';
        $deliveryNotes = DeliveryNote::query()
            ->when($date, fn ($query) => $query->whereDate('document_date', $date))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('producer', 'like', "%$search%")
                ->orWhere('document_number', 'like', "%$search%")
                ->orWhere('original_name', 'like', "%$search%")))
            ->orderByDesc('document_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('delivery-notes.index', compact('deliveryNotes', 'search', 'date'));
    }

    public function store(StoreDeliveryNoteRequest $request): RedirectResponse
    {
        $file = $request->file('pdf');
        $data = $request->validated();
        $path = $file->store('delivery-notes', 'local');

        if ($path === false) {
            throw ValidationException::withMessages(['pdf' => 'Impossibile salvare il PDF. Riprova il caricamento.']);
        }

        try {
            DeliveryNote::query()->create([
                'document_date' => $data['document_date'],
                'producer' => $data['producer'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                'file_path' => $path,
                'file_size' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return to_route('delivery-notes.index')->with('success', 'DDT caricato e archiviato correttamente.');
    }

    public function show(DeliveryNote $deliveryNote): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($deliveryNote->file_path), 404, 'PDF non disponibile.');

        return Storage::disk('local')->response($deliveryNote->file_path, $deliveryNote->downloadName(), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(DeliveryNote $deliveryNote): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($deliveryNote->file_path), 404, 'PDF non disponibile.');

        return Storage::disk('local')->download($deliveryNote->file_path, $deliveryNote->downloadName(), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
