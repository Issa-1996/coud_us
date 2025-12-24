// Base de données locale (localStorage)
let pvData = [];
let currentEditId = null;
let currentDetailId = null;
let currentPage = 1;
let itemsPerPage = 10;
let filteredData = [];

// Sauvegarder un nouveau PV
function savePV() {
    const pv = {
        id: Date.now(),
        carteEtudiant: document.getElementById('addCarteEtudiant').value,
        nom: document.getElementById('addNom').value,
        prenom: document.getElementById('addPrenom').value,
        campus: document.getElementById('addCampus').value,
        telephone7: document.getElementById('addTelephone7').value,
        telephoneResistant: document.getElementById('addTelephoneResistant').value,
        identiteFaux: document.getElementById('addIdentiteFaux').value,
        typeDocument: document.getElementById('addTypeDocument').value,
        chargeEnquete: document.getElementById('addChargeEnquete').value,
        agentAction: document.getElementById('addAgentAction').value,
        observations: document.getElementById('addObservations').value,
        statut: document.getElementById('addStatut').value,
        date: document.getElementById('addDate').value
    };

    pvData.push(pv);
    // Pas de localStorage - travail en mémoire uniquement
    
    // Fermer le modal et réinitialiser le formulaire
    bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
    document.getElementById('addForm').reset();
    
    // Recharger les données
    filteredData = [...pvData];
    currentPage = 1;
    loadPVData();
    updateStatistics();
    
    // Message de succès
    showAlert('Procès-verbal ajouté avec succès!', 'success');
}

// Charger les données dans le tableau avec pagination
function loadPVData() {
    const tbody = document.getElementById('pvTableBody');
    tbody.innerHTML = '';
    
    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucun procès-verbal enregistré</p>
                </td>
            </tr>
        `;
        return;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedData = filteredData.slice(startIndex, endIndex);
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);

    paginatedData.forEach((pv, index) => {
        const globalIndex = startIndex + index + 1;
        
        const typeDocLabel = {
            'carte_etudiant': '<span class="badge bg-primary me-1">Carte Étudiant</span>',
            'cni': '<span class="badge bg-success me-1">CNI</span>',
            'passeport': '<span class="badge bg-warning me-1">Passeport</span>',
            'autre': '<span class="badge bg-secondary me-1">Autre</span>'
        }[pv.typeDocument] || '<span class="badge bg-secondary">N/A</span>';
        
        const statutLabel = {
            'en_cours': '<span class="badge bg-warning">En cours</span>',
            'traite': '<span class="badge bg-success">Traité</span>'
        }[pv.statut] || '<span class="badge bg-secondary">N/A</span>';

        const row = `
            <tr>
                <td>${globalIndex}</td>
                <td>${pv.carteEtudiant}</td>
                <td>${pv.nom} ${pv.prenoms}</td>
                <td>${pv.campus}</td>
                <td>${pv.telephone7}</td>
                <td>${pv.identiteFaux}</td>
                <td>${typeDocLabel}</td>
                <td>${statutLabel}</td>
                <td>${pv.date}</td>
                <td>
                    <button class="btn btn-sm btn-info btn-action me-1" onclick="viewDetails(${pv.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning btn-action me-1" onclick="editPV(${pv.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-action" onclick="deletePV(${pv.id})">
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

    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Précédent</a>`;
    pagination.appendChild(prevLi);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        pagination.appendChild(li);
    }

    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Suivant</a>`;
    pagination.appendChild(nextLi);
}

function changePage(page) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadPVData();
}

function changeItemsPerPage() {
    itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    currentPage = 1;
    loadPVData();
}

