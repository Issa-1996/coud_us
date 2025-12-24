<?php
/**
 * Modèle pour la recherche d'étudiants
 * Gère la recherche et les informations sur les étudiants
 */
class RechercheModel {
    private $dataFile;
    private $etudiants = [];
    
    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/etudiants.json';
        $this->loadData();
    }
    
    /**
     * Charger les données depuis le fichier JSON
     */
    private function loadData() {
        if (file_exists($this->dataFile)) {
            $json = file_get_contents($this->dataFile);
            $this->etudiants = json_decode($json, true) ?: [];
        } else {
            $this->etudiants = [];
            $this->saveData();
        }
    }
    
    /**
     * Sauvegarder les données dans le fichier JSON
     */
    private function saveData() {
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        file_put_contents($this->dataFile, json_encode($this->etudiants, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Générer des données fictives pour le développement
     */
    public function generateFakeData($count = 100) {
        $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy', 'Kane', 'Ly', 'Tall', 'Seck', 'Faye'];
        $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou', 'Cheikh', 'Adama', 'Baba', 'Mame', 'Penda', 'Modou', 'Rokhaya'];
        $etablissements = ['Université Cheikh Anta Diop', 'ESP', 'FASTEF', 'FST', 'UFR Sciences', 'UFR Lettres'];
        $departements = ['Informatique', 'Génie Civil', 'Mathématiques', 'Physique', 'Chimie', 'Biologie', 'Économie', 'Droit', 'Littérature', 'Histoire'];
        $niveauxFormation = ['Licence', 'Master', 'Doctorat'];
        $sessions = ['2023-2024', '2022-2023', '2021-2022'];
        $typesEtudiant = ['Régulier', 'Boursier', 'Privé', 'Excellence', 'Non logé'];
        
        $this->etudiants = [];
        for ($i = 1; $i <= $count; $i++) {
            $niveau = rand(1, 3);
            $etablissement = $etablissements[array_rand($etablissements)];
            $departement = $departements[array_rand($departements)];
            $niveauFormation = $niveauxFormation[array_rand($niveauxFormation)];
            $session = $sessions[array_rand($sessions)];
            
            // Générer numéro étudiant selon l'établissement
            $numEtu = '';
            if ($etablissement === 'Université Cheikh Anta Diop') {
                $numEtu = 'UCAD-' . strtoupper(substr($departement, 0, 3)) . '-' . substr($session, 2, 2) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            } elseif ($etablissement === 'ESP') {
                $numEtu = 'ESP-' . strtoupper(substr($departement, 0, 2)) . '-' . substr($session, 2, 2) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            } else {
                $numEtu = strtoupper(substr($etablissement, 0, 4)) . '-' . substr($departement, 0, 3) . '-' . substr($session, 2, 2) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            }
            
            $dateNaissance = new DateTime(rand(1995, 2005) . '-' . rand(1, 12) . '-' . rand(1, 28));
            $lieuxNaissance = ['Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Fatick', 'Diourbel', 'Louga', 'Tambacounda', 'Kédougou'];
            
            $this->etudiants[] = [
                'id_etu' => 'ETU' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'etablissement' => $etablissement,
                'departement' => $departement,
                'niveauFormation' => $niveauFormation,
                'num_etu' => $numEtu,
                'nom' => $noms[array_rand($noms)],
                'prenoms' => $prenoms[array_rand($prenoms)],
                'dateNaissance' => $dateNaissance->format('Y-m-d'),
                'lieuNaissance' => $lieuxNaissance[array_rand($lieuxNaissance)],
                'sexe' => rand(0, 1) ? 'Masculin' : 'Féminin',
                'nationalite' => 'Sénégalaise',
                'numIdentite' => 'CNI' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'typeEtudiant' => $typesEtudiant[array_rand($typesEtudiant)],
                'moyenne' => number_format(rand(80, 180) / 10, 2),
                'sessionId' => $session,
                'niveau' => ($niveauFormation === 'Licence' ? 'L' : ($niveauFormation === 'Master' ? 'M' : 'D')) . $niveau,
                'email_perso' => strtolower(str_replace(' ', '.', $prenoms[array_rand($prenoms)])) . '.' . strtolower($noms[array_rand($noms)]) . '@gmail.com',
                'email_ucad' => strtolower(str_replace(' ', '', $prenoms[array_rand($prenoms)])) . strtolower($noms[array_rand($noms)]) . '@' . ($etablissement === 'Université Cheikh Anta Diop' ? 'ucad.edu.sn' : strtolower(str_replace(' ', '', $etablissement)) . '.sn'),
                'telephone' => '+221 ' . (rand(70, 78)) . ' ' . rand(1000000, 9999999),
                'var' => $typesEtudiant[array_rand($typesEtudiant)],
                'adresse' => 'Adresse étudiant #' . $i,
                'urgenceContact' => 'Contact urgence #' . $i,
                'urgenceTel' => '+221 ' . (rand(70, 78)) . ' ' . rand(1000000, 9999999),
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedAt' => date('Y-m-d H:i:s')
            ];
        }
        
        $this->saveData();
        return $this->etudiants;
    }
    
    /**
     * Rechercher des étudiants
     */
    public function searchEtudiants($query) {
        if (empty($query)) {
            return [];
        }
        
        $query = strtolower($query);
        $results = [];
        
        foreach ($this->etudiants as $etudiant) {
            // Recherche dans tous les champs
            if (
                strpos(strtolower($etudiant['id_etu']), $query) !== false ||
                strpos(strtolower($etudiant['num_etu']), $query) !== false ||
                strpos(strtolower($etudiant['nom']), $query) !== false ||
                strpos(strtolower($etudiant['prenoms']), $query) !== false ||
                strpos(strtolower($etudiant['etablissement']), $query) !== false ||
                strpos(strtolower($etudiant['departement']), $query) !== false ||
                strpos(strtolower($etudiant['niveau']), $query) !== false ||
                strpos(strtolower($etudiant['email_perso']), $query) !== false ||
                strpos(strtolower($etudiant['email_ucad']), $query) !== false ||
                strpos($etudiant['telephone'], $query) !== false
            ) {
                $results[] = $etudiant;
            }
        }
        
        return $results;
    }
    
    /**
     * Obtenir un étudiant par son ID
     */
    public function getEtudiantById($id) {
        foreach ($this->etudiants as $etudiant) {
            if ($etudiant['id_etu'] === $id || $etudiant['num_etu'] === $id) {
                return $etudiant;
            }
        }
        return null;
    }
    
    /**
     * Obtenir tous les étudiants avec pagination
     */
    public function getAllEtudiants($page = 1, $itemsPerPage = 20) {
        $total = count($this->etudiants);
        $totalPages = ceil($total / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;
        $paginated = array_slice($this->etudiants, $offset, $itemsPerPage);
        
        return [
            'data' => $paginated,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'itemsPerPage' => $itemsPerPage
        ];
    }
    
    /**
     * Ajouter un nouvel étudiant
     */
    public function addEtudiant($data) {
        $etudiant = [
            'id_etu' => 'ETU' . str_pad(count($this->etudiants) + 1, 6, '0', STR_PAD_LEFT),
            'etablissement' => $data['etablissement'] ?? '',
            'departement' => $data['departement'] ?? '',
            'niveauFormation' => $data['niveauFormation'] ?? '',
            'num_etu' => $data['num_etu'] ?? '',
            'nom' => $data['nom'] ?? '',
            'prenoms' => $data['prenoms'] ?? '',
            'dateNaissance' => $data['dateNaissance'] ?? '',
            'lieuNaissance' => $data['lieuNaissance'] ?? '',
            'sexe' => $data['sexe'] ?? '',
            'nationalite' => $data['nationalite'] ?? 'Sénégalaise',
            'numIdentite' => $data['numIdentite'] ?? '',
            'typeEtudiant' => $data['typeEtudiant'] ?? '',
            'moyenne' => $data['moyenne'] ?? '',
            'sessionId' => $data['sessionId'] ?? '',
            'niveau' => $data['niveau'] ?? '',
            'email_perso' => $data['email_perso'] ?? '',
            'email_ucad' => $data['email_ucad'] ?? '',
            'telephone' => $data['telephone'] ?? '',
            'var' => $data['var'] ?? '',
            'adresse' => $data['adresse'] ?? '',
            'urgenceContact' => $data['urgenceContact'] ?? '',
            'urgenceTel' => $data['urgenceTel'] ?? '',
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        $this->etudiants[] = $etudiant;
        $this->saveData();
        return $etudiant;
    }
    
    /**
     * Mettre à jour un étudiant
     */
    public function updateEtudiant($id, $data) {
        foreach ($this->etudiants as $key => $etudiant) {
            if ($etudiant['id_etu'] === $id || $etudiant['num_etu'] === $id) {
                $this->etudiants[$key] = array_merge($etudiant, $data, [
                    'updatedAt' => date('Y-m-d H:i:s')
                ]);
                $this->saveData();
                return $this->etudiants[$key];
            }
        }
        return false;
    }
    
    /**
     * Supprimer un étudiant
     */
    public function deleteEtudiant($id) {
        foreach ($this->etudiants as $key => $etudiant) {
            if ($etudiant['id_etu'] === $id || $etudiant['num_etu'] === $id) {
                unset($this->etudiants[$key]);
                $this->etudiants = array_values($this->etudiants); // Réindexer
                $this->saveData();
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtenir les statistiques
     */
    public function getStatistics() {
        $total = count($this->etudiants);
        
        $stats = [
            'total' => $total,
            'parEtablissement' => [],
            'parDepartement' => [],
            'parNiveau' => [],
            'parType' => []
        ];
        
        foreach ($this->etudiants as $etudiant) {
            // Stats par établissement
            $etablissement = $etudiant['etablissement'];
            if (!isset($stats['parEtablissement'][$etablissement])) {
                $stats['parEtablissement'][$etablissement] = 0;
            }
            $stats['parEtablissement'][$etablissement]++;
            
            // Stats par département
            $departement = $etudiant['departement'];
            if (!isset($stats['parDepartement'][$departement])) {
                $stats['parDepartement'][$departement] = 0;
            }
            $stats['parDepartement'][$departement]++;
            
            // Stats par niveau
            $niveau = $etudiant['niveau'];
            if (!isset($stats['parNiveau'][$niveau])) {
                $stats['parNiveau'][$niveau] = 0;
            }
            $stats['parNiveau'][$niveau]++;
            
            // Stats par type
            $type = $etudiant['typeEtudiant'];
            if (!isset($stats['parType'][$type])) {
                $stats['parType'][$type] = 0;
            }
            $stats['parType'][$type]++;
        }
        
        return $stats;
    }
    
    /**
     * Valider les données d'un étudiant
     */
    public function validateEtudiant($data) {
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = 'Le nom est requis';
        }
        
        if (empty($data['prenoms'])) {
            $errors[] = 'Le prénom est requis';
        }
        
        if (empty($data['dateNaissance'])) {
            $errors[] = 'La date de naissance est requise';
        }
        
        if (empty($data['etablissement'])) {
            $errors[] = 'L\'établissement est requis';
        }
        
        if (empty($data['departement'])) {
            $errors[] = 'Le département est requis';
        }
        
        if (empty($data['email_ucad'])) {
            $errors[] = 'L\'email UCAD est requis';
        } elseif (!filter_var($data['email_ucad'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email UCAD est invalide';
        }
        
        if (!empty($data['email_perso']) && !filter_var($data['email_perso'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email personnel est invalide';
        }
        
        if (!empty($data['telephone']) && !preg_match('/^\+221 [0-9]{2} [0-9]{7}$/', $data['telephone'])) {
            $errors[] = 'Le format du téléphone est invalide (ex: +221 77 1234567)';
        }
        
        return $errors;
    }
    
    /**
     * Exporter les étudiants en CSV
     */
    public function exportToCSV($search = '') {
        $data = !empty($search) ? $this->searchEtudiants($search) : $this->etudiants;
        
        $filename = 'etudiants_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($output, [
            'ID Étudiant',
            'Numéro Étudiant',
            'Nom',
            'Prénoms',
            'Établissement',
            'Département',
            'Niveau Formation',
            'Niveau',
            'Date de Naissance',
            'Lieu de Naissance',
            'Sexe',
            'Nationalité',
            'N° Identité',
            'Type Étudiant',
            'Moyenne',
            'Session',
            'Email UCAD',
            'Email Personnel',
            'Téléphone',
            'Statut',
            'Date de création'
        ]);
        
        // Données
        foreach ($data as $etudiant) {
            fputcsv($output, [
                $etudiant['id_etu'],
                $etudiant['num_etu'],
                $etudiant['nom'],
                $etudiant['prenoms'],
                $etudiant['etablissement'],
                $etudiant['departement'],
                $etudiant['niveauFormation'],
                $etudiant['niveau'],
                $etudiant['dateNaissance'],
                $etudiant['lieuNaissance'],
                $etudiant['sexe'],
                $etudiant['nationalite'],
                $etudiant['numIdentite'],
                $etudiant['typeEtudiant'],
                $etudiant['moyenne'],
                $etudiant['sessionId'],
                $etudiant['email_ucad'],
                $etudiant['email_perso'],
                $etudiant['telephone'],
                $etudiant['var'],
                $etudiant['createdAt']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
