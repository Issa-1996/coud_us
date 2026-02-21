// Variables globales
let pvData = [];
let currentEditId = null;
let currentDetailId = null;
let currentPage = 1;
let itemsPerPage = 10;
let filteredData = [];
let currentStep = 1;
const totalSteps = 4;

// Charger les données depuis la base de données
function loadPVData() {
    const search = document.getElementById('searchInput')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    
    fetch(`denonciation.php?ajax=1&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&page=${currentPage}&itemsPerPage=${itemsPerPage}`)
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('loadPVData - Réponse non-JSON:', text.substring(0, 500));
                    throw new Error('Réponse serveur: ' + text.substring(0, 200));
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                pvData = data.pvData;
                filteredData = [...pvData];
                displayTable();
                updatePagination(data.pagination);
                updateStatistics(data.statistics);
            } else {
                console.error('Erreur serveur:', data);
                showAlert((data.message || 'Erreur lors du chargement des données') + (data.debug ? ' [DEBUG: ' + data.debug + ']' : ''), 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur de connexion au serveur', 'danger');
        });
}

// Afficher le tableau
function displayTable() {
    const tbody = document.getElementById('pvTableBody');
    
    if (!pvData || pvData.length === 0) {
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

    tbody.innerHTML = '';
    
    pvData.forEach((pv, index) => {
        const globalIndex = (currentPage - 1) * itemsPerPage + index + 1;
        
        // Adapter les champs de la base de données
        const typesLabels = pv.type_denonciation ? {
            'violence': '<span class="badge bg-danger me-1">Violence</span>',
            'harcelement': '<span class="badge bg-warning me-1">Harcèlement</span>',
            'diffamation': '<span class="badge bg-info me-1">Diffamation</span>',
            'vol': '<span class="badge bg-secondary me-1">Vol</span>',
            'fraude': '<span class="badge bg-dark me-1">Fraude</span>',
            'autre': '<span class="badge bg-light me-1">Autre</span>'
        }[pv.type_denonciation] : '<span class="badge bg-secondary">Non spécifié</span>';

        const row = `
            <tr>
                <td>${globalIndex}</td>
                <td><strong>${pv.denonciateur_nom || 'N/A'} ${pv.denonciateur_prenoms || ''}</strong></td>
                <td>${pv.agent_nom ? pv.agent_nom + ' ' + pv.agent_prenoms : 'N/A'}</td>
                <td>${pv.lieu_faits || 'N/A'}</td>
                <td>${typesLabels}</td>
                <td>${pv.etudiant_nom ? pv.etudiant_nom + ' ' + pv.etudiant_prenoms : 'N/A'}</td>
                <td>${pv.denonciateur_telephone || '-'}</td>
                <td>${formatStatut(pv.statut)}</td>
                <td>
                    <button class="btn btn-sm btn-info btn-action" onclick="viewDetail(${pv.id})" title="Détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${(function() {
                        var canEdit = false, canDelete = false;
                        if (typeof USER_ROLE !== 'undefined') {
                            if (USER_ROLE === 'admin') { canEdit = true; canDelete = true; }
                            else if (USER_ROLE === 'superviseur') { canEdit = true; }
                            else if (USER_ROLE === 'agent' && pv.id_agent == USER_ID) { canEdit = true; canDelete = true; }
                        }
                        var html = '';
                        if (canEdit) html += '<button class="btn btn-sm btn-warning btn-action" onclick="editPV(' + pv.id + ')" title="Modifier"><i class="fas fa-edit"></i></button>';
                        if (canDelete) html += '<button class="btn btn-sm btn-danger btn-action" onclick="deletePV(' + pv.id + ')" title="Supprimer"><i class="fas fa-trash"></i></button>';
                        return html;
                    })()}
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Mettre à jour la pagination
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationElement = document.getElementById('pagination');
    
    if (pagination.totalPages <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    paginationInfo.textContent = `Page ${pagination.currentPage} sur ${pagination.totalPages} (${pagination.total} PV au total)`;
    
    // Générer les liens de pagination
    let paginationHTML = '';
    
    // Bouton précédent
    if (pagination.currentPage > 1) {
        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.currentPage - 1})">Précédent</a></li>`;
    }
    
    // Pages
    for (let i = 1; i <= pagination.totalPages; i++) {
        const active = i === pagination.currentPage ? 'active' : '';
        paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
    }
    
    // Bouton suivant
    if (pagination.currentPage < pagination.totalPages) {
        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.currentPage + 1})">Suivant</a></li>`;
    }
    
    paginationElement.innerHTML = paginationHTML;
}