// Filtrer les données
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    
    filteredData = pvData.filter(pv => {
        const matchesSearch = 
            pv.carteEtudiant.toLowerCase().includes(searchTerm) ||
            pv.nom.toLowerCase().includes(searchTerm) ||
            pv.prenoms.toLowerCase().includes(searchTerm) ||
            pv.campus.toLowerCase().includes(searchTerm) ||
            pv.identiteFaux.toLowerCase().includes(searchTerm);
        
        const matchesStatus = !statusFilter || pv.statut === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    loadPVData();
}

// Voir les détails
function viewDetails(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    document.getElementById('detailCarteEtudiant').textContent = pv.carteEtudiant;
    document.getElementById('detailNomPrenom').textContent = `${pv.nom} ${pv.prenoms}`;
    document.getElementById('detailCampus').textContent = pv.campus;
    document.getElementById('detailDate').textContent = pv.date;
    document.getElementById('detailTelephone7').textContent = pv.telephone7;
    document.getElementById('detailTelephoneResistant').textContent = pv.telephoneResistant;
    document.getElementById('detailIdentiteFaux').textContent = pv.identiteFaux;
    document.getElementById('detailTypeDocument').textContent = pv.typeDocument;
    document.getElementById('detailChargeEnquete').textContent = pv.chargeEnquete;
    document.getElementById('detailAgentAction').textContent = pv.agentAction;
    document.getElementById('detailObservations').textContent = pv.observations;
    
    const statutElement = document.getElementById('detailStatut');
    if (pv.statut === 'en_cours') {
        statutElement.innerHTML = '<span class="badge bg-warning">En cours</span>';
    } else {
        statutElement.innerHTML = '<span class="badge bg-success">Traité</span>';
    }

    currentDetailId = id;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Éditer un PV
function editPV(id) {
    const pv = pvData.find(p => p.id === id);
    if (!pv) return;

    document.getElementById('editCarteEtudiant').value = pv.carteEtudiant;
    document.getElementById('editNom').value = pv.nom;
    document.getElementById('editPrenom').value = pv.prenoms;
    document.getElementById('editCampus').value = pv.campus;
    document.getElementById('editTelephone7').value = pv.telephone7;
    document.getElementById('editTelephoneResistant').value = pv.telephoneResistant;
    document.getElementById('editIdentiteFaux').value = pv.identiteFaux;
    document.getElementById('editTypeDocument').value = pv.typeDocument;
    document.getElementById('editChargeEnquete').value = pv.chargeEnquete;
    document.getElementById('editAgentAction').value = pv.agentAction;
    document.getElementById('editObservations').value = pv.observations;
    document.getElementById('editStatut').value = pv.statut;
    document.getElementById('editDate').value = pv.date;

    currentEditId = id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Mettre à jour un PV
function updatePV() {
    if (!currentEditId) return;

    const index = pvData.findIndex(p => p.id === currentEditId);
    if (index === -1) return;

    pvData[index] = {
        id: currentEditId,
        carteEtudiant: document.getElementById('editCarteEtudiant').value,
        nom: document.getElementById('editNom').value,
        prenom: document.getElementById('editPrenom').value,
        campus: document.getElementById('editCampus').value,
        telephone7: document.getElementById('editTelephone7').value,
        telephoneResistant: document.getElementById('editTelephoneResistant').value,
        identiteFaux: document.getElementById('editIdentiteFaux').value,
        typeDocument: document.getElementById('editTypeDocument').value,
        chargeEnquete: document.getElementById('editChargeEnquete').value,
        agentAction: document.getElementById('editAgentAction').value,
        observations: document.getElementById('editObservations').value,
        statut: document.getElementById('editStatut').value,
        date: document.getElementById('editDate').value
    };

    // Pas de localStorage - travail en mémoire uniquement
    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    
    filteredData = [...pvData];
    loadPVData();
    updateStatistics();
    //showAlert('Procès-verbal mis à jour avec succès!', 'success');
    showAlert('Procès-verbal mis à jour avec succès!', 'success');
}

// Supprimer un PV
function deletePV(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce procès-verbal ?')) {
        pvData = pvData.filter(p => p.id !== id);
        
        // Pas de localStorage - travail en mémoire uniquement
        
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

// Imprimer un PV
function printPV() {
    const pv = pvData.find(p => p.id === currentDetailId);
    if (!pv) return;

    const printContent = `
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <h2>Procès-Verbal: Faux et Usage de Faux</h2>
            <p><strong>N° Carte Étudiant:</strong> ${pv.carteEtudiant}</p>
            <p><strong>Nom & Prénom:</strong> ${pv.nom} ${pv.prenoms}</p>
            <p><strong>Campus:</strong> ${pv.campus}</p>
            <p><strong>Téléphone:</strong> ${pv.telephone7}</p>
            <p><strong>Identité de Faux:</strong> ${pv.identiteFaux}</p>
            <p><strong>Type Document:</strong> ${pv.typeDocument}</p>
            <p><strong>Charge Enquête:</strong> ${pv.chargeEnquete}</p>
            <p><strong>Agent Action:</strong> ${pv.agentAction}</p>
            <p><strong>Observations:</strong> ${pv.observations}</p>
            <p><strong>Statut:</strong> ${pv.statut}</p>
            <p><strong>Date:</strong> ${pv.date}</p>
        </div>
    `;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser avec des données vides
    pvData = [];
    filteredData = [];
    
    // Charger les données depuis PHP si disponibles
    if (typeof phpData !== 'undefined' && phpData.length > 0) {
        pvData = phpData;
        filteredData = [...pvData];
        currentPage = phpPagination.currentPage || 1;
        itemsPerPage = phpPagination.itemsPerPage || 10;
    }
    
    // Charger et afficher les données
    loadPVData();
    updateStatistics();
    
    // Mettre la date du jour par défaut
    const addDateElement = document.getElementById('addDate');
    const editDateElement = document.getElementById('editDate');
    if (addDateElement) addDateElement.valueAsDate = new Date();
    if (editDateElement) editDateElement.valueAsDate = new Date();
    
    // Écouteurs d'événements
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    
    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterStatus) filterStatus.addEventListener('change', filterTable);
    if (itemsPerPageSelect) itemsPerPageSelect.addEventListener('change', changeItemsPerPage);
});
