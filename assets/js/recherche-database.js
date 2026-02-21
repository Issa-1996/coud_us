/**
 * Script de recherche d'étudiants résidents (base bdcodif)
 */

document.addEventListener('DOMContentLoaded', function () {
    var searchForm = document.getElementById('searchForm');
    var searchInput = document.getElementById('searchInput');

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var query = searchInput.value.trim();
            if (query.length >= 2) {
                rechercherEtudiants(query);
            } else {
                showError('Veuillez saisir au moins 2 caractères');
            }
        });
    }
});

function rechercherEtudiants(query) {
    showLoader();

    fetch('recherche.php?action=search_codif&search=' + encodeURIComponent(query))
        .then(function (response) {
            if (!response.ok) throw new Error('Erreur serveur');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                if (data.etudiants && data.etudiants.length > 0) {
                    displayResultsTable(data.etudiants, data.total);
                } else {
                    displayNotFound(query);
                }
            } else {
                showError(data.message || 'Erreur lors de la recherche');
            }
        })
        .catch(function () {
            showError('Erreur de connexion. Vérifiez votre réseau et réessayez.');
        })
        .finally(function () {
            hideLoader();
        });
}

function displayResultsTable(etudiants, total) {
    var container = document.getElementById('resultContainer');
    if (!container) return;

    // En-tête avec le nombre de résultats
    var header = document.createElement('div');
    header.className = 'd-flex justify-content-between align-items-center mb-3';
    header.innerHTML = '<h5 class="mb-0"><i class="fas fa-list me-2"></i>Résultats de la recherche</h5>' +
        '<span class="badge bg-primary fs-6">' + escHtml(String(total)) + ' étudiant(s) trouvé(s)</span>';

    // Tableau
    var tableWrapper = document.createElement('div');
    tableWrapper.className = 'table-responsive';

    var table = document.createElement('table');
    table.className = 'table table-hover align-middle';

    // Thead
    var thead = document.createElement('thead');
    thead.innerHTML = '<tr>' +
        '<th>#</th>' +
        '<th>N° Étudiant</th>' +
        '<th>Nom & Prénoms</th>' +
        '<th>Téléphone</th>' +
        '<th>Date Naissance</th>' +
        '<th>Sexe</th>' +
        '<th>Pavillon</th>' +
        '<th>Chambre</th>' +
        '<th>Lit</th>' +
        '<th>Statut</th>' +
        '</tr>';
    table.appendChild(thead);

    // Tbody
    var tbody = document.createElement('tbody');

    etudiants.forEach(function (etu, index) {
        var tr = document.createElement('tr');

        // #
        var tdNum = document.createElement('td');
        tdNum.textContent = index + 1;
        tr.appendChild(tdNum);

        // N° Étudiant
        var tdNumEtu = document.createElement('td');
        var badgeEtu = document.createElement('span');
        badgeEtu.className = 'badge bg-light text-dark border';
        badgeEtu.textContent = etu.num_etu || '-';
        tdNumEtu.appendChild(badgeEtu);
        tr.appendChild(tdNumEtu);

        // Nom & Prénoms
        var tdNom = document.createElement('td');
        var divNom = document.createElement('div');
        divNom.className = 'fw-bold';
        divNom.textContent = etu.nom || '';
        var smallPrenom = document.createElement('small');
        smallPrenom.className = 'text-muted';
        smallPrenom.textContent = etu.prenoms || '';
        tdNom.appendChild(divNom);
        tdNom.appendChild(smallPrenom);
        tr.appendChild(tdNom);

        // Téléphone
        var tdTel = document.createElement('td');
        tdTel.textContent = etu.telephone || '-';
        tr.appendChild(tdTel);

        // Date Naissance
        var tdDate = document.createElement('td');
        tdDate.textContent = etu.dateNaissance ? formatDate(etu.dateNaissance) : '-';
        tr.appendChild(tdDate);

        // Sexe
        var tdSexe = document.createElement('td');
        if (etu.sexe === 'M') {
            tdSexe.innerHTML = '<span class="badge bg-info">M</span>';
        } else if (etu.sexe === 'F') {
            tdSexe.innerHTML = '<span class="badge bg-pink" style="background-color:#e91e8e;">F</span>';
        } else {
            tdSexe.textContent = etu.sexe || '-';
        }
        tr.appendChild(tdSexe);

        // Pavillon
        var tdPav = document.createElement('td');
        tdPav.textContent = etu.pavillon || '-';
        tr.appendChild(tdPav);

        // Chambre
        var tdCh = document.createElement('td');
        tdCh.textContent = etu.chambre || '-';
        tr.appendChild(tdCh);

        // Lit
        var tdLit = document.createElement('td');
        tdLit.textContent = etu.lit || '-';
        tr.appendChild(tdLit);

        // Statut
        var tdStatut = document.createElement('td');
        var statutBadge = document.createElement('span');
        var statut = etu.statut || '';
        if (statut === 'actif' || statut === 'Actif') {
            statutBadge.className = 'badge bg-success';
            statutBadge.textContent = 'Actif';
        } else if (statut === 'inactif' || statut === 'Inactif') {
            statutBadge.className = 'badge bg-secondary';
            statutBadge.textContent = 'Inactif';
        } else {
            statutBadge.className = 'badge bg-secondary';
            statutBadge.textContent = statut || '-';
        }
        tdStatut.appendChild(statutBadge);
        tr.appendChild(tdStatut);

        tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    tableWrapper.appendChild(table);

    // Assembler
    container.innerHTML = '';
    container.appendChild(header);
    container.appendChild(tableWrapper);
}

function displayNotFound(query) {
    var container = document.getElementById('resultContainer');
    if (!container) return;

    container.innerHTML = '';

    var div = document.createElement('div');
    div.className = 'empty-state';

    var icon = document.createElement('i');
    icon.className = 'fas fa-user-times text-danger';
    div.appendChild(icon);

    var h5 = document.createElement('h5');
    h5.textContent = 'Aucun étudiant trouvé';
    div.appendChild(h5);

    var p = document.createElement('p');
    p.textContent = 'Aucun résultat pour la recherche effectuée.';
    div.appendChild(p);

    var small = document.createElement('small');
    small.className = 'text-muted';
    small.textContent = 'Terme recherché : ' + query;
    div.appendChild(small);

    container.appendChild(div);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    var date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('fr-FR');
}

function showLoader() {
    var loader = document.getElementById('loader');
    if (loader) loader.style.display = 'flex';
}

function hideLoader() {
    var loader = document.getElementById('loader');
    if (loader) loader.style.display = 'none';
}

function showError(message) {
    var alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';

    var icon = document.createElement('i');
    icon.className = 'fas fa-exclamation-triangle me-2';
    alert.appendChild(icon);

    alert.appendChild(document.createTextNode(message));

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    alert.appendChild(closeBtn);

    document.body.appendChild(alert);
    setTimeout(function () {
        if (alert.parentNode) alert.remove();
    }, 5000);
}

function escHtml(str) {
    if (!str && str !== 0) return '';
    var div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}
