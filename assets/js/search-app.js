document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const searchLoading = document.getElementById('searchLoading');

    let currentResults = [];
    let currentIndex = 0;

    // Load latest manifest info on page load
    loadLatestManifestInfo();

    // Mapare coduri țări -> nume complete
    const countryNames = {
        'PA': 'Panama',
        'LR': 'Liberia',
        'SG': 'Singapore',
        'MT': 'Malta',
        'CY': 'Cyprus',
        'GR': 'Greece',
        'IT': 'Italy',
        'RO': 'Romania'
    };

    // DOAR form submit - NU auto-search pe input
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    function performSearch() {
        const query = searchInput.value.trim();
        const yearSelect = document.getElementById('year');
        const yearId = yearSelect ? yearSelect.value : '';

        // Numără cifrele din query
        const digitCount = (query.match(/\d/g) || []).length;
        if (digitCount < 7) {
            searchResults.innerHTML = '<div style="padding: 20px; background: #fff3cd; color: #856404; border-radius: 8px;">Introduceți minim 7 cifre pentru căutare</div>';
            return;
        }

        searchLoading.style.display = 'block';
        searchResults.innerHTML = '';

        // Adaugă year_id la căutare
        let searchUrl = '/api/search.php?q=' + encodeURIComponent(query);
        if (yearId) {
            searchUrl += '&year_id=' + encodeURIComponent(yearId);
        }

        fetch(searchUrl)
            .then(response => response.json())
            .then(data => {
                searchLoading.style.display = 'none';

                if (data.error) {
                    searchResults.innerHTML = '<div style="padding: 20px; background: #ffe6e6; color: #c0392b; border-radius: 8px;">' + data.error + '</div>';
                    return;
                }

                if (!data.results || data.results.length === 0) {
                    searchResults.innerHTML = '<div style="padding: 20px; background: #e3f2fd; color: #1976d2; border-radius: 8px;">Nu s-au gasit rezultate</div>';
                    return;
                }

                currentResults = data.results;
                currentIndex = 0;
                displayCurrentResult();
            })
            .catch(error => {
                console.error('Search error:', error);
                searchLoading.style.display = 'none';
                searchResults.innerHTML = '<div style="padding: 20px; background: #ffe6e6; color: #c0392b; border-radius: 8px;">Eroare la cautare</div>';
            });
    }

    function displayCurrentResult() {
        if (currentResults.length === 0) return;

        const result = currentResults[currentIndex];
        const count = currentResults.length;

        let html = '<div class="results-navigation">';
        html += '<span class="results-count">Gasite ' + count + ' rezultate</span>';

        if (count > 1) {
            html += '<div class="navigation-buttons">';
            html += '<button onclick="window.searchApp.previousResult()" ' + (currentIndex === 0 ? 'disabled' : '') + ' class="nav-button">Anterior</button>';
            html += '<span class="current-position">' + (currentIndex + 1) + ' / ' + count + '</span>';
            html += '<button onclick="window.searchApp.nextResult()" ' + (currentIndex === count - 1 ? 'disabled' : '') + ' class="nav-button">Urmator</button>';
            html += '</div>';
        }

        html += '</div>';

        html += '<div class="results-section"><div class="result-details">';

        // Left
        html += '<div class="details-left">';
        html += '<div class="position-title">Pozitie RE1</div>';

        const manifestNumber = result.manifest_number || 'N/A';
        const permitNumber = result.permit_number || manifestNumber;
        const positionNumber = result.position_number || 'N/A';
        const operationRequest = result.operation_request || 'N/A';
        const containerNumber = result.container_number || 'N/A';
        const arrivalDate = result.arrival_date ? new Date(result.arrival_date).toLocaleDateString('ro-RO') : 'N/A';

        // Format: manifest/permit/position/request - date
        const manifestInfo = manifestNumber + '/' + permitNumber + '/' + positionNumber + '/' + operationRequest + ' - ' + arrivalDate;
        html += '<div class="manifest-info">' + manifestInfo + '</div>';

        // Colete și Greutate
        const weight = result.weight || 'N/A';
        const packages = result.packages || 'N/A';
        html += '<div class="info-item info-row">';
        html += '<div class="info-col"><span class="info-label">Colete:</span><span class="info-value">' + packages + '</span></div>';
        html += '<div class="info-col"><span class="info-label">Greutate:</span><span class="info-value">' + (weight !== 'N/A' ? weight + ' Kg' : 'N/A') + '</span></div>';
        html += '</div>';

        // Tip operațiune
        const operationType = result.operation_type === 'E' ? 'Export' : 'Import';
        html += '<div class="info-item"><span class="info-label">Tip operațiune:</span><span class="info-value">' + operationType + '</span></div>';

        // Descriere marfă
        const description = result.goods_description || 'N/A';
        html += '<div class="info-item description"><span class="info-label">Descriere marfă:</span><div class="info-value">' + description + '</div></div>';

        // Număr sumară (NUMERELE SUMARE, nu pavilionul!) - split by ; or ,
        let summaryNumber = result.summary_number || 'N/A';
        if (summaryNumber !== 'N/A') {
            // Split by semicolon or comma and display each on new line
            summaryNumber = summaryNumber.split(/[;,]/).map(s => s.trim()).filter(s => s).join('<br>');
        }
        html += '<div class="info-item"><span class="info-label">Număr sumară:</span><div class="info-value">' + summaryNumber + '</div></div>';

        html += '</div>';

        // Right
        html += '<div class="details-right">';
        html += '<div class="container-section">';
        html += '<div class="container-title">' + containerNumber + '</div>';

        const containerImage = result.container_image || '/assets/images/container_model.png';
        html += '<img src="' + containerImage + '" alt="Container" class="container-image-large" onerror="this.src=\'/assets/images/container_model.png\'">';

        html += '</div>';

        // Ship section - afiseaza informatii despre nava
        const shipName = result.ship_name;
        const flagImage = result.flag_image;
        const pavilionName = result.pavilion_name;
        const shipImage = result.ship_image;

        if (shipName && shipName !== 'N/A') {
            html += '<div class="ship-section">';
            html += '<div class="ship-title">' + shipName;

            // Flag image from pavilion
            if (flagImage) {
                html += ' <img src="' + flagImage + '" alt="Flag" title="' + (pavilionName || '') + '" class="flag-icon" style="display:inline-block;width:24px;height:16px;margin-left:8px;object-fit:contain;" onerror="this.style.display=\'none\'">';
            }

            html += '</div>';

            // Ship image from API
            if (shipImage) {
                html += '<img src="' + shipImage + '" alt="Ship" class="ship-image" onerror="this.src=\'/assets/images/vapor_model.png\'">';
            }

            html += '</div>';
        }

        html += '</div>';

        html += '</div></div>';

        searchResults.innerHTML = html;
    }

    function loadLatestManifestInfo() {
        const infoElement = document.getElementById('latestManifestInfo');
        if (!infoElement) return;

        fetch('/api/latest_manifest.php')
            .then(response => response.json())
            .then(data => {
                if (data.manifests && data.manifests.length > 0) {
                    let html = '<strong>Ultimele nave importate:</strong> ';
                    const items = data.manifests.map(m => {
                        const shipName = m.ship_name || 'N/A';
                        const manifestNumber = m.manifest_number;
                        const arrivalDate = m.arrival_date ? new Date(m.arrival_date).toLocaleDateString('ro-RO') : 'N/A';
                        return '<span style="white-space:nowrap;">' + shipName.toUpperCase() + ' (M' + manifestNumber + ', ' + arrivalDate + ')</span>';
                    });
                    html += items.join(' | ');
                    infoElement.innerHTML = html;
                } else {
                    infoElement.innerHTML = 'Nicio informație disponibilă';
                }
            })
            .catch(error => {
                console.error('Error loading latest manifest:', error);
                infoElement.innerHTML = 'Eroare la încărcarea informațiilor';
            });
    }

    window.searchApp = {
        previousResult: function() {
            if (currentIndex > 0) {
                currentIndex--;
                displayCurrentResult();
            }
        },
        nextResult: function() {
            if (currentIndex < currentResults.length - 1) {
                currentIndex++;
                displayCurrentResult();
            }
        }
    };
});