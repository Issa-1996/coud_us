// Base de données locale (localStorage)
let pvData = [];
let currentEditId = null;
let currentDetailId = null;
let currentPage = 1;
let itemsPerPage = 10;
let filteredData = [];
let currentStep = 1;
const totalSteps = 4;

// Données fictives pour remplir le tableau
function generateFakeData() {
    const noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    const prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    const campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    const types = [
        ['violence'],
        ['harcelement'],
        ['diffamation'],
        ['vol'],
        ['violence', 'harcelement']
    ];
    const statuts = ['en_cours', 'traite'];

    const fakeData = [];
    for (let i = 1; i <= 30; i++) {
        const date = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
        const typeSelected = types[Math.floor(Math.random() * types.length)];

        fakeData.push({
            id: Date.now() + i,
            soussigne: noms[Math.floor(Math.random() * noms.length)],
            soussignePrenom: prenoms[Math.floor(Math.random() * prenoms.length)],
            agents: `Agent ${i}, Agent ${i+10}`,
            campusSocial: campus[Math.floor(Math.random() * campus.length)],
            lieuConsolidation: `M. ${noms[Math.floor(Math.random() * noms.length)]}`,
            typesDenonciation: typeSelected,
            menaces: 'Menaces verbales répétées',
            harcelement: 'Harcèlement quotidien',
            diffamation: 'Propos diffamatoires',
            vol: 'Vol de biens personnels',
            autres: '',
            victimeNom: noms[Math.floor(Math.random() * noms.length)],
            victimePrenom: prenoms[Math.floor(Math.random() * prenoms.length)],
            victimeTel: `7${Math.floor(Math.random() * 9)}${String(Math.floor(Math.random() * 10000000)).padStart(7, '0')}`,
            auteurNom: noms[Math.floor(Math.random() * noms.length)],
            auteurPrenom: prenoms[Math.floor(Math.random() * prenoms.length)],
            auteurDetails: 'Personne connue de la victime',
            temoignages: 'Témoins présents sur les lieux',
            responsabilites: 'En cours d\'investigation',
            datePV: date.toISOString().split('T')[0],
            chargeEnquete: `Agent ${noms[Math.floor(Math.random() * noms.length)]}`,
            statut: statuts[Math.floor(Math.random() * statuts.length)]
        });
    }
    return fakeData;
}

function updateStepButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    // Bouton Précédent
    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'inline-block';
    }

    // Bouton Suivant / Enregistrer
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }
}

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

function validateStep(step) {
    let isValid = true;
    let message = '';

    switch (step) {
        case 1:
            const soussigne = document.getElementById('addSoussigne').value.trim();
            const soussignePrenom = document.getElementById('addSoussignePrenom').value.trim();
            if (!soussigne || !soussignePrenom) {
                message = 'Veuillez renseigner le nom et prénom du soussigné';
                isValid = false;
            }
            break;
        case 2:
            const violence = document.getElementById('addViolence').checked;
            const harcelement = document.getElementById('addHarcelement').checked;
            const diffamation = document.getElementById('addDiffamation').checked;
            const vol = document.getElementById('addVol').checked;
            if (!violence && !harcelement && !diffamation && !vol) {
                message = 'Veuillez sélectionner au moins un type de dénonciation';
                isValid = false;
            }
            break;
        case 3:
            const victimeNom = document.getElementById('addVictimeNom').value.trim();
            const victimePrenom = document.getElementById('addVictimePrenom').value.trim();
            if (!victimeNom || !victimePrenom) {
                message = 'Veuillez renseigner le nom et prénom de la victime';
                isValid = false;
            }
            break;
    }

    if (!isValid) {
        showAlert(message, 'warning');
    }

    return isValid;
}

