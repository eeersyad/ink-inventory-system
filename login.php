<?php
session_start();
require 'db.php';

$error = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'login';

    // ==========================================
    // 1. LOGIN LOGIC
    // ==========================================
    if ($action === 'login') {
        // We catch 'ic_number' and automatically strip out any dashes if the user accidentally typed them
        $login_id = str_replace('-', '', trim($_POST['ic_number']));
        $password = $_POST['password'];

        // Update query to check the 'id' column instead of 'username'
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$login_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify user and password
        if ($user) {
            $login_success = false;

            // Check if the password is securely hashed
            if (password_verify($password, $user['password'])) {
                $login_success = true;
            } 
            // Check if it's a plain-text password (from manual database entry)
            elseif ($password === $user['password']) {
                $login_success = true;
                
                // Auto-upgrade: Encrypt the plain-text password
                $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_pwd, $user['id']]);
            }

            if ($login_success) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Save the real name if it exists
                if (!empty($user['name'])) {
                    $_SESSION['name'] = $user['name'];
                }

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: staff_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid IC Number or password.";
            }
        } else {
            $error = "Invalid IC Number or password.";
        }
    }
    // ==========================================
    // 2. REGISTER LOGIC
    // ==========================================
    elseif ($action === 'register') {
        // Automatically uppercase the necessary fields using mb_strtoupper
        $id = str_replace('-', '', trim($_POST['reg_ic_number']));
        $name = mb_strtoupper(trim($_POST['reg_name']), 'UTF-8');
        $position = mb_strtoupper(trim($_POST['reg_position']), 'UTF-8');
        $department = mb_strtoupper(trim($_POST['reg_department']), 'UTF-8');
        
        // Leave Email and Password exactly as they are
        $email = trim($_POST['reg_email']);
        $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
        
        $role = 'staff'; // Default role for new signups

        // Check if IC Number already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() > 0) {
            $error = "Pendaftaran gagal: No IC telah wujud di dalam sistem.";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO users (id, password, role, name, position, department, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($insertStmt->execute([$id, $password, $role, $name, $position, $department, $email])) {
                $success_msg = "Akaun berjaya didaftarkan! Anda kini boleh log masuk.";
            } else {
                $error = "Ralat berlaku semasa pendaftaran. Sila cuba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk</title>
    <link rel="icon" type="image/png" href="images/logo-mdm.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* Hide scrollbar */
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; overflow-x: hidden; }
        
        /* Subtle background for the left side */
        .left-side {
            background-color: #F8F9FA;
        }

        /* Smooth fade-in animation for switching forms */
        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Styling to make the toggle buttons look like text links */
        .toggle-btn {
            background: none;
            border: none;
            color: #0d6efd;
            padding: 0;
            font-weight: bold;
            text-decoration: underline;
            cursor: pointer;
        }
        .toggle-btn:hover {
            color: #0a58ca;
        }

        /* ========================================== */
        /* SPLASH SCREEN / LOGIN PRELOADER            */
        /* ========================================== */
        .login-splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff; /* Latar belakang putih bersih */
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.8s ease-out, visibility 0.8s ease-out;
        }
        .login-splash.fade-out {
            opacity: 0;
            visibility: hidden;
        }
        .splash-logo {
            animation: pulseLogo 2s infinite ease-in-out;
            height: 100px;
            margin-bottom: 20px;
            object-fit: contain;
        }
        @keyframes pulseLogo {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }

        /* ========================================== */
        /* BUANG GARISAN BIRU BOOTSTRAP PADA INPUT    */
        /* ========================================== */
        .form-control:focus, .form-select:focus {
            box-shadow: none !important;
            border-color: #dee2e6 !important; 
            z-index: 0 !important; 
        }

        /* ========================================== */
        /* GLOBAL MOBILE RESPONSIVENESS FIXES         */
        /* ========================================== */
        @media (max-width: 767px) {
            body { font-size: 0.95rem; }
            h1 { font-size: 2rem !important; }
            h2 { font-size: 1.75rem !important; }
            h3 { font-size: 1.5rem !important; }
            h4 { font-size: 1.25rem !important; }

            .col-12.d-flex {
                padding: 1.5rem !important;
            }

            .d-block.d-lg-none img {
                height: 50px !important;
            }

            .form-control, .form-select, .btn {
                min-height: 48px;
            }
        }
    </style>
</head>
<body>

    <div class="container-fluid vh-100">
        <div class="row h-100 g-0">
            
            <div class="col-12 col-lg-6 left-side d-none d-lg-flex flex-column align-items-center justify-content-center text-center p-5 border-end">
                <img src="images/logo-semua.png" alt="System Logo" style="max-height: 180px;" class="mb-4 rounded-4 p-3">
                
                <h1 class="fw-bold text-dark mb-1" style="font-size: 2.5rem;">Sistem Permohonan</h1>
                <h2 class="fw-bold text-primary" style="font-size: 2.5rem;">Dakwat Printer</h2>
                
                <p class="text-muted mt-4 fs-5 w-75">
                    Permudahkan pengurusan inventori, mohon dakwat pencetak dengan mudah, dan pantau bekalan stok secara langsung.
                </p>
            </div>

            <div id="loginSplash" class="login-splash">
                <img src="images/logo-semua.png" alt="Logo MDM" class="splash-logo">
                <h3 class="fw-bold text-dark mb-0 fs-4 fs-md-3">Sistem Inventori</h3>
                <h4 class="text-primary fw-bold mb-4 fs-5 fs-md-4">Dakwat Printer</h4>
                
                <div class="spinner-grow text-primary" role="status" style="width: 2rem; height: 2rem;">
                    <span class="visually-hidden">Memuatkan...</span>
                </div>
                <p class="text-muted small mt-3">Menyediakan ruang kerja anda...</p>
            </div>

            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center bg-white p-4 p-sm-5 overflow-auto">
                <div class="w-100 my-auto" style="max-width: 400px;">
                    
                    <div class="text-center d-block d-lg-none mb-4">
                        <img src="images/logo-semua.png" alt="Logo" height="60" class="mb-2">
                        <h4 class="fw-bold text-dark mb-0">Sistem Permohonan</h4>
                        <h4 class="fw-bold text-primary">Dakwat Printer</h4>
                    </div>

                    <div id="login-section" class="fade-in <?= isset($_POST['action']) && $_POST['action'] === 'register' && !empty($error) ? 'd-none' : '' ?>">
                        <div class="mb-4 d-none d-md-block">
                            <h3 class="fw-bold">Selamat Datang! <i class="fa-solid fa-hand-wave text-warning"></i></h3>
                            <p class="text-muted">Masukkan IC dan kata laluan untuk log masuk.</p>
                        </div>

                        <form method="POST" action="login.php">
                            <input type="hidden" name="action" value="login">

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-1">No IC</label>
                                <div class="input-group input-group-lg shadow-sm rounded-4">
                                    <span class="input-group-text bg-white text-muted border-end-0 rounded-start-4"><i class="fa-solid fa-id-card"></i></span>
                                    <input type="text" name="ic_number" class="form-control border-start-0 ps-0 rounded-end-4" placeholder="Contoh: 021024111234" required autofocus oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);" maxlength="12">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-1">Kata Laluan</label>
                                <div class="input-group input-group-lg shadow-sm rounded-4">
                                    <span class="input-group-text bg-white text-muted border-end-0 rounded-start-4"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 ps-0 rounded-end-4" placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm fw-bold rounded-pill mt-2">
                                Login <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                            </button>
                        </form>

                        <div class="text-center mt-4 text-muted">
                            Belum mempunyai akaun? <button type="button" class="toggle-btn" onclick="toggleForms('register')">Daftar di sini</button>
                        </div>
                    </div>

                    <div id="register-section" class="fade-in <?= isset($_POST['action']) && $_POST['action'] === 'register' && !empty($error) ? '' : 'd-none' ?>">
                        <div class="mb-4 d-none d-md-block">
                            <h3 class="fw-bold">Cipta Akaun Baru </h3>
                        </div>

                        <form method="POST" action="login.php">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-1">Nama Penuh</label>
                                <input type="text" name="reg_name" class="form-control rounded-4 px-3" style="text-transform: uppercase;" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase ms-1">No IC</label>
                                    <input type="text" name="reg_ic_number" class="form-control rounded-4 px-3" placeholder="Contoh: 900101123456" required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);" maxlength="12">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase ms-1">Emel Rasmi</label>
                                    <input type="email" name="reg_email" class="form-control rounded-4 px-3" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase ms-1">Jawatan</label>
                                    <input type="text" name="reg_position" class="form-control rounded-4 px-3" style="text-transform: uppercase;" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase ms-1">Jabatan</label>
                                    <select name="reg_department" class="form-select rounded-4 px-3" required>
                                        <option value="" selected disabled>Pilih Jabatan...</option>
                                        <option value="JABATAN KHIDMAT PENGURUSAN">JABATAN KHIDMAT PENGURUSAN</option>
                                        <option value="JABATAN PERBENDAHARAAN">JABATAN PERBENDAHARAAN</option>
                                        <option value="JABATAN PERANCANGAN BANDAR">JABATAN PERANCANGAN BANDAR</option>
                                        <option value="JABATAN KEJURUTERAAN">JABATAN KEJURUTERAAN</option>
                                        <option value="JABATAN PERKHIDMATAN PERBANDARAN">JABATAN PERKHIDMATAN PERBANDARAN</option>
                                        <option value="JABATAN PENILAIAN">JABATAN PENILAIAN</option>
                                        <option value="JABATAN KORPORAT DAN PERHUBUNGAN AWAM">JABATAN KORPORAT DAN PERHUBUNGAN AWAM</option>
                                        <option value="BAHAGIAN PUSAT SETEMPAT (OSC)">BAHAGIAN PUSAT SETEMPAT (OSC)</option>
                                        <option value="BAHAGIAN UNDANG-UNDANG">BAHAGIAN UNDANG-UNDANG</option>
                                        <option value="BAHAGIAN AUDIT DALAM">BAHAGIAN AUDIT DALAM</option>
                                        <option value="BAHAGIAN LANDSKAP DAN REKREASI">BAHAGIAN LANDSKAP DAN REKREASI</option>
                                        <option value="BAHAGIAN PENGURUSAN HARTA">BAHAGIAN PENGURUSAN HARTA</option>
                                        <option value="BAHAGIAN TEKNOLOGI MAKLUMAT">BAHAGIAN TEKNOLOGI MAKLUMAT</option>
                                        <option value="BAHAGIAN PENGUATKUASA">BAHAGIAN PENGUATKUASA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-muted small text-uppercase ms-1">Kata Laluan</label>
                                    <input type="password" name="reg_password" class="form-control rounded-4 px-3" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm fw-bold rounded-pill mt-2">
                                Daftar Akaun <i class="fa-solid fa-paper-plane ms-1"></i>
                            </button>
                        </form>

                        <div class="text-center mt-4 text-muted">
                            Sudah mempunyai akaun? <button type="button" class="toggle-btn" onclick="toggleForms('login')">Log masuk</button>
                        </div>
                    </div>

                    <div class="text-center mt-5 text-muted small">
                        &copy; <?= date('Y') ?> Sistem Inventori Dakwat Printer. All rights reserved.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function toggleForms(formToShow) {
            const loginSec = document.getElementById('login-section');
            const registerSec = document.getElementById('register-section');

            if (formToShow === 'register') {
                loginSec.classList.add('d-none');
                registerSec.classList.remove('d-none');
            } else {
                registerSec.classList.add('d-none');
                loginSec.classList.remove('d-none');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const splash = document.getElementById('loginSplash');
            
            // Semak memori pelayar supaya animasi tak keluar berulang kali jika user salah password dan page reload
            if (!sessionStorage.getItem('hasSeenLoginSplash')) {
                // Tayang animasi selama 2 saat (2000ms), kemudian pudar (fade out)
                setTimeout(() => {
                    splash.classList.add('fade-out');
                    sessionStorage.setItem('hasSeenLoginSplash', 'true');
                }, 2000);
            } else {
                // Jika dah pernah tengok, terus tunjuk borang log masuk
                splash.style.display = 'none';
            }
        });
    </script>

    <?php if (!empty($error)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'error',
                title: 'Ralat',
                text: '<?= htmlspecialchars($error) ?>',
                confirmButtonColor: '#0d6efd'
            });
        });
    </script>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                title: 'Berjaya!',
                text: '<?= htmlspecialchars($success_msg) ?>',
                confirmButtonColor: '#198754'
            }).then(() => {
                // Ensure the login form is shown after successful registration
                toggleForms('login');
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>