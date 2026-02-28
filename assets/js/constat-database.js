// Variables globales
let currentPage = 1;
let itemsPerPage = 10;
let searchQuery = '';
let statusFilter = '';
let lastPagination = null;
let _pendingStepData = null; // Données pré-collectées avant la modal de confirmation

// Fonction utilitaire pour échapper le HTML (protection XSS)
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    var div = document.createElement('div');
    div.textContent = String(text);
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
    // Recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchQuery = e.target.value;
            currentPage = 1;
            loadPVData();
        });
    }

    // Filtre de statut
    const filterStatus = document.getElementById('filterStatus');
    if (filterStatus) {
        filterStatus.addEventListener('change', function(e) {
            statusFilter = e.target.value;
            currentPage = 1;
            loadPVData();
        });
    }

    // Changement du nombre d'éléments par page
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            itemsPerPage = parseInt(e.target.value);
            currentPage = 1;
            loadPVData();
        });
    }
}

// Configuration des événements des modales
function setupModalEventListeners() {
    // Nettoyer les containers dynamiques à chaque ouverture du modal d'ajout
    const addModal = document.getElementById('addModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function() {
            ['blessesContainer', 'dommagesContainer', 'assaillantsContainer',
             'auditionsContainer', 'temoignagesContainer'].forEach(function(id) {
                var c = document.getElementById(id);
                if (c) c.innerHTML = '';
            });
            if (typeof resetStepper === 'function') resetStepper();
        });
    }

    // Bouton de confirmation d'ajout
    const confirmSaveBtn = document.getElementById('confirmSaveBtn');
    if (confirmSaveBtn) {
        confirmSaveBtn.addEventListener('click', function() {
            savePV();
        });
    }

    // Nettoyage des backdrops résiduels quand successSaveModal se ferme
    const successSaveModal = document.getElementById('successSaveModal');
    if (successSaveModal) {
        successSaveModal.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
    
    // Bouton de confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (window.deletePvId) {
                // Désactiver le bouton pendant la suppression
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Suppression...';
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', window.deletePvId);
                
                fetch('../../pages/constat/constat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fermer la modal de confirmation
                        const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                        if (confirmModal) {
                            confirmModal.hide();
                        }
                        
                        // Afficher la modale de succès
                        const successModal = new bootstrap.Modal(document.getElementById('successDeleteModal'));
                        successModal.show();
                        
                        // Recharger les données
                        loadPVData();
                    } else {
                        showError(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    showError('Erreur lors de la suppression');
                })
                .finally(() => {
                    // Réactiver le bouton
                    this.disabled = false;
                    this.innerHTML = 'Supprimer';
                });
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

    const url = `../../pages/constat/constat.php?${params}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error(`Réponse non-JSON reçue: ${text.substring(0, 200)}...`);
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayPVData(data.pvData);
                displayPagination(data.pagination);
                displayStatistics(data.statistics);
            } else {
                showError('Erreur lors du chargement des données');
            }
        })
        .catch(error => {
            showError('Erreur lors du chargement des données');
        })
        .finally(() => {
            hideLoader();
        });
}

// Afficher les données dans le tableau
function displayPVData(pvData) {
    const tbody = document.getElementById('pvTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!pvData || pvData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucun procès-verbal trouvé</p>
                </td>
            </tr>
        `;
        return;
    }

    pvData.forEach((pv, index) => {
        var row = document.createElement('tr');

        var tdNum = document.createElement('td');
        tdNum.textContent = ((currentPage - 1) * itemsPerPage) + index + 1;
        row.appendChild(tdNum);

        var tdCarte = document.createElement('td');
        tdCarte.textContent = pv.carte_etudiant || '-';
        row.appendChild(tdCarte);

        var tdNom = document.createElement('td');
        tdNom.textContent = (pv.nom || '') + ' ' + (pv.prenoms || '');
        row.appendChild(tdNom);

        var tdDate = document.createElement('td');
        tdDate.textContent = formatDate(pv.date_incident) + ' ' + (pv.heure_incident || '');
        row.appendChild(tdDate);

        var tdLieu = document.createElement('td');
        tdLieu.textContent = pv.lieu_incident || '-';
        row.appendChild(tdLieu);

        var tdType = document.createElement('td');
        tdType.textContent = getTypeIncidentLabel(pv.type_incident);
        row.appendChild(tdType);

        var tdStatut = document.createElement('td');
        var badge = document.createElement('span');
        badge.className = pv.statut === 'en_cours' ? 'badge bg-warning' : (pv.statut === 'traite' ? 'badge bg-success' : 'badge bg-secondary');
        badge.textContent = pv.statut === 'en_cours' ? 'En cours' : (pv.statut === 'traite' ? 'Traité' : 'Archivé');
        tdStatut.appendChild(badge);
        row.appendChild(tdStatut);

        var tdActions = document.createElement('td');
        var btnGroup = document.createElement('div');
        btnGroup.className = 'btn-group btn-group-sm';

        var pvId = parseInt(pv.id);

        var btnView = document.createElement('button');
        btnView.className = 'btn btn-outline-primary btn-sm';
        btnView.title = 'Voir';
        btnView.innerHTML = '<i class="fas fa-eye"></i>';
        btnView.addEventListener('click', (function(id) { return function() { viewPV(id); }; })(pvId));
        btnGroup.appendChild(btnView);

        var btnEdit = document.createElement('button');
        btnEdit.className = 'btn btn-outline-warning btn-sm';
        btnEdit.title = 'Modifier';
        btnEdit.innerHTML = '<i class="fas fa-edit"></i>';
        btnEdit.addEventListener('click', (function(id) { return function() { editPV(id); }; })(pvId));
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

        var btnDelete = document.createElement('button');
        btnDelete.className = 'btn btn-outline-danger btn-sm';
        btnDelete.title = 'Supprimer';
        btnDelete.innerHTML = '<i class="fas fa-trash"></i>';
        btnDelete.addEventListener('click', (function(id) { return function() { deletePV(id); }; })(pvId));
        if (canDelete) btnGroup.appendChild(btnDelete);

        tdActions.appendChild(btnGroup);
        row.appendChild(tdActions);

        tbody.appendChild(row);
    });
}

// Afficher la pagination
function displayPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const paginationElement = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    
    if (!container || !paginationElement) return;

    if (pagination.totalPages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    
    // Mettre à jour les informations
    if (paginationInfo) {
        const start = ((pagination.currentPage - 1) * pagination.itemsPerPage) + 1;
        const end = Math.min(pagination.currentPage * pagination.itemsPerPage, pagination.total);
        paginationInfo.textContent = `Affichage de ${start} à ${end} sur ${pagination.total} PV`;
    }

    // Générer la pagination
    let paginationHTML = '';

    // Bouton précédent
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${pagination.currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.currentPage - 1})">&laquo;</a>`;
    paginationHTML += prevLi.outerHTML;

    // Pages
    for (let i = 1; i <= pagination.totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === pagination.currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        paginationHTML += li.outerHTML;
    }

    // Bouton suivant
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${pagination.currentPage === pagination.totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.currentPage + 1})">&raquo;</a>`;
    paginationHTML += nextLi.outerHTML;

    paginationElement.innerHTML = paginationHTML;
}

// Afficher les statistiques
function displayStatistics(statistics) {
    var totalCount = document.getElementById('totalCount');
    if (totalCount) {
        totalCount.textContent = statistics.total || 0;
    }
    var statTotal = document.getElementById('statTotal');
    if (statTotal) {
        statTotal.textContent = statistics.total || 0;
    }
    var statEnCours = document.getElementById('statEnCours');
    if (statEnCours) {
        statEnCours.textContent = statistics.enCours || 0;
    }
    var statTraites = document.getElementById('statTraites');
    if (statTraites) {
        statTraites.textContent = statistics.traites || 0;
    }
}

// Changer de page
function changePage(page) {
    currentPage = page;
    loadPVData();
}

// Changer le nombre d'éléments par page
function changeItemsPerPage() {
    loadPVData();
}

