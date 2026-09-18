<x-guest-layout>
    @push('styles')
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            position: relative;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0fdfa 100%);
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            animation: blobFloat 20s ease-in-out infinite;
        }

        .blob-1 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, #93c5fd, #60a5fa);
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #c4b5fd, #a78bfa);
            bottom: -150px;
            right: -150px;
            animation-delay: -7s;
        }

        .blob-3 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #99f6e4, #5eead4);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -14s;
        }

        @keyframes blobFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(-30px, -20px) scale(1.02); }
        }

        /* Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(59, 130, 246, 0.4);
            border-radius: 50%;
            animation: particleFloat 15s linear infinite;
        }

        @keyframes particleFloat {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 80px;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 70px;
            animation: slideInLeft 0.8s ease-out;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.35);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #1e293b, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .character-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.08);
            margin-bottom: 50px;
            max-width: 420px;
            animation: floatCard 6s ease-in-out infinite, slideInLeft 0.8s ease-out 0.2s both;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .character-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 35px 60px rgba(59, 130, 246, 0.2);
        }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .character-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }

        .avatar {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            animation: avatarGlow 3s ease-in-out infinite;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        @keyframes avatarGlow {
            0%, 100% { box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3); }
            50% { box-shadow: 0 8px 30px rgba(59, 130, 246, 0.5); }
        }

        .character-info h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .character-info p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .status-badge {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: badgeShine 3s ease-in-out infinite;
            margin-left: auto;
        }

        @keyframes badgeShine {
            0%, 100% { box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 4px 25px rgba(6, 182, 212, 0.6); }
        }

        .progress-section {
            margin-bottom: 20px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            font-weight: 500;
        }

        .progress-bar {
            height: 10px;
            background: linear-gradient(90deg, #e2e8f0, #cbd5e1);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #06b6d4, #10b981);
            background-size: 200% 100%;
            border-radius: 5px;
            animation: shimmer 3s ease-in-out infinite;
            width: 0%;
            transition: width 2s ease-out;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .xp-dots {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .xp-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s ease;
            animation: dotPulse 2s ease-in-out infinite;
        }

        .xp-dot:nth-child(2) { animation-delay: 0.2s; }
        .xp-dot:nth-child(3) { animation-delay: 0.4s; }
        .xp-dot:nth-child(4) { animation-delay: 0.6s; }
        .xp-dot:nth-child(5) { animation-delay: 0.8s; }
        .xp-dot:nth-child(6) { animation-delay: 1s; }

        .xp-dot.active {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        }

        .xp-dot.gold {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.6);
            animation: goldSparkle 1.5s ease-in-out infinite;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes goldSparkle {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(180deg); }
        }

        .main-heading {
            font-size: 48px;
            font-weight: 900;
            line-height: 1.15;
            color: var(--text-primary);
            margin-bottom: 24px;
            animation: slideInLeft 0.8s ease-out 0.4s both;
        }

        .main-heading span {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 4s ease-in-out infinite;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 200% center; }
        }

        .description {
            font-size: 18px;
            line-height: 1.75;
            color: var(--text-secondary);
            max-width: 480px;
            animation: slideInLeft 0.8s ease-out 0.6s both;
            font-weight: 400;
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 80px;
            position: relative;
            z-index: 1;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            animation: slideInRight 0.8s ease-out 0.3s both;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .welcome-text h1 {
            font-size: 38px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .welcome-text p {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 18px 20px 18px 52px;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            font-size: 15px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            outline: none;
            background: white;
            font-weight: 500;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.1), 0 10px 30px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 17px;
            transition: all 0.3s ease;
        }

        .form-input:focus + .input-icon,
        .form-input:focus ~ .input-icon {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 17px;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 4px;
        }

        .toggle-password:hover {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.15);
        }

        .forgot-link {
            float: right;
            font-size: 13px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .forgot-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .forgot-link:hover::after {
            width: 100%;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 22px;
            height: 22px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background: white;
        }

        .custom-checkbox.checked {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-color: #3b82f6;
            animation: checkPop 0.3s ease;
        }

        @keyframes checkPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .custom-checkbox i {
            color: white;
            font-size: 12px;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }

        .custom-checkbox.checked i {
            opacity: 1;
            transform: scale(1);
        }

        .checkbox-wrapper label {
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: 500;
        }

        .submit-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.35);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .submit-btn:hover:not(:disabled)::before {
            left: 100%;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.45);
        }

        .submit-btn:active:not(:disabled) {
            transform: translateY(-2px) scale(0.98);
        }

        .error-text {
            color: #ef4444;
            font-size: 13px;
            margin-top: 8px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        @media (max-width: 1024px) {
            .left-panel { display: none; }
            .right-panel { padding: 40px 24px; }
            .animated-bg { display: none; }
            body { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); }
        }

        @media (max-width: 480px) {
            .welcome-text h1 { font-size: 30px; }
            .main-heading { font-size: 36px; }
        }
    </style>
    @endpush

    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="particles" id="particles-container"></div>
    </div>

    <div class="main-container">
        <!-- Left Panel - Branding -->
        <div class="left-panel">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="logo-text">CRM Portal</div>
            </div>

            <!-- Character/Status Card -->
            <div class="character-card">
                <div class="character-header">
                    <div class="avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="character-info">
                        <h3>Welcome Back</h3>
                        <p>Internal System Access</p>
                    </div>
                    <div class="status-badge">Secure Login</div>
                </div>

                <div class="progress-section">
                    <div class="progress-label">
                        <span>System Status</span>
                        <span style="color: #10b981; font-weight: 600;">Online • Active</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                </div>

                <div class="xp-dots">
                    <div class="xp-dot active"></div>
                    <div class="xp-dot active"></div>
                    <div class="xp-dot active"></div>
                    <div class="xp-dot active"></div>
                    <div class="xp-dot active"></div>
                    <div class="xp-dot gold"></div>
                </div>
            </div>

            <h1 class="main-heading">
                Your business<br />
                <span>command center.</span>
            </h1>

            <p class="description">
                Access your CRM dashboard, manage customer relationships, 
                track sales pipeline, and drive growth—all in one powerful platform.
            </p>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="right-panel">
            <div class="login-container">
                <div class="welcome-text">
                    <h1>Welcome Back</h1>
                    <p>Please sign in to continue to your account</p>
                </div>

                <!-- Auth Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <!-- Email Address -->
                    <div class="form-group">
                        <label class="form-label" for="email">Username / Email</label>
                        <div class="input-wrapper">
                            <input 
                                id="email"
                                type="email" 
                                name="email"
                                class="form-input" 
                                placeholder="e.g. noldhy or noldhy@example.com"
                                value="{{ old('email') }}"
                                required autofocus autocomplete="username"
                            />
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="error-text" />
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-link">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        <div class="input-wrapper">
                            <input 
                                id="password"
                                type="password" 
                                name="password"
                                class="form-input" 
                                placeholder="••••••••••••"
                                required autocomplete="current-password"
                            />
                            <i class="fas fa-lock input-icon"></i>
                            <button 
                                type="button"
                                class="toggle-password"
                                id="togglePasswordBtn"
                            >
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="error-text" />
                    </div>

                    <!-- Remember Me -->
                    <div class="checkbox-wrapper" id="rememberWrapper">
                        <input id="remember_me" type="checkbox" name="remember" class="hidden" />
                        <div class="custom-checkbox" id="rememberCheckbox">
                            <i class="fas fa-check"></i>
                        </div>
                        <label for="remember_me">Keep me signed in on this device</label>
                    </div>

                    <button 
                        type="submit" 
                        class="submit-btn"
                        id="submitBtn"
                    >
                        Sign In to Dashboard
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Animate progress bar
            setTimeout(() => {
                document.getElementById('progressBar').style.width = '65%';
            }, 500);

            // Generate particles
            const particlesContainer = document.getElementById('particles-container');
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                
                const size = Math.random() * 8 + 4;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                particle.style.animationDelay = (Math.random() * 15) + 's';
                particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
                
                particlesContainer.appendChild(particle);
            }

            // Toggle Password Visibility
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            
            togglePasswordBtn.addEventListener('click', () => {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePasswordIcon.classList.remove('fa-eye');
                    togglePasswordIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    togglePasswordIcon.classList.remove('fa-eye-slash');
                    togglePasswordIcon.classList.add('fa-eye');
                }
            });

            // Remember Me Custom Checkbox
            const rememberWrapper = document.getElementById('rememberWrapper');
            const rememberCheckbox = document.getElementById('rememberCheckbox');
            const rememberInput = document.getElementById('remember_me');

            rememberWrapper.addEventListener('click', (e) => {
                if(e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
                    rememberInput.checked = !rememberInput.checked;
                }
                updateCheckboxUI();
            });
            rememberInput.addEventListener('change', updateCheckboxUI);

            function updateCheckboxUI() {
                if (rememberInput.checked) {
                    rememberCheckbox.classList.add('checked');
                } else {
                    rememberCheckbox.classList.remove('checked');
                }
            }

            // Submit Button Loading state
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnDefaultLabel = 'Sign In to Dashboard';

            function resetSubmitBtn() {
                if (!submitBtn) return;
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtnDefaultLabel;
            }

            loginForm.addEventListener('submit', () => {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <style>
                        .loading-spinner {
                            display: inline-block;
                            width: 20px;
                            height: 20px;
                            border: 3px solid rgba(255, 255, 255, 0.3);
                            border-top-color: white;
                            border-radius: 50%;
                            animation: spin 0.8s linear infinite;
                            vertical-align: middle;
                        }
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                    <span class="loading-spinner"></span>
                    <span style="margin-left: 12px; vertical-align: middle;">Authenticating...</span>
                `;
            });

            // BUGFIX (Routing Leak): setelah login sukses (302) lalu user menekan BACK,
            // browser merestorasi halaman dari bfcache dalam keadaan tombol "Authenticating..."
            // yang macet (submit sudah terlanjur jalan). Setiap 'pageshow':
            //  1) reset tombol submit ke kondisi normal;
            //  2) jika di-restore dari bfcache DAN session ternyata sudah aktif (login berhasil
            //     sebelumnya), arahkan langsung ke dashboard via lokasi router resmi.
            // NOTE: '/api/auth/status' sengaja TIDAK memakai middleware 'auth' supaya fetch ini
            // tidak menodai 'url.intended' (akar masalah routing leak).
            window.addEventListener('pageshow', (e) => {
                resetSubmitBtn();

                if (!e.persisted) return;

                fetch('{{ route('api.auth.status') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.authenticated) {
                            window.location.replace('{{ route('dashboard') }}');
                        }
                    })
                    .catch(() => {});
            });
        });
    </script>
    @endpush
</x-guest-layout>
