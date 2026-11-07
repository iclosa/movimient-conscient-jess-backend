<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accés · Moviment Conscient</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Caveat:wght@600&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="login">
    <section class="login-art">
        <div class="brand" style="padding:0">
            @include('admin.partials.logo')
            <span><b>Moviment Conscient</b><small>by Jess</small></span>
        </div>
        <div>
            <h2>Entrenar per a la vida, no només per suar.</h2>
            <p>Gestiona classes, reserves, bons i contingut de l'app des d'un sol lloc.</p>
        </div>
        <span class="sign" style="color:var(--teal-500)">Jess</span>
    </section>

    <section class="login-form">
        <form method="post" action="{{ route('login.attempt') }}">
            @csrf
            <div>
                <h1>Hola de nou</h1>
                <p class="muted" style="margin:6px 0 0">Entra per gestionar el centre.</p>
            </div>

            <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                <label class="lbl" for="email">Correu electrònic</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label class="lbl" for="password">Contrasenya</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <label style="display:flex;gap:8px;align-items:center;font-weight:500">
                <input type="checkbox" name="remember" value="1"> Mantén la sessió iniciada
            </label>

            <button class="btn primary" type="submit" style="justify-content:center">Entra</button>
        </form>
    </section>
</div>
</body>
</html>