// Voir les détails d'un PV
function viewPV(id) {
    fetch(`../../pages/constat/constat.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv) {
                console.log('[DEBUG viewPV] temoignages reçus:', JSON.stringify(pv.temoignages));
                fillDetailModal(pv);
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('[DEBUG viewPV] erreur:', error);
            showError('Erreur lors du chargement des détails');
        });
}

// Remplir la modal de détails avec affichage en steps structurés
function fillDetailModal(pv) {
    // Étape 1: Informations générales
    fillGeneralInfo(pv);
    
    // Étape 2: Blessés physiques
    fillBlesses(pv.blesses || []);
    
    // Étape 3: Dommages matériels
    fillDommages(pv.dommages || []);
    
    // Étape 4: Assaillants cités
    fillAssaillants(pv.assaillants || []);
    
    // Étape 5: Auditions
    fillAuditions(pv.auditions || []);
    
    // Étape 6: Témoignages
    fillTemoignages(pv.temoignages || []);
    
    // Étape 7: Suites et observations
    fillSuitesEtObservations(pv);
    
    // Statut
    fillStatut(pv.statut);
}

// Étape 1: Informations générales
function fillGeneralInfo(pv) {
    const elements = {
        detailCarteEtudiant: document.getElementById('detailCarteEtudiant'),
        detailNomPrenom: document.getElementById('detailNomPrenom'),
        detailCampus: document.getElementById('detailCampus'),
        detailTelephone: document.getElementById('detailTelephone'),
        detailTypeIncident: document.getElementById('detailTypeIncident'),
        detailDescriptionIncident: document.getElementById('detailDescriptionIncident'),
        detailLieuIncident: document.getElementById('detailLieuIncident'),
        detailDateIncident: document.getElementById('detailDateIncident'),
        detailHeureIncident: document.getElementById('detailHeureIncident'),
        detailDateHeure: document.getElementById('detailDateHeure')
    };
    
    if (elements.detailCarteEtudiant) elements.detailCarteEtudiant.textContent = pv.carte_etudiant || '-';
    if (elements.detailNomPrenom) elements.detailNomPrenom.textContent = `${pv.nom || ''} ${pv.prenoms || ''}`;
    if (elements.detailCampus) elements.detailCampus.textContent = pv.campus || '-';
    if (elements.detailTelephone) elements.detailTelephone.textContent = pv.telephone || '-';
    if (elements.detailTypeIncident) elements.detailTypeIncident.textContent = getTypeIncidentLabel(pv.type_incident);
    if (elements.detailDescriptionIncident) elements.detailDescriptionIncident.textContent = pv.description_incident || '-';
    if (elements.detailLieuIncident) elements.detailLieuIncident.textContent = pv.lieu_incident || '-';
    
    // Date et heure combinées pour l'onglet incident
    const dateStr = formatDate(pv.date_incident);
    const timeStr = pv.heure_incident || '-';
    const combinedDateTime = dateStr + (timeStr !== '-' ? ' à ' + timeStr : '');
    
    if (elements.detailDateIncident) elements.detailDateIncident.textContent = dateStr;
    if (elements.detailHeureIncident) elements.detailHeureIncident.textContent = timeStr;
    if (elements.detailDateHeure) elements.detailDateHeure.textContent = combinedDateTime;
}

// Étape 2: Blessés physiques
function fillBlesses(blesses) {
    const container = document.getElementById('detailBlesses');
    const countElement = document.getElementById('blessesTabCount');
    
    if (countElement) countElement.textContent = blesses.length;
    
    if (!container) return;
    
    if (!blesses || blesses.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun blessé enregistré</p>';
        return;
    }
    
    container.innerHTML = '';
    blesses.forEach(function(blesse, index) {
        var card = document.createElement('div');
        card.className = 'card mb-3 border-left-danger';
        var body = document.createElement('div');
        body.className = 'card-body';
        var title = document.createElement('h6');
        title.className = 'card-title text-danger';
        title.innerHTML = '<i class="fas fa-user-injured me-2"></i>';
        title.appendChild(document.createTextNode('Blessé ' + (index + 1)));
        body.appendChild(title);
        var row = document.createElement('div');
        row.className = 'row';
        var fields = [
            ['col-md-6 mb-2', 'Nom:', blesse.nom || 'Non spécifié'],
            ['col-md-6 mb-2', 'Prénoms:', blesse.prenoms || 'Non spécifié'],
            ['col-md-6 mb-2', 'Type de blessure:', getBlessureTypeLabel(blesse.type_blessure)],
            ['col-md-6 mb-2', 'Évacuation:', blesse.evacuation ? 'Oui' : 'Non']
        ];
        if (blesse.hopital) fields.push(['col-md-12 mb-2', 'Hôpital:', blesse.hopital]);
        if (blesse.description_blessure) fields.push(['col-md-12 mb-2', 'Description:', blesse.description_blessure]);
        fields.forEach(function(f) {
            var col = document.createElement('div');
            col.className = f[0];
            var strong = document.createElement('strong');
            strong.textContent = f[1] + ' ';
            col.appendChild(strong);
            col.appendChild(document.createTextNode(f[2]));
            row.appendChild(col);
        });
        body.appendChild(row);
        card.appendChild(body);
        container.appendChild(card);
    });
}

// Fonction pour obtenir le libellé du type de blessure
function getBlessureTypeLabel(type) {
    switch(type) {
        case 'leger': return 'Léger';
        case 'moyen': return 'Moyen';
        case 'grave': return 'Grave';
        default: return 'Non spécifié';
    }
}

// Étape 3: Dommages matériels
function fillDommages(dommages) {
    const container = document.getElementById('detailDommages');
    const countElement = document.getElementById('dommagesTabCount');
    
    if (countElement) countElement.textContent = dommages.length;
    
    if (!container) return;
    
    if (!dommages || dommages.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun dommage matériel enregistré</p>';
        return;
    }
    
    container.innerHTML = '';
    dommages.forEach(function(dommage, index) {
        var card = document.createElement('div');
        card.className = 'card mb-3 border-left-warning';
        var body = document.createElement('div');
        body.className = 'card-body';
        var title = document.createElement('h6');
        title.className = 'card-title text-warning';
        title.innerHTML = '<i class="fas fa-tools me-2"></i>';
        title.appendChild(document.createTextNode('Dommage ' + (index + 1)));
        body.appendChild(title);
        var row = document.createElement('div');
        row.className = 'row';
        var fields = [
            ['col-md-6 mb-2', 'Type:', dommage.type_domage || 'Non spécifié'],
            ['col-md-6 mb-2', 'Propriétaire:', dommage.proprietaire || 'Non spécifié']
        ];
        if (dommage.estimation_valeur) fields.push(['col-md-6 mb-2', 'Estimation:', dommage.estimation_valeur + ' FCFA']);
        if (dommage.description_domage) fields.push(['col-md-12 mb-2', 'Description:', dommage.description_domage]);
        fields.forEach(function(f) {
            var col = document.createElement('div');
            col.className = f[0];
            var strong = document.createElement('strong');
            strong.textContent = f[1] + ' ';
            col.appendChild(strong);
            col.appendChild(document.createTextNode(f[2]));
            row.appendChild(col);
        });
        body.appendChild(row);
        card.appendChild(body);
        container.appendChild(card);
    });
}

// Étape 4: Assaillants cités
function fillAssaillants(assaillants) {
    const container = document.getElementById('detailAssaillants');
    const countElement = document.getElementById('assaillantsTabCount');
    
    if (countElement) countElement.textContent = assaillants.length;
    
    if (!container) return;
    
    if (!assaillants || assaillants.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun assaillant cité</p>';
        return;
    }
    
    container.innerHTML = '';
    assaillants.forEach(function(assaillant, index) {
        var card = document.createElement('div');
        card.className = 'card mb-3 border-left-dark';
        var body = document.createElement('div');
        body.className = 'card-body';
        var title = document.createElement('h6');
        title.className = 'card-title text-dark';
        title.innerHTML = '<i class="fas fa-user-secret me-2"></i>';
        title.appendChild(document.createTextNode('Assaillant ' + (index + 1)));
        body.appendChild(title);
        var row = document.createElement('div');
        row.className = 'row';
        var fields = [
            ['col-md-6 mb-2', 'Nom:', assaillant.nom || 'Inconnu'],
            ['col-md-6 mb-2', 'Prénoms:', assaillant.prenoms || 'Inconnu'],
            ['col-md-6 mb-2', 'Statut:', getAssaillantStatusLabel(assaillant.statut)]
        ];
        if (assaillant.description_physique) fields.push(['col-md-12 mb-2', 'Description physique:', assaillant.description_physique]);
        if (assaillant.signes_distinctifs) fields.push(['col-md-12 mb-2', 'Signes distinctifs:', assaillant.signes_distinctifs]);
        fields.forEach(function(f) {
            var col = document.createElement('div');
            col.className = f[0];
            var strong = document.createElement('strong');
            strong.textContent = f[1] + ' ';
            col.appendChild(strong);
            col.appendChild(document.createTextNode(f[2]));
            row.appendChild(col);
        });
        body.appendChild(row);
        card.appendChild(body);
        container.appendChild(card);
    });
}

// Étape 5: Auditions
function fillAuditions(auditions) {
    const container = document.getElementById('detailAuditions');
    const countElement = document.getElementById('auditionsTabCount');
    
    if (countElement) countElement.textContent = auditions.length;
    
    if (!container) return;
    
    if (!auditions || auditions.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucune audition enregistrée</p>';
        return;
    }
    
    container.innerHTML = '';
    auditions.forEach(function(audition, index) {
        var card = document.createElement('div');
        card.className = 'card mb-3 border-left-info';
        var body = document.createElement('div');
        body.className = 'card-body';
        var title = document.createElement('h6');
        title.className = 'card-title text-info';
        title.innerHTML = '<i class="fas fa-microphone me-2"></i>';
        title.appendChild(document.createTextNode('Audition ' + (index + 1)));
        body.appendChild(title);
        var row = document.createElement('div');
        row.className = 'row';
        var fields = [
            ['col-md-6 mb-2', 'Témoin:', (audition.temoin_nom || '') + ' ' + (audition.temoin_prenoms || '')],
            ['col-md-6 mb-2', 'Statut:', getPersonneStatusLabel(audition.temoin_statut)]
        ];
        if (audition.temoin_telephone) fields.push(['col-md-6 mb-2', 'Téléphone:', audition.temoin_telephone]);
        if (audition.date_audition) fields.push(['col-md-6 mb-2', 'Date audition:', formatDate(audition.date_audition)]);
        if (audition.declaration) fields.push(['col-md-12 mb-2', 'Déclaration:', audition.declaration]);
        fields.forEach(function(f) {
            var col = document.createElement('div');
            col.className = f[0];
            var strong = document.createElement('strong');
            strong.textContent = f[1] + ' ';
            col.appendChild(strong);
            col.appendChild(document.createTextNode(f[2]));
            row.appendChild(col);
        });
        body.appendChild(row);
        card.appendChild(body);
        container.appendChild(card);
    });
}

// Étape 6: Témoignages
function fillTemoignages(temoignages) {
    const container = document.getElementById('detailTemoignages');
    const countElement = document.getElementById('temoignagesTabCount');
    
    if (countElement) countElement.textContent = temoignages.length;
    
    if (!container) return;
    
    if (!temoignages || temoignages.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun témoignage enregistré</p>';
        return;
    }
    
    container.innerHTML = '';
    temoignages.forEach(function(temoignage, index) {
        var card = document.createElement('div');
        card.className = 'card mb-3 border-left-primary';
        var body = document.createElement('div');
        body.className = 'card-body';
        var title = document.createElement('h6');
        title.className = 'card-title text-primary';
        title.innerHTML = '<i class="fas fa-comments me-2"></i>';
        title.appendChild(document.createTextNode('Témoignage ' + (index + 1)));
        body.appendChild(title);
        var row = document.createElement('div');
        row.className = 'row';
        var fields = [
            ['col-md-6 mb-2', 'Témoin:', (temoignage.temoin_nom || '') + ' ' + (temoignage.temoin_prenoms || '')],
            ['col-md-6 mb-2', 'Statut:', getPersonneStatusLabel(temoignage.temoin_statut)]
        ];
        if (temoignage.temoin_telephone) fields.push(['col-md-6 mb-2', 'Téléphone:', temoignage.temoin_telephone]);
        if (temoignage.temoin_adresse) fields.push(['col-md-6 mb-2', 'Adresse:', temoignage.temoin_adresse]);
        if (temoignage.date_temoignage) fields.push(['col-md-6 mb-2', 'Date témoignage:', formatDate(temoignage.date_temoignage)]);
        if (temoignage.temoignage) fields.push(['col-md-12 mb-2', 'Témoignage:', temoignage.temoignage]);
        fields.forEach(function(f) {
            var col = document.createElement('div');
            col.className = f[0];
            var strong = document.createElement('strong');
            strong.textContent = f[1] + ' ';
            col.appendChild(strong);
            col.appendChild(document.createTextNode(f[2]));
            row.appendChild(col);
        });
        body.appendChild(row);
        card.appendChild(body);
        container.appendChild(card);
    });
}

// Étape 7: Suites et observations
function fillSuitesEtObservations(pv) {
    const elements = {
        detailSuitesBlesses: document.getElementById('detailSuitesBlesses'),
        detailSuitesDommages: document.getElementById('detailSuitesDommages'),
        detailSuitesAssaillants: document.getElementById('detailSuitesAssaillants'),
        detailObservations: document.getElementById('detailObservations')
    };
    
    if (elements.detailSuitesBlesses) {
        elements.detailSuitesBlesses.textContent = pv.suites_blesses || pv.suitesBlesses || '-';
    }
    if (elements.detailSuitesDommages) {
        elements.detailSuitesDommages.textContent = pv.suites_dommages || pv.suitesDommages || '-';
    }
    if (elements.detailSuitesAssaillants) {
        elements.detailSuitesAssaillants.textContent = pv.suites_assaillants || pv.suitesAssaillants || '-';
    }
    if (elements.detailObservations) {
        elements.detailObservations.textContent = pv.observations || pv.observation || '-';
    }
}

// Statut
function fillStatut(statut) {
    const statutElement = document.getElementById('detailStatut');
    if (statutElement) {
        statutElement.innerHTML = getStatutBadge(statut);
    }
}

// Modifier un PV
function editPV(id) {
    fetch(`../../pages/constat/constat.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv) {
                // Remplir le formulaire de modification
                fillEditForm(pv);
                
                // Afficher la modale de modification
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        })
        .catch(error => {
            showError('Erreur lors du chargement des informations');
        });
}

