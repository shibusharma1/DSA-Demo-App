{{-- resources/views/erpnext/connect.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connect to ERPNext</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .logo svg {
            width: 36px;
            height: 36px;
            fill: white;
        }

        h1 {
            color: #1a1a2e;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .status-badge.connected {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.disconnected {
            background: #fef3c7;
            color: #92400e;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot.green {
            background: #10b981;
        }

        .dot.yellow {
            background: #f59e0b;
        }

        .features {
            text-align: left;
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 32px;
        }

        .features h3 {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 14px;
            color: #4b5563;
        }

        .feature-icon {
            width: 20px;
            height: 20px;
            background: #ede9fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 11px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 16px;
            display: none;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .debug-toggle {
            margin-top: 24px;
            font-size: 12px;
            color: #9ca3af;
            cursor: pointer;
            text-decoration: underline;
        }

        .debug-box {
            display: none;
            margin-top: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            text-align: left;
            font-size: 11px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
        }

        .debug-box pre {
            white-space: pre-wrap;
            word-break: break-all;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">
            <svg viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
            </svg>
        </div>

        <h1>ERPNext Integration</h1>
        <p class="subtitle">Connect Integration Hub with ERPNext (Frappe Cloud) to sync customers, invoices, payments and
            more.</p>

        @if ($account && $account->is_active)
            <div class="status-badge connected">
                <span class="dot green"></span>
                Connected — {{ $account->api_base_url }}
            </div>
        @else
            <div class="status-badge disconnected">
                <span class="dot yellow"></span>
                Not connected
            </div>
        @endif

        <div class="features">
            <h3>What will be synced</h3>
            <div class="feature-item"><span class="feature-icon">✓</span> Customers → ERPNext Customer</div>
            <div class="feature-item"><span class="feature-icon">✓</span> Invoices → ERPNext Sales Invoice</div>
            <div class="feature-item"><span class="feature-icon">✓</span> Payments → ERPNext Payment Entry</div>
            <div class="feature-item"><span class="feature-icon">✓</span> Sales Orders → ERPNext Sales Order</div>
            <div class="feature-item"><span class="feature-icon">✓</span> Items → ERPNext Item</div>
        </div>

        <div id="alert" class="alert"></div>

        <div class="btn-group">
            <button id="connectBtn" class="btn btn-primary">
                <span id="btnText">
                    {{ $account && $account->is_active ? 'Reconnect ERPNext' : 'Connect ERPNext' }}
                </span>
                <span id="spinner" class="spinner"></span>
            </button>

            @if ($account && $account->is_active)
                <button id="pingBtn" class="btn btn-secondary">Test Connection</button>
                <button id="disconnectBtn" class="btn btn-danger">Disconnect</button>
            @endif
        </div>

        <div class="debug-toggle"
            onclick="document.getElementById('debugBox').style.display = document.getElementById('debugBox').style.display === 'none' ? 'block' : 'none'">
            Show Debug Info
        </div>
        <div id="debugBox" class="debug-box">
            <pre id="debugContent">Ready.</pre>
        </div>
    </div>

    <script>
        const CONFIG = {
            authUrl: @json($authUrl),
            callbackUrl: @json($redirectUri),
            pingUrl: '/erpnext/ping',
            disconnectUrl: '/erpnext/disconnect',
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        };

        function log(msg, data) {
            const el = document.getElementById('debugContent');
            const ts = new Date().toLocaleTimeString();
            el.textContent += '\n[' + ts + '] ' + msg + (data ? '\n' + JSON.stringify(data, null, 2) : '');
            el.parentElement.scrollTop = el.parentElement.scrollHeight;
        }

        function showAlert(message, type) {
            const el = document.getElementById('alert');
            el.textContent = message;
            el.className = 'alert ' + type;
            el.style.display = 'block';
            setTimeout(() => {
                el.style.display = 'none';
            }, 6000);
        }

        function setLoading(loading) {
            document.getElementById('connectBtn').disabled = loading;
            document.getElementById('spinner').style.display = loading ? 'inline-block' : 'none';
            document.getElementById('btnText').textContent = loading ? 'Connecting...' : 'Connect ERPNext';
        }

        // Listen for message from OAuth popup
        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) return;

            log('Message from popup', event.data);

            if (event.data && event.data.success) {
                showAlert('Successfully connected to ERPNext!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('Connection failed: ' + (event.data?.message || 'Unknown error'), 'error');
                setLoading(false);
            }
        });

        // Connect button
        document.getElementById('connectBtn').addEventListener('click', function() {
            setLoading(true);
            log('Opening OAuth popup', {
                url: CONFIG.authUrl
            });

            const width = 860;
            const height = 680;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            const popup = window.open(
                CONFIG.authUrl,
                'ERPNextOAuth',
                `width=${width},height=${height},left=${left},top=${top},location=1,scrollbars=1`
            );

            if (!popup || popup.closed) {
                showAlert('Popup blocked. Please allow popups and try again.', 'error');
                setLoading(false);
                return;
            }

            // Watch for popup close
            const check = setInterval(() => {
                if (popup.closed) {
                    clearInterval(check);
                    setLoading(false);
                    log('Popup closed by user');
                }
            }, 500);

            // Timeout after 2 minutes
            setTimeout(() => {
                if (!popup.closed) {
                    popup.close();
                    clearInterval(check);
                    setLoading(false);
                    showAlert('Connection timed out. Please try again.', 'error');
                }
            }, 120000);
        });

        // Ping button
        const pingBtn = document.getElementById('pingBtn');
        if (pingBtn) {
            pingBtn.addEventListener('click', function() {
                pingBtn.disabled = true;
                pingBtn.textContent = 'Testing...';

                fetch(CONFIG.pingUrl, {
                        headers: {
                            'X-CSRF-TOKEN': CONFIG.csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        log('Ping response', data);
                        if (data.success) {
                            showAlert('Connection successful! ERPNext is reachable.', 'success');
                        } else {
                            showAlert('Connection test failed: ' + (data.message || 'Unknown error'), 'error');
                        }
                    })
                    .catch(e => {
                        log('Ping error', {
                            error: e.message
                        });
                        showAlert('Connection test failed: ' + e.message, 'error');
                    })
                    .finally(() => {
                        pingBtn.disabled = false;
                        pingBtn.textContent = 'Test Connection';
                    });
            });
        }

        // Disconnect button
        const disconnectBtn = document.getElementById('disconnectBtn');
        if (disconnectBtn) {
            disconnectBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to disconnect ERPNext?')) return;

                disconnectBtn.disabled = true;
                disconnectBtn.textContent = 'Disconnecting...';

                fetch(CONFIG.disconnectUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CONFIG.csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Disconnected from ERPNext.', 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showAlert('Disconnect failed.', 'error');
                        }
                    })
                    .catch(e => showAlert('Error: ' + e.message, 'error'))
                    .finally(() => {
                        disconnectBtn.disabled = false;
                        disconnectBtn.textContent = 'Disconnect';
                    });
            });
        }

        log('Page initialized', {
            hasToken: @json($account && $account->is_active)
        });
    </script>
</body>

</html>
