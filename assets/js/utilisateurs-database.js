// Variables globales
let currentPage = 1;
let itemsPerPage = 10;
let searchQuery = '';
let roleFilter = '';
let statutFilter = '';
let utilisateursData = [];
let pagination = {};
let statistics = {};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadUtilisateursData();
    setupEventListeners();
    initializeFilters();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
    
    // Filtres
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', handleFilters);
    }
    
    const statutFilter = document.getElementById('statutFilter');
    if (statutFilter) {
        statutFilter.addEventListener('change', handleFilters);
    }
    
    // Pagination
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', changeItemsPerPage);
    }
    
    // Formulaire d'ajout
    const addForm = document.getElementById('addForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddUtilisateur);
    }
    
    // Formulaire de modification
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', handleUpdateUtilisateur);
    }
    
    // Toggle mot de passe
    setupPasswordToggles();
}

// Initialisation des filtres
function initializeFilters() {
    // Charger les rôles disponibles
    loadRoles();
    
    // Charger les statuts disponibles
    loadStatuts();
}

// Charger les données des utilisateurs
function loadUtilisateursData() {
    showLoading();
    
    const params = new URLSearchParams({
        ajax: '1',
        page: currentPage,
        itemsPerPage: itemsPerPage,
        search: searchQuery,
        role: roleFilter,
        statut: statutFilter
    });
    
    fetch(`utilisateurs.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                utilisateursData = data.utilisateurs || [];
                pagination = data.pagination || {};
                statistics = data.statistics || {};
                
                updateTable();
                updatePagination();
                updateStatistics();
                hideLoading();
            } else {
                showError('Erreur lors du chargement des données');
                hideLoading();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Erreur de connexion au serveur');
            hideLoading();
        });
}

// Mettre à jour le tableau des utilisateurs
function updateTable() {
    const tbody = document.getElementById('utilisateursTableBody');
    if (!tbody) return;
    
    if (utilisateursData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-users empty-icon"></i>
                        <h5>Aucun utilisateur trouvé</h5>
                        <p>Aucun utilisateur ne correspond aux critères de recherche.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = utilisateursData.map(utilisateur => `
        <tr>
            <td>${utilisateur.matricule || '-'}</td>
            <td class="fw-semibold">${utilisateur.nom} ${utilisateur.prenoms}</td>
            <td>
                <div>
                    <div>${utilisateur.email}</div>
                    <small class="text-muted">${utilisateur.telephone || 'N/A'}</small>
                </div>
            </td>
            <td>
                <span class="role-badge ${utilisateur.role}">${utilisateur.role}</span>
            </td>
            <td>
                <span class="statut-badge ${utilisateur.statut}">${utilisateur.statut}</span>
            </td>
            <td>
                <div class="user-actions">
                    <button class="btn btn-sm btn-primary" onclick="viewUtilisateur(${utilisateur.id})" title="Voir">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editUtilisateur(${utilisateur.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm ${utilisateur.statut === 'actif' ? 'btn-secondary' : 'btn-success'}" 
                            onclick="toggleStatut(${utilisateur.id}, '${utilisateur.statut === 'actif' ? 'inactif' : 'actif'}')" 
                            title="${utilisateur.statut === 'actif' ? 'Désactiver' : 'Activer'}">
                        <i class="fas fa-${utilisateur.statut === 'actif' ? 'pause' : 'play'}"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="resetPassword(${utilisateur.id})" title="Réinitialiser mot de passe">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(${utilisateur.id})" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Mettre à jour la pagination
function updatePagination() {
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationElement = document.getElementById('pagination');
    
    if (!paginationContainer || !paginationElement) return;
    
    if (pagination.totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    // Informations de pagination
    if (paginationInfo) {
        const start = (pagination.currentPage - 1) * pagination.itemsPerPage + 1;
        const end = Math.min(pagination.currentPage * pagination.itemsPerPage, pagination.total);
        paginationInfo.textContent = `Affichage de ${start} à ${end} sur ${pagination.total} utilisateurs`;
    }
    
    // Générer les liens de pagination
    let paginationHTML = '';
    
    // Bouton précédent
    paginationHTML += `
        <li class="page-item ${pagination.currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${pagination.currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Pages numérotées
    const maxVisiblePages = 5;
    let startPage = Math.max(1, pagination.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(pagination.totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `;
    }
    
    // Bouton suivant
    paginationHTML += `
        <li class="page-item ${pagination.currentPage === pagination.totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${pagination.currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    paginationElement.innerHTML = paginationHTML;
}

// Mettre à jour les statistiques
function updateStatistics() {
    // Mettre à jour le total
    const totalCount = document.getElementById('totalCount');
    if (totalCount) {
        totalCount.textContent = pagination.total || 0;
    }
    
    // Mettre à jour les cartes de statistiques
    if (statistics) {
        const statActifs = document.getElementById('statActifs');
        const statInactifs = document.getElementById('statInactifs');
        
        if (statActifs) statActifs.textContent = statistics.actifs || 0;
        if (statInactifs) statInactifs.textContent = statistics.inactifs || 0;
        
        // Mettre à jour les statistiques par rôle
        updateRoleStatistics(statistics.par_role || []);
    }
}

// Mettre à jour les statistiques par rôle
function updateRoleStatistics(roleStats) {
    const roleStatsContainer = document.getElementById('roleStatsContainer');
    if (!roleStatsContainer) return;
    
    roleStatsContainer.innerHTML = roleStats.map(stat => `
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-icon ${getRoleColor(stat.role)}">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>${stat.count}</h3>
                <p>${stat.role}</p>
            </div>
        </div>
    `).join('');
}

// Gérer la recherche
function handleSearch(event) {
    searchQuery = event.target.value;
    currentPage = 1;
    loadUtilisateursData();
}

// Gérer les filtres
function handleFilters() {
    const roleFilterElement = document.getElementById('roleFilter');
    const statutFilterElement = document.getElementById('statutFilter');
    
    roleFilter = roleFilterElement ? roleFilterElement.value : '';
    statutFilter = statutFilterElement ? statutFilterElement.value : '';
    
    currentPage = 1;
    loadUtilisateursData();
}

// Changer de page
function changePage(page) {
    if (page < 1 || page > pagination.totalPages) return;
    
    currentPage = page;
    loadUtilisateursData();
}

// Changer le nombre d'éléments par page
function changeItemsPerPage() {
    const select = document.getElementById('itemsPerPage');
    if (!select) return;
    
    itemsPerPage = parseInt(select.value);
    currentPage = 1;
    loadUtilisateursData();
}

// Voir un utilisateur
function viewUtilisateur(id) {
    const utilisateur = utilisateursData.find(u => u.id === id);
    if (!utilisateur) return;
    
    // Remplir les champs du modal de vue
    document.getElementById('viewMatricule').textContent = utilisateur.matricule;
    document.getElementById('viewNom').textContent = utilisateur.nom;
    document.getElementById('viewPrenoms').textContent = utilisateur.prenoms;
    document.getElementById('viewEmail').textContent = utilisateur.email;
    document.getElementById('viewTelephone').textContent = utilisateur.telephone || 'N/A';
    
    // Afficher le rôle avec un badge
    const roleElement = document.getElementById('viewRole');
    roleElement.innerHTML = `<span class="badge bg-${getRoleColor(utilisateur.role)}">${utilisateur.role}</span>`;
    
    // Afficher le statut avec un badge
    const statutElement = document.getElementById('viewStatut');
    const statutClass = utilisateur.statut === 'actif' ? 'success' : 'danger';
    statutElement.innerHTML = `<span class="badge bg-${statutClass}">${utilisateur.statut}</span>`;
    
    document.getElementById('viewCreatedAt').textContent = formatDate(utilisateur.created_at);
    document.getElementById('viewUpdatedAt').textContent = formatDate(utilisateur.updated_at) || 'N/A';
    
    // Afficher les statistiques d'activités
    document.getElementById('viewNbFaux').textContent = utilisateur.nb_faux || 0;
    document.getElementById('viewNbConstat').textContent = utilisateur.nb_constat || 0;
    document.getElementById('viewNbDenonciation').textContent = utilisateur.nb_denonciation || 0;
    
    // Mettre à jour l'avatar
    const avatarElement = document.getElementById('viewAvatar');
    avatarElement.textContent = getInitials(utilisateur.nom, utilisateur.prenoms);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}

// Modifier un utilisateur
function editUtilisateur(id) {
    const utilisateur = utilisateursData.find(u => u.id === id);
    if (!utilisateur) return;
    
    // Remplir les champs du formulaire
    document.getElementById('editId').value = utilisateur.id;
    document.getElementById('editMatricule').value = utilisateur.matricule;
    document.getElementById('editNom').value = utilisateur.nom;
    document.getElementById('editPrenoms').value = utilisateur.prenoms;
    document.getElementById('editEmail').value = utilisateur.email;
    document.getElementById('editTelephone').value = utilisateur.telephone || '';
    document.getElementById('editRole').value = utilisateur.role;
    document.getElementById('editStatut').value = utilisateur.statut;
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

// Gérer l'ajout d'utilisateur
function handleAddUtilisateur(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        action: 'create',
        matricule: formData.get('matricule'),
        nom: formData.get('nom'),
        prenoms: formData.get('prenoms'),
        email: formData.get('email'),
        telephone: formData.get('telephone'),
        role: formData.get('role'),
        statut: formData.get('statut'),
        mot_de_passe: formData.get('mot_de_passe')
    };
    
    // Validation côté client
    const errors = validateUtilisateurForm(data);
    if (errors.length > 0) {
        showErrors(errors);
        return;
    }
    
    // Envoyer les données
    fetch('utilisateurs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Utilisateur créé avec succès');
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            event.target.reset();
            loadUtilisateursData();
        } else {
            showErrors(result.errors || [result.message]);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Erreur lors de la création de l\'utilisateur');
    });
}

// Gérer la mise à jour d'utilisateur
function handleUpdateUtilisateur(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        action: 'update',
        id: formData.get('id'),
        matricule: formData.get('matricule'),
        nom: formData.get('nom'),
        prenoms: formData.get('prenoms'),
        email: formData.get('email'),
        telephone: formData.get('telephone'),
        role: formData.get('role'),
        statut: formData.get('statut')
    };
    
    // Pas de gestion du mot de passe (fixé à COUD)
    
    // Validation côté client
    const errors = validateUtilisateurForm(data, true);
    if (errors.length > 0) {
        showErrors(errors);
        return;
    }
    
    // Envoyer les données
    fetch('utilisateurs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Utilisateur mis à jour avec succès');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadUtilisateursData();
        } else {
            showErrors(result.errors || [result.message]);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Erreur lors de la mise à jour de l\'utilisateur');
    });
}

// Basculer le statut d'un utilisateur - avec modal
function toggleStatut(id, newStatut) {
    var utilisateur = utilisateursData.find(function(u) { return u.id === id; });
    if (!utilisateur) return;

    var action = newStatut === 'actif' ? 'activer' : 'désactiver';
    var messageEl = document.getElementById('toggleStatutModalMessage');
    messageEl.innerHTML = 'Êtes-vous sûr de vouloir <strong>' + action + '</strong> l\'utilisateur <strong>' +
        escapeHtml(utilisateur.nom + ' ' + utilisateur.prenoms) + '</strong> ?';

    var headerEl = document.getElementById('toggleStatutModalHeader');
    if (newStatut === 'actif') {
        headerEl.className = 'modal-header bg-success text-white';
    } else {
        headerEl.className = 'modal-header bg-secondary text-white';
    }

    var confirmBtn = document.getElementById('toggleStatutConfirmBtn');
    var modal = new bootstrap.Modal(document.getElementById('toggleStatutModal'));

    var newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', function() {
        modal.hide();
        executeToggleStatut(id, newStatut);
    });

    modal.show();
}

function executeToggleStatut(id, newStatut) {
    fetch('utilisateurs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_statut', id: id, statut: newStatut })
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            showSuccess('Utilisateur ' + (newStatut === 'actif' ? 'activé' : 'désactivé') + ' avec succès');
            loadUtilisateursData();
        } else {
            showError(result.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        showError('Erreur lors de la modification du statut');
    });
}

// Réinitialiser le mot de passe - avec modal
function resetPassword(id) {
    var utilisateur = utilisateursData.find(function(u) { return u.id === id; });
    if (!utilisateur) return;

    var messageEl = document.getElementById('resetPasswordModalMessage');
    messageEl.innerHTML = 'Êtes-vous sûr de vouloir réinitialiser le mot de passe de <strong>' +
        escapeHtml(utilisateur.nom + ' ' + utilisateur.prenoms) + '</strong> ?<br>' +
        '<small class="text-muted">Le mot de passe sera remis à la valeur par défaut <strong>COUD</strong>.</small>';

    var confirmBtn = document.getElementById('resetPasswordConfirmBtn');
    var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));

    var newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', function() {
        modal.hide();
        executeResetPassword(id);
    });

    modal.show();
}

function executeResetPassword(id) {
    fetch('utilisateurs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reset_password', id: id })
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            showSuccess('Mot de passe réinitialisé avec succès ! Le nouveau mot de passe est : ' + result.password);
        } else {
            showError(result.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        showError('Erreur lors de la réinitialisation du mot de passe');
    });
}

// Confirmer la suppression avec modal Bootstrap
function confirmDelete(id) {
    const utilisateur = utilisateursData.find(u => u.id === id);
    if (!utilisateur) return;

    // Vérifier si l'utilisateur a des PV associés
    const totalPV = (utilisateur.nb_faux || 0) + (utilisateur.nb_constat || 0) + (utilisateur.nb_denonciation || 0);

    if (totalPV > 0) {
        showError('Impossible de supprimer cet utilisateur car il a ' + totalPV + ' PV associés.');
        return;
    }

    // Afficher le modal de confirmation
    const messageEl = document.getElementById('deleteModalMessage');
    messageEl.innerHTML = 'Êtes-vous sûr de vouloir supprimer l\'utilisateur <strong>' +
        escapeHtml(utilisateur.nom + ' ' + utilisateur.prenoms) + '</strong> (' + escapeHtml(utilisateur.matricule) + ') ?';

    const confirmBtn = document.getElementById('deleteConfirmBtn');
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));

    // Supprimer l'ancien listener pour éviter les doublons
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener('click', function() {
        modal.hide();
        deleteUtilisateur(id);
    });

    modal.show();
}

// Exécuter la suppression
function deleteUtilisateur(id) {
    fetch('utilisateurs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'delete', id: id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Utilisateur supprimé avec succès');
            loadUtilisateursData();
        } else {
            showError(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Erreur lors de la suppression de l\'utilisateur');
    });
}

// Échapper le HTML pour éviter les injections
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Validation du formulaire utilisateur
function validateUtilisateurForm(data) {
    const errors = [];
    
    // Champ obligatoires
    if (!data.matricule || data.matricule.trim() === '') {
        errors.push('Le matricule est obligatoire');
    }
    
    if (!data.nom || data.nom.trim() === '') {
        errors.push('Le nom est obligatoire');
    }
    
    if (!data.prenoms || data.prenoms.trim() === '') {
        errors.push('Les prénoms sont obligatoires');
    }
    
    if (!data.email || data.email.trim() === '') {
        errors.push('L\'email est obligatoire');
    } else if (!isValidEmail(data.email)) {
        errors.push('L\'email n\'est pas valide');
    }
    
    // Validation du téléphone si fourni
    if (data.telephone && data.telephone.trim() !== '') {
        if (!isValidPhone(data.telephone)) {
            errors.push('Le numéro de téléphone doit contenir exactement 9 chiffres');
        }
    }
    
    // Pas de validation du mot de passe pour la modification (fixé à COUD)
    
    return errors;
}

// Configuration des toggles de mot de passe
function setupPasswordToggles() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Charger les rôles disponibles
function loadRoles() {
    const roles = ['admin', 'superviseur', 'agent', 'operateur'];
    const roleFilter = document.getElementById('roleFilter');
    const addRole = document.getElementById('addRole');
    const editRole = document.getElementById('editRole');
    
    if (roleFilter) {
        roleFilter.innerHTML = '<option value="">Tous les rôles</option>' + 
            roles.map(role => `<option value="${role}">${role}</option>`).join('');
    }
    
    if (addRole) {
        addRole.innerHTML = roles.map(role => `<option value="${role}">${role}</option>`).join('');
    }
    
    if (editRole) {
        editRole.innerHTML = roles.map(role => `<option value="${role}">${role}</option>`).join('');
    }
}

// Charger les statuts disponibles
function loadStatuts() {
    const statuts = ['actif', 'inactif'];
    const statutFilter = document.getElementById('statutFilter');
    const addStatut = document.getElementById('addStatut');
    const editStatut = document.getElementById('editStatut');
    
    if (statutFilter) {
        statutFilter.innerHTML = '<option value="">Tous les statuts</option>' + 
            statuts.map(statut => `<option value="${statut}">${statut}</option>`).join('');
    }
    
    if (addStatut) {
        addStatut.innerHTML = statuts.map(statut => `<option value="${statut}">${statut}</option>`).join('');
        addStatut.value = 'actif'; // Valeur par défaut
    }
    
    if (editStatut) {
        editStatut.innerHTML = statuts.map(statut => `<option value="${statut}">${statut}</option>`).join('');
    }
}

// Fonctions utilitaires
function getInitials(nom, prenoms) {
    return (nom.charAt(0) + prenoms.charAt(0)).toUpperCase();
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[0-9]{9}$/.test(phone);
}

function getRoleColor(role) {
    const colors = {
        'admin': 'danger',
        'superviseur': 'warning',
        'agent': 'primary',
        'operateur': 'success'
    };
    return colors[role] || 'primary';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Messages
function showSuccess(message) {
    // Afficher un message de succès
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

function showError(message) {
    // Afficher un message d'erreur
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

function showErrors(errors) {
    // Afficher plusieurs erreurs
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Erreurs:</strong><br>
        ${errors.join('<br>')}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

function showLoading() {
    // Afficher un indicateur de chargement
    const tbody = document.getElementById('utilisateursTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </td>
            </tr>
        `;
    }
}

function hideLoading() {
    // Le chargement sera masqué lors de la mise à jour du tableau
}
