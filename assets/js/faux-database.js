/**
 * Script pour la gestion des PV Faux avec base de données
 */

// Variables globales
let currentPage = 1;
let itemsPerPage = 10;
let searchQuery = '';
let statusFilter = '';
let lastPagination = { totalPages: 1, total: 0 };

// Utilitaire pour échapper le HTML (protection XSS)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadPVData();
    setupEventListeners();
    setupModalEventListeners();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchQuery = e.target.value;
            currentPage = 1;
            loadPVData();
        });
    }

    const filterStatus = document.getElementById('filterStatus');
    if (filterStatus) {
        filterStatus.addEventListener('change', function(e) {
            statusFilter = e.target.value;
            currentPage = 1;
            loadPVData();
        });
    }

    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            itemsPerPage = parseInt(e.target.value);
            currentPage = 1;
            loadPVData();
        });
    }
}

// Aperçu image empreinte lors de la sélection d'un fichier
function setupEmpreintePreview(inputId, previewDivId, previewImgId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', function() {
        var previewDiv = document.getElementById(previewDivId);
        var previewImg = document.getElementById(previewImgId);
        if (!previewDiv || !previewImg) return;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewDiv.style.display = '';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            previewDiv.style.display = 'none';
        }
    });
}

// Configuration des événements des modales
function setupModalEventListeners() {
    setupEmpreintePreview('addEmpreinte', 'addEmpreintePreview', 'addEmpreinteImg');
    setupEmpreintePreview('editEmpreinte', 'editEmpreintePreview', 'editEmpreinteImg');

    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (window.deletePvId) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', window.deletePvId);

                fetch('../../pages/faux/faux.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                        if (modal) modal.hide();
                        loadPVData();
                        showSuccess('PV supprimé avec succès');
                    } else {
                        showError(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(function() {
                    showError('Erreur lors de la suppression');
                });
            }
        });
    }

    const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
    if (confirmUpdateBtn) {
        confirmUpdateBtn.addEventListener('click', function() {
            if (window.updatePvId) {
                performUpdate();
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmUpdateModal'));
                if (modal) modal.hide();
            }
        });
    }
}

