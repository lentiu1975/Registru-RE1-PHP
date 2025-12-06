// Manifest Management functionality

let currentPage = 1;
let searchFilters = {};

async function loadManifestsView() {
    const container = document.getElementById('manifests-view-container');

    // Încarcă anii disponibili
    let yearsOptions = '<option value="">Toți anii</option>';
    try {
        const yearsResponse = await fetch('api/database_years.php');
        const yearsData = await yearsResponse.json();
        if (yearsData.data) {
            yearsData.data.forEach(y => {
                const isActive = y.is_active == 1;
                const selected = isActive ? 'selected' : '';
                yearsOptions += `<option value="${y.id}" ${selected}>${y.year}${isActive ? ' (Activ)' : ''}</option>`;
            });
        }
    } catch (e) {
        console.error('Eroare la încărcare ani:', e);
    }

    container.innerHTML = `
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">An</label>
                        <select id="manifest-year" class="form-select">
                            ${yearsOptions}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Căutare</label>
                        <input type="text" id="manifest-search" class="form-control" placeholder="Nr. manifest sau nume navă...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Data început</label>
                        <input type="date" id="date-from" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Data sfârșit</label>
                        <input type="date" id="date-to" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button onclick="searchManifests()" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Caută
                        </button>
                    </div>
                    <div class="col-md-1">
                        <button onclick="resetSearch()" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Resetează
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div id="manifests-table-container">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Se încarcă...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Detalii Manifest -->
        <div class="modal fade" id="manifestDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">📋 Detalii Manifest</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="manifest-details-content">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Setează filtrul pe anul activ (selectat în dropdown)
    const yearSelect = document.getElementById('manifest-year');
    if (yearSelect && yearSelect.value) {
        searchFilters.year_id = yearSelect.value;
    }

    // Încarcă manifeste
    await loadManifests();
}

async function loadManifests(page = 1) {
    currentPage = page;
    const container = document.getElementById('manifests-table-container');

    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...searchFilters
        });

        console.log('Loading manifests with URL:', 'api/manifests/list.php?' + params.toString());

        const response = await fetch('api/manifests/list.php?' + params);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
            return;
        }

        if (data.manifests.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    📭 Nu există manifeste înregistrate
                </div>
            `;
            return;
        }

        // Tabel manifeste
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nr. Manifest</th>
                            <th>Navă</th>
                            <th>Data Sosire</th>
                            <th>Containere</th>
                            <th>Creat de</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.manifests.forEach(manifest => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(manifest.manifest_number)}</strong></td>
                    <td>${escapeHtml(manifest.ship_name || '-')}</td>
                    <td>${formatDate(manifest.arrival_date)}</td>
                    <td><span class="badge bg-primary">${manifest.container_count}</span></td>
                    <td>${escapeHtml(manifest.created_by_username || '-')}</td>
                    <td style="white-space:nowrap;">
                        <button onclick="viewManifestDetails('${escapeHtml(manifest.manifest_number)}')" class="btn btn-sm me-1" style="background:#3b82f6;color:white;border:none;">
                            <i class="bi bi-eye"></i> Detalii
                        </button>
                        <button onclick="exportManifest('${escapeHtml(manifest.manifest_number)}')" class="btn btn-sm me-1" style="background:#22c55e;color:white;border:none;">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button onclick="deleteManifest('${escapeHtml(manifest.manifest_number)}')" class="btn btn-sm" style="background:#ef4444;color:white;border:none;">
                            <i class="bi bi-trash"></i> Șterge
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        // Paginare
        if (data.pagination.total_pages > 1) {
            html += '<nav><ul class="pagination justify-content-center">';

            // Previous
            html += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadManifests(${currentPage - 1}); return false;">«</a>
                </li>
            `;

            // Pagini
            for (let i = 1; i <= data.pagination.total_pages; i++) {
                if (i === 1 || i === data.pagination.total_pages || Math.abs(i - currentPage) <= 2) {
                    html += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadManifests(${i}); return false;">${i}</a>
                        </li>
                    `;
                } else if (Math.abs(i - currentPage) === 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next
            html += `
                <li class="page-item ${currentPage === data.pagination.total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadManifests(${currentPage + 1}); return false;">»</a>
                </li>
            `;

            html += '</ul></nav>';
        }

        // Info total
        html = `
            <div class="mb-3">
                <strong>Total manifeste:</strong> ${data.pagination.total}
            </div>
        ` + html;

        container.innerHTML = html;

    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">Eroare: ' + error.message + '</div>';
    }
}

