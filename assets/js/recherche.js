const etudiants = [{
        id_etu: "ETU000123",
        etablissement: "Université Cheikh Anta Diop",
        departement: "Informatique",
        niveauFormation: "Licence",
        num_etu: "UCAD-INF-23-045",
        nom: "Ndiaye",
        prenoms: "Moussa Abdou",
        dateNaissance: "2002-05-14",
        lieuNaissance: "Dakar",
        sexe: "Masculin",
        nationalite: "Sénégalaise",
        numIdentite: "CNI123456789",
        typeEtudiant: "Régulier",
        moyenne: "14.50",
        sessionId: "2023-2024",
        niveau: "L3",
        email_perso: "moussa.ndiaye@gmail.com",
        email_ucad: "mndiaye@ucad.edu.sn",
        telephone: "+221 77 889 90 00",
        var: "Boursier"
    },
    {
        id_etu: "ETU000462",
        etablissement: "ESP",
        departement: "Génie Civil",
        niveauFormation: "Master",
        num_etu: "ESP-GC-22-188",
        nom: "Diop",
        prenoms: "Aminata",
        dateNaissance: "2001-11-02",
        lieuNaissance: "Thiès",
        sexe: "Féminin",
        nationalite: "Sénégalaise",
        numIdentite: "CNI987654321",
        typeEtudiant: "Régulier",
        moyenne: "15.10",
        sessionId: "2023-2024",
        niveau: "M1",
        email_perso: "aminata.diop@yahoo.fr",
        email_ucad: "adiop@esp.sn",
        telephone: "+221 76 123 45 67",
        var: "Excellence"
    },
    {
        id_etu: "ETU000781",
        etablissement: "FASTEF",
        departement: "Mathématiques",
        niveauFormation: "Licence",
        num_etu: "FASTEF-MATH-21-302",
        nom: "Fall",
        prenoms: "Cheikh Ahmad",
        dateNaissance: "2000-03-27",
        lieuNaissance: "Saint-Louis",
        sexe: "Masculin",
        nationalite: "Sénégalaise",
        numIdentite: "CNI555777999",
        typeEtudiant: "Privé",
        moyenne: "12.80",
        sessionId: "2022-2023",
        niveau: "L2",
        email_perso: "cheikh.fall@outlook.com",
        email_ucad: "cfall@fastef.sn",
        telephone: "+221 70 332 11 22",
        var: "Non logé"
    }
];

const form = document.getElementById('searchForm');
const input = document.getElementById('searchInput');
const resultContainer = document.getElementById('resultContainer');
const statTotal = document.getElementById('statTotal');
const statDerniere = document.getElementById('statDerniere');
const statResultats = document.getElementById('statResultats');

statTotal.textContent = etudiants.length;

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const query = input.value.trim();

    if (query.length === 0) {
        showEmptyState("Veuillez saisir un critère de recherche (nom, numéro étudiant, email...)");
        statResultats.textContent = 0;
        return;
    }

    statDerniere.textContent = query;

    const normalizedQuery = query.toLowerCase();
    const matches = etudiants.filter(etu => {
        return Object.values(etu).some(value =>
            String(value).toLowerCase().includes(normalizedQuery)
        );
    });

    statResultats.textContent = matches.length;

    if (matches.length === 0) {
        showEmptyState(`Aucun étudiant ne correspond au critère "${query}".`);
        return;
    }

    renderResults(matches);
});

function showEmptyState(message) {
    resultContainer.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h5>Étudiant introuvable</h5>
            <p>${message}</p>
            <p class="text-muted small">Astuce : essayez avec un autre identifiant ou vérifiez l'orthographe.</p>
        </div>
    `;
}

function renderResults(list) {
    resultContainer.innerHTML = list.map(etu => `
        <div class="detail-section">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="mb-1 text-primary"><i class="fas fa-user-graduate me-2"></i>${etu.nom.toUpperCase()} ${etu.prenoms}</h5>
                    <span class="badge bg-light text-dark me-2"><i class="fas fa-id-card me-1"></i>${etu.num_etu}</span>
                    <span class="badge bg-primary"><i class="fas fa-layer-group me-1"></i>${etu.departement}</span>
                </div>
                <span class="badge bg-success-subtle border border-success text-success">${etu.typeEtudiant}</span>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <p class="info-label mb-1">Identité</p>
                    <p class="info-value">
                        Né(e) le ${formatDate(etu.dateNaissance)} à ${etu.lieuNaissance}<br>
                        ${etu.sexe} - ${etu.nationalite}<br>
                        N° Identité : ${etu.numIdentite}
                    </p>
                </div>
                <div class="col-md-4">
                    <p class="info-label mb-1">Parcours académique</p>
                    <p class="info-value">
                        ${etu.etablissement} - ${etu.niveauFormation} ${etu.niveau}<br>
                        Session : ${etu.sessionId}<br>
                        Statut : ${etu.var}
                    </p>
                </div>
                <div class="col-md-4">
                    <p class="info-label mb-1">Contacts</p>
                    <p class="info-value">
                        Email UCAD : ${etu.email_ucad}<br>
                        Email perso : ${etu.email_perso}<br>
                        Téléphone : ${etu.telephone}
                    </p>
                </div>

                <div class="col-12">
                    <p class="info-label mb-1">Indicateurs académiques</p>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-light text-dark px-3 py-2"><i class="fas fa-graduation-cap me-2"></i>Niveau : ${etu.niveau}</span>
                        <span class="badge bg-light text-dark px-3 py-2"><i class="fas fa-chart-line me-2"></i>Moyenne : ${etu.moyenne}</span>
                        <span class="badge bg-light text-dark px-3 py-2"><i class="fas fa-building-columns me-2"></i>Département : ${etu.departement}</span>
                    </div>
                </div>
            </div>
        </div>
    `).join('<hr>');
}

function formatDate(dateStr) {
    try {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (error) {
        return dateStr;
    }
}
