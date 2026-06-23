<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ? AND is_deleted = 0");
$stmt->bind_param("i", $member_id);
$stmt->execute();

$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$pageTitle = "Edit Member";

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Edit Member</h1>
            <p class="text-sm text-gray-500">
                Update member information.
            </p>
        </div>

        <a href="index.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to Members
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6 md:p-8">
        <form method="POST" action="update.php" class="space-y-5">

            <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Student ID <span class="text-red-500">*</span>
                </label>

                <input type="text"
                    name="student_id"
                    required
                    minlength="3"
                    maxlength="50"
                    pattern="[A-Za-z0-9-]+"
                    title="Student ID must contain only letters, numbers, and hyphens."
                    value="<?= htmlspecialchars($member['student_id']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        First Name <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                        name="first_name"
                        required
                        minlength="2"
                        maxlength="100"
                        pattern="[A-Za-z ]+"
                        title="First name must contain letters and spaces only."
                        oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '')"
                        value="<?= htmlspecialchars($member['first_name']) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Last Name <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                        name="last_name"
                        required
                        minlength="2"
                        maxlength="100"
                        pattern="[A-Za-z ]+"
                        title="Last name must contain letters and spaces only."
                        oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '')"
                        value="<?= htmlspecialchars($member['last_name']) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Department
                </label>

                <input type="text"
                    name="department"
                    maxlength="100"
                    pattern="[A-Za-z0-9 &-]*"
                    title="Department can contain letters, numbers, spaces, ampersand, and hyphens."
                    value="<?= htmlspecialchars($member['department']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>

                    <input type="email"
                        name="email"
                        maxlength="255"
                        value="<?= htmlspecialchars($member['email']) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Phone Number
                    </label>

                    <input type="tel"
                        name="phone"
                        maxlength="11"
                        inputmode="numeric"
                        autocomplete="tel"
                        pattern="01[3-9][0-9]{8}"
                        title="Enter a valid 11-digit Bangladeshi phone number starting with 01."
                        placeholder="01712345678"
                        oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                        value="<?= htmlspecialchars($member['phone']) ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="index.php"
                class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>

                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i> Update Member
                </button>
            </div>

        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>  