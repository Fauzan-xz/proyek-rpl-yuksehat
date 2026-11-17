<?php
/**
 * Automated Unit Test Suite for YukSehat
 * Mata Kuliah: Rekayasa Perangkat Lunak (TIKB2503021) - STMIK IKMI Cirebon
 * Memenuhi Kriteria Penilaian Dimensi 4: Testing & GitHub CI/CD Pipeline
 */

define('TEST_MODE', true);

// Set error reporting
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '1');

// Set safe temporary session path for CLI testing
session_save_path(sys_get_temp_dir());
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';
require_once $root . '/app/helpers/AuthHelper.php';
require_once $root . '/app/helpers/MedicalFormatHelper.php';
require_once $root . '/app/helpers/SessionHelper.php';
require_once $root . '/app/services/FonnteService.php';
require_once $root . '/app/services/AiService.php';

$passedCount = 0;
$failedCount = 0;
$tests = [];

function assertTest($name, $condition, $message = '') {
    global $passedCount, $failedCount, $tests;
    if ($condition) {
        $passedCount++;
        $status = "✅ PASS";
        echo "{$status} - {$name}\n";
    } else {
        $failedCount++;
        $status = "❌ FAIL";
        echo "{$status} - {$name}: {$message}\n";
    }
    $tests[] = ['name' => $name, 'passed' => (bool)$condition, 'message' => $message];
}

echo "====================================================================\n";
echo " 🩺 YUKSEHAT AUTOMATED UNIT TESTING SUITE\n";
echo " STMIK IKMI Cirebon - S1 Teknik Informatika (Kurikulum OBE)\n";
echo "====================================================================\n\n";

// ---------------------------------------------------------
// 1. UNIT TEST: AuthHelper & CSRF Security Engine
// ---------------------------------------------------------
echo "--- [1] TESTING: AuthHelper & CSRF Security ---\n";

$token1 = AuthHelper::generateCSRF();
assertTest("CSRF Token Generated", !empty($token1) && strlen($token1) === 64, "Token length must be 64 hex chars");

$isValid = AuthHelper::validateCSRF($token1);
assertTest("CSRF Token Validation Valid", $isValid === true, "Valid token must pass hash_equals validation");

$isInvalid = AuthHelper::validateCSRF("invalid_token_12345");
assertTest("CSRF Token Validation Rejects Tampered", $isInvalid === false, "Tampered token must be rejected");

$token2 = AuthHelper::rotateCSRF();
assertTest("CSRF Token Rotation", !empty($token2) && $token2 !== $token1, "Rotated token must generate a new 32-byte secret");

$oldTokenValidAfterRotation = AuthHelper::validateCSRF($token1);
assertTest("Old CSRF Token Invalidated After Rotation", $oldTokenValidAfterRotation === false, "Old token must be invalidated");

// Test Ronal-only input rule logic
$_SESSION['user'] = ['role' => 'pengurus', 'username' => 'ronal'];
assertTest("BR-002: Ronal Allowed to Input Examination", AuthHelper::canInputPemeriksaan() === true);

$_SESSION['user'] = ['role' => 'pengurus', 'username' => 'ahmad'];
assertTest("BR-002: Non-Ronal Pengurus Blocked from Input", AuthHelper::canInputPemeriksaan() === false);

$_SESSION['user'] = ['role' => 'admin', 'username' => 'admin'];
assertTest("BR-002: Admin Blocked from Examination Input", AuthHelper::canInputPemeriksaan() === false);

$_SESSION['user'] = ['role' => 'santri', 'username' => '1001'];
assertTest("BR-002: Santri Blocked from Examination Input", AuthHelper::canInputPemeriksaan() === false);
unset($_SESSION['user']);


// ---------------------------------------------------------
// 2. UNIT TEST: MedicalFormatHelper (Clinical Text & AI Drafter)
// ---------------------------------------------------------
echo "\n--- [2] TESTING: MedicalFormatHelper ---\n";

$rawHtml = "Keluhan Santri &amp; Sakit Mata &quot;Perih&quot;";
$clean = MedicalFormatHelper::cleanEntities($rawHtml);
assertTest("Entity Decoding Cleanliness", $clean === 'Keluhan Santri & Sakit Mata "Perih"');

$longText = "Santri mengalami demam tinggi disertai batuk kering dan sakit kepala berdenyut sejak dua hari yang lalu di asrama pondok";
$truncated = MedicalFormatHelper::truncateWord($longText, 45, '...');
assertTest("Word-boundary Truncation", str_ends_with($truncated, '...') && mb_strlen($truncated) <= 48);

$aiDraftText = "[Draf Rekomendasi AI]: Berikut adalah hasil analisis keluhan santri.";
assertTest("AI Draft Detection Positive", MedicalFormatHelper::isAiDraft($aiDraftText) === true);

$manualDoctorNotes = "Pasien tampak lemas, conjungtiva anemis, istirahat di UKS 1x24 jam.";
assertTest("AI Draft Detection Negative for Manual Notes", MedicalFormatHelper::isAiDraft($manualDoctorNotes) === false);

