<?php
session_start();

$logged_in = isset($_SESSION['user_id']);
$currentPage = 'profile';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GYM Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php include 'assets/sidebar.php'; ?>
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Profile</div>
                <div class="topbar-subtitle">Manage your account</div>
            </div>
        </div>
        <div class="topbar-actions">
            <button class="topbar-btn"><i class="fas fa-bell"></i><span class="topbar-notif">4</span></button>
            <div class="topbar-user">
                <div class="user-avatar-sm">IM</div>
                <span class="user-name-sm">Irfan Malik</span><i class="fas fa-chevron-down" style="font-size:1rem;color:rgba(6,30,41,.4);margin-left:.4rem"></i>
            </div>
            <div class="topbar-btn">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class="fas fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                </button>
            </div>
        </div>
    </header>
    <main class="main-content">
        <!-- Hero Banner -->
        <div class="profile-hero">
            <div class="profile-cover">
                <div class="profile-av-wrap">
                    <div class="profile-av-lg">IM</div>
                    <div class="profile-cam"><i class="fas fa-camera"></i></div>
                </div>
                <div class="profile-info-hero">
                    <div class="profile-hero-name">Irfan Malik</div>
                    <div class="profile-hero-role">Head Trainer · Gym</div>
                    <div style="display:flex;align-items:center;gap:.6rem;margin-top:.6rem;position:relative;z-index:1">
                        <span style="font-size:1.15rem;color:rgba(255,255,255,.5)"><i class="fas fa-location-dot" style="margin-right:.4rem"></i>Islamabad, PK</span>
                        <span style="color:rgba(255,255,255,.2)">·</span>
                        <span style="font-size:1.15rem;color:#22c55e"><i class="fas fa-circle" style="font-size:.7rem;margin-right:.3rem"></i>Available</span>
                    </div>
                </div>
                <div class="profile-hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-val">8</div>
                        <div class="hero-stat-label">Clients</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-val">4.9★</div>
                        <div class="hero-stat-label">Rating</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-val">6yr</div>
                        <div class="hero-stat-label">Experience</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-val">87</div>
                        <div class="hero-stat-label">Sessions/mo</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="section-card" style="animation-delay:.1s">
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="switchTab('personal',this)">Personal Info</button>
                        <button class="tab-btn" onclick="switchTab('professional',this)">Professional</button>
                        <button class="tab-btn" onclick="switchTab('security',this)">Security</button>
                    </div>
                    <!-- Personal Tab -->
                    <div class="tab-pane active" id="tab-personal">
                        <div class="form-row-2 form-group">
                            <div><label class="gym-label">First Name</label><input type="text" class="gym-input" value="Irfan"></div>
                            <div><label class="gym-label">Last Name</label><input type="text" class="gym-input" value="Malik"></div>
                        </div>
                        <div class="form-group"><label class="gym-label">Email Address</label><input type="email" class="gym-input" value="irfanmalik@gym.com"></div>
                        <div class="form-row-2 form-group">
                            <div><label class="gym-label">Phone Number</label><input type="text" class="gym-input" value="+92 300 123 4567"></div>
                            <div><label class="gym-label">Date of Birth</label><input type="date" class="gym-input" value="1990-06-15"></div>
                        </div>
                        <div class="form-row-3 form-group">
                            <div><label class="gym-label">City</label><input type="text" class="gym-input" value="Islamabad"></div>
                            <div><label class="gym-label">Country</label><input type="text" class="gym-input" value="Pakistan"></div>
                            <div><label class="gym-label">Gender</label>
                                <select class="gym-select">
                                    <option selected>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label class="gym-label">Bio</label><textarea class="gym-textarea" rows="4" placeholder="Write a short bio…">Certified personal trainer with 6+ years of experience specializing in strength training, weight loss, and athletic conditioning. Passionate about helping clients achieve their fitness goals through science-based programming.</textarea></div>
                        <div style="display:flex;justify-content:flex-end">
                            <button class="btn-cancel">Cancel</button>
                            <button class="btn-save" onclick="alert('Profile saved!')"><i class="fas fa-check"></i>Save Changes</button>
                        </div>
                    </div>
                    <!-- Professional Tab -->
                    <div class="tab-pane" id="tab-professional">
                        <div class="form-row-2 form-group">
                            <div><label class="gym-label">Job Title</label><input type="text" class="gym-input" value="Head Trainer"></div>
                            <div><label class="gym-label">Years of Experience</label><input type="number" class="gym-input" value="6"></div>
                        </div>
                        <div class="form-row-2 form-group">
                            <div><label class="gym-label">Gym / Organization</label><input type="text" class="gym-input" value="Gym Fitness"></div>
                            <div><label class="gym-label">Hourly Rate ($)</label><input type="number" class="gym-input" value="75"></div>
                        </div>
                        <div class="form-group"><label class="gym-label">Specializations</label>
                            <div style="padding:1.4rem;background:var(--light);border-radius:1rem;border:1.5px solid rgba(29,84,109,.12)">
                                <span class="skill-chip">Strength Training</span><span class="skill-chip">Weight Loss</span><span class="skill-chip">HIIT</span><span class="skill-chip">Powerlifting</span><span class="skill-chip">Nutrition</span><span class="skill-chip">Mobility</span><span class="skill-chip">Athletic Conditioning</span>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:flex-end">
                            <button class="btn-cancel">Cancel</button>
                            <button class="btn-save" onclick="alert('Professional info saved!')"><i class="fas fa-check"></i>Save Changes</button>
                        </div>
                    </div>
                    <!-- Security Tab -->
                    <div class="tab-pane" id="tab-security">
                        <div class="form-group"><label class="gym-label">Current Password</label><input type="password" class="gym-input" placeholder="••••••••"></div>
                        <div class="form-row-2 form-group">
                            <div><label class="gym-label">New Password</label><input type="password" class="gym-input" placeholder="Min 8 characters"></div>
                            <div><label class="gym-label">Confirm New Password</label><input type="password" class="gym-input" placeholder="Repeat password"></div>
                        </div>
                        <div style="padding:1.6rem;background:rgba(29,84,109,.04);border-radius:1rem;border:1px solid rgba(29,84,109,.1);margin-bottom:2rem">
                            <div style="font-size:1.3rem;font-weight:600;color:var(--dark);margin-bottom:1rem">Password Requirements</div>
                            <div style="font-size:1.2rem;color:rgba(6,30,41,.55);display:flex;flex-direction:column;gap:.6rem">
                                <div><i class="fas fa-check" style="color:#22c55e;margin-right:.6rem"></i>Minimum 8 characters</div>
                                <div><i class="fas fa-check" style="color:#22c55e;margin-right:.6rem"></i>At least one uppercase letter</div>
                                <div><i class="fas fa-check" style="color:#22c55e;margin-right:.6rem"></i>At least one number</div>
                                <div><i class="fas fa-times" style="color:#ef4444;margin-right:.6rem"></i>At least one special character</div>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:flex-end">
                            <button class="btn-save" onclick="alert('Password updated!')"><i class="fas fa-lock"></i>Update Password</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Certifications -->
                <div class="section-card" style="animation-delay:.15s;margin-bottom:2rem">
                    <div class="section-header">
                        <span class="section-title">Certifications</span>
                        <button style="width:3rem;height:3rem;border-radius:.7rem;border:1.5px solid rgba(29,84,109,.15);background:transparent;color:var(--primary);font-size:1.4rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">🏋️</div>
                        <div>
                            <div class="cert-name">NASM-CPT</div>
                            <div class="cert-org">Personal Trainer · 2019</div>
                        </div>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">🥗</div>
                        <div>
                            <div class="cert-name">Precision Nutrition</div>
                            <div class="cert-org">Nutrition Coach · 2020</div>
                        </div>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">⚡</div>
                        <div>
                            <div class="cert-name">CSCS</div>
                            <div class="cert-org">Strength & Conditioning · 2022</div>
                        </div>
                    </div>
                    <div class="cert-item">
                        <div class="cert-icon">🧘</div>
                        <div>
                            <div class="cert-name">Yoga Alliance RYT</div>
                            <div class="cert-org">Yoga Instructor · 2023</div>
                        </div>
                    </div>
                </div>
                <!-- Quick Stats -->
                <div class="section-card" style="animation-delay:.2s">
                    <div class="section-header"><span class="section-title">This Month</span></div>
                    <div style="padding:2rem">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 0;border-bottom:1px solid rgba(29,84,109,.06)">
                            <span style="font-size:1.3rem;color:rgba(6,30,41,.5)">Sessions Conducted</span>
                            <span style="font-size:1.5rem;font-weight:800;color:var(--primary)">87</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 0;border-bottom:1px solid rgba(29,84,109,.06)">
                            <span style="font-size:1.3rem;color:rgba(6,30,41,.5)">New Clients</span>
                            <span style="font-size:1.5rem;font-weight:800;color:#22c55e">+3</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 0;border-bottom:1px solid rgba(29,84,109,.06)">
                            <span style="font-size:1.3rem;color:rgba(6,30,41,.5)">Client Rating</span>
                            <span style="font-size:1.5rem;font-weight:800;color:#f59e0b">4.9 ⭐</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 0">
                            <span style="font-size:1.3rem;color:rgba(6,30,41,.5)">Revenue</span>
                            <span style="font-size:1.5rem;font-weight:800;color:var(--dark)">$3,200</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Logout ──
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }

        const sidebar = document.getElementById('sidebar'),
            toggle = document.getElementById('sidebarToggle'),
            overlay = document.getElementById('sidebarOverlay');
        toggle.onclick = () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        };
        overlay.onclick = () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        };

        function switchTab(id, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + id).classList.add('active');
        }
    </script>
</body>

</html>