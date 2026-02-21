// Fonctions pour gérer les formulaires dynamiques du module constat

// Variable pour le step actuel
let currentStep = 1;
const totalSteps = 5;

// Fonction de navigation entre les steps du formulaire d'ajout
function changeStep(direction) {
    const newStep = currentStep + direction;
    if (newStep < 1 || newStep > totalSteps) return;

    // Masquer le step actuel
    const currentStepEl = document.getElementById('step' + currentStep);
    if (currentStepEl) currentStepEl.classList.remove('active');

    // Mettre à jour le stepper visuel
    const stepperItems = document.querySelectorAll('#addModal .stepper-item');
    stepperItems.forEach(item => {
        const stepNum = parseInt(item.getAttribute('data-step'));
        item.classList.remove('active', 'completed');
        if (stepNum < newStep) {
            item.classList.add('completed');
        } else if (stepNum === newStep) {
            item.classList.add('active');
        }
    });

    // Afficher le nouveau step
    currentStep = newStep;
    const newStepEl = document.getElementById('step' + currentStep);
    if (newStepEl) newStepEl.classList.add('active');

    // Gérer la visibilité des boutons
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-block';
    document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'inline-block';
    document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-block' : 'none';
}

// Réinitialiser le stepper (utilisé lors de l'ouverture du modal)
function resetStepper() {
    currentStep = 1;
    // Réinitialiser les steps
    for (let i = 1; i <= totalSteps; i++) {
        const stepEl = document.getElementById('step' + i);
        if (stepEl) stepEl.classList.toggle('active', i === 1);
    }
    // Réinitialiser le stepper visuel
    const stepperItems = document.querySelectorAll('#addModal .stepper-item');
    stepperItems.forEach(item => {
        const stepNum = parseInt(item.getAttribute('data-step'));
        item.classList.remove('active', 'completed');
        if (stepNum === 1) item.classList.add('active');
    });
    // Réinitialiser les boutons
    document.getElementById('prevBtn').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'inline-block';
    document.getElementById('submitBtn').style.display = 'none';
}

// Variables globales pour les compteurs
let blesseCount = 0;
let dommageCount = 0;
let assaillantCount = 0;
let auditionCount = 0;
let temoignageCount = 0;

