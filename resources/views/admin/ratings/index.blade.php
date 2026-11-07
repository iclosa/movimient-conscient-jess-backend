@extends('admin.layouts.app')
@section('title', 'Valoracions')

@section('content')
    <x-page-header title="Valoracions" subtitle="Què opina l'alumnat de les classes que ha fet." />

    @if ($total === 0)
        <div class="card empty">
            <div class="ring"><x-icon name="star" /></div>
            <h3>Encara no hi ha valoracions</h3>
            <p>Apareixeran quan l'alumnat valori les classes passades des de l'app.</p>
        </div>
    @else
        <div class="grid cols-3">
            <div class="card stat" style="--tint:#fdf1da">
                <div class="label">Valoració mitjana</div>
                <div class="value">{{ number_format($average, 1, ',', '') }} <span class="muted" style="font-size:20px">/ 5</span></div>
                <x-stars :value="$average" />
            </div>
            <div class="card stat">
                <div class="label">Valoracions rebudes</div>
                <div class="value">{{ $total }}</div>
            </div>
            <div class="card card-pad">
                @foreach ([5, 4, 3, 2, 1] as $stars)
                    @php $count = $distribution[$stars] ?? 0; $share = $total ? round($count / $total * 100) : 0; @endphp
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
                        <span style="width:16px;font-weight:600">{{ $stars }}</span><span class="stars">★</span>
                        <div class="progress" style="flex:1;--c:#e4b429"><i style="width:{{ $share }}%"></i></div>
                        <span class="muted" style="width:32px;text-align:right">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid main-side" style="margin-top:20px">
            <div class="card">
                <div class="card-head"><h2>Últimes valoracions</h2></div>
                <ul class="list">
                    @foreach ($latest as $booking)
                        <li style="align-items:flex-start">
                            <span class="dot" style="--c:{{ $booking->classSession->activity->color }};margin-top:6px"></span>
                            <span class="grow">
                                <span class="title">{{ $booking->classSession->activity->name }} · {{ $booking->classSession->starts_at->format('d/m') }}</span>
                                <span class="sub">{{ $booking->attendee_name }}</span>
                                @if ($booking->rating_comment)<span style="display:block;margin-top:4px">«{{ $booking->rating_comment }}»</span>@endif
                            </span>
                            <x-stars :value="$booking->rating" />
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <div class="card-head"><h2>Per activitat</h2></div>
                <ul class="list">
                    @foreach ($perActivity as $activity)
                        <li>
                            <span class="dot" style="--c:{{ $activity->color }}"></span>
                            <span class="grow"><span class="title">{{ $activity->name }}</span><span class="sub">{{ $activity->votes }} valoracions</span></span>
                            <strong>{{ number_format($activity->average, 1, ',', '') }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
