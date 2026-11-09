@extends('layouts.app')
@section('title', 'DDT produttore')
@section('content')
<div class="page-heading"><div><div class="eyebrow">I DOCUMENTI DELLE TUE CONSEGNE</div><h1>DDT produttore <span class="title-count">{{ $deliveryNotes->total() }}</span></h1><p>Carica il documento giornaliero del produttore e ritrovalo quando serve.</p></div></div>
<div class="ddt-layout">
    <section class="card form-card ddt-upload">
        <h2>Carica un DDT</h2>
        <form method="post" action="{{ route('delivery-notes.store') }}" enctype="multipart/form-data">
            @csrf
            <label>Data del DDT <span>*</span><input type="date" name="document_date" value="{{ old('document_date', now('Europe/Rome')->format('Y-m-d')) }}" required></label>
            <label>Produttore<input name="producer" value="{{ old('producer') }}" maxlength="255" placeholder="Nome del produttore (facoltativo)"></label>
            <label>Numero documento<input name="document_number" value="{{ old('document_number') }}" maxlength="100" placeholder="Es. DDT 123/2026 (facoltativo)"></label>
            <label class="ddt-file-label">PDF del DDT <span>*</span><input type="file" name="pdf" accept=".pdf,application/pdf" required aria-describedby="pdf-help"><small id="pdf-help">Seleziona il PDF ricevuto dal produttore. Massimo 10 MB.</small></label>
            @if($errors->any())<p class="field-help">Per riprovare il caricamento, seleziona nuovamente il PDF.</p>@endif
            <button type="submit" class="button primary ddt-submit">↑ Carica DDT</button>
        </form>
        <p class="field-help">Il PDF viene conservato nell’archivio documenti. Puoi caricare più DDT nella stessa giornata.</p>
    </section>
    <section class="card ddt-archive">
        <div class="card-heading"><div><h2>Archivio DDT</h2><p>I documenti più recenti vengono mostrati per primi.</p></div></div>
        <form method="get" action="{{ route('delivery-notes.index') }}" class="toolbar ddt-toolbar">
            <label class="search-field"><span>⌕</span><input name="q" value="{{ $search }}" maxlength="100" placeholder="Produttore, numero o nome file…" aria-label="Cerca DDT"></label>
            <input type="date" name="date" value="{{ $date }}" aria-label="Filtra per data DDT">
            <button class="button" type="submit">Filtra</button>
            @if($search || $date)<a class="text-link" href="{{ route('delivery-notes.index') }}">Azzera filtri</a>@endif
        </form>
        <div class="table-scroll"><table class="responsive-table" role="table"><thead><tr><th>Data DDT</th><th>Documento</th><th>Produttore</th><th>PDF</th></tr></thead><tbody>
        @forelse($deliveryNotes as $deliveryNote)
            <tr>
                <td data-label="Data DDT"><strong>{{ $deliveryNote->document_date->format('d/m/Y') }}</strong><small>Caricato il {{ $deliveryNote->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i') }}</small></td>
                <td class="mobile-record-title" data-label="Documento"><strong>{{ $deliveryNote->document_number ?: 'DDT senza numero' }}</strong><small>{{ $deliveryNote->original_name }}</small><small>{{ number_format($deliveryNote->file_size / 1024, 0, ',', '.') }} KB</small></td>
                <td data-label="Produttore">{{ $deliveryNote->producer ?: '—' }}</td>
                <td class="mobile-record-actions" data-label="PDF"><div class="ddt-actions"><a class="button small" href="{{ route('delivery-notes.show', $deliveryNote) }}" target="_blank" rel="noopener" aria-label="Apri PDF {{ $deliveryNote->document_number ?: $deliveryNote->original_name }}">Apri ↗</a><a class="text-link" href="{{ route('delivery-notes.download', $deliveryNote) }}" aria-label="Scarica PDF {{ $deliveryNote->document_number ?: $deliveryNote->original_name }}">Scarica ↓</a></div></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty-state"><span>▧</span><h3>{{ $search || $date ? 'Nessun DDT trovato' : 'Un posto per ogni consegna' }}</h3><p>{{ $search || $date ? 'Prova a cambiare la data o i termini di ricerca.' : 'Carica il primo PDF del produttore. I documenti saranno raccolti qui, ordinati per data.' }}</p></div></td></tr>
        @endforelse
        </tbody></table></div>
        <div class="pagination">{{ $deliveryNotes->links() }}</div>
    </section>
</div>
@endsection
