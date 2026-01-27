<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frisan - E-commerce with Secure Authentication</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .nav {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            padding: 10px 20px;
            background: #667eea;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: #764ba2;
        }
        
        .hero {
            background: white;
            border-radius: 12px;
            padding: 60px 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .hero h2 {
            font-size: 42px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .feature {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .feature:hover {
            transform: translateY(-5px);
        }
        
        .feature h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .feature p {
            color: #666;
            line-height: 1.6;
        }
        
        .feature-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .endpoints {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .endpoints h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .endpoint {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .endpoint strong {
            color: #667eea;
        }
        
        .footer {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 0;
            text-align: center;
            border-radius: 12px;
            margin-top: 40px;
        }
        
        .footer p {
            color: #666;
            margin: 10px 0;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .status {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .cta-button {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
            margin-top: 20px;
        }
        
        .cta-button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🛍️ Frisan</h1>
            <p>Production-Ready E-commerce with Secure Authentication & Email Verification</p>
            
            <div class="nav">
                <a href="/">🏠 Home</a>
                <a href="/api/auth/signup" style="background: #28a745;">📝 Sign Up</a>
                <a href="/api/auth/login" style="background: #764ba2;">🔐 Login</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="status">
            ✅ System Running! Complete authentication system ready to test.
        </div>

        <div class="hero">
            <h2>Secure Authentication System</h2>
            <p>A complete, production-ready authentication platform with email verification, rate limiting, Google OAuth, and secure logout.</p>
            
            <a href="#endpoints" class="cta-button">View API Endpoints</a>
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">📧</div>
                <h3>Email Verification</h3>
                <p>Automatic 6-digit verification codes sent to email. 15-minute expiry with secure code validation.</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🔐</div>
                <h3>Secure Passwords</h3>
                <p>Bcrypt password hashing with minimum 6 characters. Timing-safe verification to prevent attacks.</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <h3>Rate Limiting</h3>
                <p>5 failed attempts per 15 minutes. Prevents brute force attacks on signup, login, and verification.</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🔵</div>
                <h3>Google OAuth</h3>
                <p>One-click signup with Google. Auto-verifies email and links existing accounts seamlessly.</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📝</div>
                <h3>Activity Logging</h3>
                <p>All authentication events logged. Track user signups, logins, logouts, and verification.</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🚪</div>
                <h3>Secure Logout</h3>
                <p>Proper session cleanup. Destroys cookies and server-side session data securely.</p>
            </div>
        </div>

        <div class="endpoints" id="endpoints">
            <h3>🔌 API Endpoints (Ready to Test)</h3>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/signup<br>
                Create account with email, password, name, and confirm password
            </div>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/verify-email<br>
                Verify email with 6-digit code sent to inbox
            </div>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/login<br>
                Login with email and password (must be verified first)
            </div>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/logout<br>
                Logout and destroy session (idempotent)
            </div>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/resend-code<br>
                Request new verification code (3 attempts per 5 min)
            </div>
            
            <div class="endpoint">
                <strong>POST</strong> /api/auth/google<br>
                Signup/login with Google OAuth
            </div>
        </div>

        <div class="footer">
            <h3>🚀 Ready to Use</h3>
            <p><strong>Test with Postman:</strong> Import the API endpoints and test all 6 flows</p>
            <p><strong>View Docs:</strong> Read <code>READ_FIRST.md</code> for complete documentation</p>
            <p><strong>Production Ready:</strong> Apply 3 critical security fixes (30 min) before deployment</p>
            <p style="margin-top: 30px; font-size: 12px; color: #999;">
                Built with PHP • MySQL • Security-First Architecture<br>
                © 2026 Frisan - Complete E-commerce Solution
            </p>
        </div>
    </div>
</body>
</html>
