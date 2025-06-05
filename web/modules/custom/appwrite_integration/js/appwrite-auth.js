// (function ($, Drupal) {
//   'use strict';

//   // Appwrite configuration
//   const APPWRITE_ENDPOINT = 'https://fra.cloud.appwrite.io/v1';
//   const APPWRITE_PROJECT_ID = '683ea5970037a0cd8c8b'; // Replace with your actual project ID
  
//   // Debug mode - set to false in production
//   const DEBUG_MODE = true;
  
//   let appwrite;
//   let account;

//   // Initialize Appwrite SDK
//   async function initializeAppwrite() {
//     try {
//       // Load Appwrite SDK dynamically
//       if (typeof window.Appwrite === 'undefined') {
//         await loadAppwriteSDK();
//       }
      
//       appwrite = new window.Appwrite.Client()
//         .setEndpoint(APPWRITE_ENDPOINT)
//         .setProject(APPWRITE_PROJECT_ID);
      
//       account = new window.Appwrite.Account(appwrite);
      
//       console.log('Appwrite initialized successfully');
//       return true;
//     } catch (error) {
//       console.error('Failed to initialize Appwrite:', error);
//       return false;
//     }
//   }

//   // Load Appwrite SDK
//   function loadAppwriteSDK() {
//     return new Promise((resolve, reject) => {
//       const script = document.createElement('script');
//       script.src = 'https://cdn.jsdelivr.net/npm/appwrite@13.0.1/dist/iife/sdk.js';
//       script.onload = resolve;
//       script.onerror = reject;
//       document.head.appendChild(script);
//     });
//   }

//   // Check if user is authenticated
//   async function checkAuth() {
//     try {
//       const user = await account.get();
//       if (DEBUG_MODE) console.log('User authenticated:', user);
//       return user;
//     } catch (error) {
//       if (DEBUG_MODE) console.log('User not authenticated:', error.message, error);
//       return null;
//     }
//   }

//   // Debug function to check session and cookies
//   async function debugSession() {
//     if (!DEBUG_MODE) return;
    
//     console.log('=== DEBUG SESSION INFO ===');
//     console.log('Current URL:', window.location.href);
//     console.log('All cookies:', document.cookie);
    
//     // Check for Appwrite session cookies
//     const cookies = document.cookie.split(';').reduce((acc, cookie) => {
//       const [name, value] = cookie.trim().split('=');
//       acc[name] = value;
//       return acc;
//     }, {});
    
//     const appwriteCookies = Object.keys(cookies).filter(key => 
//       key.includes('appwrite') || key.includes('session')
//     );
    
//     console.log('Appwrite-related cookies:', appwriteCookies.map(key => ({
//       key,
//       value: cookies[key]
//     })));
    
//     try {
//       const sessions = await account.listSessions();
//       console.log('Active sessions:', sessions);
//     } catch (error) {
//       console.log('Error getting sessions:', error);
//     }
    
//     console.log('=== END DEBUG INFO ===');
//   }

//   // Login with GitHub
//   async function loginWithGitHub() {
//     try {
//       if (DEBUG_MODE) {
//         console.log('=== LOGIN START ===');
//         console.log('Current domain:', window.location.origin);
//       }
      
//       // Use absolute URLs to avoid any routing issues
//       const successUrl = window.location.origin + '/appwrite/auth/callback';
//       const failureUrl = window.location.origin + '/appwrite/login?error=1';
      
//       if (DEBUG_MODE) {
//         console.log('Success URL:', successUrl);
//         console.log('Failure URL:', failureUrl);
//       }
      
//       showLoading('Redirecting to GitHub...');
      
//       // Create OAuth2 session
//       account.createOAuth2Session(
//         'github',
//         successUrl,
//         failureUrl
//       );
      
//     } catch (error) {
//       console.error('Login failed:', error);
//       showError('Login failed: ' + error.message);
//     }
//   }

//   // Handle authentication callback
//   async function handleAuthCallback() {
//     try {
//       showLoading('Completing authentication...');
      
//       if (DEBUG_MODE) {
//         console.log('=== AUTH CALLBACK START ===');
//         console.log('URL params:', new URLSearchParams(window.location.search).toString());
//         console.log('Hash:', window.location.hash);
//       }
      
//       // Debug session info
//       await debugSession();
      
//       // Wait longer for the session to be established
//       let attempts = 0;
//       const maxAttempts = 10;
//       let user = null;
      
//       while (attempts < maxAttempts) {
//         try {
//           if (DEBUG_MODE) console.log(`Authentication attempt ${attempts + 1}/${maxAttempts}`);
          
//           // Wait before each attempt
//           await new Promise(resolve => setTimeout(resolve, 1000));
          
//           user = await account.get();
//           if (user) {
//             if (DEBUG_MODE) console.log('Authentication successful:', user);
//             break;
//           }
//         } catch (error) {
//           if (DEBUG_MODE) console.log(`Attempt ${attempts + 1} failed:`, error.message);
//           attempts++;
          