function resetStepper() {
    currentStep = 1;

    // Réinitialiser toutes les étapes
    for (let i = 1; i <= totalSteps; i++) {
        document.getElementById('step' + i).classList.remove('active');
        const stepItem = document.querySelector('.stepper-item[data-step="' + i + '"]');
        stepItem.classList.remove('active', 'completed');
    }

    // Activer la première étape
    document.getElementById('step1').classList.add('active');
    document.querySelector('.stepper-item[data-step="1"]').classList.add('active');

    updateStepButtons();
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

    document.getElementById('addDatePV').valueAsDate = new Date();
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('filterMotif').addEventListener('change', filterTable);
    document.getElementById('filterStatus').addEventListener('change', filterTable);

    // Réinitialiser le stepper à l'ouverture du modal
    const addModal = document.getElementById('addModal');
    addModal.addEventListener('show.bs.modal', function() {
        resetStepper();
    });
});

// Sauvegarder un PV
function savePV() {
    // Validation finale
    if (!validateStep(4)) {
        return;
    }

    const datePV = document.getElementById('addDatePV').value;
    if (!datePV) {
        showAlert('Veuillez renseigner la date du PV', 'warning');
        return;
    }

    const typesDenonciation = [];
    if (document.getElementById('addViolence').checked) typesDenonciation.push('violence');
    if (document.getElementById('addHarcelement').checked) typesDenonciation.push('harcelement');
    if (document.getElementById('addDiffamation').checked) typesDenonciation.push('diffamation');
    if (document.getElementById('addVol').checked) typesDenonciation.push('vol');

    const pv = {
        id: Date.now(),
        soussigne: document.getElementById('addSoussigne').value,
        soussignePrenom: document.getElementById('addSoussignePrenom').value,
        agents: document.getElementById('addAgents').value,
        campusSocial: document.getElementById('addCampusSocial').value,
        lieuConsolidation: document.getElementById('addLieuConsolidation').value,
        typesDenonciation: typesDenonciation,
        menaces: document.getElementById('addMenaces').value,
        harcelement: document.getElementById('addHarcelement2').value,
        diffamation: document.getElementById('addDiffamation2').value,
        vol: document.getElementById('addVol2').value,
        autres: document.getElementById('addAutres').value,
        victimeNom: document.getElementById('addVictimeNom').value,
        victimePrenom: document.getElementById('addVictimePrenom').value,
        victimeTel: document.getElementById('addVictimeTel').value,
        auteurNom: document.getElementById('addAuteurNom').value,
        auteurPrenom: document.getElementById('addAuteurPrenom').value,
        auteurDetails: document.getElementById('addAuteurDetails').value,
        temoignages: document.getElementById('addTemoignages').value,
        responsabilites: document.getElementById('addResponsabilites').value,
        datePV: datePV,
        chargeEnquete: document.getElementById('addChargeEnquete').value,
        statut: document.getElementById('addStatut').value
    };

    pvData.push(pv);
    localStorage.setItem('pvDenonciation', JSON.stringify(pvData));

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
                <td colspan="9" class="text-center py-5">
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
        const typesLabels = pv.typesDenonciation && pv.typesDenonciation.length ? pv.typesDenonciation.map(t => {
            const labels = {
                'violence': '<span class="badge bg-danger me-1">Violence</span>',
                'harcelement': '<span class="badge bg-warning me-1">Harcèlement</span>',
                'diffamation': '<span class="badge bg-info me-1">Diffamation</span>',
                'vol': '<span class="badge bg-secondary me-1">Vol</span>'
            };
            return labels[t] || '';
        }).join('') : '<span class="badge bg-secondary">Non spécifié</span>';

        const row = `
            <tr>
                <td>${globalIndex}</td>
                <td><strong>${pv.soussigne || 'N/A'} ${pv.soussignePrenom || ''}</strong></td>
                <td>${pv.agents || 'N/A'}</td>
                <td>${pv.campusSocial || 'N/A'}</td>
                <td>${typesLabels}</td>
                <td>${pv.victimeNom || 'N/A'} ${pv.victimePrenom || ''}</td>
                <td>${pv.victimeTel || '-'}</td>
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

// Format du statut
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
    const motifFilter = document.getElementById('filterMotif').value;
    const statusFilter = document.getElementById('filterStatus').value;

    filteredData = pvData.filter(pv => {
        const matchesSearch =
            pv.soussigne.toLowerCase().includes(searchTerm) ||
            pv.victimeNom.toLowerCase().includes(searchTerm) ||
            pv.campusSocial.toLowerCase().includes(searchTerm) ||
            (pv.victimeTel && pv.victimeTel.includes(searchTerm));

        const matchesMotif = !motifFilter || pv.typesDenonciation.includes(motifFilter);
        const matchesStatus = !statusFilter || pv.statut === statusFilter;

        return matchesSearch && matchesMotif && matchesStatus;
    });

    currentPage = 1;
    loadPVData();
}

// Modifier
function editPV(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    document.getElementById('editId').value = pv.id;
    document.getElementById('editSoussigne').value = pv.soussigne;
    document.getElementById('editSoussignePrenom').value = pv.soussignePrenom;
    document.getElementById('editAgents').value = pv.agents;
    document.getElementById('editCampusSocial').value = pv.campusSocial;
    document.getElementById('editLieuConsolidation').value = pv.lieuConsolidation;

    document.getElementById('editViolence').checked = pv.typesDenonciation.includes('violence');
    document.getElementById('editHarcelement').checked = pv.typesDenonciation.includes('harcelement');
    document.getElementById('editDiffamation').checked = pv.typesDenonciation.includes('diffamation');
    document.getElementById('editVol').checked = pv.typesDenonciation.includes('vol');

    document.getElementById('editMenaces').value = pv.menaces;
    document.getElementById('editHarcelement2').value = pv.harcelement;
    document.getElementById('editDiffamation2').value = pv.diffamation;
    document.getElementById('editVol2').value = pv.vol;
    document.getElementById('editAutres').value = pv.autres;
    document.getElementById('editVictimeNom').value = pv.victimeNom;
    document.getElementById('editVictimePrenom').value = pv.victimePrenom;
    document.getElementById('editVictimeTel').value = pv.victimeTel;
    document.getElementById('editAuteurNom').value = pv.auteurNom;
    document.getElementById('editAuteurPrenom').value = pv.auteurPrenom;
    document.getElementById('editAuteurDetails').value = pv.auteurDetails;
    document.getElementById('editTemoignages').value = pv.temoignages;
    document.getElementById('editResponsabilites').value = pv.responsabilites;
    document.getElementById('editDatePV').value = pv.datePV;
    document.getElementById('editChargeEnquete').value = pv.chargeEnquete;
    document.getElementById('editStatut').value = pv.statut;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updatePV() {
    const id = parseInt(document.getElementById('editId').value);
    const index = pvData.findIndex(p => p.id === id);
    if (index === -1) return;

    const typesDenonciation = [];
    if (document.getElementById('editViolence').checked) typesDenonciation.push('violence');
    if (document.getElementById('editHarcelement').checked) typesDenonciation.push('harcelement');
    if (document.getElementById('editDiffamation').checked) typesDenonciation.push('diffamation');
    if (document.getElementById('editVol').checked) typesDenonciation.push('vol');

    pvData[index] = {
        id: id,
        soussigne: document.getElementById('editSoussigne').value,
        soussignePrenom: document.getElementById('editSoussignePrenom').value,
        agents: document.getElementById('editAgents').value,
        campusSocial: document.getElementById('editCampusSocial').value,
        lieuConsolidation: document.getElementById('editLieuConsolidation').value,
        typesDenonciation: typesDenonciation,
        menaces: document.getElementById('editMenaces').value,
        harcelement: document.getElementById('editHarcelement2').value,
        diffamation: document.getElementById('editDiffamation2').value,
        vol: document.getElementById('editVol2').value,
        autres: document.getElementById('editAutres').value,
        victimeNom: document.getElementById('editVictimeNom').value,
        victimePrenom: document.getElementById('editVictimePrenom').value,
        victimeTel: document.getElementById('editVictimeTel').value,
        auteurNom: document.getElementById('editAuteurNom').value,
        auteurPrenom: document.getElementById('editAuteurPrenom').value,
        auteurDetails: document.getElementById('editAuteurDetails').value,
        temoignages: document.getElementById('editTemoignages').value,
        responsabilites: document.getElementById('editResponsabilites').value,
        datePV: document.getElementById('editDatePV').value,
        chargeEnquete: document.getElementById('editChargeEnquete').value,
        statut: document.getElementById('editStatut').value
    };

    localStorage.setItem('pvDenonciation', JSON.stringify(pvData));
    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

    filteredData = [...pvData];
    loadPVData();
    updateStatistics();
    showAlert('Procès-verbal modifié avec succès!', 'success');
}

// Voir détails
function viewDetail(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    const typesLabels = pv.typesDenonciation.map(t => {
        const labels = {
            'violence': '<span class="badge bg-danger me-1">Violence</span>',
            'harcelement': '<span class="badge bg-warning me-1">Harcèlement</span>',
            'diffamation': '<span class="badge bg-info me-1">Diffamation</span>',
            'vol': '<span class="badge bg-secondary me-1">Vol</span>'
        };
        return labels[t] || '';
    }).join('');

    const content = `
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-user me-2"></i>Soussigné
                        </h6>
                        <p><span class="detail-label">Nom & Prénom:</span> ${pv.soussigne} ${pv.soussignePrenom}</p>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-user-shield me-2"></i>Agents en Action
                        </h6>
                        <p>${pv.agents}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Lieu
                        </h6>
                        <p><span class="detail-label">Campus:</span> ${pv.campusSocial}</p>
                        <p><span class="detail-label">Consolidations:</span> ${pv.lieuConsolidation}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-exclamation-circle me-2"></i>Type de Dénonciation
                        </h6>
                        <p>${typesLabels}</p>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>Détails de la Dénonciation
                        </h6>
                        ${pv.menaces ? `<p><span class="detail-label">Menaces:</span> ${pv.menaces}</p>` : ''}
                        ${pv.harcelement ? `<p><span class="detail-label">Harcèlement:</span> ${pv.harcelement}</p>` : ''}
                        ${pv.diffamation ? `<p><span class="detail-label">Diffamation:</span> ${pv.diffamation}</p>` : ''}
                        ${pv.vol ? `<p><span class="detail-label">Vol:</span> ${pv.vol}</p>` : ''}
                        ${pv.autres ? `<p><span class="detail-label">Autres:</span> ${pv.autres}</p>` : ''}
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-user-injured me-2"></i>La Victime
                        </h6>
                        <p><span class="detail-label">Nom & Prénom:</span> ${pv.victimeNom} ${pv.victimePrenom}</p>
                        <p><span class="detail-label">Téléphone:</span> ${pv.victimeTel}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-user-secret me-2"></i>L'Auteur des Faits
                        </h6>
                        <p><span class="detail-label">Nom & Prénom:</span> ${pv.auteurNom} ${pv.auteurPrenom}</p>
                        <p><span class="detail-label">Détails:</span> ${pv.auteurDetails}</p>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-users me-2"></i>Témoignages
                        </h6>
                        <p>${pv.temoignages || '-'}</p>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-gavel me-2"></i>Responsabilités
                        </h6>
                        <p>${pv.responsabilites || '-'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-calendar me-2"></i>Date & Chargé d'enquête
                        </h6>
                        <p><span class="detail-label">Date:</span> ${new Date(pv.datePV).toLocaleDateString('fr-FR')}</p>
                        <p><span class="detail-label">Chargé:</span> ${pv.chargeEnquete}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">
                            <i class="fas fa-flag me-2"></i>Statut
                        </h6>
                        <p>${formatStatut(pv.statut)}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('detailContent').innerHTML = content;
    currentDetailId = id;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Supprimer
function deletePV(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce procès-verbal ?')) {
        pvData = pvData.filter(p => p.id !== id);
        localStorage.setItem('pvDenonciation', JSON.stringify(pvData));

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