// Fonction pour ajouter un blessé dans le formulaire d'ajout
function addBlesse() {
    blesseCount++;
    const container = document.getElementById('blessesContainer');
    const blesseHtml = `
        <div class="blesse-item" id="blesse-${blesseCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="blesse_nom_${blesseCount}" placeholder="Nom du blessé">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="blesse_prenoms_${blesseCount}" placeholder="Prénoms">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="blesse_gravite_${blesseCount}">
                        <option value="">Gravité</option>
                        <option value="leger">Léger</option>
                        <option value="moyen">Moyen</option>
                        <option value="grave">Grave</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeBlesse(${blesseCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', blesseHtml);
}

// Fonction pour supprimer un blessé
function removeBlesse(id) {
    const element = document.getElementById(`blesse-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonction pour ajouter un dommage matériel dans le formulaire d'ajout
function addDommage() {
    dommageCount++;
    const container = document.getElementById('dommagesContainer');
    const dommageHtml = `
        <div class="dommage-item" id="dommage-${dommageCount}">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label small">Type de dommage</label>
                    <select class="form-select" name="dommage_type_${dommageCount}" onchange="toggleDommageAutreFieldAdd(this, ${dommageCount})">
                        <option value="">-- Sélectionner --</option>
                        <option value="materiel">Matériel</option>
                        <option value="immobilier">Immobilier</option>
                        <option value="vehicule">Véhicule</option>
                        <option value="autre">Autre (préciser)</option>
                    </select>
                    <input type="text" class="form-control mt-2" id="dommage_type_autre_${dommageCount}" name="dommage_type_autre_${dommageCount}" placeholder="Précisez le type de dommage" style="display: none;">
                </div>
                <div class="col-md-5 mb-2">
                    <label class="form-label small">Description</label>
                    <input type="text" class="form-control" name="dommage_description_${dommageCount}" placeholder="Description du dommage">
                </div>
                <div class="col-md-5 mb-2">
                    <label class="form-label small">Valeur estimée (FCFA)</label>
                    <input type="number" class="form-control" name="dommage_valeur_${dommageCount}" placeholder="Valeur estimée">
                </div>
                <div class="col-md-5 mb-2">
                    <label class="form-label small">Propriétaire</label>
                    <input type="text" class="form-control" name="dommage_proprietaire_${dommageCount}" placeholder="Propriétaire du bien">
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger w-100" onclick="removeDommage(${dommageCount})">
                        <i class="fas fa-times me-1"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', dommageHtml);
}

// Fonction pour supprimer un dommage
function removeDommage(id) {
    const element = document.getElementById(`dommage-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonction pour ajouter un assaillant dans le formulaire d'ajout
function addAssaillant() {
    assaillantCount++;
    const container = document.getElementById('assaillantsContainer');
    const assaillantHtml = `
        <div class="assaillant-item" id="assaillant-${assaillantCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="assaillant_nom_${assaillantCount}" placeholder="Nom de l'assaillant">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="assaillant_description_${assaillantCount}" placeholder="Description">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="assaillant_statut_${assaillantCount}">
                        <option value="">Statut</option>
                        <option value="identifie">Identifié</option>
                        <option value="recherche">En recherche</option>
                        <option value="inconnu">Inconnu</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAssaillant(${assaillantCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', assaillantHtml);
}

// Fonction pour supprimer un assaillant
function removeAssaillant(id) {
    const element = document.getElementById(`assaillant-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonction pour ajouter une audition dans le formulaire d'ajout
function addAudition() {
    auditionCount++;
    const container = document.getElementById('auditionsContainer');
    const auditionHtml = `
        <div class="audition-item" id="audition-${auditionCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="audition_nom_${auditionCount}" placeholder="Nom de la personne auditionnée">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="datetime-local" class="form-control" name="audition_date_${auditionCount}">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="audition_type_${auditionCount}">
                        <option value="">Type d'audition</option>
                        <option value="temoin">Témoin</option>
                        <option value="victime">Victime</option>
                        <option value="suspect">Suspect</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAudition(${auditionCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-11 mb-2">
                    <textarea class="form-control" name="audition_contenu_${auditionCount}" rows="2" placeholder="Contenu de l'audition"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', auditionHtml);
}

// Fonction pour supprimer une audition
function removeAudition(id) {
    const element = document.getElementById(`audition-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonction pour ajouter un témoignage dans le formulaire d'ajout
function addTemoignage() {
    temoignageCount++;
    const container = document.getElementById('temoignagesContainer');
    const temoignageHtml = `
        <div class="temoignage-item" id="temoignage-${temoignageCount}">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control" name="temoignage_nom_${temoignageCount}" placeholder="Nom du témoin">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control" name="temoignage_prenoms_${temoignageCount}" placeholder="Prénoms du témoin">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="temoignage_statut_${temoignageCount}">
                        <option value="etudiant">Étudiant</option>
                        <option value="personnel">Personnel</option>
                        <option value="externe">Externe</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="tel" class="form-control" name="temoignage_telephone_${temoignageCount}" placeholder="Téléphone">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTemoignage(${temoignageCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="temoignage_adresse_${temoignageCount}" placeholder="Adresse du témoin">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="datetime-local" class="form-control" name="temoignage_date_${temoignageCount}">
                </div>
                <div class="col-md-4 mb-2">
                    <textarea class="form-control" name="temoignage_contenu_${temoignageCount}" rows="2" placeholder="Contenu du témoignage"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', temoignageHtml);
}

// Fonction pour supprimer un témoignage
function removeTemoignage(id) {
    const element = document.getElementById(`temoignage-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonctions pour le formulaire de modification
let editBlesseCount = 0;
let editDommageCount = 0;
let editAssaillantCount = 0;
let editAuditionCount = 0;
let editTemoignageCount = 0;

function addEditBlesse() {
    editBlesseCount++;
    const container = document.getElementById('editBlessesContainer');
    const blesseHtml = `
        <div class="blesse-item" id="edit-blesse-${editBlesseCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_blesse_nom_${editBlesseCount}" placeholder="Nom du blessé">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_blesse_prenoms_${editBlesseCount}" placeholder="Prénoms">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="edit_blesse_gravite_${editBlesseCount}">
                        <option value="">Gravité</option>
                        <option value="leger">Léger</option>
                        <option value="moyen">Moyen</option>
                        <option value="grave">Grave</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditBlesse(${editBlesseCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', blesseHtml);
}

function removeEditBlesse(id) {
    const element = document.getElementById(`edit-blesse-${id}`);
    if (element) {
        element.remove();
    }
}

function addEditDommage() {
    editDommageCount++;
    const container = document.getElementById('editDommagesContainer');
    const dommageHtml = `
        <div class="dommage-item" id="edit-dommage-${editDommageCount}">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <input type="text" class="form-control" name="edit_dommage_description_${editDommageCount}" placeholder="Description du dommage">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_dommage_valeur_${editDommageCount}" placeholder="Valeur estimée">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-select" name="edit_dommage_type_${editDommageCount}">
                        <option value="">Type</option>
                        <option value="materiel">Matériel</option>
                        <option value="vehicule">Véhicule</option>
                        <option value="batiment">Bâtiment</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditDommage(${editDommageCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', dommageHtml);
}

function removeEditDommage(id) {
    const element = document.getElementById(`edit-dommage-${id}`);
    if (element) {
        element.remove();
    }
}

function addEditAssaillant() {
    editAssaillantCount++;
    const container = document.getElementById('editAssaillantsContainer');
    const assaillantHtml = `
        <div class="assaillant-item" id="edit-assaillant-${editAssaillantCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_assaillant_nom_${editAssaillantCount}" placeholder="Nom de l'assaillant">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_assaillant_description_${editAssaillantCount}" placeholder="Description">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="edit_assaillant_statut_${editAssaillantCount}">
                        <option value="">Statut</option>
                        <option value="identifie">Identifié</option>
                        <option value="recherche">En recherche</option>
                        <option value="inconnu">Inconnu</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditAssaillant(${editAssaillantCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', assaillantHtml);
}

function removeEditAssaillant(id) {
    const element = document.getElementById(`edit-assaillant-${id}`);
    if (element) {
        element.remove();
    }
}

function addEditAudition() {
    editAuditionCount++;
    const container = document.getElementById('editAuditionsContainer');
    const auditionHtml = `
        <div class="audition-item" id="edit-audition-${editAuditionCount}">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_audition_nom_${editAuditionCount}" placeholder="Nom de la personne auditionnée">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="datetime-local" class="form-control" name="edit_audition_date_${editAuditionCount}">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="edit_audition_type_${editAuditionCount}">
                        <option value="">Type d'audition</option>
                        <option value="temoin">Témoin</option>
                        <option value="victime">Victime</option>
                        <option value="suspect">Suspect</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditAudition(${editAuditionCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-11 mb-2">
                    <textarea class="form-control" name="edit_audition_contenu_${editAuditionCount}" rows="2" placeholder="Contenu de l'audition"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', auditionHtml);
}

function removeEditAudition(id) {
    const element = document.getElementById(`edit-audition-${id}`);
    if (element) {
        element.remove();
    }
}

function addEditTemoignage() {
    editTemoignageCount++;
    const container = document.getElementById('editTemoignagesContainer');
    const temoignageHtml = `
        <div class="temoignage-item" id="edit-temoignage-${editTemoignageCount}">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control" name="edit_temoignage_nom_${editTemoignageCount}" placeholder="Nom du témoin">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control" name="edit_temoignage_prenoms_${editTemoignageCount}" placeholder="Prénoms du témoin">
                </div>
                <div class="col-md-3 mb-2">
                    <select class="form-select" name="edit_temoignage_statut_${editTemoignageCount}">
                        <option value="etudiant">Étudiant</option>
                        <option value="personnel">Personnel</option>
                        <option value="externe">Externe</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="tel" class="form-control" name="edit_temoignage_telephone_${editTemoignageCount}" placeholder="Téléphone">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditTemoignage(${editTemoignageCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" class="form-control" name="edit_temoignage_adresse_${editTemoignageCount}" placeholder="Adresse du témoin">
                </div>
                <div class="col-md-4 mb-2">
                    <input type="datetime-local" class="form-control" name="edit_temoignage_date_${editTemoignageCount}">
                </div>
                <div class="col-md-4 mb-2">
                    <textarea class="form-control" name="edit_temoignage_contenu_${editTemoignageCount}" rows="2" placeholder="Contenu du témoignage"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', temoignageHtml);
}

function removeEditTemoignage(id) {
    const element = document.getElementById(`edit-temoignage-${id}`);
    if (element) {
        element.remove();
    }
}

// Fonction pour gérer l'affichage du champ "Autre" pour le type de dommage (formulaire d'ajout)
function toggleDommageAutreFieldAdd(selectElement, dommageId) {
    const autreField = document.getElementById(`dommage_type_autre_${dommageId}`);

    if (selectElement.value === 'Autre') {
        autreField.style.display = 'block';
        autreField.focus();
    } else {
        autreField.style.display = 'none';
        autreField.value = '';
    }
}
