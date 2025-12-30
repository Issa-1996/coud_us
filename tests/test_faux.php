<?php
require_once 'data/FauxModel-fonctions.php';

// Test 1: Validate data
echo "Test 1: Validation\n";
$validData = [
    'carteEtudiant' => 'ETU000001',
    'nom' => 'Test',
    'prenoms' => 'User',
    'campus' => 'Campus Social ESP',
    'telephone7' => '71234567',
    'telephoneResistant' => '78123456',
    'identiteFaux' => 'M. Test User',
    'typeDocument' => 'carte_etudiant',
    'chargeEnquete' => 'Agent A',
    'agentAction' => 'Agent B',
    'observations' => 'Test observation',
    'statut' => 'en_cours',
    'date' => '2024-01-01'
];
$errors = validateFauxPV($validData);
if (empty($errors)) {
    echo "Validation passed\n";
} else {
    echo "Validation failed: " . implode(', ', $errors) . "\n";
}

// Test 2: Create PV
echo "\nTest 2: Create PV\n";
$newId = createFauxPV($validData);
if ($newId) {
    echo "Created PV with ID: $newId\n";
    $testId = $newId;
} else {
    echo "Failed to create PV\n";
    exit;
}

// Test 3: Get PV by ID
echo "\nTest 3: Get PV by ID\n";
$pv = getFauxPVById($testId);
if ($pv) {
    echo "Retrieved PV: " . $pv['nom'] . " " . $pv['prenoms'] . "\n";
} else {
    echo "Failed to retrieve PV\n";
}

// Test 4: Update PV
echo "\nTest 4: Update PV\n";
$updateData = $validData;
$updateData['observations'] = 'Updated observation';
$updated = updateFauxPV($testId, $updateData);
if ($updated) {
    echo "Updated PV successfully\n";
} else {
    echo "Failed to update PV\n";
}

// Test 5: Get all PVs with pagination
echo "\nTest 5: Get all PVs\n";
$result = getAllFauxPV(1, 10);
if ($result['total'] > 0) {
    echo "Retrieved " . $result['total'] . " PVs\n";
} else {
    echo "No PVs found or error\n";
}

// Test 6: Get statistics
echo "\nTest 6: Get statistics\n";
$stats = getFauxStatistics();
echo "Total: " . $stats['total'] . ", En cours: " . $stats['enCours'] . ", Traites: " . $stats['traites'] . "\n";

// Test 7: Delete PV
echo "\nTest 7: Delete PV\n";
$deleted = deleteFauxPV($testId);
if ($deleted) {
    echo "Deleted PV successfully\n";
} else {
    echo "Failed to delete PV\n";
}

echo "\nAll tests completed.\n";
?>
