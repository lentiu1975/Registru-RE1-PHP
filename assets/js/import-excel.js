// Import Excel functionality

async function loadImportForm() {
    const container = document.getElementById('import-form-container');

    try {
        // Încarcă template-uri
        const response = await fetch('api/get_templates.php');
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">Eroare: ' + data.error + '</div>';
            return;
        }

        const templates = data.templates;

        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <form id="import-excel-form" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Număr Manifest *</label>
                                <input type="text" name="manifest_number" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nume Navă *</label>
                                <input type="text" name="ship_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data Sosire *</label>
                                <input type="date" name="arrival_date" class="form-control" required value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Template Import</label>
                                <select name="template_id" class="form-select">
                                    ${templates.map(t => `<option value="${t.id}" ${t.is_default ? 'selected' : ''}>${t.name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fișier Excel (XLS/XLSX) *</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xls,.xlsx" required>
                            <small class="text-muted">Format acceptat: XLS sau XLSX</small>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                📤 Importă Date
                            </button>
                        </div>
                        <div id="import-progress" style="display: none;">
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                            <p class="text-muted">Se procesează...</p>
                        </div>
                        <div id="import-result"></div>
                    </form>
                </div>
            </div>
        `;

        // Handler pentru submit
        document.getElementById('import-excel-form').addEventListener('submit', handleImportSubmit);

    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">Eroare la încărcare: ' + error.message + '</div>';
    }
}

async function handleImportSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const progress = document.getElementById('import-progress');
    const result = document.getElementById('import-result');

    // Arată progress
    progress.style.display = 'block';
    result.innerHTML = '';

    try {
        const response = await fetch('api/import_excel.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        progress.style.display = 'none';

        if (data.success) {
            result.innerHTML = `
                <div class="alert alert-success">
                    <h5>✅ Import Finalizat!</h5>
                    <p><strong>${data.imported}</strong> containere importate cu succes în manifestul <strong>${data.manifest_number}</strong></p>
                    ${data.errors.length > 0 ? `
                        <hr>
                        <h6>Atenționări (${data.errors.length}):</h6>
                        <ul class="mb-0">
                            ${data.errors.slice(0, 10).map(err => '<li>' + err + '</li>').join('')}
                            ${data.errors.length > 10 ? '<li>... și ' + (data.errors.length - 10) + ' mai multe</li>' : ''}
                        </ul>
                    ` : ''}
                </div>
            `;

            // Reset form
            form.reset();

        } else {
            result.innerHTML = `
                <div class="alert alert-danger">
                    <h5>❌ Eroare Import</h5>
                    <p>${data.error}</p>
                </div>
            `;
        }

    } catch (error) {
        progress.style.display = 'none';
        result.innerHTML = `
            <div class="alert alert-danger">
                <h5>❌ Eroare</h5>
                <p>${error.message}</p>
            </div>
        `;
    }
}
