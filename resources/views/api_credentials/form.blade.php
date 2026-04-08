<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Credential Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#009688',
                    },
                },
            },
        }
    </script>
</head>

<body>
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6" style="color:#009688;">
            {{ $credential ? 'Edit' : 'Create' }} API Credential
            @if ($service_type)
                ({{ strtoupper($service_type) }})
            @endif
        </h1>

        @if (session('success'))
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- @if ($errors->any())
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif --}}

        <form action="{{ route('api-credentials.save') }}" method="POST" class="bg-white p-6 rounded shadow-md">
            @csrf
            <input type="hidden" name="service_type" value="{{ $credential->service_type ?? ($service_type ?? '') }}">

            <div class="mb-4">
                <label class="block mb-1 font-bold">Client ID</label>
                <input type="text" name="client_id" value="{{ old('client_id', $credential->client_id ?? '') }}"
                    class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-bold">Client Secret</label>
                <input type="text" name="client_secret"
                    value="{{ old('client_secret', $credential->client_secret ?? '') }}"
                    class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-bold">Redirect URI</label>
                <input type="url" name="redirect_uri"
                    value="{{ old('redirect_uri', $credential->redirect_uri ?? '') }}" class="w-full border p-2 rounded"
                    required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-bold">Accounts URL</label>
                <input type="url" name="accounts_url"
                    value="{{ old('accounts_url', $credential->accounts_url ?? '') }}" class="w-full border p-2 rounded"
                    required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-bold">API Base URL</label>
                <input type="url" name="api_base" value="{{ old('api_base', $credential->api_base ?? '') }}"
                    class="w-full border p-2 rounded" required>
            </div>

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">Access Token</label>
                <input type="text" name="access_token"
                    value="{{ old('access_token', $credential->access_token ?? '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">Token Expiry (expired_at)</label>
                <input type="datetime-local" name="expired_at"
                    value="{{ old('expired_at', isset($credential->expired_at) ? $credential->expired_at->format('Y-m-d\TH:i') : '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">Refresh Token</label>
                <input type="text" name="refresh_token"
                    value="{{ old('refresh_token', $credential->refresh_token ?? '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}
            {{-- 
            <div class="mb-4">
                <label class="block mb-1 font-bold">Revoked At</label>
                <input type="datetime-local" name="revoked_at"
                    value="{{ old('revoked_at', isset($credential->revoked_at) ? $credential->revoked_at->format('Y-m-d\TH:i') : '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">OAuth Type</label>
                <input type="text" name="oauth_type" value="{{ old('oauth_type', $credential->oauth_type ?? '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            <div class="mb-4">
                <label class="block mb-1 font-bold">OAuth Type</label>

                <select name="oauth_type" class="w-full border p-2 rounded">
                    <option value="">-- Select OAuth Type --</option>

                    <option value="oauth1"
                        {{ old('oauth_type', $credential->oauth_type ?? '') == 'oauth1' ? 'selected' : '' }}>
                        OAuth 1.0
                    </option>

                    <option value="oauth2"
                        {{ old('oauth_type', $credential->oauth_type ?? '') == 'oauth2' ? 'selected' : '' }}>
                        OAuth 2.0
                    </option>

                    <option value="bearer"
                        {{ old('oauth_type', $credential->oauth_type ?? '') == 'bearer' ? 'selected' : '' }}>
                        Bearer Token
                    </option>

                    <option value="api_key"
                        {{ old('oauth_type', $credential->oauth_type ?? '') == 'api_key' ? 'selected' : '' }}>
                        API Key
                    </option>
                </select>
            </div>

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">Service Type</label>
                <input type="text" name="service_type"
                    value="{{ old('service_type', $credential->service_type ?? '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            <div class="mb-4">
                <label class="block mb-1 font-bold">Service Type</label>

                <select name="service_type" class="w-full border p-2 rounded">
                    <option value="">-- Select Service --</option>

                    <option value="zb"
                        {{ old('service_type', $credential->service_type ?? '') == 'zb' ? 'selected' : '' }}>
                        Zoho Books
                    </option>

                    <option value="qb"
                        {{ old('service_type', $credential->service_type ?? '') == 'qb' ? 'selected' : '' }}>
                        QuickBooks
                    </option>

                    <option value="sap"
                        {{ old('service_type', $credential->service_type ?? '') == 'sap' ? 'selected' : '' }}>
                        SAP
                    </option>

                    <option value="tally"
                        {{ old('service_type', $credential->service_type ?? '') == 'tally' ? 'selected' : '' }}>
                        Tally
                    </option>

                    <option value="busy"
                        {{ old('service_type', $credential->service_type ?? '') == 'busy' ? 'selected' : '' }}>
                        Busy Accounting
                    </option>

                    <option value="xero"
                        {{ old('service_type', $credential->service_type ?? '') == 'xero' ? 'selected' : '' }}>
                        Xero
                    </option>

                    <option value="freshbooks"
                        {{ old('service_type', $credential->service_type ?? '') == 'freshbooks' ? 'selected' : '' }}>
                        FreshBooks
                    </option>

                    <option value="wave"
                        {{ old('service_type', $credential->service_type ?? '') == 'wave' ? 'selected' : '' }}>
                        Wave Accounting
                    </option>

                </select>
            </div>

            {{-- <div class="mb-4">
                <label class="block mb-1 font-bold">Scope</label>
                <input type="text" name="scope" value="{{ old('scope', $credential->scope ?? '') }}"
                    class="w-full border p-2 rounded">
            </div> --}}

            <button type="submit" class="bg-[#009688] hover:bg-teal-700 text-white font-bold py-2 px-4 rounded">
                {{ $credential ? 'Update' : 'Create' }}
            </button>
        </form>
    </div>
</body>

</html>