//           // If it's a network error, wait longer
//           if (error.message.includes('network') || error.code === 0) {
//             await new Promise(resolve => setTimeout(resolve, 2000));
//           }
//         }
//       }
      
//       if (user) {
//         showSuccess('Authentication successful! Redirecting to dashboard...');
//         setTimeout(() => {
//           window.location.href = '/appwrite/dashboard';
//         }, 1500);
//       } else {
//         throw new Error('Authentication failed after multiple attempts - no user session found');
//       }
      
//     } catch (error) {
//       console.error('Auth callback error:', error);
      
//       if (DEBUG_MODE) {
//         console.log('=== AUTH CALLBACK ERROR DEBUG ===');
//         await debugSession();
//         console.log('Error details:', {
//           message: error.message,
//           code: error.code,
//           type: error.type,
//           response: error.response
//         });
//       }
      
//       showError('Authentication failed: ' + error.message + '. Please try again.');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 4000);
//     }
//   }

//   // Load and display user dashboard
//   async function loadDashboard() {
//     try {
//       showLoading('Loading dashboard...');
      
//       const user = await checkAuth();
//       if (!user) {
//         throw new Error('User not authenticated');
//       }

//       // Display user information
//       displayUserInfo(user);
//       hideLoading();
      
//     } catch (error) {
//       console.error('Dashboard load error:', error);
//       showError('Failed to load dashboard. Redirecting to login...');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 2000);
//     }
//   }

//   // Display user information
//   function displayUserInfo(user) {
//     const userInfoHtml = `
//       <div class="user-info">
//         <h2>Welcome, ${user.name || user.email}!</h2>
//         <div class="user-details">
//           <p><strong>Email:</strong> ${user.email}</p>
//           <p><strong>User ID:</strong> ${user.$id}</p>
//           <p><strong>Registration:</strong> ${new Date(user.registration).toLocaleDateString()}</p>
//           <p><strong>Last Active:</strong> ${new Date(user.accessedAt).toLocaleString()}</p>
//           <p><strong>Email Verified:</strong> ${user.emailVerification ? 'Yes' : 'No'}</p>
//         </div>
//       </div>
//     `;
    
//     const dashboardContent = document.getElementById('dashboard-content');
//     if (dashboardContent) {
//       dashboardContent.innerHTML = userInfoHtml;
//     }
//   }

//   // Logout user
//   async function logout() {
//     try {
//       showLoading('Logging out...');
      
//       // Delete current session
//       await account.deleteSession('current');
      
//       showSuccess('Logged out successfully!');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 1500);
      
//     } catch (error) {
//       console.error('Logout error:', error);
//       // Even if logout fails on server, redirect to login
//       showError('Logout completed. Redirecting...');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 1500);
//     }
//   }

//   // Utility functions for UI feedback
//   function showLoading(message) {
//     const loadingDiv = document.getElementById('loading-message') || createMessageDiv('loading-message');
//     loadingDiv.innerHTML = `<div class="loading-spinner"></div> ${message}`;
//     loadingDiv.style.display = 'block';
//   }

//   function hideLoading() {
//     const loadingDiv = document.getElementById('loading-message');
//     if (loadingDiv) {
//       loadingDiv.style.display = 'none';
//     }
//   }

//   function showError(message) {
//     const errorDiv = document.getElementById('error-message') || createMessageDiv('error-message');
//     errorDiv.innerHTML = `<div class="error-icon">⚠️</div> ${message}`;
//     errorDiv.className = 'message error-message';
//     errorDiv.style.display = 'block';
//   }

//   function showSuccess(message) {
//     const successDiv = document.getElementById('success-message') || createMessageDiv('success-message');
//     successDiv.innerHTML = `<div class="success-icon">✅</div> ${message}`;
//     successDiv.className = 'message success-message';
//     successDiv.style.display = 'block';
//   }

//   function createMessageDiv(id) {
//     const div = document.createElement('div');
//     div.id = id;
//     div.className = 'message';
//     document.body.insertBefore(div, document.body.firstChild);
//     return div;
//   }

//   // Initialize when DOM is ready
//   $(document).ready(async function() {
//     console.log('Initializing Appwrite OAuth module...');
    
//     const initialized = await initializeAppwrite();
//     if (!initialized) {
//       console.error('Failed to initialize Appwrite');
//       return;
//     }

//     // Handle different pages
//     const currentPath = window.location.pathname;
    
//     if (currentPath === '/appwrite/login') {
//       // Login page
//       $('#github-login-btn').on('click', function(e) {
//         e.preventDefault();
//         loginWithGitHub();
//       });
      
//       // Check if there's an error parameter
//       const urlParams = new URLSearchParams(window.location.search);
//       if (urlParams.get('error')) {
//         showError('Authentication failed. Please try again.');
//       }
      
//     } else if (currentPath === '/appwrite/auth/callback') {
//       // Auth callback page
//       handleAuthCallback();
      
//     } else if (currentPath === '/appwrite/dashboard') {
//       // Dashboard page
//       loadDashboard();
      
