<!DOCTYPE html>
<html>
<head>
    <style>
        .hetzner-panel {
            background: #ffffff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .hetzner-header {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f5f8fa;
        }
        
        .hetzner-logo {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #d50c2d 0%, #c2185b 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            color: white;
            font-size: 18px;
        }
        
        .hetzner-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: auto;
        }
        
        .status-running {
            background: #d4edda;
            color: #155724;
        }
        
        .status-stopped {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-starting {
            background: #fff3cd;
            color: #856404;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 6px;
            border-left: 3px solid #d50c2d;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #1a1a1a;
            font-weight: 500;
            word-break: break-all;
        }
        
        .info-value code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn-hetzner {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #d50c2d;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b00a25;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(213, 12, 45, 0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .server-specs {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .spec-item {
            text-align: center;
        }
        
        .spec-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .spec-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        
        .spec-value {
            font-size: 18px;
            font-weight: 700;
        }
        
        .quick-actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .quick-actions h3 {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: #1a1a1a;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 12px 16px;
            border-radius: 4px;
            margin-top: 16px;
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-hetzner {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="hetzner-panel">
        <div class="hetzner-header">
            <div class="hetzner-logo">H</div>
            <h2 class="hetzner-title">{$server_name}</h2>
            <span class="status-badge status-{$status|lower}">{$status}</span>
        </div>
        
        <div class="server-specs">
            <h3 style="margin: 0 0 8px 0; font-size: 16px;">Server Specifications</h3>
            <div style="opacity: 0.9; font-size: 14px; margin-bottom: 12px;">{$server_type} • {$location}</div>
            <div class="specs-grid">
                <div class="spec-item">
                    <div class="spec-icon">🖥️</div>
                    <div class="spec-label">Server ID</div>
                    <div class="spec-value">#{$server_id}</div>
                </div>
                <div class="spec-item">
                    <div class="spec-icon">💿</div>
                    <div class="spec-label">OS Image</div>
                    <div class="spec-value" style="font-size: 14px;">{$image}</div>
                </div>
                <div class="spec-item">
                    <div class="spec-icon">📅</div>
                    <div class="spec-label">Created</div>
                    <div class="spec-value" style="font-size: 13px;">{$created|date_format:"%b %d, %Y"}</div>
                </div>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">IPv4 Address</div>
                <div class="info-value"><code>{$ip_address}</code></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">IPv6 Network</div>
                <div class="info-value"><code>{$ipv6_network}</code></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Location</div>
                <div class="info-value">{$location}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Server Type</div>
                <div class="info-value">{$server_type}</div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <button type="button" class="btn-hetzner btn-primary" onclick="moduleAction('RebootServer')">
                    🔄 Reboot Server
                </button>
                <button type="button" class="btn-hetzner btn-secondary" onclick="moduleAction('ResetPassword')">
                    🔑 Reset Password
                </button>
                <a href="clientarea.php?action=productdetails&id={$serviceid}" class="btn-hetzner btn-secondary">
                    ⚙️ Manage Service
                </a>
            </div>
        </div>
        
        <div class="alert-info">
            <strong>ℹ️ Access Information:</strong> You can access your server using SSH with the IP address above. Root credentials are available in the service details.
        </div>
    </div>
    
    <script>
        function moduleAction(action) {
            if (confirm('Are you sure you want to perform this action?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=' + action;
                
                var token = document.querySelector('input[name="token"]');
                if (token) {
                    var tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'token';
                    tokenInput.value = token.value;
                    form.appendChild(tokenInput);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>