// Remplir le formulaire de modification
function fillEditForm(pv) {
    // Informations générales
    document.getElementById('editId').value = pv.id || '';
    document.getElementById('editCarteEtudiant').value = pv.carte_etudiant || '';
    document.getElementById('editNom').value = pv.nom || '';
    document.getElementById('editPrenoms').value = pv.prenoms || '';
    document.getElementById('editCampus').value = pv.campus || '';
    document.getElementById('editTelephone').value = pv.telephone || '';
    
    // Détails de l'incident
    document.getElementById('editTypeIncident').value = pv.type_incident || '';
    document.getElementById('editDateIncident').value = formatDateForInput(pv.date_incident) || '';
    document.getElementById('editHeureIncident').value = pv.heure_incident || '';
    document.getElementById('editLieuIncident').value = pv.lieu_incident || '';
    document.getElementById('editDescriptionIncident').value = pv.description_incident || '';
    
    // Suites et observations
    document.getElementById('editSuitesBlesses').value = pv.suites_blesses || '';
    document.getElementById('editSuitesDommages').value = pv.suites_dommages || '';
    document.getElementById('editSuitesAssaillants').value = pv.suites_assaillants || '';
    document.getElementById('editObservations').value = pv.observations || '';
    document.getElementById('editStatut').value = pv.statut || 'en_cours';
    document.getElementById('editDate').value = formatDateForInput(pv.created_at) || '';
    
    // Remplir les conteneurs dynamiques
    fillEditBlesses(pv.blesses || []);
    fillEditDommages(pv.dommages || []);
    fillEditAssaillants(pv.assaillants || []);
    fillEditAuditions(pv.auditions || []);
    fillEditTemoignages(pv.temoignages || []);
}

// Formater la date pour les champs input type="date"
function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    // Si la date est déjà au format YYYY-MM-DD, la retourner directement
    if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateString;
    }
    
    // Convertir la date au format YYYY-MM-DD pour input type="date"
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

