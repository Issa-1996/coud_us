// Base de données locale (localStorage)
let pvData = [];
let currentEditId = null;
let currentDetailId = null;
let currentPage = 1;
let itemsPerPage = 10;
let filteredData = [];

let blesseCount = 0;
let dommageCount = 0;
let assaillantCount = 0;
let auditionCount = 0;
let temoignageCount = 0;
let currentStep = 1;
const totalSteps = 5;

// Gestion du stepper
function changeStep(direction) {
    if (direction === 1 && !validateStep(currentStep)) {
        return;
    }

    document.getElementById('step' + currentStep).classList.remove('active');
    document.querySelector('.stepper-item[data-step="' + currentStep + '"]').classList.remove('active');

    if (direction === 1) {
        document.querySelector('.stepper-item[data-step="' + currentStep + '"]').classList.add('completed');
    }

    currentStep += direction;

    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector('.stepper-item[data-step="' + currentStep + '"]').classList.add('active');

    updateStepButtons();
}

function updateStepButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'inline-block';
    }

    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }
}

function validateStep(step) {
    // Validation basique - peut être étendue
    return true;
}

function resetStepper() {
    currentStep = 1;

    for (let i = 1; i <= totalSteps; i++) {
        document.getElementById('step' + i).classList.remove('active');
        const stepItem = document.querySelector('.stepper-item[data-step="' + i + '"]');
        stepItem.classList.remove('active', 'completed');
    }

    document.getElementById('step1').classList.add('active');
    document.querySelector('.stepper-item[data-step="1"]').classList.add('active');

    updateStepButtons();

    // Réinitialiser les compteurs
    blesseCount = 0;
    dommageCount = 0;
    assaillantCount = 0;
    auditionCount = 0;
    temoignageCount = 0;

    // Vider les containers
    document.getElementById('blessesContainer').innerHTML = '';
    document.getElementById('dommagesContainer').innerHTML = '';
    document.getElementById('assaillantsContainer').innerHTML = '';
    document.getElementById('auditionsContainer').innerHTML = '';
    document.getElementById('temoignagesContainer').innerHTML = '';

    // Ajouter au moins un élément par défaut
    addBlesse();
    addDommage();
    addAssaillant();
    addAudition();
    addTemoignage();
}

