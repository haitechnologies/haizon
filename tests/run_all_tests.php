<?php
/**
 * Haizon complete test suite runner
 */
echo "==================================================\n";
echo "HAIZON COMPLETE TEST SUITE RUNNER\n";
echo "==================================================\n\n";

passthru("php tests/setup_test_fixtures.php");
echo "\n";

$tests = [
    "tests/integration-multi-org.php",
    "tests/test_user_psr.php",
    "tests/test_customer_psr.php",
    "tests/test_department_psr.php",
    "tests/test_designation_psr.php",
    "tests/test_bank_psr.php",
    "tests/test_currency_psr.php",
    "tests/test_payment_method_psr.php",
    "tests/test_leave_psr.php",
    "tests/test_invoice_psr.php",
    "tests/test_datatable_psr.php",
    "tests/test_error_capture.php",
    "tests/test_setup_entities_psr.php"
];

$failed = 0;
foreach ($tests as $test) {
    echo "Running $test...\n";
    $code = 0;
    passthru("php $test", $code);
    if ($code !== 0) {
        echo "❌ $test FAILED!\n\n";
        $failed++;
    } else {
        echo "✅ $test PASSED.\n\n";
    }
}

passthru("php tests/teardown_test_fixtures.php");
echo "\n";

if ($failed > 0) {
    echo "❌ TEST SUITE FAILED with $failed failures.\n";
    exit(1);
} else {
    echo "🎉 ALL TEST SUITES PASSED SUCCESSFULLY!\n";
    exit(0);
}
