@extends('layouts.app')
@section('title', $product->exists ? 'Modifica prodotto' : 'Nuovo prodotto')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('products.index') }}">← Prodotti</a><h1>{{ $product->exists ? $product->name : 'Nuovo prodotto' }}</h1><p>Definisci il prodotto e le varianti da gestire a magazzino.</p></div></div>
<form method="post" action="{{ $product->exists ? route('products.update',$product) : route('products.store') }}">
@csrf @if($product->exists) @method('PUT') @endif
<div class="form-layout"><div class="form-main"><section class="card form-card"><h2>Informazioni del prodotto</h2><label>Nome prodotto <span>*</span><input name="name" value="{{ old('name',$product->name) }}" required maxlength="255" placeholder="Es. T-shirt Cheflow"></label><label>Descrizione<textarea name="description" rows="4" maxlength="10000" placeholder="Materiali, caratteristiche e informazioni utili…">{{ old('description',$product->description) }}</textarea></label></section>
<section class="card form-card"><div class="section-heading"><div><h2>Varianti</h2><p>Ogni variante ha uno SKU univoco e una propria disponibilità.</p></div></div>
<div data-vue="variants" data-props="{{ json_encode(['initialVariants'=>old('variants',$variants),'isEditing'=>$product->exists]) }}"></div>
<noscript><p>Attiva JavaScript per inserire e modificare le varianti.</p></noscript>
</section></div><aside class="form-aside"><section class="card form-card"><h2>Organizzazione</h2><label>Stato<select name="status"><option value="active" @selected(old('status',$product->status)==='active')>Attivo</option><option value="archived" @selected(old('status',$product->status)==='archived')>Archiviato</option></select></label><label>Categoria<input name="category" value="{{ old('category',$product->category) }}" maxlength="255" placeholder="Es. Abbigliamento"></label><label>Fornitore / brand<input name="vendor" value="{{ old('vendor',$product->vendor) }}" maxlength="255" placeholder="Es. Cheflow"></label></section><div class="info-box"><strong>Uno storico sempre completo</strong><p>Le quantità esistenti si aggiornano con un carico o uno scarico. Archivia un prodotto per rimuoverlo dal catalogo attivo conservandone lo storico.</p></div></aside></div>
<div class="form-actions"><a class="button" href="{{ route('products.index') }}">Annulla</a><button class="button primary" type="submit">{{ $product->exists ? 'Salva modifiche' : 'Crea prodotto' }}</button></div></form>
@endsection
