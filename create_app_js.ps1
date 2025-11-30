$content = @'
/**
 * Search functionality for Container Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const searchLoading = document.getElementById('searchLoading');

    let searchTimeout = null;

    if (!searchInput || !searchResults) {
        console.error('Search elements not found');
        return;
    }

    function performSearch(query) {
        if (!query || query.trim().length < 2) {
            searchResults.innerHTML = '';
            if (searchLoading) searchLoading.style.display = 'none';
            return;
        }

        if (searchLoading) searchLoading.style.display = 'block';
        searchResults.innerHTML = '';

        fetch('/api/search.php?q=' + encodeURIComponent(query))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (searchLoading) searchLoading.style.display = 'none';

                if (data.error) {
                    searchResults.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + data.error + '</div>';
                    return;
                }

                if (!data.results || data.results.length === 0) {
                    searchResults.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Nu s-au găsit rezultate pentru "' + data.query + '"</div>';
                    return;
                }

                displayResults(data.results, data.count);
            })
            .catch(error => {
                console.error('Search error:', error);
                if (searchLoading) searchLoading.style.display = 'none';
                searchResults.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Eroare la căutare. Vă rugăm încercați din nou.</div>';
            });
    }

    function displayResults(results, count) {
        let html = '<div class="alert alert-success mb-3"><i class="bi bi-check-circle"></i> Găsite ' + count + ' rezultate</div>';
        html += '<div class="table-responsive"><table class="table table-hover">';
        html += '<thead class="table-light"><tr><th>Container</th><th>Tip</th><th>Manifest</th><th>Data Sosire</th><th>Sigiliu</th><th>Greutate</th><th>Descriere</th></tr></thead>';
        html += '<tbody>';

        results.forEach(result => {
            const containerImage = result.container_image ? '<img src="' + result.container_image + '" alt="Container" style="height: 30px; margin-right: 5px;">' : '';
            const manifestNumber = result.manifest_number || '-';
            const arrivalDate = result.arrival_date ? new Date(result.arrival_date).toLocaleDateString('ro-RO') : '-';
            const sealNumber = result.seal_number || '-';
            const weight = result.weight ? result.weight + ' kg' : '-';
            const description = result.goods_description || '-';

            html += '<tr>';
            html += '<td>' + containerImage + '<strong>' + (result.container_number || '-') + '</strong></td>';
            html += '<td>' + (result.container_type || '-') + '</td>';
            html += '<td>' + manifestNumber + '</td>';
            html += '<td>' + arrivalDate + '</td>';
            html += '<td>' + sealNumber + '</td>';
            html += '<td>' + weight + '</td>';
            html += '<td>' + description + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        searchResults.innerHTML = html;
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.innerHTML = '';
            if (searchLoading) searchLoading.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 500);
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            performSearch(this.value.trim());
        }
    });
});
'@

$content | Out-File -FilePath "assets\js\app.js" -Encoding UTF8
Write-Host "app.js created successfully!" -ForegroundColor Green