// Formater la date et heure pour les champs input type="datetime-local"
function formatDateTimeForInput(dateTimeString) {
    if (!dateTimeString) return '';
    
    // Si déjà au format ISO, retourner directement
    if (dateTimeString.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/)) {
        return dateTimeString;
    }
    
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Fonctions pour remplir les conteneurs de modification
function fillEditBlesses(blesses) {
    const container = document.getElementById('editBlessesContainer');
    container.innerHTML = '';
    
    blesses.forEach((blesse, index) => {
        addEditBlesse(blesse);
    });
}

function fillEditDommages(dommages) {
    const container = document.getElementById('editDommagesContainer');
    container.innerHTML = '';
    
    dommages.forEach((dommage, index) => {
        addEditDommage(dommage);
    });
}

function fillEditAssaillants(assaillants) {
    const container = document.getElementById('editAssaillantsContainer');
    container.innerHTML = '';
    
    assaillants.forEach((assaillant, index) => {
        addEditAssaillant(assaillant);
    });
}

function fillEditAuditions(auditions) {
    const container = document.getElementById('editAuditionsContainer');
    container.innerHTML = '';
    
    auditions.forEach((audition, index) => {
        addEditAudition(audition);
    });
}

function fillEditTemoignages(temoignages) {
    const container = document.getElementById('editTemoignagesContainer');
    container.innerHTML = '';
    
    temoignages.forEach((temoignage, index) => {
        addEditTemoignage(temoignage);
    });
}

// Fonctions pour ajouter des éléments dans le formulaire de modification
function addEditBlesse(blesse = {}) {
    const container = document.getElementById('editBlessesContainer');
    const blesseDiv = document.createElement('div');
    blesseDiv.className = 'card mb-3';
    
    blesseDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Nom</label>
                    <input type="text" class="form-control" name="edit_blesse_nom[]" value="${blesse.nom || ''}" placeholder="Nom du blessé">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Prénoms</label>
                    <input type="text" class="form-control" name="edit_blesse_prenoms[]" value="${blesse.prenoms || ''}" placeholder="Prénoms du blessé">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Type de blessure</label>
                    <input type="text" class="form-control" name="edit_blesse_type_blessure[]" value="${blesse.type_blessure || ''}" placeholder="Ex: Contusion, Fracture, Entorse...">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Évacuation</label>
                    <select class="form-select" name="edit_blesse_evacuation[]">
                        <option value="0" ${blesse.evacuation === 0 || blesse.evacuation === false ? 'selected' : ''}>Non</option>
                        <option value="1" ${blesse.evacuation === 1 || blesse.evacuation === true ? 'selected' : ''}>Oui</option>
                    </select>
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Description de la blessure</label>
                    <textarea class="form-control" name="edit_blesse_description_blessure[]" rows="3" placeholder="Description détaillée de la blessure">${blesse.description_blessure || ''}</textarea>
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Hôpital</label>
                    <input type="text" class="form-control" name="edit_blesse_hopital[]" value="${blesse.hopital || ''}" placeholder="Nom de l'hôpital (si évacuation)">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        Supprimer ce blessé
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(blesseDiv);
}

function addEditDommage(dommage = {}) {
    const container = document.getElementById('editDommagesContainer');
    const dommageDiv = document.createElement('div');
    dommageDiv.className = 'card mb-3';

    dommageDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-2">
                    <label class="form-label">Type de dommage</label>
                    <select class="form-select" name="edit_dommage_type[]">
                        <option value="">-- Sélectionner --</option>
                        <option value="materiel" ${dommage.type_domage === 'materiel' ? 'selected' : ''}>Matériel</option>
                        <option value="immobilier" ${dommage.type_domage === 'immobilier' ? 'selected' : ''}>Immobilier</option>
                        <option value="vehicule" ${dommage.type_domage === 'vehicule' ? 'selected' : ''}>Véhicule</option>
                        <option value="autre" ${dommage.type_domage === 'autre' ? 'selected' : ''}>Autre</option>
                    </select>
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="edit_dommage_description[]" value="${dommage.description_domage || ''}" placeholder="Description du dommage">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Estimation (FCFA)</label>
                    <input type="number" class="form-control" name="edit_dommage_estimation[]" value="${dommage.estimation_valeur || ''}" placeholder="Valeur estimée">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Propriétaire</label>
                    <input type="text" class="form-control" name="edit_dommage_proprietaire[]" value="${dommage.proprietaire || ''}" placeholder="Propriétaire du bien">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        Supprimer ce dommage
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(dommageDiv);
}

function addEditAssaillant(assaillant = {}) {
    const container = document.getElementById('editAssaillantsContainer');
    const assaillantDiv = document.createElement('div');
    assaillantDiv.className = 'card mb-3';
    assaillantDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Nom</label>
                    <input type="text" class="form-control" name="edit_assaillant_nom[]" value="${assaillant.nom || ''}" placeholder="Nom de l'assaillant">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Prénoms</label>
                    <input type="text" class="form-control" name="edit_assaillant_prenoms[]" value="${assaillant.prenoms || ''}" placeholder="Prénoms de l'assaillant">
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Description physique</label>
                    <textarea class="form-control" name="edit_assaillant_description[]" rows="3" placeholder="Description physique de l'assaillant">${assaillant.description_physique || ''}</textarea>
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Signes distinctifs</label>
                    <textarea class="form-control" name="edit_assaillant_signes[]" rows="3" placeholder="Signes distinctifs (tatouages, cicatrices, etc.)">${assaillant.signes_distinctifs || ''}</textarea>
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Statut identification</label>
                    <input type="text" class="form-control" name="edit_assaillant_statut_id[]" value="${assaillant.statut || ''}" placeholder="Ex: Identifié et arrêté, En cours de recherche, Identité inconnue...">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        Supprimer cet assaillant
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(assaillantDiv);
}

function addEditAudition(audition = {}) {
    const container = document.getElementById('editAuditionsContainer');
    const auditionDiv = document.createElement('div');
    auditionDiv.className = 'card mb-3';
    auditionDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Nom du témoin</label>
                    <input type="text" class="form-control" name="edit_audition_nom[]" value="${audition.temoin_nom || ''}" placeholder="Nom du témoin">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Prénoms du témoin</label>
                    <input type="text" class="form-control" name="edit_audition_prenoms[]" value="${audition.temoin_prenoms || ''}" placeholder="Prénoms du témoin">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="edit_audition_statut[]">
                        <option value="etudiant" ${audition.temoin_statut === 'etudiant' ? 'selected' : ''}>Étudiant</option>
                        <option value="personnel" ${audition.temoin_statut === 'personnel' ? 'selected' : ''}>Personnel</option>
                        <option value="externe" ${audition.temoin_statut === 'externe' ? 'selected' : ''}>Externe</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" name="edit_audition_telephone[]" value="${audition.temoin_telephone || ''}" placeholder="Téléphone">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Date audition</label>
                    <input type="datetime-local" class="form-control" name="edit_audition_date[]" value="${formatDateTimeForInput(audition.date_audition)}">
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Déclaration</label>
                    <textarea class="form-control" name="edit_audition_declaration[]" rows="3" placeholder="Déclaration du témoin">${audition.declaration || ''}</textarea>
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        Supprimer cette audition
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(auditionDiv);
}

function addEditTemoignage(temoignage = {}) {
    const container = document.getElementById('editTemoignagesContainer');
    const temoignageDiv = document.createElement('div');
    temoignageDiv.className = 'card mb-3';
    temoignageDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Nom du témoin</label>
                    <input type="text" class="form-control" name="edit_temoignage_nom[]" value="${temoignage.temoin_nom || ''}" placeholder="Nom du témoin">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Prénoms du témoin</label>
                    <input type="text" class="form-control" name="edit_temoignage_prenoms[]" value="${temoignage.temoin_prenoms || ''}" placeholder="Prénoms du témoin">
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Témoignage</label>
                    <textarea class="form-control" name="edit_temoignage_texte[]" rows="4" placeholder="Témoignage écrit">${temoignage.temoignage || ''}</textarea>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="edit_temoignage_statut[]">
                        <option value="etudiant" ${temoignage.temoin_statut === 'etudiant' ? 'selected' : ''}>Étudiant</option>
                        <option value="personnel" ${temoignage.temoin_statut === 'personnel' ? 'selected' : ''}>Personnel</option>
                        <option value="externe" ${temoignage.temoin_statut === 'externe' ? 'selected' : ''}>Externe</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" name="edit_temoignage_telephone[]" value="${temoignage.temoin_telephone || ''}" placeholder="Téléphone">
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Adresse</label>
                    <input type="text" class="form-control" name="edit_temoignage_adresse[]" value="${temoignage.temoin_adresse || ''}" placeholder="Adresse du témoin">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Date témoignage</label>
                    <input type="datetime-local" class="form-control" name="edit_temoignage_date[]" value="${formatDateTimeForInput(temoignage.date_temoignage)}">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        Supprimer ce témoignage
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(temoignageDiv);
}