// Changer de page
function changePage(page) {
    currentPage = page;
    loadPVData();
}

// Mettre à jour les statistiques
function updateStatistics(statistics) {
    if (!statistics) return;
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? 0; };
    set('totalCount', statistics.total);
    set('statTotal', statistics.total);
    set('statEnAttente', statistics.enAttente);
    set('statEnCours', statistics.enCours);
    set('statTraites', statistics.traites);
}

// Formater le statut
function formatStatut(statut) {
    const statuts = {
        'en_attente': '<span class="badge bg-warning">En attente</span>',
        'en_cours': '<span class="badge bg-info">En cours</span>',
        'traite': '<span class="badge bg-success">Traité</span>',
        'archive': '<span class="badge bg-secondary">Archivé</span>'
    };
    return statuts[statut] || '<span class="badge bg-secondary">Inconnu</span>';
}

// Voir les détails
function viewDetail(id) {
    fetch(`denonciation.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv) {
                displayDetail(pv);
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                modal.show();
            } else {
                showAlert('PV non trouvé', 'warning');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur lors du chargement des détails', 'danger');
        });
}

// Afficher les détails
function displayDetail(pv) {
    const content = document.getElementById('detailContent');
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informations du dénonciateur</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="fw-bold" style="width:40%">Nom</td><td>${pv.denonciateur_nom || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Prénoms</td><td>${pv.denonciateur_prenoms || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Téléphone</td><td>${pv.denonciateur_telephone || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Email</td><td>${pv.denonciateur_email || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Adresse</td><td>${pv.denonciateur_adresse || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Anonyme</td><td>${pv.denonciateur_anonyme == 1 ? '<span class="badge bg-warning">Oui</span>' : '<span class="badge bg-secondary">Non</span>'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations système</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="fw-bold" style="width:40%">Numéro PV</td><td><span class="badge bg-dark">${pv.numero_pv || 'N/A'}</span></td></tr>
                            <tr><td class="fw-bold">Statut</td><td>${formatStatut(pv.statut)}</td></tr>
                            <tr><td class="fw-bold">Date dénonciation</td><td>${pv.date_denonciation || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Date création</td><td>${pv.created_at || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Dernière modif.</td><td>${pv.updated_at || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Agent</td><td>${pv.agent_nom ? pv.agent_nom + ' ' + pv.agent_prenoms : 'N/A'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Détails de la dénonciation</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="fw-bold" style="width:40%">Type</td><td>${formatTypeDenonciation(pv.type_denonciation)}</td></tr>
                            <tr><td class="fw-bold">Date des faits</td><td>${pv.date_faits || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Lieu des faits</td><td>${pv.lieu_faits || 'N/A'}</td></tr>
                        </table>
                        <hr class="my-2">
                        <p class="fw-bold mb-1">Motif :</p>
                        <p class="mb-2" style="white-space: pre-wrap;">${pv.motif_denonciation || 'N/A'}</p>
                        <p class="fw-bold mb-1">Description :</p>
                        <p class="mb-0" style="white-space: pre-wrap;">${pv.description_denonciation || 'N/A'}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Étudiant concerné</h6>
                    </div>
                    <div class="card-body">
                        ${pv.etudiant_nom ? `
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="fw-bold" style="width:40%">Nom complet</td><td>${pv.etudiant_nom || ''} ${pv.etudiant_prenoms || ''}</td></tr>
                            <tr><td class="fw-bold">N° Étudiant</td><td>${pv.etudiant_carte || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Établissement</td><td>${pv.etudiant_etablissement || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Département</td><td>${pv.etudiant_departement || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Niveau</td><td>${pv.etudiant_niveau || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Type</td><td>${pv.etudiant_type || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Régime</td><td>${pv.etudiant_regime || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Email perso</td><td>${pv.etudiant_email || 'N/A'}</td></tr>
                            <tr><td class="fw-bold">Email UCAD</td><td>${pv.etudiant_email_ucad || 'N/A'}</td></tr>
                        </table>
                        ` : '<p class="text-muted mb-0">Aucun étudiant associé</p>'}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card mb-0">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Preuves / Pièces jointes</h6>
                    </div>
                    <div class="card-body">
                        ${pv.preuves && pv.preuves.length > 0 ? `
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Fichier</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${pv.preuves.map((p, i) => `
                                    <tr>
                                        <td>${i + 1}</td>
                                        <td>${formatTypePreuve(p.type_preuve)}</td>
                                        <td>${p.description_preuve || '-'}</td>
                                        <td>${p.chemin_fichier ? '<a href="../../' + p.chemin_fichier + '" target="_blank" class="text-decoration-none"><i class="fas fa-paperclip me-1"></i>' + p.chemin_fichier.split('/').pop() + '</a>' : '-'}</td>
                                        <td>${p.date_preuve || '-'}</td>
                                    </tr>`).join('')}
                                </tbody>
                            </table>
                        </div>` : '<p class="text-muted mb-0">Aucune preuve enregistrée</p>'}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Rechercher un étudiant
function searchEtudiant(prefix) {
    const query = document.getElementById(prefix + 'IdEtudiant').value.trim();
    if (!query || query.length < 2) {
        showAlert('Veuillez saisir au moins 2 caractères pour rechercher', 'warning');
        return;
    }

    const resultsDiv = document.getElementById(prefix + 'EtudiantResults');
    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin me-1"></i>Recherche...</div>';

    fetch(`denonciation.php?action=search_etudiant&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.etudiants.length > 0) {
                resultsDiv.innerHTML = data.etudiants.map(etu => `
                    <button type="button" class="list-group-item list-group-item-action py-1 px-2"
                        onclick='selectEtudiant("${prefix}", ${JSON.stringify(etu).replace(/'/g, "&#39;")})'>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${etu.nom} ${etu.prenoms}</strong>
                                <small class="text-muted ms-2">${etu.num_etu}</small>
                            </div>
                            <small class="text-muted">${etu.etablissement || ''} - ${etu.departement || ''}</small>
                        </div>
                    </button>
                `).join('');
            } else {
                resultsDiv.innerHTML = '<div class="list-group-item text-muted text-center">Aucun étudiant trouvé</div>';
            }
        })
        .catch(error => {
            console.error('Erreur recherche étudiant:', error);
            resultsDiv.innerHTML = '<div class="list-group-item text-danger text-center">Erreur de recherche</div>';
        });
}

// Sélectionner un étudiant trouvé
function selectEtudiant(prefix, etu) {
    // Remplir le champ avec le num_etu
    document.getElementById(prefix + 'IdEtudiant').value = etu.num_etu;

    // Masquer les résultats
    document.getElementById(prefix + 'EtudiantResults').style.display = 'none';

    // Afficher la preview
    const preview = document.getElementById(prefix + 'EtudiantPreview');
    preview.style.display = 'block';

    document.getElementById(prefix + 'EtuNom').textContent = (etu.nom || '') + ' ' + (etu.prenoms || '');
    document.getElementById(prefix + 'EtuNum').textContent = etu.num_etu || '-';
    document.getElementById(prefix + 'EtuType').textContent = etu.typeEtudiant || '-';
    document.getElementById(prefix + 'EtuEtablissement').textContent = etu.etablissement || '-';
    document.getElementById(prefix + 'EtuDepartement').textContent = etu.departement || '-';
    document.getElementById(prefix + 'EtuNiveau').textContent = etu.niveauFormation || '-';
}

// Effacer l'étudiant sélectionné
function clearEtudiant(prefix) {
    document.getElementById(prefix + 'IdEtudiant').value = '';
    document.getElementById(prefix + 'EtudiantResults').style.display = 'none';
    document.getElementById(prefix + 'EtudiantPreview').style.display = 'none';
}

// Formater le type de dénonciation
function formatTypeDenonciation(type) {
    const types = {
        'violence': 'Violence',
        'harcelement': 'Harcèlement',
        'diffamation': 'Diffamation',
        'vol': 'Vol',
        'fraude': 'Fraude',
        'autre': 'Autre'
    };
    return types[type] || type || 'N/A';
}

// Modifier un PV - charger les données dans le formulaire d'édition
function editPV(id) {
    fetch(`denonciation.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv && pv.id) {
                fillEditForm(pv);
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            } else {
                showAlert('PV non trouvé', 'warning');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur lors du chargement du PV', 'danger');
        });
}

// Remplir le formulaire d'édition avec les données du PV
function fillEditForm(pv) {
    document.getElementById('editId').value = pv.id || '';
    document.getElementById('editDenonciateurNom').value = pv.denonciateur_nom || '';
    document.getElementById('editDenonciateurPrenoms').value = pv.denonciateur_prenoms || '';
    document.getElementById('editDenonciateurTelephone').value = pv.denonciateur_telephone || '';
    document.getElementById('editDenonciateurEmail').value = pv.denonciateur_email || '';
    document.getElementById('editDenonciateurAdresse').value = pv.denonciateur_adresse || '';
    document.getElementById('editDenonciateurAnonyme').checked = pv.denonciateur_anonyme == 1;
    document.getElementById('editTypeDenonciation').value = pv.type_denonciation || '';
    document.getElementById('editMotifDenonciation').value = pv.motif_denonciation || '';
    document.getElementById('editDescriptionDenonciation').value = pv.description_denonciation || '';
    document.getElementById('editDateDenonciation').value = pv.date_denonciation || '';
    document.getElementById('editDateFaits').value = pv.date_faits || '';
    document.getElementById('editLieuFaits').value = pv.lieu_faits || '';
    document.getElementById('editIdEtudiant').value = pv.etudiant_carte || pv.id_etudiant || '';
    document.getElementById('editStatut').value = pv.statut || 'en_attente';

    // Afficher la preview étudiant si un étudiant est lié
    if (pv.etudiant_nom) {
        const preview = document.getElementById('editEtudiantPreview');
        preview.style.display = 'block';
        document.getElementById('editEtuNom').textContent = (pv.etudiant_nom || '') + ' ' + (pv.etudiant_prenoms || '');
        document.getElementById('editEtuNum').textContent = pv.etudiant_carte || '-';
        document.getElementById('editEtuType').textContent = pv.etudiant_type || '-';
        document.getElementById('editEtuEtablissement').textContent = pv.etudiant_etablissement || '-';
        document.getElementById('editEtuDepartement').textContent = pv.etudiant_departement || '-';
        document.getElementById('editEtuNiveau').textContent = pv.etudiant_niveau || '-';
    } else {
        document.getElementById('editEtudiantPreview').style.display = 'none';
    }

    // Charger les preuves existantes
    const editPreuvesContainer = document.getElementById('editPreuvesContainer');
    editPreuvesContainer.innerHTML = '';
    if (pv.preuves && pv.preuves.length > 0) {
        pv.preuves.forEach(preuve => {
            addPreuveRow('edit', preuve);
        });
    }
}

// Afficher la modale de confirmation de modification
function showConfirmEditModal() {
    const modal = new bootstrap.Modal(document.getElementById('confirmEditModal'));
    modal.show();
}

// Confirmer et mettre à jour un PV
function confirmUpdatePV() {
    const pvId = document.getElementById('editId').value;

    if (!pvId) {
        showAlert('ID du PV non trouvé', 'danger');
        return;
    }

    // Fermer la modale de confirmation
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmEditModal'));
    if (confirmModal) confirmModal.hide();

    const data = {
        action: 'update',
        id: pvId,
        denonciateur_nom: document.getElementById('editDenonciateurNom').value,
        denonciateur_prenoms: document.getElementById('editDenonciateurPrenoms').value,
        denonciateur_telephone: document.getElementById('editDenonciateurTelephone').value,
        denonciateur_email: document.getElementById('editDenonciateurEmail').value,
        denonciateur_adresse: document.getElementById('editDenonciateurAdresse').value,
        denonciateur_anonyme: document.getElementById('editDenonciateurAnonyme').checked ? 1 : 0,
        idEtudiant: document.getElementById('editIdEtudiant').value || null,
        type_denonciation: document.getElementById('editTypeDenonciation').value,
        motif_denonciation: document.getElementById('editMotifDenonciation').value,
        description_denonciation: document.getElementById('editDescriptionDenonciation').value,
        date_denonciation: document.getElementById('editDateDenonciation').value,
        date_faits: document.getElementById('editDateFaits').value || null,
        lieu_faits: document.getElementById('editLieuFaits').value,
        statut: document.getElementById('editStatut').value,
        preuves: collectPreuves('edit')
    };

    fetch('denonciation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            if (editModal) editModal.hide();

            const successModal = new bootstrap.Modal(document.getElementById('successEditModal'));
            successModal.show();

            loadPVData();
        } else {
            showAlert(result.message || 'Erreur lors de la mise à jour', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'danger');
    });
}

// Supprimer un PV - ouvrir la modale de confirmation
function deletePV(id) {
    fetch(`denonciation.php?action=detail&id=${id}`)
        .then(response => response.json())
        .then(pv => {
            if (pv && pv.id) {
                document.getElementById('deletePvName').textContent = (pv.denonciateur_nom || '') + ' ' + (pv.denonciateur_prenoms || '');
                document.getElementById('deletePvType').textContent = pv.type_denonciation || 'N/A';
                window.deletePvId = id;

                const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur lors du chargement des informations', 'danger');
        });
}

// Confirmer la suppression
function confirmDeletePV() {
    if (!window.deletePvId) return;

    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Suppression...';

    fetch('denonciation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: window.deletePvId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
            if (confirmModal) confirmModal.hide();

            const successModal = new bootstrap.Modal(document.getElementById('successDeleteModal'));
            successModal.show();

            loadPVData();
        } else {
            showAlert(result.message || 'Erreur lors de la suppression', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur lors de la suppression', 'danger');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash me-1"></i>Supprimer';
    });
}

// Filtrer les données
function filterTable() {
    loadPVData();
}

// Changer le nombre d'éléments par page
function changeItemsPerPage() {
    const select = document.getElementById('itemsPerPage');
    itemsPerPage = parseInt(select.value);
    currentPage = 1;
    loadPVData();
}

// Afficher une alerte
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Formater le type de preuve
function formatTypePreuve(type) {
    const types = {
        'document': 'Document',
        'photo': 'Photo',
        'video': 'Vidéo',
        'audio': 'Audio',
        'temoignage': 'Témoignage',
        'autre': 'Autre'
    };
    return types[type] || type || '-';
}

// Ajouter une ligne de preuve dans le formulaire (add ou edit)
function addPreuveRow(prefix, data = null) {
    const container = document.getElementById(prefix + 'PreuvesContainer');
    const index = container.querySelectorAll('.preuve-row').length;

    const row = document.createElement('div');
    row.className = 'preuve-row border rounded p-3 mb-2 position-relative';

    const hasFile = data && data.chemin_fichier;
    row.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top: 5px; right: 5px;" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label">Type de preuve</label>
                <select class="form-select form-select-sm preuve-type">
                    <option value="">-- Sélectionner --</option>
                    <option value="document" ${data && data.type_preuve === 'document' ? 'selected' : ''}>Document</option>
                    <option value="photo" ${data && data.type_preuve === 'photo' ? 'selected' : ''}>Photo</option>
                    <option value="video" ${data && data.type_preuve === 'video' ? 'selected' : ''}>Vidéo</option>
                    <option value="audio" ${data && data.type_preuve === 'audio' ? 'selected' : ''}>Audio</option>
                    <option value="temoignage" ${data && data.type_preuve === 'temoignage' ? 'selected' : ''}>Témoignage</option>
                    <option value="autre" ${data && data.type_preuve === 'autre' ? 'selected' : ''}>Autre</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Description</label>
                <input type="text" class="form-control form-control-sm preuve-description" placeholder="Description de la preuve" value="${data ? (data.description_preuve || '') : ''}">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Fichier</label>
                <input type="hidden" class="preuve-chemin" value="${hasFile ? data.chemin_fichier : ''}">
                ${hasFile ? `
                <div class="preuve-fichier-info">
                    <span class="badge bg-success me-1"><i class="fas fa-check"></i></span>
                    <small class="text-success">${data.chemin_fichier.split('/').pop()}</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="resetFileInput(this)">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <input type="file" class="form-control form-control-sm preuve-file mt-1 d-none" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp4,.avi,.mp3,.wav,.doc,.docx,.xls,.xlsx,.txt">
                ` : `
                <input type="file" class="form-control form-control-sm preuve-file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp4,.avi,.mp3,.wav,.doc,.docx,.xls,.xlsx,.txt">
                `}
                <div class="preuve-upload-status mt-1" style="display:none;"></div>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Date</label>
                <input type="date" class="form-control form-control-sm preuve-date" value="${data ? (data.date_preuve || '') : ''}">
            </div>
        </div>
    `;
    container.appendChild(row);

    // Ajouter l'événement d'upload sur le file input
    const fileInput = row.querySelector('.preuve-file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            uploadPreuveFile(this);
        });
    }
}

// Réinitialiser le fichier pour en choisir un nouveau
function resetFileInput(btn) {
    const container = btn.closest('.col-md-4');
    const infoDiv = container.querySelector('.preuve-fichier-info');
    const fileInput = container.querySelector('.preuve-file');
    if (infoDiv) infoDiv.remove();
    if (fileInput) fileInput.classList.remove('d-none');
}

// Uploader un fichier de preuve
function uploadPreuveFile(fileInput) {
    const file = fileInput.files[0];
    if (!file) return;

    const row = fileInput.closest('.preuve-row');
    const statusDiv = row.querySelector('.preuve-upload-status');
    const cheminInput = row.querySelector('.preuve-chemin');

    // Vérifier la taille (10 Mo max)
    if (file.size > 10 * 1024 * 1024) {
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Fichier trop volumineux (max 10 Mo)</small>';
        fileInput.value = '';
        return;
    }

    // Afficher le statut d'upload
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<small class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...</small>';
    fileInput.disabled = true;

    const formData = new FormData();
    formData.append('action', 'upload_preuve');
    formData.append('fichier_preuve', file);

    fetch('denonciation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            cheminInput.value = result.chemin;
            statusDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>' + result.nom_original + '</small>';
        } else {
            statusDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + (result.message || 'Erreur upload') + '</small>';
            fileInput.value = '';
        }
    })
    .catch(error => {
        console.error('Erreur upload:', error);
        statusDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur de connexion</small>';
        fileInput.value = '';
    })
    .finally(() => {
        fileInput.disabled = false;
    });
}

// Collecter les preuves d'un conteneur
function collectPreuves(prefix) {
    const container = document.getElementById(prefix + 'PreuvesContainer');
    const rows = container.querySelectorAll('.preuve-row');
    const preuves = [];

    rows.forEach(row => {
        const type = row.querySelector('.preuve-type')?.value || '';
        const description = row.querySelector('.preuve-description')?.value || '';
        const chemin = row.querySelector('.preuve-chemin')?.value || '';
        const date = row.querySelector('.preuve-date')?.value || '';

        // N'ajouter que si au moins un champ est rempli
        if (type || description || chemin) {
            preuves.push({
                type_preuve: type,
                description_preuve: description,
                chemin_fichier: chemin,
                date_preuve: date || null
            });
        }
    });

    return preuves;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données depuis la base de données
    loadPVData();

    // Initialiser les événements
    document.getElementById('searchInput')?.addEventListener('input', filterTable);
    document.getElementById('filterMotif')?.addEventListener('change', filterTable);
    document.getElementById('filterStatus')?.addEventListener('change', filterTable);
    document.getElementById('itemsPerPage')?.addEventListener('change', changeItemsPerPage);

    // Bouton de confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', confirmDeletePV);
    }

    // Bouton de confirmation de modification
    const confirmEditBtn = document.getElementById('confirmEditBtn');
    if (confirmEditBtn) {
        confirmEditBtn.addEventListener('click', confirmUpdatePV);
    }
});
