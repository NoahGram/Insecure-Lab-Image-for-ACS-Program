<h2 class="page-title">System Diagnostics</h2>

<?php if (!is_admin()): ?>
    <div class="muted" style="color:#a33;">Access denied. Administrator privileges required.</div>
<?php else: ?>

    <div style="margin-bottom: 20px;">
        <p class="small muted">Run system checks to verify server connectivity and environment configuration.</p>
    </div>

    <div style="margin-bottom: 30px;">
        <h3 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
            Connectivity Checker
        </h3>
        <form id="connectivity-form" onsubmit="event.preventDefault(); checkConnectivity();"
            style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1; max-width: 400px;">
                <label class="small muted" for="url-input">Target URL</label><br>
                <input type="text" id="url-input" class="input" placeholder="https://api.example.com/health"
                    style="width: 100%;" required>
            </div>
            <button type="submit" class="btn" style="margin-left: 20px;">Check Status</button>
        </form>

        <div id="connectivity-result" style="margin-top: 15px; display: none;">
            <div
                style="background: #f9fafb; border: 1px solid #ddd; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; overflow-x: auto; word-break: break-all;">
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px;">

        <div>
            <h3 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                Environment Info
            </h3>
            <p class="small muted" style="margin-bottom: 15px;">Inspect server configuration and session data.</p>
            <div style="display: flex; gap: 10px;">
                <button onclick="runDiagnostic('api_test', {show_config:1})" class="btn"
                    style="background: #fff; border: 1px solid #ddd; color: #333;">PHP Config</button>
                <button onclick="runDiagnostic('api_test', {show_session:1})" class="btn"
                    style="background: #fff; border: 1px solid #ddd; color: #333;">Session State</button>
            </div>
            <div id="env-result" style="margin-top: 15px; display: none;">
                <div
                        style="background: #f9fafb; border: 1px solid #ddd; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; overflow-x: auto; max-height: 300px; word-break: break-all;">
                    </div>
            </div>
        </div>

        <div>
            <h3 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                Database Health
            </h3>
            <p class="small muted" style="margin-bottom: 15px;">Verify database connection parameters.</p>
            <button onclick="runDiagnostic('diagnostics', {check:'db'})" class="btn"
                style="background: #fff; border: 1px solid #ddd; color: #333;">Run Integrity Check</button>
            <div id="db-result" style="margin-top: 15px; display: none;">
                <div
                    style="background: #f9fafb; border: 1px solid #ddd; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; overflow-x: auto; word-break: break-all;">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-left: 4px solid #3b82f6;">
        <h3 style="margin: 0 0 10px 0; font-size: 14px;">Diagnostic Tool Dictionary</h3>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
            <li><strong>Connectivity:</strong> Performs integration checks to confirm external service availability.</li>
            <li><strong>Environment:</strong> Inspects loaded extensions and session data.</li>
            <li><strong>Database:</strong> Verifies database availability, latency, and server metadata.</li>
        </ul>
    </div>

    <script>
        async function checkConnectivity() {
            const url = document.getElementById('url-input').value;
            const container = document.getElementById('connectivity-result');
            const resultBox = container.firstElementChild;

            container.style.display = 'block';
            resultBox.textContent = 'Pinging resource...';
            resultBox.style.color = '#666';

            try {
                const formData = new FormData();
                formData.append('url', url);

                const response = await fetch('?action=fetch_resource', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                try {
                    const json = JSON.parse(text);
                    resultBox.textContent = JSON.stringify(json, null, 2);
                    resultBox.style.color = '#333';
                } catch (e) {
                    resultBox.textContent = text;
                }
            } catch (err) {
                resultBox.textContent = 'Error: ' + err.message;
                resultBox.style.color = '#d32f2f';
            }
        }

        async function runDiagnostic(action, params) {
            const container = document.getElementById(action === 'api_test' ? 'env-result' : 'db-result');
            const resultBox = container.firstElementChild;

            container.style.display = 'block';
            resultBox.textContent = 'Running diagnostic...';
            resultBox.style.color = '#666';

            const qs = new URLSearchParams(params).toString();

            try {
                const response = await fetch(`?action=${action}&${qs}`);
                const text = await response.text();
                try {
                    const json = JSON.parse(text);
                    resultBox.textContent = JSON.stringify(json, null, 2);
                    resultBox.style.color = '#333';
                } catch (e) {
                    resultBox.textContent = text;
                }
            } catch (err) {
                resultBox.textContent = 'Error: ' + err.message;
                resultBox.style.color = '#d32f2f';
            }
        }
    </script>

<?php endif; ?>