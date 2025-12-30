// Base de données locale (localStorage)
let pvData = [];
let currentEditId = null;
let currentDetailId = null;
let currentPage = 1;
let itemsPerPage = 10;
let filteredData = [];
let searchTimeout = null;

// Sauvegarder un nouveau PV
function savePV() {
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('carteEtudiant', document.getElementById('addCarteEtudiant').value);
    formData.append('nom', document.getElementById('addNom').value);
    formData.append('prenoms', document.getElementById('addPrenom').value);
    formData.append('campus', document.getElementById('addCampus').value);
    formData.append('telephone7', document.getElementById('addTelephone7').value);
    formData.append('telephoneResistant', document.getElementById('addTelephoneResistant').value);
    formData.append('identiteFaux', document.getElementById('addIdentiteFaux').value);
    formData.append('typeDocument', document.getElementById('addTypeDocument').value);
    formData.append('chargeEnquete', document.getElementById('addChargeEnquete').value);
    formData.append('agentAction', document.getElementById('addAgentAction').value);
    formData.append('observations', document.getElementById('addObservations').value);
    formData.append('statut', document.getElementById('addStatut').value);
    formData.append('date', document.getElementById('addDate').value);

    fetch('faux.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal et réinitialiser le formulaire
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            document.getElementById('addForm').reset();

            // Recharger les données depuis le serveur
            refreshData();

            // Message de succès
            showAlert('Procès-verbal ajouté avec succès!', 'success');
        } else {
            // Afficher les erreurs
            showErrors(data.errors);
        }
    })
    .catch(error => {
        // console.error('Erreur:', error);
        showAlert('Erreur lors de l\'ajout du PV', 'danger');
    });
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
                <td>${pv.chargeEnquete}</td>
                <td>${pv.agentAction}</td>
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

// Filtrer les données avec debounce pour la recherche en temps réel
function filterTable() {
    // Annuler le timeout précédent s'il existe
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Définir un nouveau timeout pour retarder la recherche
    searchTimeout = setTimeout(() => {
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
    }, 300); // Délai de 300ms avant d'exécuter la recherche
}

// Voir les détails
function viewDetails(id) {
    const formData = new FormData();
    formData.append('action', 'detail');
    formData.append('id', id);

    fetch('faux.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const pv = data.data;

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
        } else {
            showAlert('Erreur lors du chargement des détails', 'danger');
        }
    })
    .catch(error => {
        // console.error('Erreur:', error);
        showAlert('Erreur lors du chargement des détails', 'danger');
    });
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

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', currentEditId);
    formData.append('carteEtudiant', document.getElementById('editCarteEtudiant').value);
    formData.append('nom', document.getElementById('editNom').value);
    formData.append('prenoms', document.getElementById('editPrenom').value);
    formData.append('campus', document.getElementById('editCampus').value);
    formData.append('telephone7', document.getElementById('editTelephone7').value);
    formData.append('telephoneResistant', document.getElementById('editTelephoneResistant').value);
    formData.append('identiteFaux', document.getElementById('editIdentiteFaux').value);
    formData.append('typeDocument', document.getElementById('editTypeDocument').value);
    formData.append('chargeEnquete', document.getElementById('editChargeEnquete').value);
    formData.append('agentAction', document.getElementById('editAgentAction').value);
    formData.append('observations', document.getElementById('editObservations').value);
    formData.append('statut', document.getElementById('editStatut').value);
    formData.append('date', document.getElementById('editDate').value);

    fetch('faux.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

            // Recharger les données depuis le serveur
            refreshData();

            // Message de succès
            showAlert('Procès-verbal mis à jour avec succès!', 'success');
        } else {
            // Afficher les erreurs
            showErrors(data.errors);
        }
    })
    .catch(error => {
        // console.error('Erreur:', error);
        showAlert('Erreur lors de la mise à jour du PV', 'danger');
    });
}

// Supprimer un PV
function deletePV(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce procès-verbal ?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('faux.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger les données depuis le serveur
                refreshData();
                showAlert('Procès-verbal supprimé avec succès!', 'success');
            } else {
                showAlert('Erreur lors de la suppression', 'danger');
            }
        })
        .catch(error => {
            // console.error('Erreur:', error);
            showAlert('Erreur lors de la suppression', 'danger');
        });
    }
}

// Mettre à jour les statistiques
function updateStatistics() {
    const total = pvData.length;
    const enCours = pvData.filter(p => p.statut === 'en_cours').length;
    const traites = pvData.filter(p => p.statut === 'traite').length;

    // Mettre à jour seulement les éléments qui existent
    const totalPVElement = document.getElementById('totalPV');
    const enCoursElement = document.getElementById('enCours');
    const traitesElement = document.getElementById('traites');

    if (totalPVElement) totalPVElement.textContent = total;
    if (enCoursElement) enCoursElement.textContent = enCours;
    if (traitesElement) traitesElement.textContent = traites;
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

// Afficher les erreurs
function showErrors(errors) {
    // console.log('Erreurs reçues:', errors); // Pour debug

    if (!errors || (Array.isArray(errors) && errors.length === 0)) {
        // console.warn('showErrors appelé avec des erreurs vides');
        return; // Ne rien afficher si pas d'erreurs
    }

    if (typeof errors === 'string') {
        showAlert('Erreur: ' + errors, 'danger');
    } else if (Array.isArray(errors)) {
        if (errors.length === 0) return;
        let errorMessage = 'Erreurs de validation:\n';
        errors.forEach(error => {
            errorMessage += '• ' + error + '\n';
        });
        showAlert(errorMessage, 'danger');
    } else {
        let errorMessage = 'Erreurs de validation:\n';
        for (const field in errors) {
            errorMessage += '• ' + field + ': ' + errors[field] + '\n';
        }
        showAlert(errorMessage, 'danger');
    }
}

// Recharger les données depuis le serveur
function refreshData() {
    const formData = new FormData();
    formData.append('action', 'get_list');
    formData.append('page', '1');
    formData.append('search', '');
    formData.append('status', '');

    fetch('faux.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pvData = data.data;
            filteredData = [...pvData];
            currentPage = 1;
            loadPVData();
            updateStatistics();
        } else {
            showAlert('Erreur lors du chargement des données', 'danger');
        }
    })
    .catch(error => {
        // console.error('Erreur:', error);
        showAlert('Erreur lors du chargement des données', 'danger');
    });
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
    // Charger les données depuis le serveur
    refreshData();

    // Mettre la date du jour par défaut
    const addDateElement = document.getElementById('addDate');
    const editDateElement = document.getElementById('editDate');
    if (addDateElement) addDateElement.valueAsDate = new Date();
    if (editDateElement) editDateElement.valueAsDate = new Date();

    // Prévention des soumissions de formulaires multiples
    preventFormSubmissions();

    // Écouteurs d'événements pour recherche et filtres
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const itemsPerPageSelect = document.getElementById('itemsPerPage');

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterStatus) filterStatus.addEventListener('change', filterTable);
    if (itemsPerPageSelect) itemsPerPageSelect.addEventListener('change', changeItemsPerPage);
});

// Fonction pour prévenir les soumissions de formulaires multiples
function preventFormSubmissions() {
    const addForm = document.getElementById('addForm');
    const editForm = document.getElementById('editForm');

    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Empêcher la soumission par défaut
            // La soumission se fait uniquement via le bouton onclick
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Empêcher la soumission par défaut
            // La soumission se fait uniquement via le bouton onclick
        });
    }
}