//       $('#logout-btn').on('click', function(e) {
//         e.preventDefault();
//         logout();
//       });
      
//     } else if (currentPath === '/appwrite/logout') {
//       // Logout page
//       logout();
//     }
//   });

// })(jQuery, Drupal);




// // Updated Appwrite OAuth module using localStorage instead of third-party cookies
// (function ($, Drupal) {
//   'use strict';

//   const APPWRITE_ENDPOINT = 'https://fra.cloud.appwrite.io/v1';
//   const APPWRITE_PROJECT_ID = '683ea5970037a0cd8c8b';
//   const DEBUG_MODE = true;

//   let appwrite;
//   let account;

//   async function initializeAppwrite() {
//     try {
//       if (typeof window.Appwrite === 'undefined') {
//         await loadAppwriteSDK();
//       }
//       appwrite = new window.Appwrite.Client()
//         .setEndpoint(APPWRITE_ENDPOINT)
//         .setProject(APPWRITE_PROJECT_ID);

//       account = new window.Appwrite.Account(appwrite);
//       console.log('Appwrite initialized');
//       return true;
//     } catch (error) {
//       console.error('Initialization failed:', error);
//       return false;
//     }
//   }

//   function loadAppwriteSDK() {
//     return new Promise((resolve, reject) => {
//       const script = document.createElement('script');
//       script.src = 'https://cdn.jsdelivr.net/npm/appwrite@13.0.1/dist/iife/sdk.js';
//       script.onload = resolve;
//       script.onerror = reject;
//       document.head.appendChild(script);
//     });
//   }

//   async function loginWithGitHub() {
//     const success = `${window.location.origin}/appwrite/auth/callback#token=true`;
//     const failure = `${window.location.origin}/appwrite/login?error=1`;

//     account.createOAuth2Session('github', success, failure);
//   }

//   async function handleAuthCallback() {
//     const hash = window.location.hash;
//     const params = new URLSearchParams(hash.substring(1));

//     if (params.get('token') === 'true') {
//       try {
//         const user = await account.get();
//         localStorage.setItem('appwriteUser', JSON.stringify(user));
//         localStorage.setItem('sessionActive', 'true');
//         window.location.href = '/appwrite/dashboard';
//       } catch (e) {
//         console.error('Callback error:', e);
//         window.location.href = '/appwrite/login?error=1';
//       }
//     }
//   }

//   async function loadDashboard() {
//     const session = localStorage.getItem('sessionActive');
//     if (!session) {
//       window.location.href = '/appwrite/login';
//       return;
//     }

//     try {
//       const user = JSON.parse(localStorage.getItem('appwriteUser')) || await account.get();
//       displayUserInfo(user);
//     } catch (error) {
//       console.error('Dashboard load error:', error);
//       localStorage.clear();
//       window.location.href = '/appwrite/login';
//     }
//   }

//   async function logout() {
//     try {
//       await account.deleteSession('current');
//     } catch (e) {
//       console.warn('Session delete failed:', e);
//     }
//     localStorage.clear();
//     window.location.href = '/appwrite/login';
//   }

//   function displayUserInfo(user) {
//     const html = `
//       <div class="user-info">
//         <h2>Welcome, ${user.name || user.email}!</h2>
//         <p><strong>Email:</strong> ${user.email}</p>
//         <p><strong>User ID:</strong> ${user.$id}</p>
//         <p><strong>Email Verified:</strong> ${user.emailVerification ? 'Yes' : 'No'}</p>
//       </div>
//     `;
//     const el = document.getElementById('dashboard-content');
//     if (el) el.innerHTML = html;
//   }

//   $(document).ready(async function () {
//     const init = await initializeAppwrite();
//     if (!init) return;

//     const path = window.location.pathname;

//     if (path === '/appwrite/login') {
//       $('#github-login-btn').on('click', loginWithGitHub);
//     } else if (path === '/appwrite/auth/callback') {
//       handleAuthCallback();
//     } else if (path === '/appwrite/dashboard') {
//       loadDashboard();
//       $('#logout-btn').on('click', logout);
//     } else if (path === '/appwrite/logout') {
//       logout();
//     }
//   });

// })(jQuery, Drupal);


// Updated Appwrite OAuth module using localStorage with loading spinners
(function ($, Drupal) {
  'use strict';

  const APPWRITE_ENDPOINT = 'https://fra.cloud.appwrite.io/v1';
  const APPWRITE_PROJECT_ID = '683ea5970037a0cd8c8b';
  const DEBUG_MODE = true;

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


//   // Logout user
//   async function logout() {
//     try {
//       showLoading('Logging out...');
      
//       // Delete current session
//       await account.deleteSession('current');
      
//       showSuccess('Logged out successfully!');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 1500);
      
//     } catch (error) {
//       console.error('Logout error:', error);
//       // Even if logout fails on server, redirect to login
//       showError('Logout completed. Redirecting...');
//       setTimeout(() => {
//         window.location.href = '/appwrite/login';
//       }, 1500);
//     }
//   }

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

    if (path === '/appwrite/login') {
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