$summarized = MedicalFormatHelper::summarizeHasil($manualDoctorNotes, 100);
assertTest("Medical Summarization of Manual Notes", !empty($summarized) && mb_strlen($summarized) <= 105);


// ---------------------------------------------------------
// 3. UNIT TEST: FonnteService (WhatsApp Normalization & Safe Mock)
// ---------------------------------------------------------
echo "\n--- [3] TESTING: FonnteService (WhatsApp Normalization) ---\n";

$phone1 = FonnteService::formatPhoneNumber("081234567890");
assertTest("Normalizing '08xxx' to '628xxx'", $phone1 === "6281234567890");

$phone2 = FonnteService::formatPhoneNumber("81234567890");
assertTest("Normalizing '8xxx' to '628xxx'", $phone2 === "6281234567890");

$phone3 = FonnteService::formatPhoneNumber("+62 812-3456-7890");
assertTest("Stripping non-digits and country code preservation", $phone3 === "6281234567890");

// Test Safe Mock Handler
FonnteService::setMockHandler(function($action, $target, $message, $options) {
    return [
        'status' => true,
        'target' => $target,
        'message_id' => 'FONNTE_MOCK_12345',
        'is_mock' => true
    ];
});

$mockRes = FonnteService::send("081234567890", "Halo Uji Coba Unit Test");
assertTest("Fonnte Mock Delivery Handler", isset($mockRes['status']) && $mockRes['status'] === true && $mockRes['is_mock'] === true);
FonnteService::setMockHandler(null);


// ---------------------------------------------------------
// 4. UNIT TEST: AiService (Model Catalog & Architecture)
// ---------------------------------------------------------
echo "\n--- [4] TESTING: AiService (Dual-Engine AI Catalog) ---\n";

$catalog = AiService::getModelCatalog();
assertTest("AI Catalog Exists", is_array($catalog) && count($catalog) >= 4);
assertTest("Primary Model NVIDIA Nemotron Present", isset($catalog['nvidia/nemotron-3.5-bolt-e4b']));
assertTest("Secondary Model Google Gemini Present", isset($catalog['gemini-flash-lite-latest']));
assertTest("Gemini 3.5 Flash Present", isset($catalog['gemini-3.5-flash']));
assertTest("Provider Mapping Correctness", $catalog['gemini-flash-lite-latest']['provider'] === 'gemini');


// ---------------------------------------------------------
// 5. UNIT TEST: Static Code Syntax Verification (PHP Lint)
// ---------------------------------------------------------
echo "\n--- [5] TESTING: Static Code Syntax (PHP Lint Check) ---\n";

$criticalFiles = [
    $root . '/app/init.php',
    $root . '/app/core/App.php',
    $root . '/app/core/Controller.php',
    $root . '/app/core/Database.php',
    $root . '/app/controllers/Home.php',
    $root . '/app/controllers/Login.php',
    $root . '/app/controllers/Register.php',
    $root . '/app/controllers/Dashboard.php',
    $root . '/app/controllers/Keluhan.php',
    $root . '/app/controllers/Pemeriksaan.php',
    $root . '/app/controllers/Santri.php',
    $root . '/app/controllers/Laporan.php',
    $root . '/app/controllers/Notifikasi.php',
    $root . '/app/controllers/Monitoring.php',
    $root . '/app/controllers/Settings.php',
    $root . '/app/controllers/Users.php',
    $root . '/app/controllers/Chatbot.php',
    $root . '/app/models/User_model.php',
    $root . '/app/models/Santri_model.php',
    $root . '/app/models/Pemeriksaan_model.php',
    $root . '/app/models/Dashboard_model.php',
    $root . '/app/models/Laporan_model.php',
    $root . '/app/models/Notifikasi_model.php',
    $root . '/app/services/AiService.php',
    $root . '/app/services/ChatbotDataService.php',
    $root . '/app/services/FonnteService.php',
    $root . '/app/services/WaQueueService.php',
];

$allSyntaxValid = true;
$phpBinary = PHP_BINARY ?: 'php';
foreach ($criticalFiles as $filePath) {
    $cmd = escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($filePath) . ' 2>&1';
    $output = shell_exec($cmd);
    if (!str_contains($output, 'No syntax errors detected')) {
        $allSyntaxValid = false;
        echo "Syntax error in {$filePath}: {$output}\n";
        break;
    }
}
assertTest("All 27 Critical PHP Files Have Zero Syntax Errors", $allSyntaxValid === true);


// ---------------------------------------------------------
// TEST SUMMARY & EXIT CODE
// ---------------------------------------------------------
echo "\n====================================================================\n";
echo " 📊 TEST RESULT SUMMARY\n";
echo " Total Tests: " . ($passedCount + $failedCount) . "\n";
echo " Passed:      {$passedCount} (" . round(($passedCount / max(1, $passedCount + $failedCount)) * 100, 1) . "%)\n";
echo " Failed:      {$failedCount}\n";
echo " Status:      " . ($failedCount === 0 ? "PASSED (STATUS HIJAU / GREEN BUILD 🟢)" : "FAILED 🔴") . "\n";
echo "====================================================================\n";

if ($failedCount > 0) {
    exit(1);
}
exit(0);
