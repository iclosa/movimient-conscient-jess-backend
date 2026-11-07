@php
    $nav = [
        'Principal' => [
            ['Resum', 'admin.dashboard', 'home', 'admin.dashboard'],
            ['Calendari', 'admin.calendar', 'calendar', 'admin.calendar'],
        ],
        'Classes' => [
            ['Classes', 'admin.sessions.index', 'clock', 'admin.sessions.*'],
            ['Horari setmanal', 'admin.templates.index', 'repeat', 'admin.templates.*'],
            ['Activitats', 'admin.activities.index', 'leaf', 'admin.activities.*'],
            ['Sales', 'admin.rooms.index', 'door', 'admin.rooms.*'],
            ['Professorat', 'admin.instructors.index', 'user', 'admin.instructors.*'],
        ],
        'Alumnat' => [
            ['Alumnes', 'admin.students.index', 'users', 'admin.students.*'],
            ['Bons i subscripcions', 'admin.passes.index', 'ticket', 'admin.passes.*'],
            ['Cobraments', 'admin.payments.index', 'euro', 'admin.payments.*'],
            ['Valoracions', 'admin.ratings.index', 'star', 'admin.ratings.*'],
        ],
        'Contingut' => [
            ['Vídeos', 'admin.videos.index', 'video', 'admin.videos.*'],
            ['Promocions', 'admin.promotions.index', 'tag', 'admin.promotions.*'],
            ['Tallers i retirs', 'admin.events.index', 'sparkles', 'admin.events.*'],
            ['Blog', 'admin.posts.index', 'book', 'admin.posts.*'],
            ['Més (benestar)', 'admin.wellness.index', 'heart', 'admin.wellness.*'],
        ],
        'Comunicació' => [
            ['Avisos a l\'app', 'admin.announcements.index', 'megaphone', 'admin.announcements.*'],
        ],
        'Centre' => [
            ['Ajustos', 'admin.settings.edit', 'settings', 'admin.settings.*'],
            ['Equip', 'admin.team.index', 'shield', 'admin.team.*'],
        ],
    ];
    $initials = collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panell') · Moviment Conscient</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Caveat:wght@600&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            @include('admin.partials.logo')
            <span><b>Moviment Conscient</b><small>by Jess</small></span>
        </a>

        @foreach ($nav as $group => $items)
            <div class="nav-group">
                <div class="nav-title">{{ $group }}</div>
                @foreach ($items as [$label, $route, $icon, $pattern])
                    <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" />{{ $label }}
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="sidebar-foot">
            <div class="me">
                <span class="avatar">{{ $initials }}</span>
                <span><b>{{ auth()->user()->name }}</b><small>Administració</small></span>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="Tanca la sessió" aria-label="Tanca la sessió"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
    </aside>
    <div class="scrim" data-sidebar-close></div>

    <div class="main">
        <header class="topbar">
            <button type="button" data-sidebar-toggle aria-label="Obre el menú"><x-icon name="menu" /></button>
            <strong style="font-family:var(--font-head)">Moviment Conscient</strong>
        </header>

        <main class="content">
            @if (session('status'))
                <div class="alert success" data-autohide role="status"><x-icon name="check" />{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert error" role="alert"><x-icon name="alert" />{{ session('error') }}</div>
            @endif
            @if ($errors->any() && ! request()->routeIs('admin.*.create', 'admin.*.edit'))
                <div class="alert error" role="alert"><x-icon name="alert" />{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/admin.js') }}" defer></script>
</body>
</html>
