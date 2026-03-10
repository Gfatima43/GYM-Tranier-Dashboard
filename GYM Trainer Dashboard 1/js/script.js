// Flip Container
const flipContainer = document.getElementById('flipContainer');
const signupBtn = document.getElementById('signupBtn');
const loginBtn = document.getElementById('loginBtn');

// Toggle flip animation
if (flipContainer && signupBtn) {
  signupBtn.addEventListener('click', () =>
    flipContainer.classList.add("flipped"),
  );
}

if (flipContainer && loginBtn) {
  loginBtn.addEventListener('click', () =>
    flipContainer.classList.remove("flipped"),
  );
}

// Password visibility toggle
const toggleLoginPassword = document.getElementById('toggleLogin');
const passwordInput = document.getElementById('password');

toggleLoginPassword.addEventListener('click', function() {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    updatePasswordIcon(toggleLoginPassword, type);
});

// Signup password visibility toggle
const toggleSignupPass = document.getElementById('toggleSignupPass');
const signupPasswordInput = document.getElementById('signup-password');

toggleSignupPass.addEventListener('click', function() {
    const type = signupPasswordInput.type === 'password' ? 'text' : 'password';
    signupPasswordInput.type = type;
    updatePasswordIcon(toggleSignupPass, type);
});

// Confirm password visibility toggle
const toggleConfirmPass = document.getElementById('toggleConfirmPass');
const confirmPasswordInput = document.getElementById('confirm-password');

toggleConfirmPass.addEventListener('click', function() {
    const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
    confirmPasswordInput.type = type;
    updatePasswordIcon(toggleConfirmPass, type);
});

// Update password icon
function updatePasswordIcon(button, type) {
    const icon = button.querySelector('i');
    if (type === 'text') {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form submissions
// const loginForm = document.getElementById('loginForm');
// const signupForm = document.getElementById('signupForm');

// // LOGIN
// loginForm.addEventListener('submit', function() {
    //     const email = document.getElementById('email').value;
    //     const password = document.getElementById('password').value;
    //     const remember = document.getElementById('remember').checked;
    
    //     console.log('Login:', { email, password, remember });
    //     // Reset form
    //     loginForm.reset();
    
    //     window.location.href = 'dashboard.php';
    // });
    
// // REGISTER
// signupForm.addEventListener('submit', function() {
//     const password = document.getElementById('signup-password').value;
//     const confirmPassword = document.getElementById('confirm-password').value;

//     if (password !== confirmPassword) {
//         alert('Passwords do not match!');
//         return;
//     }
    
//     // Reset form and flip back to login
//     signupForm.reset();
//     flipContainer.classList.remove('flipped');
// });

// Remember me functionality (localStorage)
const rememberCheckbox = document.getElementById('remember');
const emailInput = document.getElementById('email');

if (rememberCheckbox && emailInput) {
    const passwordInput = document.getElementById('password');

    // Load saved email and password on page load
    window.addEventListener('load', () => {
        const savedEmail = localStorage.getItem('storedEmail');        
        const rememberMe = localStorage.getItem('rememberMe')
        if (savedEmail && rememberMe === 'true') {
            emailInput.value = savedEmail;
            rememberCheckbox.checked = true;
        }
    });

    // Save email when remember me is checked
    rememberCheckbox.addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('storedEmail', emailInput.value);
            localStorage.setItem('rememberMe', 'true');
        } else {
            localStorage.removeItem('storedEmail');
            localStorage.removeItem('rememberMe');
        }
    });

    // Update saved email when input changes and remember me is checked
    emailInput.addEventListener('change', () => {
        if (rememberCheckbox.checked) localStorage.setItem('storedEmail', emailInput.value);
    });
};


// ── Sidebar Toggle (mobile) ──
const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarOverlay = document.getElementById("sidebarOverlay");

if (sidebarToggle && sidebar && sidebarOverlay) {
    sidebarToggle.addEventListener("click", () => {
    sidebar.classList.toggle("open");
    sidebarOverlay.classList.toggle("open");
    });
    sidebarOverlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("open");
    });
}

// ── Active Nav Link (SPA feel) ──
// document.querySelectorAll('.nav-item-link').forEach(link => {
//     link.addEventListener('click', function () {
//         document.querySelectorAll('.nav-item-link').forEach(l => l.classList.remove('active'));
//         this.classList.add('active');
//         // Update topbar title
//         const labels = {
//             dashboard:  'Dashboard',
//             overview:   'Overview',
//             clients:    'My Clients',
//             workouts:   'Workout Plans',
//             schedule:   'Schedule',
//             attendance: 'Attendance',
//             messages:   'Messages',
//             profile:    'Profile',
//         };
//         const page = this.dataset.page;
//         if (page && labels[page]) {
//             document.querySelector('.topbar-title').textContent = labels[page];
//         }
//         if (window.innerWidth < 992) closeSidebar();
//     });
// });

// ── Updated Date Daily ──
document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById('current-date');
    if (!el) return;
    const today   = new Date();
    const options = { weekday:'long', day:'2-digit', month:'short', year:'numeric' };
    el.textContent = today.toLocaleDateString('en-GB', options);
});

// ── Task Toggle ──
function toggleTask(row) {
    const check = row.querySelector('.task-check');
    const text  = row.querySelector('.task-text');
    check.classList.toggle('done');
    text.classList.toggle('done-text');
    if (check.classList.contains('done')) {
        check.innerHTML = '<i class="fas fa-check"></i>';
    } else {
        check.innerHTML = '';
    }
}

// ── Logout ──
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}

// ── Animate progress bars on load ──
window.addEventListener('load', () => {
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0%';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.width = target; }, 200);
        });
    });
});

// ── Counter animation ──
function animateCount(el, target, duration = 1200) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(() => {
        start += step;
        if (start >= target) { el.textContent = el.dataset.suffix ? target + el.dataset.suffix : target; clearInterval(timer); return; }
        el.textContent = Math.floor(start) + (el.dataset.suffix || '');
    }, 16);
}

// ── Search filter ──
const searchInput = document.querySelector('.topbar-search input');
if(searchInput) {
    searchInput.addEventListener("input", function () {
      const query = this.value.toLowerCase();
      document.querySelectorAll(".client-row").forEach((row) => {
        const name = row.querySelector(".client-name");
        if (name) {
          row.style.display = name.textContent.toLowerCase().includes(query) ? "" : "none";
        }
      });
    });
};