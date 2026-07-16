<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Pointage — scan QR</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(160deg, #0c447c 0%, #185fa5 100%);
            min-height: 100dvh;
            color: #1e293b;
        }
        .wrap { max-width: 420px; margin: 0 auto; padding: 1.5rem 1rem 2rem; }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,.15);
        }
        h1 { margin: 0 0 .5rem; font-size: 1.2rem; color: #0c447c; }
        p { font-size: .9rem; color: #64748b; line-height: 1.5; }
        .btn {
            display: block;
            width: 100%;
            margin-top: .75rem;
            padding: .75rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary { background: #185fa5; color: #fff; }
        .btn-outline { background: #f8fafc; color: #0c447c; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Pointage Cofina</h1>
        <p>QR Code site reconnu. Ouvrez l’application <strong>CofiPointe</strong> pour pointer, ou continuez sur le portail web si vous y êtes connecté.</p>

        @if ($deepLink)
            <a class="btn btn-primary" id="open-app" href="{{ $deepLink }}">Ouvrir CofiPointe</a>
        @endif

        @if ($webPointerUrl)
            <a class="btn btn-outline" href="{{ $webPointerUrl }}">Pointer sur le web</a>
        @endif

        @if (! $deepLink && ! $webPointerUrl)
            <p style="color:#b91c1c;">Paramètre QR manquant ou invalide.</p>
        @endif
    </div>
</div>
@if ($deepLink)
<script>
    (function () {
        var link = @json($deepLink);
        try { window.location.href = link; } catch (e) {}
    })();
</script>
@endif
</body>
</html>
