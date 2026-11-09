@extends('layouts.app')
@section('title', 'Nuovo movimento')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('movements.index') }}">← Movimenti</a><h1>Registra un movimento</h1><p>Aggiorna le scorte con un carico o uno scarico tracciato.</p></div></div>
@if($variants->isEmpty())<section class="card empty-state"><span>▣</span><h3>Prima, aggiungi un prodotto</h3><p>Per registrare un movimento serve almeno una variante attiva.</p><a class="button primary" href="{{ route('products.create') }}">＋ Aggiungi prodotto</a></section>
@else
<form method="post" action="{{ route('movements.store') }}">@csrf
<div data-vue="movement" data-props="{{ json_encode(['variants'=>$variants,'initial'=>['variant_id'=>old('variant_id',request('variant')),'type'=>old('type',request('type','in')),'quantity'=>old('quantity',1),'reason'=>old('reason',''),'reference'=>old('reference',''),'notes'=>old('notes','')]]) }}"></div>
<noscript><div class="alert error">Attiva JavaScript per registrare un movimento.</div></noscript>
</form>@endif
@endsection