// Fonctions pour ajouter des éléments dynamiques
function addBlesse() {
    blesseCount++;
    const html = `
        <div class="card mb-3" id="blesse-${blesseCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Blessé #${blesseCount}</h6>
                    ${blesseCount > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeBlesse(${blesseCount})"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="blesseNom-${blesseCount}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Prénoms</label>
                        <input type="text" class="form-control" id="blessePrenom-${blesseCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Localisations</label>
                        <input type="text" class="form-control" id="blesseLoc-${blesseCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Facultés et N° CE</label>
                        <input type="text" class="form-control" id="blesseFaculte-${blesseCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Téléphones</label>
                        <input type="tel" class="form-control" id="blesseTel-${blesseCount}">
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('blessesContainer').insertAdjacentHTML('beforeend', html);
}

function removeBlesse(id) {
    document.getElementById('blesse-' + id).remove();
}

function addDommage() {
    dommageCount++;
    const html = `
        <div class="card mb-3" id="dommage-${dommageCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Dommage #${dommageCount}</h6>
                    ${dommageCount > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeDommage(${dommageCount})"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="dommageNom-${dommageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Prénoms</label>
                        <input type="text" class="form-control" id="dommagePrenom-${dommageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Localisations</label>
                        <input type="text" class="form-control" id="dommageLoc-${dommageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Facultés et N° CE</label>
                        <input type="text" class="form-control" id="dommageFaculte-${dommageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Téléphones</label>
                        <input type="tel" class="form-control" id="dommageTel-${dommageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Préjudices subits</label>
                        <input type="text" class="form-control" id="dommagePrejudice-${dommageCount}">
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('dommagesContainer').insertAdjacentHTML('beforeend', html);
}

function removeDommage(id) {
    document.getElementById('dommage-' + id).remove();
}

function addAssaillant() {
    assaillantCount++;
    const html = `
        <div class="card mb-3" id="assaillant-${assaillantCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Assaillant #${assaillantCount}</h6>
                    ${assaillantCount > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeAssaillant(${assaillantCount})"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="assaillantNom-${assaillantCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Prénoms</label>
                        <input type="text" class="form-control" id="assaillantPrenom-${assaillantCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Localisations</label>
                        <input type="text" class="form-control" id="assaillantLoc-${assaillantCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Facultés et N° CE</label>
                        <input type="text" class="form-control" id="assaillantFaculte-${assaillantCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Téléphones</label>
                        <input type="tel" class="form-control" id="assaillantTel-${assaillantCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Rôles</label>
                        <input type="text" class="form-control" id="assaillantRole-${assaillantCount}">
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('assaillantsContainer').insertAdjacentHTML('beforeend', html);
}

function removeAssaillant(id) {
    document.getElementById('assaillant-' + id).remove();
}

function addAudition() {
    auditionCount++;
    const html = `
        <div class="card mb-3" id="audition-${auditionCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Audition #${auditionCount}</h6>
                    ${auditionCount > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeAudition(${auditionCount})"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Mr ; Mme ; Mlle</label>
                        <input type="text" class="form-control" id="auditionNom-${auditionCount}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Né(e) le</label>
                        <input type="date" class="form-control" id="auditionNaissance-${auditionCount}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Résidant(e) à</label>
                        <input type="text" class="form-control" id="auditionResidence-${auditionCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Téléphone N°</label>
                        <input type="tel" class="form-control" id="auditionTel-${auditionCount}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Titulaire de la carte d'étudiant N°</label>
                        <input type="text" class="form-control" id="auditionCarte-${auditionCount}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Faculté</label>
                        <input type="text" class="form-control" id="auditionFaculte-${auditionCount}">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Propos</label>
                        <textarea class="form-control" id="auditionPropos-${auditionCount}" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('auditionsContainer').insertAdjacentHTML('beforeend', html);
}

function removeAudition(id) {
    document.getElementById('audition-' + id).remove();
}

function addTemoignage() {
    temoignageCount++;
    const html = `
        <div class="card mb-3" id="temoignage-${temoignageCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Témoignage #${temoignageCount}</h6>
                    ${temoignageCount > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeTemoignage(${temoignageCount})"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Mr ; Mme ; Mlle</label>
                        <input type="text" class="form-control" id="temoignageNom-${temoignageCount}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Né(e) le</label>
                        <input type="date" class="form-control" id="temoignageNaissance-${temoignageCount}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Résidant(e) à</label>
                        <input type="text" class="form-control" id="temoignageResidence-${temoignageCount}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Téléphone N°</label>
                        <input type="tel" class="form-control" id="temoignageTel-${temoignageCount}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Titulaire de la carte d'étudiant N°</label>
                        <input type="text" class="form-control" id="temoignageCarte-${temoignageCount}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Faculté</label>
                        <input type="text" class="form-control" id="temoignageFaculte-${temoignageCount}">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Propos</label>
                        <textarea class="form-control" id="temoignagePropos-${temoignageCount}" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('temoignagesContainer').insertAdjacentHTML('beforeend', html);
}

function removeTemoignage(id) {
    document.getElementById('temoignage-' + id).remove();
}

// Données fictives
function generateFakeData() {
    const noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf'];
    const prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama'];
    const lieux = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    const natures = ['Bagarre', 'Vol', 'Accident', 'Incendie', 'Vandalisme', 'Agression'];
    const statuts = ['en_cours', 'traite'];

    const fakeData = [];
    for (let i = 1; i <= 25; i++) {
        const date = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);

        fakeData.push({
            id: Date.now() + i,
            dateIncident: date.toISOString().split('T')[0],
            heureIncident: `${Math.floor(Math.random() * 24)}:${Math.floor(Math.random() * 60).toString().padStart(2, '0')}`,
            lieuIncident: lieux[Math.floor(Math.random() * lieux.length)],
            natureIncident: natures[Math.floor(Math.random() * natures.length)],
            blesses: Math.floor(Math.random() * 5),
            dommages: Math.floor(Math.random() * 5),
            assaillants: Math.floor(Math.random() * 3),
            chargeRenseignements: `Agent ${noms[Math.floor(Math.random() * noms.length)]}`,
            coordonnateurSecurite: `Chef ${noms[Math.floor(Math.random() * noms.length)]}`,
            statut: statuts[Math.floor(Math.random() * statuts.length)],
            blessesList: [],
            dommagesList: [],
            assaillantsList: [],
            auditionsList: [],
            temoignagesList: [],
            suitesBlesses: 'Évacuation vers l\'infirmerie',
            suitesDommages: 'Évaluation des dégâts en cours',
            suitesAssaillants: 'Recherche en cours'
        });
    }
    return fakeData;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Forcer la régénération des données fictives pour le test
    localStorage.removeItem('pvData');

    // Générer les données fictives
    pvData = generateFakeData();
    localStorage.setItem('pvData', JSON.stringify(pvData));

    filteredData = [...pvData];
    loadPVData();
    updateStatistics();

    document.getElementById('addDateIncident').valueAsDate = new Date();
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('filterStatus').addEventListener('change', filterTable);

    const addModal = document.getElementById('addModal');
    addModal.addEventListener('show.bs.modal', function() {
        resetStepper();
    });
});

// Sauvegarder un PV
function savePV() {
    // Validation
    const dateIncident = document.getElementById('addDateIncident').value;
    const heureIncident = document.getElementById('addHeureIncident').value;
    const lieuIncident = document.getElementById('addLieuIncident').value;
    const natureIncident = document.getElementById('addNatureIncident').value;

    if (!dateIncident || !heureIncident || !lieuIncident || !natureIncident) {
        showAlert('Veuillez remplir tous les champs obligatoires', 'warning');
        return;
    }

    // Collecter les données des blessés
    const blessesList = [];
    for (let i = 1; i <= blesseCount; i++) {
        if (document.getElementById('blesse-' + i)) {
            blessesList.push({
                nom: document.getElementById('blesseNom-' + i)?.value || '',
                prenom: document.getElementById('blessePrenom-' + i)?.value || '',
                localisation: document.getElementById('blesseLoc-' + i)?.value || '',
                faculte: document.getElementById('blesseFaculte-' + i)?.value || '',
                telephone: document.getElementById('blesseTel-' + i)?.value || ''
            });
        }
    }

    // Collecter les données des dommages
    const dommagesList = [];
    for (let i = 1; i <= dommageCount; i++) {
        if (document.getElementById('dommage-' + i)) {
            dommagesList.push({
                nom: document.getElementById('dommageNom-' + i)?.value || '',
                prenom: document.getElementById('dommagePrenom-' + i)?.value || '',
                localisation: document.getElementById('dommageLoc-' + i)?.value || '',
                faculte: document.getElementById('dommageFaculte-' + i)?.value || '',
                telephone: document.getElementById('dommageTel-' + i)?.value || '',
                prejudice: document.getElementById('dommagePrejudice-' + i)?.value || ''
            });
        }
    }

    // Collecter les données des assaillants
    const assaillantsList = [];
    for (let i = 1; i <= assaillantCount; i++) {
        if (document.getElementById('assaillant-' + i)) {
            assaillantsList.push({
                nom: document.getElementById('assaillantNom-' + i)?.value || '',
                prenom: document.getElementById('assaillantPrenom-' + i)?.value || '',
                localisation: document.getElementById('assaillantLoc-' + i)?.value || '',
                faculte: document.getElementById('assaillantFaculte-' + i)?.value || '',
                telephone: document.getElementById('assaillantTel-' + i)?.value || '',
                role: document.getElementById('assaillantRole-' + i)?.value || ''
            });
        }
    }

    // Collecter les auditions
    const auditionsList = [];
    for (let i = 1; i <= auditionCount; i++) {
        if (document.getElementById('audition-' + i)) {
            auditionsList.push({
                nom: document.getElementById('auditionNom-' + i)?.value || '',
                naissance: document.getElementById('auditionNaissance-' + i)?.value || '',
                residence: document.getElementById('auditionResidence-' + i)?.value || '',
                telephone: document.getElementById('auditionTel-' + i)?.value || '',
                carte: document.getElementById('auditionCarte-' + i)?.value || '',
                faculte: document.getElementById('auditionFaculte-' + i)?.value || '',
                propos: document.getElementById('auditionPropos-' + i)?.value || ''
            });
        }
    }

    // Collecter les témoignages
    const temoignagesList = [];
    for (let i = 1; i <= temoignageCount; i++) {
        if (document.getElementById('temoignage-' + i)) {
            temoignagesList.push({
                nom: document.getElementById('temoignageNom-' + i)?.value || '',
                naissance: document.getElementById('temoignageNaissance-' + i)?.value || '',
                residence: document.getElementById('temoignageResidence-' + i)?.value || '',
                telephone: document.getElementById('temoignageTel-' + i)?.value || '',
                carte: document.getElementById('temoignageCart-' + i)?.value || '',
                faculte: document.getElementById('temoignageFaculte-' + i)?.value || '',
                propos: document.getElementById('temoignagePropos-' + i)?.value || ''
            });
        }
    }

    const pv = {
        id: Date.now(),
        dateIncident: dateIncident,
        heureIncident: heureIncident,
        lieuIncident: lieuIncident,
        natureIncident: natureIncident,
        blesses: blessesList.length,
        dommages: dommagesList.length,
        assaillants: assaillantsList.length,
        chargeRenseignements: document.getElementById('addChargeRenseignements').value,
        coordonnateurSecurite: document.getElementById('addCoordonnateurSecurite').value,
        statut: document.getElementById('addStatut').value,
        blessesList: blessesList,
        dommagesList: dommagesList,
        assaillantsList: assaillantsList,
        auditionsList: auditionsList,
        temoignagesList: temoignagesList,
        suitesBlesses: document.getElementById('addSuitesBlesses').value,
        suitesDommages: document.getElementById('addSuitesDommages').value,
        suitesAssaillants: document.getElementById('addSuitesAssaillants').value
    };

    pvData.push(pv);
    localStorage.setItem('pvConstat', JSON.stringify(pvData));

    bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
    document.getElementById('addForm').reset();
    resetStepper();

    filteredData = [...pvData];
    currentPage = 1;
    loadPVData();
    updateStatistics();

    showAlert('Procès-verbal ajouté avec succès!', 'success');
}

// Charger les données
function loadPVData() {
    const tbody = document.getElementById('pvTableBody');
    tbody.innerHTML = '';

    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucun procès-verbal enregistré</p>
                </td>
            </tr>
        `;
        document.getElementById('paginationContainer').style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedData = filteredData.slice(startIndex, endIndex);

    paginatedData.forEach((pv, index) => {
        const globalIndex = startIndex + index + 1;

        const natureLabel = {
            'Bagarre': '<span class="badge bg-danger me-1">Bagarre</span>',
            'Vol': '<span class="badge bg-warning me-1">Vol</span>',
            'Accident': '<span class="badge bg-info me-1">Accident</span>',
            'Incendie': '<span class="badge bg-danger me-1">Incendie</span>',
            'Vandalisme': '<span class="badge bg-secondary me-1">Vandalisme</span>',
            'Agression': '<span class="badge bg-dark me-1">Agression</span>'
        } [pv.natureIncident] || '<span class="badge bg-secondary">N/A</span>';

        const lieuLabel = {
            'Campus Social ESP': '<span class="badge bg-danger-subtle text-danger me-1">ESP</span>',
            'Campus Social UCAD': '<span class="badge bg-primary-subtle text-primary me-1">UCAD</span>',
            'Résidence Claudel': '<span class="badge bg-success-subtle text-success me-1">Claudel</span>',
            'Cité Mixte': '<span class="badge bg-info-subtle text-info me-1">Mixte</span>'
        } [pv.lieuIncident] || '<span class="badge bg-secondary">N/A</span>';

        const row = `
            <tr>
                <td>${globalIndex}</td>
                <td>${formatDate(pv.dateIncident)} ${pv.heureIncident || '00:00'}</td>
                <td>${lieuLabel}</td>
                <td>${natureLabel}</td>
                <td>${pv.blesses > 0 ? `<span class="badge bg-danger">${pv.blesses}</span>` : '-'}</td>
                <td>${pv.dommages > 0 ? `<span class="badge bg-warning">${pv.dommages}</span>` : '-'}</td>
                <td>${formatStatut(pv.statut)}</td>
                <td>
                    <button class="btn btn-sm btn-info btn-action" onclick="viewDetail(${pv.id})" title="Détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning btn-action" onclick="editPV(${pv.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-action" onclick="deletePV(${pv.id})" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });

    document.getElementById('totalCount').textContent = filteredData.length;
    renderPagination(totalPages);
}

function formatDate(dateString) {
    if (!dateString) return 'Date non spécifiée';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Date invalide';
    return date.toLocaleDateString('fr-FR');
}

function formatStatut(statut) {
    if (statut === 'en_cours') {
        return '<span class="badge badge-status bg-warning text-dark"><i class="fas fa-clock me-1"></i>En cours</span>';
    } else if (statut === 'traite') {
        return '<span class="badge badge-status bg-success"><i class="fas fa-check-circle me-1"></i>Traité</span>';
    } else {
        return '<span class="badge badge-status bg-secondary">Non défini</span>';
    }
}

// Pagination
function renderPagination(totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');

    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;"><i class="fas fa-chevron-left"></i></a>`;
    pagination.appendChild(prevLi);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
        pagination.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;"><i class="fas fa-chevron-right"></i></a>`;
    pagination.appendChild(nextLi);

    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, filteredData.length);
    document.getElementById('paginationInfo').textContent = `Affichage de ${startItem} à ${endItem} sur ${filteredData.length} entrées`;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadPVData();
    document.querySelector('.table-wrapper').scrollIntoView({
        behavior: 'smooth'
    });
}

function changeItemsPerPage() {
    itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    currentPage = 1;
    loadPVData();
}

// Filtrer
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    
    filteredData = pvData.filter(pv => {
        const matchesSearch = 
            pv.dateIncident.toLowerCase().includes(searchTerm) ||
            pv.lieuIncident.toLowerCase().includes(searchTerm) ||
            pv.natureIncident.toLowerCase().includes(searchTerm) ||
            pv.chargeRenseignements.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || pv.statut === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    loadPVData();
}

// Voir les détails
function viewDetail(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    // Remplir les détails principaux
    document.getElementById('detailDateIncident').textContent = formatDate(pv.dateIncident);
    document.getElementById('detailHeureIncident').textContent = pv.heureIncident || 'Non spécifié';
    document.getElementById('detailLieuIncident').textContent = pv.lieuIncident;
    document.getElementById('detailNatureIncident').textContent = pv.natureIncident;
    document.getElementById('detailChargeRenseignements').textContent = pv.chargeRenseignements;
    document.getElementById('detailCoordonnateurSecurite').textContent = pv.coordonnateurSecurite;
    document.getElementById('detailStatut').innerHTML = formatStatut(pv.statut);

    // Afficher les listes
    displayList('detailBlesses', pv.blessesList, 'blessé');
    displayList('detailDommages', pv.dommagesList, 'dommage');
    displayList('detailAssaillants', pv.assaillantsList, 'assaillant');
    displayList('detailAuditions', pv.auditionsList, 'audition');
    displayList('detailTemoignages', pv.temoignagesList, 'témoin');

    // Suites données
    document.getElementById('detailSuitesBlesses').textContent = pv.suitesBlesses || 'Non spécifié';
    document.getElementById('detailSuitesDommages').textContent = pv.suitesDommages || 'Non spécifié';
    document.getElementById('detailSuitesAssaillants').textContent = pv.suitesAssaillants || 'Non spécifié';

    currentDetailId = id;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function displayList(containerId, list, type) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';

    if (!list || list.length === 0) {
        container.innerHTML = `<p class="text-muted">Aucun ${type} enregistré</p>`;
        return;
    }

    list.forEach((item, index) => {
        const itemHtml = `
            <div class="border rounded p-2 mb-2">
                <h6 class="text-primary">${type.charAt(0).toUpperCase() + type.slice(1)} #${index + 1}</h6>
                <div class="row">
                    ${item.nom ? `<div class="col-md-6"><strong>Nom:</strong> ${item.nom}</div>` : ''}
                    ${item.prenom ? `<div class="col-md-6"><strong>Prénoms:</strong> ${item.prenom}</div>` : ''}
                    ${item.localisation ? `<div class="col-md-6"><strong>Localisation:</strong> ${item.localisation}</div>` : ''}
                    ${item.faculte ? `<div class="col-md-6"><strong>Faculté:</strong> ${item.faculte}</div>` : ''}
                    ${item.telephone ? `<div class="col-md-6"><strong>Téléphone:</strong> ${item.telephone}</div>` : ''}
                    ${item.naissance ? `<div class="col-md-6"><strong>Date de naissance:</strong> ${formatDate(item.naissance)}</div>` : ''}
                    ${item.residence ? `<div class="col-md-6"><strong>Résidence:</strong> ${item.residence}</div>` : ''}
                    ${item.carte ? `<div class="col-md-6"><strong>N° Carte:</strong> ${item.carte}</div>` : ''}
                    ${item.role ? `<div class="col-md-6"><strong>Rôle:</strong> ${item.role}</div>` : ''}
                    ${item.prejudice ? `<div class="col-md-12"><strong>Préjudice:</strong> ${item.prejudice}</div>` : ''}
                    ${item.propos ? `<div class="col-md-12"><strong>Propos:</strong> ${item.propos}</div>` : ''}
                </div>
            </div>
        `;
        container.innerHTML += itemHtml;
    });
}

// Éditer un PV
function editPV(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    // Remplir les champs principaux
    document.getElementById('editDateIncident').value = pv.dateIncident;
    document.getElementById('editHeureIncident').value = pv.heureIncident || '';
    document.getElementById('editLieuIncident').value = pv.lieuIncident;
    document.getElementById('editNatureIncident').value = pv.natureIncident;
    document.getElementById('editChargeRenseignements').value = pv.chargeRenseignements;
    document.getElementById('editCoordonnateurSecurite').value = pv.coordonnateurSecurite;
    document.getElementById('editStatut').value = pv.statut;

    // Remplir les suites
    document.getElementById('editSuitesBlesses').value = pv.suitesBlesses || '';
    document.getElementById('editSuitesDommages').value = pv.suitesDommages || '';
    document.getElementById('editSuitesAssaillants').value = pv.suitesAssaillants || '';

    currentEditId = id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Mettre à jour un PV
function updatePV() {
    if (!currentEditId) return;

    const index = pvData.findIndex(p => p.id === currentEditId);
    if (index === -1) return;

    pvData[index] = {
        ...pvData[index],
        dateIncident: document.getElementById('editDateIncident').value,
        heureIncident: document.getElementById('editHeureIncident').value,
        lieuIncident: document.getElementById('editLieuIncident').value,
        natureIncident: document.getElementById('editNatureIncident').value,
        chargeRenseignements: document.getElementById('editChargeRenseignements').value,
        coordonnateurSecurite: document.getElementById('editCoordonnateurSecurite').value,
        statut: document.getElementById('editStatut').value,
        suitesBlesses: document.getElementById('editSuitesBlesses').value,
        suitesDommages: document.getElementById('editSuitesDommages').value,
        suitesAssaillants: document.getElementById('editSuitesAssaillants').value
    };

    localStorage.setItem('pvConstat', JSON.stringify(pvData));
    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

    filteredData = [...pvData];
    loadPVData();
    updateStatistics();

    showAlert('Procès-verbal mis à jour avec succès!', 'success');
}

// Supprimer un PV
function deletePV(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce procès-verbal ?')) {
        pvData = pvData.filter(p => p.id !== id);
        localStorage.setItem('pvConstat', JSON.stringify(pvData));

        filteredData = [...pvData];
        const totalPages = Math.ceil(filteredData.length / itemsPerPage);
        if (currentPage > totalPages && currentPage > 1) {
            currentPage--;
        }

        loadPVData();
        updateStatistics();

        showAlert('Procès-verbal supprimé avec succès!', 'danger');
    }
}

// Mettre à jour les statistiques
function updateStatistics() {
    const total = pvData.length;
    const enCours = pvData.filter(p => p.statut === 'en_cours').length;
    const traites = pvData.filter(p => p.statut === 'traite').length;

    document.getElementById('totalPV').textContent = total;
    document.getElementById('enCours').textContent = enCours;
    document.getElementById('traites').textContent = traites;
}

// Afficher une alerte
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
