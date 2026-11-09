<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panoramica') · Cheflow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="topbar"><a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">c<span>↗</span></span>cheflow<span class="brand-divider"></span><span class="brand-sub">Magazzino</span></a><span class="workspace-label"><span class="online-dot"></span> Workspace locale</span></header>
<div class="app-shell">
    <aside class="sidebar">
        <div class="workspace"><span class="workspace-icon">C</span><div><strong>Cheflow</strong><small>Gestione del magazzino</small></div></div>
        <div class="nav-label">WORKSPACE</div>
        <nav aria-label="Menu principale">
            <a class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}"><span>◫</span> Panoramica</a>
            <a class="{{ request()->routeIs('products.*') ? 'selected' : '' }}" href="{{ route('products.index') }}"><span>▣</span> Prodotti</a>
            <a class="{{ request()->routeIs('inventory.*') ? 'selected' : '' }}" href="{{ route('inventory.index') }}"><span>▤</span> Inventario</a>
            <a class="{{ request()->routeIs('movements.*') ? 'selected' : '' }}" href="{{ route('movements.index') }}"><span>⇄</span> Movimenti</a>
            <a class="{{ request()->routeIs('delivery-notes.*') ? 'selected' : '' }}" href="{{ route('delivery-notes.index') }}"><span>▧</span> DDT produttore</a>
        </nav>
        <div class="sidebar-note"><span class="online-dot"></span> Tutto sotto controllo<p>Prodotti, scorte e movimenti.<br>Un unico posto per gestirli.</p></div>
        <div class="sidebar-bottom"><span class="avatar">CF</span><div><strong>Magazzino principale</strong><small>Archivio SQLite</small></div></div>
    </aside>
    <main>
        <div class="breadcrumb">Workspace <span>/</span> @yield('title', 'Panoramica')</div>
        @if(session('success'))<div class="alert success" role="status">✓ {{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert error" role="alert"><strong>Controlla i dati inseriti</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
        <footer class="page-footer">CHEFLOW <span>Ogni prodotto al suo posto.</span></footer>
    </main>
</div>
</body>
</html>