async function searchManifests() {
    console.log('searchManifests called');

    const searchValue = document.getElementById('manifest-search')?.value || '';
    const dateFromValue = document.getElementById('date-from')?.value || '';
    const dateToValue = document.getElementById('date-to')?.value || '';
    const yearIdValue = document.getElementById('manifest-year')?.value || '';

    console.log('Search filters:', {
        search: searchValue,
        date_from: dateFromValue,
        date_to: dateToValue,
        year_id: yearIdValue
    });

    searchFilters = {
        search: searchValue,
        date_from: dateFromValue,
        date_to: dateToValue,
        year_id: yearIdValue
    };

    await loadManifests(1);
}

async function resetSearch() {
    // Resetează câmpurile de căutare
    const searchInput = document.getElementById('manifest-search');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');
    const yearSelect = document.getElementById('manifest-year');

    if (searchInput) searchInput.value = '';
    if (dateFromInput) dateFromInput.value = '';
    if (dateToInput) dateToInput.value = '';

    // Resetează filtrele păstrând doar anul selectat
    searchFilters = {
        year_id: yearSelect?.value || ''
    };

    // Reîncarcă manifestele
    await loadManifests(1);
}

async function viewManifestDetails(manifestNumber) {
    const modalElement = document.getElementById('manifestDetailsModal');
    const content = document.getElementById('manifest-details-content');

    // Verifică dacă modalul există
    if (!modalElement) {
        console.error('Modal element not found!');
        return;
    }

    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });

    modal.show();

    try {
        const response = await fetch('api/manifests/details.php?manifest_number=' + encodeURIComponent(manifestNumber));
        const data = await response.json();

        if (!data.success) {
            content.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
            return;
        }

        const m = data.manifest;
        const stats = data.stats;

        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Informații Manifest</h5>
                    <table class="table table-sm">
                        <tr><th>Număr Manifest:</th><td><strong>${escapeHtml(m.manifest_number)}</strong></td></tr>
                        <tr><th>Navă:</th><td>${escapeHtml(m.ship_name)}</td></tr>
                        <tr><th>Data Sosire:</th><td>${formatDate(m.arrival_date)}</td></tr>
                        <tr><th>Creat de:</th><td>${escapeHtml(m.created_by_username || '-')}</td></tr>
                        <tr><th>Data Creare:</th><td>${formatDateTime(m.created_at)}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Statistici</h5>
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body py-3">
                                    <h3 class="mb-1">${stats.total_containers}</h3>
                                    <small>Total Containere</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-warning text-dark h-100">
                                <div class="card-body py-3">
                                    <h3 class="mb-1">${stats.duplicate_containers || 0}</h3>
                                    <small>Duplicate</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body py-3">
                                    <h3 class="mb-1">${stats.containers_with_observations || 0}</h3>
                                    <small>Cu Observații</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h5>Lista Containere (${data.containers.length})</h5>
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Numar Manifest</th>
                            <th>Numar Permis</th>
                            <th>Numar Pozitie</th>
                            <th>Cerere Operatiune</th>
                            <th>Data Inregistrare</th>
                            <th>Container</th>
                            <th>Descriere Marfa</th>
                            <th>Numar Colete</th>
                            <th>Greutate Bruta</th>
                            <th>Tip Operatiune</th>
                            <th>Nume Nava</th>
                            <th>Pavilion Nava</th>
                            <th>Numar Sumara</th>
                            <th>Tip Container</th>
                            <th>Model Container</th>
                            <th>Linie Maritima</th>
                            <th>Observatii</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.containers.forEach((c, index) => {
            // Determină clasa pentru celula containerului
            let containerCellClass = '';
            const hasObservations = c.observatii && c.observatii.trim().length >= 5;

            if (hasObservations) {
                containerCellClass = 'table-danger'; // Roșu pentru observații >= 5 caractere
            } else if (c.is_duplicate) {
                containerCellClass = 'table-warning'; // Galben pentru duplicate
            }

            html += `
                <tr data-entry-id="${c.id}">
                    <td>${escapeHtml(c.numar_manifest || '-')}</td>
                    <td>${escapeHtml(c.permit_number || '-')}</td>
                    <td>${escapeHtml(c.position_number || '-')}</td>
                    <td>${escapeHtml(c.operation_request || '-')}</td>
                    <td>${formatDate(c.data_inregistrare)}</td>
                    <td class="${containerCellClass}"><strong>${escapeHtml(c.container_number || '-')}</strong></td>
                    <td>${escapeHtml(c.goods_description || '-')}</td>
                    <td>${c.packages || '-'}</td>
                    <td>${c.weight || '-'}</td>
                    <td>${escapeHtml(c.operation_type || '-')}</td>
                    <td>${escapeHtml(c.ship_name || '-')}</td>
                    <td>${escapeHtml(c.ship_flag || '-')}</td>
                    <td>${escapeHtml(c.summary_number || '-')}</td>
                    <td>${escapeHtml(c.container_type || '-')}</td>
                    <td>${escapeHtml(c.model_container || '-')}</td>
                    <td class="editable" data-field="linie_maritima" data-entry-id="${c.id}" ondblclick="editCell(this)" style="cursor: pointer;" title="Dublu-click pentru editare">${escapeHtml(c.linie_maritima || '-')}</td>
                    <td class="editable" data-field="observatii" data-entry-id="${c.id}" ondblclick="editCell(this)" style="cursor: pointer;" title="Dublu-click pentru editare">${escapeHtml(c.observatii || '-')}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-end">
                <button onclick="exportManifest('${escapeHtml(m.manifest_number)}')" class="btn btn-success">
                    📥 Export Excel
                </button>
            </div>
        `;

        content.innerHTML = html;

    } catch (error) {
        content.innerHTML = '<div class="alert alert-danger">Eroare: ' + error.message + '</div>';
    }
}

function exportManifest(manifestNumber) {
    console.log('Export called for:', manifestNumber);
    // Deschidem direct în fereastră nouă
    window.open('api/manifests/export.php?manifest_number=' + encodeURIComponent(manifestNumber), '_blank');
}

async function deleteManifest(manifestNumber) {
    if (!confirm(`Sigur ștergi manifestul ${manifestNumber}?\n\nAceasta va șterge și toate containerele asociate!`)) {
        return;
    }

    try {
        const response = await fetch('api/manifests/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({manifest_number: manifestNumber})
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ ' + data.message);
            await loadManifests(currentPage);
        } else {
            alert('❌ ' + data.error);
        }

    } catch (error) {
        alert('❌ Eroare: ' + error.message);
    }
}

// Inline editing function
function editCell(cell) {
    const currentValue = cell.textContent.trim();
    const field = cell.getAttribute('data-field');
    const entryId = cell.closest('tr').getAttribute('data-entry-id');
    const row = cell.closest('tr');
    const containerCell = row.querySelector('td:nth-child(6)'); // Celula cu containerul (coloana 6)

    // Nu permite editare multipla
    if (cell.querySelector('input')) {
        return;
    }

    // Create input element
    const input = document.createElement('input');
    input.type = 'text';
    input.value = currentValue === '-' ? '' : currentValue;
    input.className = 'form-control form-control-sm';
    input.style.minWidth = '150px';

    // Save function
    const saveEdit = async () => {
        const newValue = input.value.trim();

        // Dacă nu s-a schimbat nimic, anulează
        if (newValue === currentValue || (newValue === '' && currentValue === '-')) {
            cell.textContent = currentValue;
            return;
        }

        // Arată loading
        cell.innerHTML = '<span class="text-muted">Se salvează...</span>';

        try {
            const response = await fetch('api/entries/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: entryId,
                    [field]: newValue
                })
            });

            const data = await response.json();

            if (data.success) {
                cell.textContent = newValue || '-';

                // Dacă am editat observatii, actualizează highlighting-ul containerului
                if (field === 'observatii') {
                    // Elimină toate clasele de highlighting
                    containerCell.classList.remove('table-danger', 'table-warning');

                    // Aplică roșu dacă observatii >= 5 caractere
                    if (newValue.length >= 5) {
                        containerCell.classList.add('table-danger');
                    }
                    // Aici ar trebui să verificăm dacă e duplicate pentru galben,
                    // dar nu avem acea informație în client fără să reîncărcăm
                }

                // Feedback vizual verde pentru salvare cu succes
                cell.classList.add('table-success');
                setTimeout(() => cell.classList.remove('table-success'), 2000);
            } else {
                alert('❌ Eroare la salvare: ' + data.error);
                cell.textContent = currentValue;
            }
        } catch (error) {
            alert('❌ Eroare: ' + error.message);
            cell.textContent = currentValue;
        }
    };

    // Replace cell content with input
    cell.textContent = '';
    cell.appendChild(input);
    input.focus();
    input.select();

    // Save on Enter or blur
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveEdit();
        } else if (e.key === 'Escape') {
            cell.textContent = currentValue;
        }
    });

    input.addEventListener('blur', saveEdit);
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO') + ' ' + date.toLocaleTimeString('ro-RO');
}
