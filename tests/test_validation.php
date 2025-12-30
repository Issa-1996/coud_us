<?php
require_once 'models/FauxModel.php';

// Test de validation avec téléphone vide
$data = [
    'carteEtudiant' => 'ETU123456',
    'nom' => 'Test',
    'prenoms' => 'User',
    'campus' => 'Campus Test',
    'telephone7' => '', // Vide maintenant
    'telephoneResistant' => '',
    'identiteFaux' => 'M. Test',
    'typeDocument' => 'carte_etudiant',
    'chargeEnquete' => '',
    'agentAction' => '',
    'observations' => '',
    'statut' => 'en_cours',
    'date' => date('Y-m-d')
];

$errors = validatePV($data);
echo 'Erreurs de validation: ' . count($errors) . PHP_EOL;
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo '- ' . $error . PHP_EOL;
    }
} else {
    echo 'Validation réussie!' . PHP_EOL;
}
?>
