(function ($, Drupal) {
  'use strict';

  const settings = window.drupalSettings?.appwrite_integration || {};
  const APPWRITE_ENDPOINT = settings.endpoint;
  const APPWRITE_PROJECT_ID = settings.project_id;

  let appwrite;
  let account;

  async function initializeAppwrite() {
    try {
      if (typeof window.Appwrite === 'undefined') {
        await loadAppwriteSDK();
      }
      appwrite = new window.Appwrite.Client()
        .setEndpoint(APPWRITE_ENDPOINT)
        .setProject(APPWRITE_PROJECT_ID);

      account = new window.Appwrite.Account(appwrite);
      console.log('Appwrite initialized');
      return true;
    } catch (error) {
      console.error('Initialization failed:', error);
      return false;
    }
  }

  function loadAppwriteSDK() {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/appwrite@13.0.1/dist/iife/sdk.js';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function showLoading(message) {
    const div = document.getElementById('loading-message') || createMessageDiv('loading-message');
    div.innerHTML = `<div class="loading-spinner"></div> ${message}`;
    div.style.display = 'block';
  }

  function hideLoading() {
    const div = document.getElementById('loading-message');
    if (div) div.style.display = 'none';
  }

  function showError(message) {
    const div = document.getElementById('error-message') || createMessageDiv('error-message');
    div.innerHTML = `<div class="error-icon">⚠️</div> ${message}`;
    div.className = 'message error-message';
    div.style.display = 'block';
  }

  function showSuccess(message) {
    const div = document.getElementById('success-message') || createMessageDiv('success-message');
    div.innerHTML = `<div class="success-icon">✅</div> ${message}`;
    div.className = 'message success-message';
    div.style.display = 'block';
  }

  function createMessageDiv(id) {
    const div = document.createElement('div');
    div.id = id;
    div.className = 'message';
    document.body.insertBefore(div, document.body.firstChild);
    return div;
  }

  async function loginWithGoogle() {
    showLoading('Redirecting to Google...');
    const success = `${window.location.origin}/appwrite/auth/callback#token=true`;
    const failure = `${window.location.origin}/appwrite/login?error=1`;
    account.createOAuth2Session('google', success, failure);
  }

  async function loginWithGitHub() {
    showLoading('Redirecting to GitHub...');
    const success = `${window.location.origin}/appwrite/auth/callback#token=true`;
    const failure = `${window.location.origin}/appwrite/login?error=1`;
    account.createOAuth2Session('github', success, failure);
  }

  async function handleAuthCallback() {
    showLoading('Finalizing authentication...');
    const hash = window.location.hash;
    const params = new URLSearchParams(hash.substring(1));

    if (params.get('token') === 'true') {
      try {
        const user = await account.get();
        localStorage.setItem('appwriteUser', JSON.stringify(user));
        localStorage.setItem('sessionActive', 'true');
        if (user) {
          showSuccess('Authentication successful! Redirecting to dashboard...');
          setTimeout(() => {
            window.location.href = '/appwrite/dashboard';
          }, 1500);
        } else {
          throw new Error('Authentication failed after multiple attempts - no user session found');
        }
        
      } catch (e) {
        console.error('Callback error:', e);
        showError('Authentication failed. Please try again.');
        setTimeout(() => window.location.href = '/appwrite/login?error=1', 2000);
      }
    }
  }

  async function loadDashboard() {
    showLoading('Loading dashboard...');
    const session = localStorage.getItem('sessionActive');
    if (!session) {
      window.location.href = '/appwrite/login';
      return;
    }

    try {
      const user = JSON.parse(localStorage.getItem('appwriteUser')) || await account.get();
      displayUserInfo(user);
      hideLoading();
    } catch (error) {
      console.error('Dashboard load error:', error);
      localStorage.clear();
      showError('Session expired. Redirecting...');
      setTimeout(() => window.location.href = '/appwrite/login', 2000);
    }
  }

  async function logout() {
    showLoading('Logging out...');
    try {
      await account.deleteSession('current');
    } catch (e) {
      console.warn('Session delete failed:', e);
    }
    localStorage.clear();
    showSuccess('Logged out successfully');
    setTimeout(() => window.location.href = '/appwrite/login', 1500);
  }

  function displayUserInfo(user) {
    const html = `
      <div class="user-info">
        <h2>Welcome, ${user.name || user.email}!</h2>
        <p><strong>Email:</strong> ${user.email}</p>
        <p><strong>User ID:</strong> ${user.$id}</p>
        <p><strong>Email Verified:</strong> ${user.emailVerification ? 'Yes' : 'No'}</p>
        <p><strong>Registration:</strong> ${new Date(user.registration).toLocaleDateString()}</p>
        <p><strong>Last Active:</strong> ${new Date(user.accessedAt).toLocaleString()}</p>
      </div>
    `;
    const el = document.getElementById('dashboard-content');
    if (el) el.innerHTML = html;
  }

  $(document).ready(async function () {
    const init = await initializeAppwrite();
    if (!init) return;

    const path = window.location.pathname;

    if (path === '/appwrite/google/login') {
      $('#google-login-btn').on('click', loginWithGoogle);
    } else if (path === '/appwrite/github/login') {
      $('#github-login-btn').on('click', loginWithGitHub);
    } else if (path === '/appwrite/auth/callback') {
      handleAuthCallback();
    } else if (path === '/appwrite/dashboard') {
      loadDashboard();
      $('#logout-btn').on('click', logout);
    } else if (path === '/appwrite/logout') {
      logout();
    }
  });

})(jQuery, Drupal);