// Supprimer un PV
function deletePV(id) {
    fetch(`../../pages/constat/constat.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv) {
                document.getElementById('deletePvName').textContent = (pv.nom || '') + ' ' + (pv.prenoms || '');
                document.getElementById('deletePvDate').textContent = formatDate(pv.date_incident) || 'N/A';
                window.deletePvId = id;
                
                const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                modal.show();
            }
        })
        .catch(error => {
            showError('Erreur lors du chargement des informations');
        });
}

// Afficher la modale de confirmation de modification
function showConfirmEditModal() {
    const modal = new bootstrap.Modal(document.getElementById('confirmEditModal'));
    modal.show();
}

// Confirmer et mettre à jour un PV
function collectEditItems(containerId, prefix, fieldMapping) {
    const container = document.getElementById(containerId);
    if (!container) return [];

    const items = container.querySelectorAll('.card, .blesse-item, .dommage-item, .assaillant-item, .audition-item, .temoignage-item');
    const result = [];

    items.forEach(item => {
        const inputs = item.querySelectorAll('input, select, textarea');
        const data = {};

        inputs.forEach(input => {
            if (!input.name) return;
            const fieldName = input.name.replace(new RegExp('^' + prefix), '').replace(/(_\d+|\[\])$/, '');
            const value = input.value.trim();

            if (fieldMapping[fieldName] !== undefined) {
                data[fieldMapping[fieldName]] = value;
            } else {
                data[fieldName] = value;
            }
        });

        if (Object.keys(data).length > 0) {
            result.push(data);
        }
    });

    return result;
}

function collectEditBlesses() {
    return collectEditItems('editBlessesContainer', 'edit_blesse_', {
        'nom': 'nom',
        'prenoms': 'prenoms',
        'type_blessure': 'typeBlessure',
        'evacuation': 'evacuation',
        'description_blessure': 'description',
        'hopital': 'hopital'
    });
}

function collectEditDommages() {
    return collectEditItems('editDommagesContainer', 'edit_dommage_', {
        'type': 'type',
        'description': 'description',
        'estimation': 'estimation',
        'proprietaire': 'proprietaire'
    });
}

function collectEditAssaillants() {
    return collectEditItems('editAssaillantsContainer', 'edit_assaillant_', {
        'nom': 'nom',
        'prenoms': 'prenoms',
        'description': 'description',
        'signes': 'signes',
        'statut_id': 'statut'
    });
}

function collectEditAuditions() {
    return collectEditItems('editAuditionsContainer', 'edit_audition_', {
        'nom': 'nom',
        'prenoms': 'prenoms',
        'statut': 'statut',
        'telephone': 'telephone',
        'date': 'date',
        'declaration': 'declaration'
    });
}

function collectEditTemoignages() {
    return collectEditItems('editTemoignagesContainer', 'edit_temoignage_', {
        'nom': 'nom',
        'prenoms': 'prenoms',
        'telephone': 'telephone',
        'adresse': 'adresse',
        'statut': 'statut',
        'date': 'date',
        'texte': 'temoignage',
        'contenu': 'temoignage'
    });
}

function confirmUpdatePV() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const pvId = document.getElementById('editId').value;
    
    if (!pvId) {
        showError('ID du PV non trouvé');
        return;
    }
    
    // Fermer la modale de confirmation
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmEditModal'));
    if (confirmModal) {
        confirmModal.hide();
    }
    
    // Désactiver le bouton pendant la sauvegarde
    const submitBtn = document.getElementById('confirmEditBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
    
    // Préparer les données pour l'envoi
    const heureIncidentRaw = formData.get('editHeureIncident');
    const heureIncident = heureIncidentRaw ? heureIncidentRaw.substring(0, 5) : null; // Garder seulement HH:MM
    
    const data = {
        action: 'update',
        id: pvId,
        carte_etudiant: formData.get('editCarteEtudiant'),
        nom: formData.get('editNom'),
        prenoms: formData.get('editPrenoms'),
        campus: formData.get('editCampus'),
        telephone: formData.get('editTelephone'),
        type_incident: formData.get('editTypeIncident'),
        date_incident: formData.get('editDateIncident'),
        heure_incident: heureIncident, // Format HH:MM
        lieu_incident: formData.get('editLieuIncident'),
        description_incident: formData.get('editDescriptionIncident'),
        suites_blesses: formData.get('editSuitesBlesses'),
        suites_dommages: formData.get('editSuitesDommages'),
        suites_assaillants: formData.get('editSuitesAssaillants'),
        observations: formData.get('editObservations'),
        statut: formData.get('editStatut'),
        created_at: formData.get('editDate'),
        blesses: collectEditBlesses(),
        dommages: collectEditDommages(),
        assaillants: collectEditAssaillants(),
        auditions: collectEditAuditions(),
        temoignages: collectEditTemoignages()
    };
    
    // Envoyer la requête de mise à jour
    fetch('../../pages/constat/constat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                return { success: false, message: 'Réponse invalide du serveur' };
            }
        });
    })
    .then(result => {
        if (result.success) {
            // Afficher la modale de succès
            const successModal = new bootstrap.Modal(document.getElementById('successEditModal'));
            successModal.show();
            
            // Fermer la modale de modification après un court délai
            setTimeout(() => {
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (editModal) {
                    editModal.hide();
                }
            }, 500);
            
            // Rafraîchir la liste des PV
            loadPVData();
        } else {
            showError(result.message || 'Erreur lors de la mise à jour du PV');
        }
    })
    .catch(error => {
        showError('Erreur lors de la mise à jour du PV');
    })
    .finally(() => {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Oui, modifier';
    });
}

// Fonction pour confirmer la création d'un PV
function confirmSavePV() {
    const form = document.getElementById('addForm');
    if (!form) {
        showError('Formulaire non trouvé');
        return;
    }
    
    // Validation de base avec messages d'erreur détaillés
    const carteEtudiant = document.getElementById('addCarteEtudiant')?.value?.trim();
    const nom = document.getElementById('addNom')?.value?.trim();
    const prenoms = document.getElementById('addPrenoms')?.value?.trim();
    const campus = document.getElementById('addCampus')?.value?.trim();
    const typeIncident = document.getElementById('addTypeIncident')?.value?.trim();
    const dateIncident = document.getElementById('addDateIncident')?.value?.trim();
    const lieuIncident = document.getElementById('addLieuIncident')?.value?.trim();
    const descriptionIncident = document.getElementById('addDescriptionIncident')?.value?.trim();
    
    // Collecter les erreurs de validation
    const errors = [];
    
    if (!carteEtudiant) {
        errors.push('Le numéro de carte étudiant est requis');
    } else if (!/^[A-Z0-9]{3,20}$/i.test(carteEtudiant)) {
        errors.push('Le format de la carte étudiant est invalide (ex: ETU000001 ou 123456789)');
    }
    
    if (!nom) {
        errors.push('Le nom est requis');
    } else if (nom.length < 2) {
        errors.push('Le nom doit contenir au moins 2 caractères');
    }
    
    if (!prenoms) {
        errors.push('Le prénom est requis');
    } else if (prenoms.length < 2) {
        errors.push('Le prénom doit contenir au moins 2 caractères');
    }
    
    if (!campus) {
        errors.push('Le campus/résidence est requis');
    }
    
    if (!typeIncident) {
        errors.push('Le type d\'incident est requis');
    }
    
    if (!dateIncident) {
        errors.push('La date de l\'incident est requise');
    } else {
        const date = new Date(dateIncident);
        if (isNaN(date.getTime())) {
            errors.push('Le format de la date est invalide');
        } else if (date > new Date()) {
            errors.push('La date de l\'incident ne peut pas être dans le futur');
        }
    }
    
    if (!lieuIncident) {
        errors.push('Le lieu de l\'incident est requis');
    }
    
    if (!descriptionIncident) {
        errors.push('La description de l\'incident est requise');
    } else if (descriptionIncident.length < 5) {
        errors.push('La description doit contenir au moins 5 caractères');
    }
    
    // Afficher les erreurs s'il y en a
    if (errors.length > 0) {
        let errorMessage = '<strong>Erreurs de validation :</strong><ul class="mt-2 mb-0">';
        errors.forEach(error => {
            errorMessage += `<li>${error}</li>`;
        });
        errorMessage += '</ul>';
        showError(errorMessage, true);
        return;
    }
    
    // Collecter toutes les données des steps AVANT d'ouvrir la modal de confirmation
    _pendingStepData = {
        blesses: collectStepData('blesses'),
        dommages: collectStepData('dommages'),
        assaillants: collectStepData('assaillants'),
        auditions: collectStepData('auditions'),
        temoignages: collectStepData('temoignages'),
        telephone: document.getElementById('addTelephone')?.value?.trim() || '',
        heureIncident: document.getElementById('addHeureIncident')?.value?.trim() || '',
        observations: document.getElementById('addObservations')?.value?.trim() || '',
        suitesBlesses: document.getElementById('addSuitesBlesses')?.value?.trim() || '',
        suitesDommages: document.getElementById('addSuitesDommages')?.value?.trim() || '',
        suitesAssaillants: document.getElementById('addSuitesAssaillants')?.value?.trim() || ''
    };

    // Préparer le récapitulatif (sécurisé)
    var summaryContainer = document.getElementById('confirmSummary');
    summaryContainer.innerHTML = '';
    var row = document.createElement('div');
    row.className = 'row';
    var summaryFields = [
        ['N° Carte:', carteEtudiant],
        ['Nom:', nom + ' ' + prenoms],
        ['Campus:', campus],
        ['Type:', typeIncident],
        ['Date:', dateIncident],
        ['Lieu:', lieuIncident],
        ['Témoignages:', _pendingStepData.temoignages.length + ' ajouté(s)']
    ];
    summaryFields.forEach(function(f) {
        var col = document.createElement('div');
        col.className = 'col-md-6';
        var strong = document.createElement('strong');
        strong.textContent = f[0] + ' ';
        col.appendChild(strong);
        col.appendChild(document.createTextNode(f[1]));
        row.appendChild(col);
    });
    summaryContainer.appendChild(row);
    var descDiv = document.createElement('div');
    descDiv.className = 'mt-2';
    var descStrong = document.createElement('strong');
    descStrong.textContent = 'Description: ';
    descDiv.appendChild(descStrong);
    descDiv.appendChild(document.createTextNode(descriptionIncident.substring(0, 100) + (descriptionIncident.length > 100 ? '...' : '')));
    summaryContainer.appendChild(descDiv);

    // Afficher la modale de confirmation
    const modal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    modal.show();
}

// Sauvegarder un PV (création)
function savePV() {
    const form = document.getElementById('addForm');
    if (!form) {
        showError('Formulaire non trouvé');
        return;
    }
    
    // Validation de base
    const carteEtudiant = document.getElementById('addCarteEtudiant')?.value?.trim();
    const nom = document.getElementById('addNom')?.value?.trim();
    const prenoms = document.getElementById('addPrenoms')?.value?.trim();
    const campus = document.getElementById('addCampus')?.value?.trim();
    const telephone = document.getElementById('addTelephone')?.value?.trim();
    const typeIncident = document.getElementById('addTypeIncident')?.value?.trim();
    const dateIncident = document.getElementById('addDateIncident')?.value?.trim();
    const heureIncident = document.getElementById('addHeureIncident')?.value?.trim();
    const lieuIncident = document.getElementById('addLieuIncident')?.value?.trim();
    const descriptionIncident = document.getElementById('addDescriptionIncident')?.value?.trim();
    
    // Utiliser les données pré-collectées (avant la modal de confirmation)
    const blesses = _pendingStepData != null ? _pendingStepData.blesses : collectStepData('blesses');
    const dommages = _pendingStepData != null ? _pendingStepData.dommages : collectStepData('dommages');
    const assaillants = _pendingStepData != null ? _pendingStepData.assaillants : collectStepData('assaillants');
    const auditions = _pendingStepData != null ? _pendingStepData.auditions : collectStepData('auditions');
    const temoignages = _pendingStepData != null ? _pendingStepData.temoignages : collectStepData('temoignages');

    // Collecter les observations et suites
    const observations = _pendingStepData != null ? _pendingStepData.observations : (document.getElementById('addObservations')?.value?.trim() || '');
    const suitesBlesses = _pendingStepData != null ? _pendingStepData.suitesBlesses : (document.getElementById('addSuitesBlesses')?.value?.trim() || '');
    const suitesDommages = _pendingStepData != null ? _pendingStepData.suitesDommages : (document.getElementById('addSuitesDommages')?.value?.trim() || '');
    const suitesAssaillants = _pendingStepData != null ? _pendingStepData.suitesAssaillants : (document.getElementById('addSuitesAssaillants')?.value?.trim() || '');
    
    if (!carteEtudiant || !nom || !prenoms || !campus || !typeIncident || !dateIncident || !lieuIncident || !descriptionIncident) {
        showError('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    // Préparer les données complètes
    const data = {
        action: 'create',
        carteEtudiant: carteEtudiant,
        nom: nom,
        prenoms: prenoms,
        campus: campus,
        telephone: telephone,
        typeIncident: typeIncident,
        dateIncident: dateIncident,
        heureIncident: heureIncident,
        lieuIncident: lieuIncident,
        descriptionIncident: descriptionIncident,
        // Données des sections
        blesses: blesses,
        dommages: dommages,
        assaillants: assaillants,
        auditions: auditions,
        temoignages: temoignages,
        // Observations et suites
        observations: observations,
        suitesBlesses: suitesBlesses,
        suitesDommages: suitesDommages,
        suitesAssaillants: suitesAssaillants,
        statut: 'en_cours',
        date: new Date().toISOString().split('T')[0]
    };

    console.log('[DEBUG savePV] temoignages envoyés:', JSON.stringify(data.temoignages), '| source:', _pendingStepData != null ? 'pré-collecté' : 'DOM direct');

    // Désactiver le bouton pendant la sauvegarde
    const submitBtn = document.getElementById('confirmSaveBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
    }
    
    // Envoyer la requête
    fetch('../../pages/constat/constat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            _pendingStepData = null;

            // 1. Fermer les deux modales empilées
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmSaveModal'));
            if (confirmModal) confirmModal.hide();

            const addModalInstance = bootstrap.Modal.getInstance(document.getElementById('addModal'));
            if (addModalInstance) addModalInstance.hide();

            // Réinitialiser le formulaire et rafraîchir la liste
            form.reset();
            loadPVData();

            // 2. Attendre la fin des animations Bootstrap, nettoyer les backdrops,
            //    puis afficher la modale de succès proprement
            setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                document.getElementById('pvNumber').textContent = result.id
                    ? 'ID: ' + result.id
                    : 'PV-CONSTAT-' + new Date().toISOString().split('T')[0].replace(/-/g, '');

                const successModal = new bootstrap.Modal(document.getElementById('successSaveModal'));
                successModal.show();
            }, 400);
        } else {
            // Afficher les erreurs de validation détaillées
            if (result.errors && Array.isArray(result.errors)) {
                let errorMessage = '<strong>Erreurs de validation :</strong><ul class="mt-2 mb-0">';
                result.errors.forEach(error => {
                    errorMessage += `<li>${error}</li>`;
                });
                errorMessage += '</ul>';
                showError(errorMessage, true); // true pour HTML autorisé
            } else {
                showError(result.message || 'Erreur lors de la création du PV');
            }
        }
    })
    .catch(error => {
        showError('Erreur lors de la création du PV');
    })
    .finally(() => {
        // Réactiver le bouton
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Confirmer la création';
        }
    });
}

// Fonction pour collecter les données d'une étape
function collectStepData(stepName) {
    let containerId = '';

    // Déterminer l'ID du conteneur selon le type de données
    switch(stepName) {
        case 'blesses':
            containerId = 'blessesContainer';
            break;
        case 'dommages':
            containerId = 'dommagesContainer';
            break;
        case 'assaillants':
            containerId = 'assaillantsContainer';
            break;
        case 'auditions':
            containerId = 'auditionsContainer';
            break;
        case 'temoignages':
            containerId = 'temoignagesContainer';
            break;
        default:
            return [];
    }

    const container = document.getElementById(containerId);
    if (!container) return [];

    // Chercher les éléments dynamiques (.blesse-item, .dommage-item, etc.)
    var itemClass = stepName === 'blesses' ? '.blesse-item' : ('.' + stepName.replace(/s$/, '') + '-item');
    var cards = container.querySelectorAll(itemClass);
    if (cards.length === 0) cards = container.querySelectorAll('.card');
    const data = [];
    
    cards.forEach(card => {
        const cardData = {};
        
        // Collecter tous les champs input/select/textarea dans la carte
        const inputs = card.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name && input.value.trim()) {
                // Extraire le nom du champ : supprimer le préfixe (blesse_/dommage_/etc.) et le suffixe (_N)
                let fieldName = input.name.replace(/^(blesse|dommage|assaillant|audition|temoignage)_/, '').replace(/_\d+$/, '');
                const value = input.value.trim();

                // Mapping pour les blessés
                if (stepName === 'blesses') {
                    switch(fieldName) {
                        case 'nom': cardData['nom'] = value; break;
                        case 'prenoms': cardData['prenoms'] = value; break;
                        case 'gravite': cardData['typeBlessure'] = value; break;
                        default: cardData[fieldName] = value;
                    }
                }
                // Mapping pour les dommages
                else if (stepName === 'dommages') {
                    switch(fieldName) {
                        case 'description': cardData['description'] = value; break;
                        case 'type': cardData['type'] = value; break;
                        case 'valeur': cardData['estimation'] = value; break;
                        default: cardData[fieldName] = value;
                    }
                }
                // Mapping pour les assaillants
                else if (stepName === 'assaillants') {
                    switch(fieldName) {
                        case 'nom': cardData['nom'] = value; break;
                        case 'description': cardData['description'] = value; break;
                        case 'statut': cardData['statut'] = value; break;
                        default: cardData[fieldName] = value;
                    }
                }
                // Mapping pour les auditions
                else if (stepName === 'auditions') {
                    switch(fieldName) {
                        case 'nom': cardData['nom'] = value; break;
                        case 'date': cardData['date'] = value; break;
                        case 'type': cardData['statut'] = value; break;
                        case 'contenu': cardData['declaration'] = value; break;
                        default: cardData[fieldName] = value;
                    }
                }
                // Mapping pour les témoignages
                else if (stepName === 'temoignages') {
                    switch(fieldName) {
                        case 'nom': cardData['nom'] = value; break;
                        case 'prenoms': cardData['prenoms'] = value; break;
                        case 'telephone': cardData['telephone'] = value; break;
                        case 'adresse': cardData['adresse'] = value; break;
                        case 'statut': cardData['statut'] = value; break;
                        case 'date': cardData['date'] = value; break;
                        case 'contenu': cardData['temoignage'] = value; break;
                        default: cardData[fieldName] = value;
                    }
                }
                else {
                    cardData[fieldName] = value;
                }
            }
        });
        
        // Si la carte a des données, l'ajouter au tableau
        if (Object.keys(cardData).length > 0) {
            data.push(cardData);
        }
    });
    
    return data;
}

// Fonctions utilitaires
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

function getTypeIncidentLabel(type) {
    const labels = {
        'vol': 'Vol',
        'agression': 'Agression',
        'degradation': 'Dégradation',
        'perte': 'Perte',
        'incendie': 'Incendie',
        'autre': 'Autre'
    };
    return labels[type] || type || '-';
}

function getStatutBadge(statut) {
    const badges = {
        'en_cours': '<span class="badge bg-warning">En cours</span>',
        'traite': '<span class="badge bg-success">Traité</span>',
        'archive': '<span class="badge bg-secondary">Archivé</span>'
    };
    return badges[statut] || statut || '-';
}

function getPersonneStatusLabel(statut) {
    const labels = {
        'etudiant': 'Étudiant',
        'personnel': 'Personnel',
        'externe': 'Externe',
        'visiteur': 'Visiteur',
        'autre': 'Autre'
    };
    return labels[statut] || statut || '-';
}

function getAssaillantStatusLabel(statut) {
    const labels = {
        'etudiant': 'Étudiant',
        'personnel': 'Personnel',
        'externe': 'Externe',
        'inconnu': 'Inconnu',
        'autre': 'Autre'
    };
    return labels[statut] || statut || '-';
}

function getDommageTypeLabel(type) {
    const labels = {
        'telephone': 'Téléphone',
        'ordinateur': 'Ordinateur',
        'tablette': 'Tablette',
        'vetements': 'Vêtements',
        'argent': 'Argent',
        'documents': 'Documents',
        'autre': 'Autre'
    };
    return labels[type] || type || '-';
}

function showLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.style.display = 'flex';
    }
}

function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

function showSuccess(message) {
    var alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    var msgSpan = document.createElement('span');
    msgSpan.textContent = message;
    alert.appendChild(msgSpan);
    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    alert.appendChild(closeBtn);

    var container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
    }

    setTimeout(function() {
        if (alert.parentNode) alert.remove();
    }, 5000);
}

// Afficher une notification d'erreur
function showError(message, isHtml = false) {
    // Créer ou mettre à jour la modale d'erreur
    let errorModal = document.getElementById('errorModal');
    
    if (!errorModal) {
        // Créer la modale d'erreur si elle n'existe pas
        const modalHtml = `
            <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>Erreur de Validation
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger mb-0">
                                <div id="errorMessage"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Compris
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        errorModal = document.getElementById('errorModal');
    }
    
    // Mettre à jour le message d'erreur
    const errorMessageElement = document.getElementById('errorMessage');
    if (errorMessageElement) {
        if (isHtml) {
            errorMessageElement.innerHTML = message;
        } else {
            errorMessageElement.textContent = message;
        }
    }
    
    // Afficher la modale d'erreur
    const modal = new bootstrap.Modal(errorModal);
    modal.show();
    
    // Fermer automatiquement après 5 secondes
    setTimeout(() => {
        modal.hide();
    }, 5000);
}

