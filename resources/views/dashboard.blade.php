@extends('layouts.app')
@section('title', 'Panoramica')
@section('content')
<div class="page-heading"><div><div class="eyebrow">IL TUO MAGAZZINO, A COLPO D’OCCHIO</div><h1>Panoramica</h1><p>Una visione chiara di prodotti, disponibilità e attività recenti.</p></div><a class="button primary" href="{{ route('movements.create') }}">＋ Registra movimento</a></div>
<div class="stats-grid">
    <div class="stat"><div class="stat-label">Prodotti attivi <span>▣</span></div><strong>{{ number_format($productCount,0,',','.') }}</strong><small>{{ $variantCount }} varianti a catalogo</small></div>
    <div class="stat"><div class="stat-label">Unità disponibili <span>▤</span></div><strong>{{ number_format($units,0,',','.') }}</strong><small>Nel magazzino principale</small></div>
    <div class="stat"><div class="stat-label">Scorte da monitorare <span class="amber">◷</span></div><strong>{{ $lowCount }}</strong><small>Varianti sotto soglia o esaurite</small></div>
    <div class="stat"><div class="stat-label">Valore di vendita <span>€</span></div><strong class="money">€ {{ number_format($value / 100,2,',','.') }}</strong><small>Prezzo × quantità disponibile</small></div>
</div>
<div class="overview-banner"><div class="banner-symbol">▦</div><div><h2>Spazio ai tuoi prodotti.</h2><p>Costruisci il catalogo, aggiungi le varianti e tieni aggiornate le tue scorte.</p></div><a class="button" href="{{ route('products.create') }}">Aggiungi prodotto ↗</a></div>
<div class="dashboard-columns">
<section class="card"><div class="card-heading"><div><h2>Movimenti recenti</h2><p>Le ultime attività del magazzino</p></div><a class="text-link" href="{{ route('movements.index') }}">Vedi tutti ↗</a></div>
@forelse($movements as $movement)
<div class="activity"><span class="movement-icon {{ $movement->delta > 0 ? 'positive' : 'negative' }}">{{ $movement->delta > 0 ? '↙' : '↗' }}</span><div class="activity-detail"><strong>{{ $movement->variant->product->name }}</strong><small>{{ $movement->variant->name }} · {{ $movement->reason }}</small></div><div class="activity-value"><strong class="{{ $movement->delta > 0 ? 'positive-text' : '' }}">{{ $movement->delta > 0 ? '+' : '' }}{{ $movement->delta }}</strong><small>{{ $movement->created_at->timezone('Europe/Rome')->format('d/m · H:i') }}</small></div></div>
@empty<div class="empty-state"><span>⇄</span><h3>La storia inizia da qui</h3><p>I carichi e gli scarichi appariranno in questa sezione.</p><a href="{{ route('movements.create') }}" class="text-link">Registra il primo movimento →</a></div>@endforelse
</section>
<section class="card"><div class="card-heading"><div><h2>Scorte in attenzione <span class="count">{{ $lowCount }}</span></h2><p>È il momento di controllare le disponibilità</p></div></div>
@forelse($lowStock as $variant)<a class="low-row" href="{{ route('movements.create', ['variant'=>$variant->id,'type'=>'in']) }}"><span class="product-placeholder">▣</span><div><strong>{{ $variant->product->name }}</strong><small>{{ $variant->name }} · {{ $variant->sku }}</small></div><span class="badge {{ $variant->quantity === 0 ? 'danger' : 'warning' }}">{{ $variant->quantity }} disponibili</span></a>
@empty<div class="empty-state"><span>✓</span><h3>{{ $variantCount ? 'Scorte in ordine' : 'Nessuna scorta da monitorare' }}</h3><p>{{ $variantCount ? 'Tutte le varianti sono sopra la soglia minima.' : 'Aggiungi i tuoi prodotti per iniziare.' }}</p></div>@endforelse
<div class="card-bottom"><a class="text-link" href="{{ route('inventory.index',['stock'=>'low']) }}">Apri l’inventario →</a></div></section>
</div>
@endsection