// Charger les données des PV depuis le serveur
function loadPVData() {
    const params = new URLSearchParams({
        ajax: '1',
        page: currentPage,
        itemsPerPage: itemsPerPage,
        search: searchQuery,
        status: statusFilter
    });

    showLoader();

    fetch(`../../pages/faux/faux.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur serveur');
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse invalide du serveur');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayPVData(data.pvData);
                updatePagination(data.pagination);
                updateStatistics(data.statistics);
            } else {
                showError('Erreur lors du chargement des données');
            }
        })
        .catch(function() {
            showError('Erreur de connexion au serveur');
        })
        .finally(function() {
            hideLoader();
        });
}

// Afficher les données dans le tableau
function displayPVData(pvData) {
    const tbody = document.getElementById('pvTableBody');
    tbody.innerHTML = '';

    if (!pvData || pvData.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.setAttribute('colspan', '8');
        td.className = 'text-center py-4';
        td.innerHTML = '<i class="fas fa-inbox fa-2x text-muted mb-2"></i><div class="text-muted">Aucun procès-verbal trouvé</div>';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    pvData.forEach(function(pv, index) {
        const row = createPVRow(pv, index);
        tbody.appendChild(row);
    });
}

// Créer une ligne de tableau pour un PV (protégé contre XSS)
function createPVRow(pv, index) {
    const tr = document.createElement('tr');

    // Numéro
    const tdNum = document.createElement('td');
    tdNum.textContent = ((currentPage - 1) * itemsPerPage) + index + 1;
    tr.appendChild(tdNum);

    // N° de pièce (tous types)
    const tdCarte = document.createElement('td');
    tdCarte.textContent = pv.carte_etudiant || '—';
    tr.appendChild(tdCarte);

    // Nom & Prénom
    const tdNom = document.createElement('td');
    const divNom = document.createElement('div');
    divNom.className = 'fw-bold';
    divNom.textContent = pv.nom || '';
    const smallPrenom = document.createElement('small');
    smallPrenom.className = 'text-muted';
    smallPrenom.textContent = pv.prenoms || '';
    tdNom.appendChild(divNom);
    tdNom.appendChild(smallPrenom);
    tr.appendChild(tdNom);

    // Campus
    const tdCampus = document.createElement('td');
    tdCampus.textContent = pv.campus || '';
    tr.appendChild(tdCampus);

    // Téléphone
    const tdTel = document.createElement('td');
    tdTel.textContent = pv.telephone_principal || '-';
    tr.appendChild(tdTel);

    // Type document
    const tdType = document.createElement('td');
    tdType.textContent = getTypeDocumentLabel(pv.type_document);
    tr.appendChild(tdType);

    // Statut
    const tdStatut = document.createElement('td');
    tdStatut.innerHTML = getStatutBadge(pv.statut);
    tr.appendChild(tdStatut);

    // Actions
    const tdActions = document.createElement('td');
    const btnGroup = document.createElement('div');
    btnGroup.className = 'btn-group';
    btnGroup.setAttribute('role', 'group');

    const btnView = document.createElement('button');
    btnView.className = 'btn btn-sm btn-outline-primary';
    btnView.title = 'Voir détails';
    btnView.innerHTML = '<i class="fas fa-eye"></i>';
    btnView.addEventListener('click', function() { viewDetails(pv.id); });

    const btnEdit = document.createElement('button');
    btnEdit.className = 'btn btn-sm btn-outline-warning';
    btnEdit.title = 'Modifier';
    btnEdit.innerHTML = '<i class="fas fa-edit"></i>';
    btnEdit.addEventListener('click', function() { editPV(pv.id); });

    const btnDelete = document.createElement('button');
    btnDelete.className = 'btn btn-sm btn-outline-danger';
    btnDelete.title = 'Supprimer';
    btnDelete.innerHTML = '<i class="fas fa-trash"></i>';
    btnDelete.addEventListener('click', function() { deletePV(pv.id); });

    btnGroup.appendChild(btnView);

    // Vérification des permissions selon le rôle
    var canEdit = false;
    var canDelete = false;
    if (typeof USER_ROLE !== 'undefined') {
        if (USER_ROLE === 'admin') { canEdit = true; canDelete = true; }
        else if (USER_ROLE === 'superviseur') { canEdit = true; }
        else if (USER_ROLE === 'agent') {
            if (pv.id_agent == USER_ID) { canEdit = true; canDelete = true; }
        }
    }

    if (canEdit) btnGroup.appendChild(btnEdit);
    if (canDelete) btnGroup.appendChild(btnDelete);

    tdActions.appendChild(btnGroup);
    tr.appendChild(tdActions);

    return tr;
}

// Obtenir le badge de statut
function getStatutBadge(statut) {
    const badges = {
        'en_cours': '<span class="badge bg-warning">En cours</span>',
        'traite': '<span class="badge bg-success">Traité</span>',
        'archive': '<span class="badge bg-secondary">Archivé</span>'
    };
    return badges[statut] || '<span class="badge bg-secondary">Inconnu</span>';
}

// Obtenir le label du type de document
function getTypeDocumentLabel(type) {
    const labels = {
        'carte_etudiant': 'Carte étudiant',
        'cni': 'CNI',
        'passport': 'Passeport',
        'carte_personnel': 'Carte personnelle',
        // rétrocompat anciens enregistrements
        'passeport': 'Passeport',
        'permis': 'Permis',
        'autre': 'Autre'
    };
    return labels[type] || type || '';
}

// Mettre à jour la pagination
function updatePagination(pagination) {
    lastPagination = pagination;

    const paginationContainer = document.getElementById('paginationContainer');
    const paginationUl = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    const totalCount = document.getElementById('totalCount');

    totalCount.textContent = pagination.total;

    if (pagination.totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    paginationUl.innerHTML = '';

    // Bouton précédent
    const prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
    const prevLink = document.createElement('a');
    prevLink.className = 'page-link';
    prevLink.href = '#';
    prevLink.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevLink.addEventListener('click', function(e) { e.preventDefault(); changePage(currentPage - 1); });
    prevLi.appendChild(prevLink);
    paginationUl.appendChild(prevLi);

    // Pages
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(pagination.totalPages, currentPage + 2);

    if (startPage > 1) {
        addPageLink(1);
        if (startPage > 2) addEllipsis();
    }

    for (let i = startPage; i <= endPage; i++) {
        addPageLink(i);
    }

    if (endPage < pagination.totalPages) {
        if (endPage < pagination.totalPages - 1) addEllipsis();
        addPageLink(pagination.totalPages);
    }

    // Bouton suivant
    const nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (currentPage === pagination.totalPages ? ' disabled' : '');
    const nextLink = document.createElement('a');
    nextLink.className = 'page-link';
    nextLink.href = '#';
    nextLink.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextLink.addEventListener('click', function(e) { e.preventDefault(); changePage(currentPage + 1); });
    nextLi.appendChild(nextLink);
    paginationUl.appendChild(nextLi);

    // Informations de pagination
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, pagination.total);
    paginationInfo.textContent = 'Affichage de ' + start + ' à ' + end + ' sur ' + pagination.total + ' résultats';

    function addPageLink(pageNum) {
        const li = document.createElement('li');
        li.className = 'page-item' + (pageNum === currentPage ? ' active' : '');
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = pageNum;
        link.addEventListener('click', function(e) { e.preventDefault(); changePage(pageNum); });
        li.appendChild(link);
        paginationUl.appendChild(li);
    }

    function addEllipsis() {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = '...';
        li.appendChild(link);
        paginationUl.appendChild(li);
    }
}

// Mettre à jour les statistiques
function updateStatistics(statistics) {
    if (!statistics) return;

    const statsContainer = document.getElementById('statsContainer');
    if (!statsContainer) return;

    const total = document.getElementById('statTotal');
    const enCours = document.getElementById('statEnCours');
    const traites = document.getElementById('statTraites');

    if (total) total.textContent = statistics.total || 0;
    if (enCours) enCours.textContent = statistics.enCours || 0;
    if (traites) traites.textContent = statistics.traites || 0;
}

// Changer de page
function changePage(page) {
    if (page < 1 || page > lastPagination.totalPages) return;
    currentPage = page;
    loadPVData();
}

// Changer le nombre d'éléments par page
function changeItemsPerPage() {
    loadPVData();
}

// Voir les détails d'un PV
function viewDetails(id) {
    fetch('../../pages/faux/faux.php?action=detail&id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(pv) {
            if (pv) {
                fillDetailModal(pv);
                var modal = new bootstrap.Modal(document.getElementById('detailModal'));
                modal.show();
            }
        })
        .catch(function() {
            showError('Erreur lors du chargement des détails');
        });
}

// Remplir le modal de détails (protégé XSS via textContent)
function fillDetailModal(pv) {
    document.getElementById('detailNomPrenom').textContent = (pv.nom || '') + ' ' + (pv.prenoms || '');
    document.getElementById('detailCampus').textContent = pv.campus || '-';
    document.getElementById('detailDate').textContent = pv.date_pv ? formatDate(pv.date_pv) : '-';
    document.getElementById('detailTelephone7').textContent = pv.telephone_principal || '-';
    document.getElementById('detailTelephoneResistant').textContent = pv.telephone_resistant || '-';
    document.getElementById('detailIdentiteFaux').textContent = pv.identite_faux || '-';
    document.getElementById('detailTypeDocument').textContent = getTypeDocumentLabel(pv.type_document);
    document.getElementById('detailChargeEnquete').textContent = pv.charge_enquete || '-';
    document.getElementById('detailAgentAction').textContent = pv.agent_action || '-';
    document.getElementById('detailObservations').textContent = pv.observations || '-';
    document.getElementById('detailStatut').innerHTML = getStatutBadge(pv.statut);

    // Empreinte dans le détail
    var empreinteRow = document.getElementById('detailEmpreinteRow');
    var empreinteImg = document.getElementById('detailEmpreinteImg');
    if (pv.empreinte && empreinteRow && empreinteImg) {
        empreinteImg.src = '../../' + pv.empreinte;
        empreinteRow.style.display = '';
    } else if (empreinteRow) {
        empreinteRow.style.display = 'none';
    }

    // Afficher le N° de pièce avec le bon label pour chaque type
    const pieceLabels = {
        'carte_etudiant': "N° Carte d'Étudiant :",
        'cni':            'N° CNI :',
        'passport':       'N° Passeport :',
        'carte_personnel':'N° Carte personnelle :',
        // rétrocompat anciens enregistrements
        'passeport':      'N° Passeport :',
        'permis':         'N° Permis :',
        'autre':          'N° Pièce :'
    };
    const carteRow   = document.getElementById('detailCarteEtudiantRow');
    const carteLabel = document.getElementById('detailPieceLabelText');
    const carteVal   = document.getElementById('detailCarteEtudiant');
    if (carteRow) carteRow.style.display = '';
    if (carteLabel) carteLabel.textContent = pieceLabels[pv.type_document] || 'N° de pièce :';
    if (carteVal)   carteVal.textContent   = pv.carte_etudiant || '-';
}

// Modifier un PV
function editPV(id) {
    fetch('../../pages/faux/faux.php?action=detail&id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(pv) {
            if (pv) {
                fillEditModal(pv);
                var modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        })
        .catch(function() {
            showError('Erreur lors du chargement des détails');
        });
}

// Remplir le modal d'édition
function fillEditModal(pv) {
    document.getElementById('editId').value = pv.id;
    document.getElementById('editCarteEtudiant').value = pv.carte_etudiant || '';
    document.getElementById('editNom').value = pv.nom || '';
    document.getElementById('editPrenom').value = pv.prenoms || '';
    document.getElementById('editCampus').value = pv.campus || '';
    document.getElementById('editTelephone7').value = pv.telephone_principal || '';
    document.getElementById('editTelephoneResistant').value = pv.telephone_resistant || '';
    document.getElementById('editIdentiteFaux').value = pv.identite_faux || '';
    document.getElementById('editTypeDocument').value = pv.type_document || '';
    document.getElementById('editChargeEnquete').value = pv.charge_enquete || '';
    document.getElementById('editAgentAction').value = pv.agent_action || '';
    document.getElementById('editObservations').value = pv.observations || '';
    document.getElementById('editStatut').value = pv.statut || 'en_cours';
    document.getElementById('editDate').value = pv.date_pv || '';

    // Empreinte existante
    var empreinteExistanteInput = document.getElementById('editEmpreinteExistante');
    if (empreinteExistanteInput) empreinteExistanteInput.value = pv.empreinte || '';

    // Aperçu de l'empreinte existante
    var editPreviewDiv = document.getElementById('editEmpreintePreview');
    var editPreviewImg = document.getElementById('editEmpreinteImg');
    if (pv.empreinte && editPreviewDiv && editPreviewImg) {
        editPreviewImg.src = '../../' + pv.empreinte;
        editPreviewDiv.style.display = '';
    } else if (editPreviewDiv) {
        editPreviewDiv.style.display = 'none';
    }

    toggleCarteEtudiantField('edit');
}

// Afficher/adapter le champ N° de pièce selon le type sélectionné
function toggleCarteEtudiantField(prefix) {
    const typeSelect = document.getElementById(prefix + 'TypeDocument');
    const carteGroup = document.getElementById(prefix + 'CarteEtudiantGroup');
    const labelSpan  = document.getElementById(prefix + 'PieceLabelText');
    const carteInput = document.getElementById(prefix + 'CarteEtudiant');
    if (!typeSelect || !carteGroup) return;

    const pieceInfo = {
        'carte_etudiant': { label: "N° Carte d'Étudiant", placeholder: 'Ex: 20240CNVU' },
        'cni':            { label: 'N° CNI',               placeholder: 'Ex: 1 2345 67 89 012 13' },
        'passport':       { label: 'N° Passeport',         placeholder: 'Ex: AA1234567' },
        'carte_personnel':{ label: 'N° Carte personnelle', placeholder: 'Ex: CP-123456' }
    };

    const selected = typeSelect.value;
    if (selected && pieceInfo[selected]) {
        carteGroup.style.display = '';
        if (labelSpan)  labelSpan.textContent  = pieceInfo[selected].label;
        if (carteInput) carteInput.placeholder = pieceInfo[selected].placeholder;
    } else {
        carteGroup.style.display = 'none';
        if (carteInput) carteInput.value = '';
    }
}

// Supprimer un PV (ouvrir modal de confirmation)
function deletePV(id) {
    fetch('../../pages/faux/faux.php?action=detail&id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(pv) {
            if (pv) {
                document.getElementById('deletePvNumber').textContent = pv.numero_pv || 'N/A';
                document.getElementById('deletePvName').textContent = (pv.nom || '') + ' ' + (pv.prenoms || '');
                document.getElementById('deletePvDate').textContent = pv.date_pv ? formatDate(pv.date_pv) : 'N/A';
                window.deletePvId = id;
                var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                modal.show();
            }
        })
        .catch(function() {
            showError('Erreur lors du chargement des détails du PV');
        });
}

// Sauvegarder un PV (création)
function savePV() {
    var form = document.getElementById('addForm');
    if (!form) {
        showError('Formulaire non trouvé');
        return;
    }

    var data = {
        carteEtudiant: document.getElementById('addCarteEtudiant').value.trim(),
        nom: document.getElementById('addNom').value.trim(),
        prenoms: document.getElementById('addPrenom').value.trim(),
        campus: document.getElementById('addCampus').value.trim(),
        telephone7: document.getElementById('addTelephone7').value.trim(),
        telephoneResistant: document.getElementById('addTelephoneResistant').value.trim(),
        identiteFaux: document.getElementById('addIdentiteFaux').value.trim(),
        typeDocument: document.getElementById('addTypeDocument').value,
        chargeEnquete: document.getElementById('addChargeEnquete').value.trim(),
        agentAction: document.getElementById('addAgentAction').value.trim(),
        observations: document.getElementById('addObservations').value.trim(),
        statut: document.getElementById('addStatut').value,
        date: document.getElementById('addDate').value
    };

    // Validation
    if (!data.nom || !data.prenoms || !data.campus || !data.telephone7 || !data.typeDocument || !data.date) {
        showError('Veuillez remplir tous les champs obligatoires');
        return;
    }
    if (!data.carteEtudiant) {
        showError('Veuillez saisir le numéro de la pièce sélectionnée');
        return;
    }

    var formData = new FormData();
    formData.append('action', 'create');
    Object.keys(data).forEach(function(key) {
        formData.append(key, data[key]);
    });
    // Joindre le fichier empreinte s'il est sélectionné
    var empreinteFileAdd = document.getElementById('addEmpreinte');
    if (empreinteFileAdd && empreinteFileAdd.files[0]) {
        formData.append('empreinte', empreinteFileAdd.files[0]);
    }

    fetch('../../pages/faux/faux.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) throw new Error('Erreur serveur');
        return response.json();
    })
    .then(function(result) {
        if (result.success) {
            document.getElementById('addPvNumber').textContent = result.id || 'N/A';
            document.getElementById('addPvName').textContent = data.nom + ' ' + data.prenoms;

            var addModal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
            if (addModal) addModal.hide();

            // Reset du formulaire
            form.reset();
            // Remettre la date du jour
            document.getElementById('addDate').value = new Date().toISOString().split('T')[0];
            // Masquer le champ carte étudiant
            const addCarteGroup = document.getElementById('addCarteEtudiantGroup');
            if (addCarteGroup) addCarteGroup.style.display = 'none';
            // Masquer l'aperçu de l'empreinte
            var addEmpreintePreview = document.getElementById('addEmpreintePreview');
            if (addEmpreintePreview) addEmpreintePreview.style.display = 'none';

            var successModal = new bootstrap.Modal(document.getElementById('successAddModal'));
            successModal.show();

            loadPVData();
        } else {
            showError(result.message || 'Erreur lors de la création du PV');
        }
    })
    .catch(function() {
        showError('Erreur lors de la sauvegarde');
    });
}

// Mettre à jour un PV (avec confirmation)
function updatePV() {
    var data = {
        id: document.getElementById('editId').value,
        carteEtudiant: document.getElementById('editCarteEtudiant').value.trim(),
        nom: document.getElementById('editNom').value.trim(),
        prenoms: document.getElementById('editPrenom').value.trim(),
        campus: document.getElementById('editCampus').value.trim(),
        telephone7: document.getElementById('editTelephone7').value.trim(),
        telephoneResistant: document.getElementById('editTelephoneResistant').value.trim(),
        identiteFaux: document.getElementById('editIdentiteFaux').value.trim(),
        typeDocument: document.getElementById('editTypeDocument').value,
        chargeEnquete: document.getElementById('editChargeEnquete').value.trim(),
        agentAction: document.getElementById('editAgentAction').value.trim(),
        observations: document.getElementById('editObservations').value.trim(),
        statut: document.getElementById('editStatut').value,
        date: document.getElementById('editDate').value
    };

    if (!data.id || !data.nom || !data.prenoms || !data.campus || !data.telephone7 || !data.typeDocument || !data.date) {
        showError('Veuillez remplir tous les champs obligatoires');
        return;
    }
    if (!data.carteEtudiant) {
        showError('Veuillez saisir le numéro de la pièce sélectionnée');
        return;
    }

    window.updatePvData = data;
    document.getElementById('updatePvNumber').textContent = data.id;
    document.getElementById('updatePvName').textContent = data.nom + ' ' + data.prenoms;
    window.updatePvId = data.id;

    var editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
    if (editModal) editModal.hide();

    var confirmModal = new bootstrap.Modal(document.getElementById('confirmUpdateModal'));
    confirmModal.show();
}

// Effectuer la mise à jour réelle
function performUpdate() {
    var data = window.updatePvData;

    var formData = new FormData();
    formData.append('action', 'update');
    Object.keys(data).forEach(function(key) {
        formData.append(key, data[key]);
    });
    // Joindre le fichier empreinte si un nouveau fichier a été sélectionné
    var empreinteFileEdit = document.getElementById('editEmpreinte');
    if (empreinteFileEdit && empreinteFileEdit.files[0]) {
        formData.append('empreinte', empreinteFileEdit.files[0]);
    }
    // Envoyer l'ancienne empreinte pour la conserver si aucun nouveau fichier
    var empreinteExistanteVal = document.getElementById('editEmpreinteExistante');
    if (empreinteExistanteVal) {
        formData.append('empreinte_existante', empreinteExistanteVal.value);
    }

    fetch('../../pages/faux/faux.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) throw new Error('Erreur serveur');
        return response.json();
    })
    .then(function(result) {
        if (result.success) {
            loadPVData();
            showSuccess('PV modifié avec succès');
        } else {
            showError(result.message || 'Erreur lors de la mise à jour');
        }
    })
    .catch(function() {
        showError('Erreur lors de la mise à jour');
    });
}

// Imprimer un PV
function printPV() {
    var detailContent = document.querySelector('#detailModal .modal-body');
    if (!detailContent) return;

    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Impression PV - USCOUD</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body { padding: 20px; font-family: Arial, sans-serif; } .card { margin-bottom: 15px; } @media print { .no-print { display: none; } }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2 class="text-center mb-4">Procès-Verbal - Faux et Usage de Faux</h2>');
    printWindow.document.write('<p class="text-center text-muted mb-4">USCOUD - Système de Gestion des Procès-Verbaux</p>');
    printWindow.document.write('<hr>');
    printWindow.document.write(detailContent.innerHTML);
    printWindow.document.write('<hr><p class="text-center text-muted mt-4">Imprimé le ' + new Date().toLocaleDateString('fr-FR') + '</p>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    setTimeout(function() {
        printWindow.print();
    }, 500);
}

// Fonctions utilitaires
function formatDate(dateString) {
    if (!dateString) return '-';
    var date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('fr-FR');
}

function showLoader() {
    var tbody = document.getElementById('pvTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><div class="mt-2">Chargement des données...</div></td></tr>';
}

function hideLoader() {
    // Le loader sera remplacé par les données
}

function showError(message) {
    // Supprimer les anciennes alertes
    document.querySelectorAll('.alert-floating-error').forEach(function(el) { el.remove(); });

    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed alert-floating-error';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';

    var icon = document.createElement('i');
    icon.className = 'fas fa-exclamation-triangle me-2';
    alertDiv.appendChild(icon);

    var strong = document.createElement('strong');
    strong.textContent = 'Erreur ! ';
    alertDiv.appendChild(strong);

    alertDiv.appendChild(document.createTextNode(message));

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    alertDiv.appendChild(closeBtn);

    document.body.insertBefore(alertDiv, document.body.firstChild);

    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(function() { alertDiv.remove(); }, 150);
        }
    }, 6000);
}

function showSuccess(message) {
    document.querySelectorAll('.alert-floating-success').forEach(function(el) { el.remove(); });

    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed alert-floating-success';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';

    var icon = document.createElement('i');
    icon.className = 'fas fa-check-circle me-2';
    alertDiv.appendChild(icon);

    var strong = document.createElement('strong');
    strong.textContent = 'Succès ! ';
    alertDiv.appendChild(strong);

    alertDiv.appendChild(document.createTextNode(message));

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    alertDiv.appendChild(closeBtn);

    document.body.insertBefore(alertDiv, document.body.firstChild);

    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(function() { alertDiv.remove(); }, 150);
        }
    }, 4000);
}
