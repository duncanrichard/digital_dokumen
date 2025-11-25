@extends('layouts/blankLayout')

@section('title', 'Login')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  .auth-container-premium {
    min-height: 100vh;
    display: flex;
    position: relative;
    background: #0f172a;
    overflow: hidden;
  }
  
  /* Animated Background */
  .auth-container-premium::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: linear-gradient(45deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    top: -250px;
    right: -250px;
    opacity: 0.3;
    filter: blur(80px);
    animation: float 8s ease-in-out infinite;
  }
  
  .auth-container-premium::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: linear-gradient(45deg, #ec4899, #f43f5e);
    border-radius: 50%;
    bottom: -200px;
    left: -200px;
    opacity: 0.3;
    filter: blur(80px);
    animation: float 6s ease-in-out infinite reverse;
  }
  
  @keyframes float {
    0%, 100% { transform: translateY(0) translateX(0); }
    50% { transform: translateY(-20px) translateX(20px); }
  }
  
  /* Glassmorphism Card */
  .glass-card {
    position: relative;
    z-index: 10;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 480px;
    margin: auto;
    padding: 3.5rem;
  }
  
  /* Logo Section */
  .logo-section {
    text-align: center;
    margin-bottom: 2.5rem;
  }
  
  .logo-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
  }
  
  .logo-icon i {
    font-size: 30px;
    color: white;
  }
  
  .brand-name {
    color: white;
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.5px;
  }
  
  /* Header */
  .auth-header {
    text-align: center;
    margin-bottom: 2.5rem;
  }
  
  .auth-header h1 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #fff, #94a3b8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .auth-header p {
    color: #94a3b8;
    font-size: 0.95rem;
  }
  
  /* Form Groups */
  .form-group-premium {
    margin-bottom: 1.5rem;
    position: relative;
  }
  
  .input-wrapper {
    position: relative;
  }
  
  .input-icon-premium {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 1.25rem;
    z-index: 2;
    transition: color 0.3s;
  }
  
  .form-control-premium {
    width: 100%;
    padding: 1.125rem 1.25rem 1.125rem 3.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
  }
  
  .form-control-premium::placeholder {
    color: #64748b;
  }
  
  .form-control-premium:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
  }
  
  .form-control-premium:focus ~ .input-icon-premium {
    color: #3b82f6;
  }
  
  .form-label-premium {
    display: block;
    color: #cbd5e1;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }
  
  /* Password Toggle */
  .password-toggle-premium {
    position: absolute;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #64748b;
    font-size: 1.25rem;
    z-index: 2;
    transition: color 0.3s;
  }
  
  .password-toggle-premium:hover {
    color: #3b82f6;
  }
  
  /* Remember & Forgot */
  .form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }
  
  .checkbox-wrapper {
    display: flex;
    align-items: center;
  }
  
  .checkbox-premium {
    width: 20px;
    height: 20px;
    margin-right: 0.5rem;
    cursor: pointer;
    accent-color: #3b82f6;
  }
  
  .checkbox-label {
    color: #cbd5e1;
    font-size: 0.9rem;
    cursor: pointer;
    user-select: none;
  }
  
  .forgot-link {
    color: #60a5fa;
    font-size: 0.9rem;
    text-decoration: none;
    transition: color 0.3s;
  }
  
  .forgot-link:hover {
    color: #3b82f6;
  }
  
  /* Button */
  .btn-premium {
    width: 100%;
    padding: 1.125rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 24px rgba(59, 130, 246, 0.4);
    position: relative;
    overflow: hidden;
  }
  
  .btn-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
  }
  
  .btn-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 32px rgba(59, 130, 246, 0.5);
  }
  
  .btn-premium:hover::before {
    left: 100%;
  }
  
  .btn-premium:active {
    transform: translateY(0);
  }
  
  /* Alert */
  .alert-premium {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-left: 4px solid #ef4444;
    color: #fca5a5;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
  }
  
  .alert-premium i {
    margin-right: 0.75rem;
    font-size: 1.25rem;
  }
  
  /* Decorative Elements */
  .decorative-line {
    position: relative;
    text-align: center;
    margin: 2rem 0;
  }
  
  .decorative-line::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  }
  
  .decorative-line span {
    position: relative;
    background: rgba(15, 23, 42, 0.8);
    padding: 0 1rem;
    color: #64748b;
    font-size: 0.85rem;
  }
  
  /* Register Link */
  .register-section {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .register-section p {
    color: #94a3b8;
    font-size: 0.9rem;
  }
  
  .register-link {
    color: #60a5fa;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
  }
  
  .register-link:hover {
    color: #3b82f6;
  }
  
  /* Social Login (Optional) */
  .social-login {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
  }
  
  .social-btn {
    flex: 1;
    padding: 0.875rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: white;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }
  
  .social-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
  }
  
  .social-btn i {
    font-size: 1.125rem;
  }
  
  /* Footer Info */
  .auth-footer {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
    z-index: 10;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .glass-card {
      padding: 2.5rem 2rem;
      margin: 1rem;
    }
    
    .auth-header h1 {
      font-size: 1.75rem;
    }
    
    .logo-icon {
      width: 50px;
      height: 50px;
    }
    
    .logo-icon i {
      font-size: 24px;
    }
    
    .brand-name {
      font-size: 1.5rem;
    }
  }
  
  @media (max-width: 480px) {
    .glass-card {
      padding: 2rem 1.5rem;
    }
    
    .form-options {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }
    
    .social-login {
      flex-direction: column;
    }
  }
  
  /* Loading Animation */
  .btn-premium.loading {
    pointer-events: none;
    opacity: 0.7;
  }
  
  .btn-premium.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
  }
  
  @keyframes spin {
    to { transform: translateY(-50%) rotate(360deg); }
  }
  
  /* Error state */
  .form-control-premium.error {
    border-color: #ef4444;
  }
  
  .error-message {
    color: #fca5a5;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .error-message i {
    font-size: 1rem;
  }
