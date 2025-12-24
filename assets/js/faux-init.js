// Initialisation des données PHP pour faux.js
document.addEventListener('DOMContentLoaded', function() {
    // Passer les données PHP au JavaScript
    if (typeof phpData !== 'undefined' && phpData.length > 0) {
        pvData = phpData;
        filteredData = [...pvData];
        currentPage = phpPagination.currentPage || 1;
        itemsPerPage = phpPagination.itemsPerPage || 10;
        loadPVData();
        updateStatistics();
    }
});