// Fonction d'impression du constat avec les 4 images
function printPV() {
    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank');
    
    // Récupérer les données du PV actuellement affiché dans la modal
    const pvData = getCurrentPVData();
    
    if (!pvData) {
        showError('Aucune donnée de PV à imprimer');
        return;
    }
    
    // Générer le HTML d'impression avec les 4 images
    const printHTML = generatePrintHTML(pvData);
    
    // Écrire le contenu dans la nouvelle fenêtre
    printWindow.document.write(printHTML);
    printWindow.document.close();
    
    // Attendre que le contenu soit chargé puis imprimer
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 1000);
    };
}

// Récupérer les données du PV actuellement affiché
function getCurrentPVData() {
    // Récupérer les informations depuis la modal de détails
    return {
        carte_etudiant: document.getElementById('detailCarteEtudiant')?.textContent || '-',
        nom_prenom: document.getElementById('detailNomPrenom')?.textContent || '-',
        campus: document.getElementById('detailCampus')?.textContent || '-',
        telephone: document.getElementById('detailTelephone')?.textContent || '-',
        type_incident: document.getElementById('detailTypeIncident')?.textContent || '-',
        description_incident: document.getElementById('detailDescriptionIncident')?.textContent || '-',
        lieu_incident: document.getElementById('detailLieuIncident')?.textContent || '-',
        date_incident: document.getElementById('detailDateIncident')?.textContent || '-',
        heure_incident: document.getElementById('detailHeureIncident')?.textContent || '-',
        suites_blesses: document.getElementById('detailSuitesBlesses')?.textContent || '-',
        suites_dommages: document.getElementById('detailSuitesDommages')?.textContent || '-',
        suites_assaillants: document.getElementById('detailSuitesAssaillants')?.textContent || '-',
        observations: document.getElementById('detailObservations')?.textContent || '-',
        statut: document.getElementById('detailStatut')?.textContent || '-',
        // Récupérer les données des étapes
        blesses: getStepData('detailBlesses'),
        dommages: getStepData('detailDommages'),
        assaillants: getStepData('detailAssaillants'),
        auditions: getStepData('detailAuditions'),
        temoignages: getStepData('detailTemoignages')
    };
}