</style>
@endsection

@section('content')
<div class="auth-container-premium">
  <div class="glass-card">
    <!-- Logo Section -->
    <div class="logo-section">
      <div class="logo-icon">
        @include('_partials.macros',["height"=>30,"withbg"=>'fill: #fff;'])
      </div>
      <div class="brand-name">{{ config('variables.templateName') }}</div>
    </div>
    
    <!-- Header -->
    <div class="auth-header">
      <h1>Welcome Back</h1>
      <p>Sign in to access your account</p>
    </div>
    
    <!-- Alert -->
    @if ($errors->any())
      <div class="alert-premium">
        <i class="mdi mdi-alert-circle"></i>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif
    
    <!-- Login Form -->
    <form id="formAuthentication" action="{{ route('login.perform') }}" method="POST" novalidate>
      @csrf
      
      <!-- Username Field -->
      <div class="form-group-premium">
        <label for="username" class="form-label-premium">Username</label>
        <div class="input-wrapper">
          <i class="mdi mdi-account-outline input-icon-premium"></i>
          <input type="text" 
                 class="form-control-premium @error('username') error @enderror" 
                 id="username" 
                 name="username" 
                 value="{{ old('username') }}"
                 placeholder="Enter your username" 
                 autofocus 
                 required>
        </div>
        @error('username')
          <div class="error-message">
            <i class="mdi mdi-alert-circle-outline"></i>
            <span>{{ $message }}</span>
          </div>
        @enderror
      </div>
      
      <!-- Password Field -->
      <div class="form-group-premium">
        <label for="password" class="form-label-premium">Password</label>
        <div class="input-wrapper">
          <i class="mdi mdi-lock-outline input-icon-premium"></i>
          <input type="password" 
                 class="form-control-premium @error('password') error @enderror" 
                 id="password" 
                 name="password" 
                 placeholder="Enter your password" 
                 required>
          <span class="password-toggle-premium" onclick="togglePasswordVisibility()">
            <i class="mdi mdi-eye-off-outline" id="togglePasswordIcon"></i>
          </span>
        </div>
        @error('password')
          <div class="error-message">
            <i class="mdi mdi-alert-circle-outline"></i>
            <span>{{ $message }}</span>
          </div>
        @enderror
      </div>
      
      <!-- Remember & Forgot -->
      <div class="form-options">
        <div class="checkbox-wrapper">
          <input type="checkbox" 
                 class="checkbox-premium" 
                 id="remember" 
                 name="remember" 
                 value="1">
          <label for="remember" class="checkbox-label">Remember me</label>
        </div>
        {{-- <a href="{{ url('auth/forgot-password-basic') }}" class="forgot-link">Forgot Password?</a> --}}
      </div>
      
      <!-- Submit Button -->
      <button type="submit" class="btn-premium" id="loginBtn">
        <span>Sign In</span>
      </button>
    </form>
    
    {{-- Optional: Social Login
    <div class="decorative-line">
      <span>Or continue with</span>
    </div>
    
    <div class="social-login">
      <button type="button" class="social-btn">
        <i class="mdi mdi-google"></i>
        <span>Google</span>
      </button>
      <button type="button" class="social-btn">
        <i class="mdi mdi-microsoft"></i>
        <span>Microsoft</span>
      </button>
    </div>
    --}}
    
    {{-- Optional: Register Link
    <div class="register-section">
      <p>Don't have an account? <a href="{{ url('auth/register-basic') }}" class="register-link">Create Account</a></p>
    </div>
    --}}
  </div>
  
  <!-- Footer -->
  <div class="auth-footer">
    © {{ date('Y') }} {{ config('variables.templateName') }}. All rights reserved.
  </div>
</div>

<script>
  // Toggle Password Visibility
  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      toggleIcon.classList.remove('mdi-eye-off-outline');
      toggleIcon.classList.add('mdi-eye-outline');
    } else {
      passwordInput.type = 'password';
      toggleIcon.classList.remove('mdi-eye-outline');
      toggleIcon.classList.add('mdi-eye-off-outline');
    }
  }
  
  // Form Submit Loading State
  document.getElementById('formAuthentication').addEventListener('submit', function(e) {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.querySelector('span').textContent = 'Signing in...';
  });
  
  // Clear error on input
  const inputs = document.querySelectorAll('.form-control-premium');
  inputs.forEach(input => {
    input.addEventListener('input', function() {
      this.classList.remove('error');
      const errorMsg = this.closest('.form-group-premium').querySelector('.error-message');
      if (errorMsg) {
        errorMsg.style.display = 'none';
      }
    });
  });
</script>
@endsection