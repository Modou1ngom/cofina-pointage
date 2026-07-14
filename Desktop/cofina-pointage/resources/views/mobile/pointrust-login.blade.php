<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <title>Connexion — PointRust</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(160deg, #0c447c 0%, #185fa5 45%, #1a6bb5 100%);
            min-height: 100dvh;
            color: #1e293b;
        }
        .wrap {
            max-width: 420px;
            margin: 0 auto;
            padding: 1.5rem 1rem 2rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,.15);
        }
        h1 {
            margin: 0 0 .25rem;
            font-size: 1.25rem;
            color: #0c447c;
        }
        .sub { font-size: .8rem; color: #64748b; margin-bottom: 1.25rem; }
        label { display: block; font-size: .75rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: .65rem .75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        input:focus { outline: 2px solid #185fa5; outline-offset: 0; border-color: #185fa5; }
        button[type="submit"] {
            width: 100%;
            padding: .75rem 1rem;
            border: none;
            border-radius: 8px;
            background: #ea580c;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: .25rem;
        }
        button[type="submit"]:disabled { opacity: .55; cursor: not-allowed; }
        .err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: .65rem .75rem;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 1rem;
            display: none;
        }
        .err.show { display: block; }
        .hint { font-size: .7rem; color: #94a3b8; margin-top: 1rem; line-height: 1.4; }
        #step-otp { display: none; }
        #step-otp.visible { display: block; }
        #step-password.hidden { display: none; }
        pre#debug { display: none; font-size: .65rem; overflow: auto; max-height: 8rem; background: #f8fafc; padding: .5rem; border-radius: 6px; margin-top: 1rem; }
        pre#debug.show { display: block; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Connexion PointRust</h1>
        <p class="sub">Identifiants du même compte que sur le portail web.</p>

        <div id="err" class="err" role="alert"></div>

        <form id="step-password" autocomplete="on">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" autocomplete="username" required inputmode="email">

            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit" id="btn-login">Se connecter</button>
        </form>

        <div id="step-otp">
            <p class="sub" style="margin-top:0">Code à 6 chiffres envoyé à votre adresse e-mail.</p>
            <label for="identifier">E-mail</label>
            <input id="identifier" type="email" readonly>

            <label for="otp-code">Code OTP</label>
            <input id="otp-code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" autocomplete="one-time-code">

            <button type="button" id="btn-otp">Valider le code</button>
        </div>

        <p class="hint">
            Après connexion réussie, l’application native récupère le jeton (interface JavaScript ou message WebView).
        </p>
        <pre id="debug"></pre>
    </div>
</div>

<script>
(function () {
    const API_BASE = @json($apiBaseUrl);
    const NATIVE_SCHEME = @json($nativeScheme);

    const elErr = document.getElementById('err');
    const elDebug = document.getElementById('debug');
    const stepPwd = document.getElementById('step-password');
    const stepOtp = document.getElementById('step-otp');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const identifierInput = document.getElementById('identifier');
    const otpInput = document.getElementById('otp-code');
    const btnLogin = document.getElementById('btn-login');
    const btnOtp = document.getElementById('btn-otp');

    function showErr(msg) {
        elErr.textContent = msg || 'Une erreur est survenue.';
        elErr.classList.add('show');
    }
    function clearErr() {
        elErr.textContent = '';
        elErr.classList.remove('show');
    }

    function deliverToNative(payload) {
        const json = JSON.stringify(payload);
        try {
            if (window.PointrustNative && typeof window.PointrustNative.onLoginSuccess === 'function') {
                window.PointrustNative.onLoginSuccess(json);
                return true;
            }
        } catch (e) {}
        try {
            if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
                window.ReactNativeWebView.postMessage(json);
                return true;
            }
        } catch (e) {}
        try {
            if (window.Android && typeof window.Android.onLoginSuccess === 'function') {
                window.Android.onLoginSuccess(json);
                return true;
            }
        } catch (e) {}
        if (NATIVE_SCHEME && typeof NATIVE_SCHEME === 'string' && NATIVE_SCHEME.length > 1) {
            try {
                window.location.href = NATIVE_SCHEME + '://auth?payload=' + encodeURIComponent(json);
                return true;
            } catch (e) {}
        }
        elDebug.textContent = json;
        elDebug.classList.add('show');
        return false;
    }

    async function postJson(path, body) {
        const res = await fetch(API_BASE + path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(function () { return {}; });
        return { ok: res.ok, status: res.status, data: data };
    }

    stepPwd.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErr();
        btnLogin.disabled = true;
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const r = await postJson('/login', {
            email: email,
            password: password,
            device_name: 'pointrust',
        });
        btnLogin.disabled = false;

        if (r.status === 401) {
            showErr(r.data.message || 'Identifiants incorrects.');
            return;
        }
        if (r.status === 403) {
            showErr(r.data.message || 'Compte désactivé.');
            return;
        }
        if (!r.ok) {
            showErr(r.data.message || 'Connexion impossible (' + r.status + ').');
            return;
        }

        if (r.data.requires_otp === true || r.data.requiresOtp === true) {
            identifierInput.value = email;
            stepPwd.classList.add('hidden');
            stepOtp.classList.add('visible');
            otpInput.focus();
            return;
        }

        const token = r.data.token || r.data.access_token || r.data.accessToken;
        if (!token) {
            showErr('Réponse serveur inattendue (pas de jeton).');
            return;
        }
        deliverToNative(r.data);
    });

    btnOtp.addEventListener('click', async function () {
        clearErr();
        btnOtp.disabled = true;
        const r = await postJson('/verify-otp', {
            identifier: identifierInput.value.trim(),
            code: otpInput.value.trim(),
        });
        btnOtp.disabled = false;

        if (!r.ok) {
            showErr(r.data.message || 'Code invalide ou expiré.');
            return;
        }
        const token = r.data.token || r.data.access_token || r.data.accessToken;
        if (!token) {
            showErr('Réponse serveur inattendue (pas de jeton).');
            return;
        }
        deliverToNative(r.data);
    });
})();
</script>
</body>
</html>
