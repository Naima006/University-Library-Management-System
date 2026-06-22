<?php
session_start();

include("../config/db.php");
include("../config/activity_log.php");

/* If an already logged-in user opens login.php, send them to their dashboard */
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../staff/dashboard.php");
    }
    exit;
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($email === '' || $password === '' || !in_array($role, ['admin', 'staff'])) {
        $error_message = "Please enter your email, password, and role.";
    } else {
        $stmt = $conn->prepare("
            SELECT user_id, full_name, email, password, role, is_active
            FROM users
            WHERE email = ? AND role = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error_message = "Something went wrong. Please try again.";
        } else {
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if ((int)$user['is_active'] === 0) {
                    $error_message = "This account has been disabled. Please contact an administrator.";
                } elseif (password_verify($password, $user['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];

                    addActivityLog(
                        $conn,
                        $_SESSION['user_id'],
                        "User Login",
                        "users",
                        $_SESSION['user_id'],
                        $user['full_name'] . " logged in as " . ucfirst($user['role']) . "."
                    );

                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: ../staff/dashboard.php");
                    }
                    exit;

                } else {
                    $error_message = "Incorrect email, password, or selected role.";
                }
            } else {
                $error_message = "Incorrect email, password, or selected role.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | University Library Management System</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="min-h-screen bg-slate-950">

    <div class="min-h-screen grid lg:grid-cols-2">

        <!-- Left branding panel -->
        <section class="hidden lg:flex relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-950 p-12 text-white">
            <div class="absolute inset-0 opacity-20"
                 style="background-image: radial-gradient(circle at 20% 20%, #60a5fa 0, transparent 28%), radial-gradient(circle at 80% 70%, #2563eb 0, transparent 25%);">
            </div>

            <div class="relative z-10 flex flex-col justify-between w-full">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/20 border border-blue-300/30 flex items-center justify-center">
                        <i class="fas fa-book-open text-xl text-blue-200"></i>
                    </div>

                    <div>
                        <h1 class="font-bold text-lg">ULMS</h1>
                        <p class="text-sm text-slate-300">University Library Management System</p>
                    </div>
                </div>

                <div class="max-w-lg">
                    <p class="text-blue-200 font-medium mb-3">
                        Welcome back
                    </p>

                    <h2 class="text-4xl font-bold leading-tight mb-5">
                        Manage your university library with confidence.
                    </h2>

                    <p class="text-slate-300 leading-relaxed">
                        Access books, members, circulation records, reports, and activity logs from one secure system.
                    </p>
                </div>

                <p class="text-sm text-slate-400">
                    © <?= date("Y") ?> University Library Management System
                </p>
            </div>
        </section>

        <!-- Login panel -->
        <main class="flex items-center justify-center px-5 py-10 sm:px-8 bg-slate-100">
            <div class="w-full max-w-md">

                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-7">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg">
                        <i class="fas fa-book-open text-2xl"></i>
                    </div>

                    <h1 class="text-xl font-bold text-slate-800">ULMS</h1>
                    <p class="text-sm text-slate-500">University Library Management System</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl shadow-slate-300/50 border border-slate-200 p-6 sm:p-8">

                    <div class="mb-7">
                        <h2 class="text-2xl font-bold text-slate-800">
                            Sign in to your account
                        </h2>

                        <p class="text-sm text-slate-500 mt-2">
                            Enter your credentials to continue.
                        </p>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="mb-5 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <i class="fas fa-circle-exclamation mt-0.5"></i>
                            <span><?= htmlspecialchars($error_message) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST"
                          action="login.php"
                          id="loginForm"
                          autocomplete="off"
                          class="space-y-5">

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                                Email Address
                            </label>

                            <div class="relative">
                                <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autocomplete="off"
                                    placeholder="Enter your email address"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                                Password
                            </label>

                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Enter your password"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-12 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                <button type="button"
                                        id="togglePassword"
                                        aria-label="Show or hide password"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                    <i id="passwordIcon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-semibold text-slate-700 mb-2">
                                Login As
                            </label>

                            <div class="relative">
                                <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                <select
                                    id="role"
                                    name="role"
                                    required
                                    autocomplete="off"
                                    class="w-full appearance-none rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-10 text-slate-800 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="" selected disabled>Select your role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="staff">Staff</option>
                                </select>

                                <i class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            <i class="fas fa-right-to-bracket mr-2"></i>
                            Sign In
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-500">
                    Authorized university library personnel only.
                </p>
            </div>
        </main>
    </div>

    <script>
        const loginForm = document.getElementById("loginForm");
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");
        const passwordIcon = document.getElementById("passwordIcon");

        /*
            Clears any values restored by browser autofill or browser back button.
            The role returns to "Select your role" as well.
        */
        window.addEventListener("pageshow", function () {
            loginForm.reset();
            passwordInput.type = "password";
            passwordIcon.classList.remove("fa-eye-slash");
            passwordIcon.classList.add("fa-eye");
        });

        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";

            passwordIcon.classList.toggle("fa-eye", !isPassword);
            passwordIcon.classList.toggle("fa-eye-slash", isPassword);
        });
    </script>

</body>
</html>