// Récupérer les données d'une étape
function getStepData(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return [];
    
    const cards = container.querySelectorAll('.card');
    const data = [];
    
    cards.forEach(card => {
        const cardData = {};
        const rows = card.querySelectorAll('.row > div');
        
        rows.forEach(row => {
            const text = row.textContent.trim();
            if (text.includes(':')) {
                const [key, value] = text.split(':').map(s => s.trim());
                cardData[key] = value;
            }
        });
        
        if (Object.keys(cardData).length > 0) {
            data.push(cardData);
        }
    });
    
    return data;
}

// Générer le HTML d'impression avec les 4 images
function generatePrintHTML(pvData) {
    const currentDate = new Date().toLocaleDateString('fr-FR');
    const currentTime = new Date().toLocaleTimeString('fr-FR');
    
    return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROCÈS-VERBAL DE CONSTAT - ${pvData.nom_prenom}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        
        .print-title {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .print-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .print-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .print-section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .print-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .print-info-item {
            display: flex;
            gap: 5px;
        }
        
        .print-info-label {
            font-weight: bold;
            color: #333;
        }
        
        .print-info-value {
            color: #000;
        }
        
        .print-card {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .print-card-title {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .print-card-content {
            font-size: 11px;
        }
        
        .print-footer {
            margin-top: 40px;
            text-align: center;
            border-top: 2px solid #007bff;
            padding-top: 20px;
            font-size: 10px;
            color: #666;
        }
        
        .print-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .print-signature {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 20px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="print-header">
            <div class="print-title">PROCÈS-VERBAL DE CONSTAT</div>
            <div class="print-subtitle">Université Catholique de Développement - USCoud</div>
            <div class="print-subtitle">Direction des Affaires Estudiantines et Sociales</div>
            <div class="print-subtitle">Service de Sécurité et de Discipline</div>
        </div>

        <!-- Informations générales -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-info-circle"></i>
                INFORMATIONS GÉNÉRALES
            </div>
            <div class="print-info-grid">
                <div class="print-info-item">
                    <span class="print-info-label">N° Carte Étudiant:</span>
                    <span class="print-info-value">${pvData.carte_etudiant}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Nom & Prénoms:</span>
                    <span class="print-info-value">${pvData.nom_prenom}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Campus/Résidence:</span>
                    <span class="print-info-value">${pvData.campus}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Téléphone:</span>
                    <span class="print-info-value">${pvData.telephone}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Type d'Incident:</span>
                    <span class="print-info-value">${pvData.type_incident}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Date & Heure:</span>
                    <span class="print-info-value">${pvData.date_incident} ${pvData.heure_incident}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Lieu:</span>
                    <span class="print-info-value">${pvData.lieu_incident}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Statut:</span>
                    <span class="print-info-value">${pvData.statut}</span>
                </div>
            </div>
            <div class="print-info-item">
                <span class="print-info-label">Description de l'Incident:</span>
            </div>
            <div class="print-card">
                <div class="print-card-content">${pvData.description_incident}</div>
            </div>
        </div>

        <!-- Blessés physiques -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-user-injured"></i>
                BLESSÉS PHYSIQUES (${pvData.blesses.length})
            </div>
            ${pvData.blesses.length > 0 ? pvData.blesses.map((blesse, index) => `
                <div class="print-card">
                    <div class="print-card-title">Blessé ${index + 1}</div>
                    <div class="print-card-content">
                        ${Object.entries(blesse).map(([key, value]) => 
                            `<div><strong>${key}:</strong> ${value}</div>`
                        ).join('')}
                    </div>
                </div>
            `).join('') : '<div class="print-card"><div class="print-card-content">Aucun blessé enregistré</div></div>'}
        </div>

        <!-- Dommages matériels -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-tools"></i>
                DOMMAGES MATÉRIELS (${pvData.dommages.length})
            </div>
            ${pvData.dommages.length > 0 ? pvData.dommages.map((dommage, index) => `
                <div class="print-card">
                    <div class="print-card-title">Dommage ${index + 1}</div>
                    <div class="print-card-content">
                        ${Object.entries(dommage).map(([key, value]) => 
                            `<div><strong>${key}:</strong> ${value}</div>`
                        ).join('')}
                    </div>
                </div>
            `).join('') : '<div class="print-card"><div class="print-card-content">Aucun dommage matériel enregistré</div></div>'}
        </div>

        <!-- Assaillants cités -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-user-secret"></i>
                ASSAILLANTS CITÉS (${pvData.assaillants.length})
            </div>
            ${pvData.assaillants.length > 0 ? pvData.assaillants.map((assaillant, index) => `
                <div class="print-card">
                    <div class="print-card-title">Assaillant ${index + 1}</div>
                    <div class="print-card-content">
                        ${Object.entries(assaillant).map(([key, value]) => 
                            `<div><strong>${key}:</strong> ${value}</div>`
                        ).join('')}
                    </div>
                </div>
            `).join('') : '<div class="print-card"><div class="print-card-content">Aucun assaillant cité</div></div>'}
        </div>

        <!-- Auditions -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-microphone"></i>
                AUDITIONS (${pvData.auditions.length})
            </div>
            ${pvData.auditions.length > 0 ? pvData.auditions.map((audition, index) => `
                <div class="print-card">
                    <div class="print-card-title">Audition ${index + 1}</div>
                    <div class="print-card-content">
                        ${Object.entries(audition).map(([key, value]) => 
                            `<div><strong>${key}:</strong> ${value}</div>`
                        ).join('')}
                    </div>
                </div>
            `).join('') : '<div class="print-card"><div class="print-card-content">Aucune audition enregistrée</div></div>'}
        </div>

        <!-- Témoignages -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-comments"></i>
                TÉMOIGNAGES (${pvData.temoignages.length})
            </div>
            ${pvData.temoignages.length > 0 ? pvData.temoignages.map((temoignage, index) => `
                <div class="print-card">
                    <div class="print-card-title">Témoignage ${index + 1}</div>
                    <div class="print-card-content">
                        ${Object.entries(temoignage).map(([key, value]) => 
                            `<div><strong>${key}:</strong> ${value}</div>`
                        ).join('')}
                    </div>
                </div>
            `).join('') : '<div class="print-card"><div class="print-card-content">Aucun témoignage enregistré</div></div>'}
        </div>

        <!-- Suites et observations -->
        <div class="print-section">
            <div class="print-section-title">
                <i class="fas fa-sticky-note"></i>
                SUITES ET OBSERVATIONS
            </div>
            <div class="print-info-grid">
                <div class="print-info-item">
                    <span class="print-info-label">Suites blessés:</span>
                    <span class="print-info-value">${pvData.suites_blesses}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Suites dommages:</span>
                    <span class="print-info-value">${pvData.suites_dommages}</span>
                </div>
                <div class="print-info-item">
                    <span class="print-info-label">Suites assaillants:</span>
                    <span class="print-info-value">${pvData.suites_assaillants}</span>
                </div>
            </div>
            <div class="print-info-item">
                <span class="print-info-label">Observations générales:</span>
            </div>
            <div class="print-card">
                <div class="print-card-content">${pvData.observations}</div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="print-signatures">
            <div class="print-signature">
                <div>Signature du plaignant/victime</div>
                <small>${pvData.nom_prenom}</small>
            </div>
            <div class="print-signature">
                <div>Signature de l'agent de sécurité</div>
                <small>Service de Sécurité USCoud</small>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="print-footer">
            <div>PROCÈS-VERBAL ÉTABLI LE ${currentDate.toUpperCase()} À ${currentTime}</div>
            <div>Université Catholique de Développement - USCoud</div>
            <div>Ce document est un constat officiel et ne peut être utilisé que dans le cadre des procédures disciplinaires internes</div>
        </div>
    </div>
</body>
</html>
    `;
}
