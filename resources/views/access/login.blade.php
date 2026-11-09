<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accedi al magazzino · Cheflow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="access-page">
    <div class="access-container">
        <div class="brand access-brand"><span class="brand-mark">c<span>↗</span></span>cheflow</div>
        <section class="card form-card access-card">
            <div class="eyebrow">MAGAZZINO CHEFLOW</div><h1>Benvenuto</h1>
            <p>Inserisci la password per accedere al magazzino.</p>
            <form method="post" action="{{ route('access.unlock') }}">
                @csrf
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password" maxlength="256" placeholder="La tua password" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                @error('password')<p class="access-error" id="password-error" role="alert">{{ $message }}</p>@enderror
                <button class="button primary" type="submit">Entra nel magazzino →</button>
            </form>
        </section>
        <small class="access-footer">Ogni prodotto al suo posto.</small>
    </div>
</body>
</html>
