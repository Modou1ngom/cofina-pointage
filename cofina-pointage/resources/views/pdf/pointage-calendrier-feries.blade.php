<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Calendrier fériés {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #0C447C; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .meta { font-size: 9px; color: #5c5a57; margin-bottom: 10px; }
        .month-block { display: inline-block; width: 24%; vertical-align: top; margin: 0 0.5% 8px; }
        .month-title { font-weight: bold; font-size: 9px; margin-bottom: 2px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid th { font-size: 6px; color: #888; }
        .grid td { border: 1px solid #e2e0d8; width: 14%; height: 11px; text-align: center; font-size: 6px; }
        .ouvrable { background: #fff; }
        .weekend { background: #f1efe8; }
        .ferie-chome { background: #fde8e8; }
        .ferie-majore { background: #fff3e0; }
        .ferie-official { background: #e6f1fb; }
        .legend { margin-top: 8px; font-size: 7px; clear: both; }
        .sw { display: inline-block; width: 10px; height: 8px; border: 1px solid #ccc; vertical-align: middle; margin-right: 2px; }
    </style>
</head>
<body>
    <h1>Calendrier annuel — jours fériés {{ $year }}</h1>
    <p class="meta">Profil : {{ $profile->libelle ?? '—' }} — Pays : {{ $country === 'all' ? 'Tous' : strtoupper($country) }}</p>

    @foreach($grilleAnnuelle as $m => $cells)
        @php
            $first = \Carbon\Carbon::createFromDate($year, $m, 1);
            $pad = ($first->dayOfWeek + 6) % 7;
            $last = (int) $first->copy()->endOfMonth()->format('d');
            $byDate = collect($cells)->keyBy('date');
            $rows = [];
            $row = array_fill(0, $pad, null);
            for ($d = 1; $d <= $last; $d++) {
                $iso = sprintf('%04d-%02d-%02d', $year, $m, $d);
                $row[] = $byDate->get($iso);
                if (count($row) === 7) {
                    $rows[] = $row;
                    $row = [];
                }
            }
            if (count($row) > 0) {
                while (count($row) < 7) {
                    $row[] = null;
                }
                $rows[] = $row;
            }
        @endphp
        <div class="month-block">
            <div class="month-title">{{ $monthNames[$m] ?? $m }}</div>
            <table class="grid">
                <tr><th>L</th><th>M</th><th>M</th><th>J</th><th>V</th><th>S</th><th>D</th></tr>
                @foreach($rows as $week)
                    <tr>
                        @foreach($week as $c)
                            @if($c === null)
                                <td></td>
                            @else
                                @php
                                    $cls = 'ouvrable';
                                    if (($c['type'] ?? '') === 'weekend') {
                                        $cls = 'weekend';
                                    } elseif (($c['type'] ?? '') === 'ferie') {
                                        if (($c['ferie_source'] ?? '') === 'official') {
                                            $cls = 'ferie-official';
                                        } elseif (($c['ferie_subtype'] ?? '') === 'majore') {
                                            $cls = 'ferie-majore';
                                        } else {
                                            $cls = 'ferie-chome';
                                        }
                                    }
                                @endphp
                                <td class="{{ $cls }}">{{ substr($c['date'], 8, 2) }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div class="legend">
        <span><span class="sw" style="background:#fff"></span> Ouvrable</span>
        <span><span class="sw" style="background:#f1efe8"></span> Week-end</span>
        <span><span class="sw" style="background:#fde8e8"></span> Férié chômé</span>
        <span><span class="sw" style="background:#fff3e0"></span> Férié majoré</span>
        <span><span class="sw" style="background:#e6f1fb"></span> Import officiel</span>
    </div>
</body>
</html>
