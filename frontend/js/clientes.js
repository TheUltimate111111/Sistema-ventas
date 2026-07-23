document.addEventListener('DOMContentLoaded', () => {
    const formatMoney = (v) => '$' + Number(v).toFixed(2);

    let allClients = [];
    let filteredClients = [];
    let currentDetailId = null;

    const statTotalClientes = document.getElementById('statTotalClientes');
    const statTotalFacturado = document.getElementById('statTotalFacturado');
    const statTicketPromedio = document.getElementById('statTicketPromedio');
    const statActivos = document.getElementById('statActivos');

    const searchInput = document.getElementById('crmSearch');
    const filterClasificacion = document.getElementById('crmFilterClasificacion');
    const filterFechaDesde = document.getElementById('crmFilterFechaDesde');
    const filterFechaHasta = document.getElementById('crmFilterFechaHasta');
    const filterGastoMin = document.getElementById('crmFilterGastoMin');
    const filterGastoMax = document.getElementById('crmFilterGastoMax');
    const filterOrdenar = document.getElementById('crmFilterOrdenar');
    const btnBuscar = document.getElementById('crmBtnBuscar');
    const btnLimpiar = document.getElementById('crmBtnLimpiar');
    const tableBody = document.getElementById('crmTableBody');
    const noResults = document.getElementById('crmNoResults');
    const totalFiltered = document.getElementById('crmTotalFiltered');

    const detailOverlay = document.getElementById('crmDetailOverlay');
    const detailPanel = document.getElementById('crmDetailPanel');
    const detailCloseBtn = document.getElementById('crmDetailClose');

    const clientModal = document.getElementById('crmClientModal');
    const clientForm = document.getElementById('crmClientForm');
    const clientModalTitle = document.getElementById('crmClientModalTitle');
    const clientFormAction = document.getElementById('crmFormAction');
    const clientFormId = document.getElementById('crmFormId');
    const clientFormCedula = document.getElementById('crmFormCedula');
    const clientFormNombre = document.getElementById('crmFormNombre');
    const clientFormCorreo = document.getElementById('crmFormCorreo');
    const clientFormTelefono = document.getElementById('crmFormTelefono');
    const clientFormDireccion = document.getElementById('crmFormDireccion');
    const clientFormMessage = document.getElementById('crmFormMessage');
    const btnNewClient = document.getElementById('crmBtnNewClient');

    const reportModal = document.getElementById('crmReportModal');
    const reportBtn = document.getElementById('crmBtnReport');
    const reportContent = document.getElementById('crmReportContent');

    let bsClientModal = null;
    let bsReportModal = null;
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsClientModal = new bootstrap.Modal(clientModal);
            bsReportModal = new bootstrap.Modal(reportModal);
        }
    } catch (e) { /* fallback */ }

    function openClientModal() {
        if (bsClientModal) bsClientModal.show();
        else { clientModal.classList.add('show'); clientModal.style.display = 'block'; document.body.style.overflow = 'hidden'; }
    }
    function closeClientModalFn() {
        if (bsClientModal) bsClientModal.hide();
        else { clientModal.classList.remove('show'); clientModal.style.display = ''; document.body.style.overflow = ''; }
    }
    function openReportModal() {
        if (bsReportModal) bsReportModal.show();
        else { reportModal.classList.add('show'); reportModal.style.display = 'block'; document.body.style.overflow = 'hidden'; }
    }
    function closeReportModalFn() {
        if (bsReportModal) bsReportModal.hide();
        else { reportModal.classList.remove('show'); reportModal.style.display = ''; document.body.style.overflow = ''; }
    }

    /* ========== CLASSIFICATION ========== */
    function calcularClasificacion(c) {
        const total = parseFloat(c.total_gastado) || 0;
        const compras = parseInt(c.num_compras) || 0;
        const ultima = c.ultima_compra;

        if (compras === 0) return { tipo: 'Sin compras', css: 'sin-compras', icono: '○' };

        const diasSin = ultima
            ? Math.floor((Date.now() - new Date(ultima).getTime()) / 86400000)
            : 999;

        if (diasSin > 180) return { tipo: 'Inactivo', css: 'inactivo', icono: '●' };
        if (total >= 1000 || (compras >= 10 && diasSin <= 30))
            return { tipo: 'Premium', css: 'premium', icono: '★' };
        if (total >= 200 || compras >= 5)
            return { tipo: 'Frecuente', css: 'frecuente', icono: '◆' };
        return { tipo: 'Nuevo', css: 'nuevo', icono: '●' };
    }

    function calcularIndicadores(c) {
        const total = parseFloat(c.total_gastado) || 0;
        const compras = parseInt(c.num_compras) || 0;
        const prim = c.primera_compra;
        const ult = c.ultima_compra;
        const reg = c.fecha_registro;

        const ticketPromedio = compras > 0 ? total / compras : 0;

        const diasCliente = reg
            ? Math.floor((Date.now() - new Date(reg).getTime()) / 86400000)
            : 0;

        const diasSinCompra = ult
            ? Math.floor((Date.now() - new Date(ult).getTime()) / 86400000)
            : null;

        let frecuencia = null;
        if (compras > 1 && prim && ult) {
            const diff = new Date(ult).getTime() - new Date(prim).getTime();
            frecuencia = Math.round(diff / (compras - 1) / 86400000);
        }

        let retencion = 0;
        if (diasCliente > 0 && compras > 0) {
            const meses = Math.max(1, Math.floor(diasCliente / 30));
            retencion = Math.min(100, Math.round((compras / meses) * 100));
        }

        return { ticketPromedio, diasCliente, diasSinCompra, frecuencia, retencion };
    }

    /* ========== LOAD DATA ========== */
    async function loadClients() {
        try {
            const res = await fetch('backend/api_clientes.php');
            const data = await res.json();
            allClients = Array.isArray(data) ? data : [];
            applyFilters();
        } catch (e) {
            allClients = [];
            applyFilters();
        }
    }

    /* ========== FILTERING ========== */
    function applyFilters() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const clasif = filterClasificacion?.value || '';
        const fechaDesde = filterFechaDesde?.value || '';
        const fechaHasta = filterFechaHasta?.value || '';
        const gastoMin = parseFloat(filterGastoMin?.value) || 0;
        const gastoMax = parseFloat(filterGastoMax?.value) || Infinity;
        const ordenar = filterOrdenar?.value || 'id-asc';

        filteredClients = allClients.filter((c) => {
            const matchSearch = q === '' ||
                (c.nombre_completo || '').toLowerCase().includes(q) ||
                (c.cedula || '').includes(q) ||
                (c.correo || '').toLowerCase().includes(q);

            const clas = calcularClasificacion(c);
            const matchClasif = clasif === '' || clas.css === clasif;

            const regDate = (c.fecha_registro || '').substring(0, 10);
            const matchFechaDesde = fechaDesde === '' || regDate >= fechaDesde;
            const matchFechaHasta = fechaHasta === '' || regDate <= fechaHasta;

            const gasto = parseFloat(c.total_gastado) || 0;
            const matchGastoMin = gasto >= gastoMin;
            const matchGastoMax = gasto <= gastoMax;

            return matchSearch && matchClasif && matchFechaDesde && matchFechaHasta && matchGastoMin && matchGastoMax;
        });

        const [field, dir] = ordenar.split('-');
        filteredClients.sort((a, b) => {
            let va, vb;
            switch (field) {
                case 'nombre':
                    va = (a.nombre_completo || '').toLowerCase();
                    vb = (b.nombre_completo || '').toLowerCase();
                    return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
                case 'gasto':
                    va = parseFloat(a.total_gastado) || 0;
                    vb = parseFloat(b.total_gastado) || 0;
                    break;
                case 'compras':
                    va = parseInt(a.num_compras) || 0;
                    vb = parseInt(b.num_compras) || 0;
                    break;
                case 'ultima':
                    va = a.ultima_compra || '';
                    vb = b.ultima_compra || '';
                    break;
                default:
                    va = parseInt(a.id) || 0;
                    vb = parseInt(b.id) || 0;
            }
            if (typeof va === 'number') return dir === 'asc' ? va - vb : vb - va;
            return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
        });

        renderTable();
        renderStats();
        renderCharts();
    }

    /* ========== RENDER STATS ========== */
    function renderStats() {
        if (statTotalClientes) statTotalClientes.textContent = filteredClients.length;

        const totalGastado = filteredClients.reduce((s, c) => s + (parseFloat(c.total_gastado) || 0), 0);
        const totalCompras = filteredClients.reduce((s, c) => s + (parseInt(c.num_compras) || 0), 0);

        if (statTotalFacturado) statTotalFacturado.textContent = formatMoney(totalGastado);
        if (statTicketPromedio) statTicketPromedio.textContent = totalCompras > 0 ? formatMoney(totalGastado / totalCompras) : '$0.00';

        const activos = filteredClients.filter((c) => {
            const clas = calcularClasificacion(c);
            return clas.css !== 'inactivo' && clas.css !== 'sin-compras';
        }).length;
        if (statActivos) statActivos.textContent = activos;
    }

    /* ========== RENDER CHARTS ========== */
    const chartColors = {
        premium:   '#8b5cf6',
        frecuente: '#22c55e',
        nuevo:     '#3b82f6',
        inactivo:  '#ef4444',
        'sin-compras': '#94a3b8'
    };
    const chartLabels = {
        premium: 'Premium',
        frecuente: 'Frecuente',
        nuevo: 'Nuevo',
        inactivo: 'Inactivo',
        'sin-compras': 'Sin compras'
    };

    function renderCharts() {
        renderDonutChart();
        renderRevenueChart();
        renderTopClientsChart();
    }

    function renderDonutChart() {
        const container = document.getElementById('crmDonutChart');
        const legend = document.getElementById('crmDonutLegend');
        if (!container) return;

        const counts = { premium: 0, frecuente: 0, nuevo: 0, inactivo: 0, 'sin-compras': 0 };
        filteredClients.forEach((c) => {
            const clas = calcularClasificacion(c);
            counts[clas.css] = (counts[clas.css] || 0) + 1;
        });

        const total = filteredClients.length;
        if (total === 0) {
            container.innerHTML = '<div class="crm-chart-empty">Sin datos</div>';
            if (legend) legend.innerHTML = '';
            return;
        }

        const radius = 70;
        const cx = 80, cy = 80;
        const circumference = 2 * Math.PI * radius;
        let accumulated = 0;

        const entries = Object.entries(counts).filter(([, v]) => v > 0);
        let arcsHtml = '';

        entries.forEach(([key, count]) => {
            const pct = count / total;
            const dashLen = pct * circumference;
            const dashOffset = -accumulated * circumference;
            arcsHtml += `<circle cx="${cx}" cy="${cy}" r="${radius}" fill="none"
                stroke="${chartColors[key]}" stroke-width="28"
                stroke-dasharray="${dashLen} ${circumference - dashLen}"
                stroke-dashoffset="${dashOffset}"
                transform="rotate(-90 ${cx} ${cy})"
                style="transition: stroke-dasharray 0.6s ease, stroke-dashoffset 0.6s ease;"/>`;
            accumulated += pct;
        });

        container.innerHTML = `<svg viewBox="0 0 160 160" class="crm-donut-svg">
            <circle cx="${cx}" cy="${cy}" r="${radius}" fill="none" stroke="#f1f5f9" stroke-width="28"/>
            ${arcsHtml}
            <text x="${cx}" y="${cy - 6}" text-anchor="middle" class="crm-donut-total">${total}</text>
            <text x="${cx}" y="${cy + 12}" text-anchor="middle" class="crm-donut-label">clientes</text>
        </svg>`;

        if (legend) {
            legend.innerHTML = entries.map(([key, count]) => {
                const pct = total > 0 ? (count / total * 100).toFixed(1) : 0;
                return `<div class="crm-legend-item">
                    <span class="crm-legend-dot" style="background:${chartColors[key]};"></span>
                    <span class="crm-legend-text">${chartLabels[key]}</span>
                    <span class="crm-legend-value">${count} (${pct}%)</span>
                </div>`;
            }).join('');
        }
    }

    function renderRevenueChart() {
        const container = document.getElementById('crmRevenueChart');
        if (!container) return;

        const revenue = { premium: 0, frecuente: 0, nuevo: 0, inactivo: 0, 'sin-compras': 0 };
        filteredClients.forEach((c) => {
            const clas = calcularClasificacion(c);
            revenue[clas.css] = (revenue[clas.css] || 0) + (parseFloat(c.total_gastado) || 0);
        });

        const entries = Object.entries(revenue).filter(([, v]) => v > 0);
        const maxRev = entries.length > 0 ? Math.max(...entries.map(([, v]) => v)) : 0;

        if (maxRev === 0) {
            container.innerHTML = '<div class="crm-chart-empty">Sin datos</div>';
            return;
        }

        container.innerHTML = entries.map(([key, val]) => {
            const pct = maxRev > 0 ? (val / maxRev * 100) : 0;
            return `<div class="crm-hbar-row">
                <div class="crm-hbar-label">${chartLabels[key]}</div>
                <div class="crm-hbar-track">
                    <div class="crm-hbar-fill" style="width:${Math.max(pct, 3)}%;background:${chartColors[key]};"></div>
                </div>
                <div class="crm-hbar-value">${formatMoney(val)}</div>
            </div>`;
        }).join('');
    }

    function renderTopClientsChart() {
        const container = document.getElementById('crmTopClientsChart');
        if (!container) return;

        const top = [...filteredClients]
            .sort((a, b) => (parseFloat(b.total_gastado) || 0) - (parseFloat(a.total_gastado) || 0))
            .slice(0, 10);

        if (top.length === 0) {
            container.innerHTML = '<div class="crm-chart-empty">Sin datos</div>';
            return;
        }

        const maxGasto = Math.max(...top.map(c => parseFloat(c.total_gastado) || 0));
        const barColors = ['#16a34a','#22c55e','#34d399','#059669','#10b981','#0d9488','#14b8a6','#06b6d4','#0ea5e9','#38bdf8'];

        container.innerHTML = top.map((c, i) => {
            const gasto = parseFloat(c.total_gastado) || 0;
            const pct = maxGasto > 0 ? (gasto / maxGasto * 100) : 0;
            const name = (c.nombre_completo || '').length > 16
                ? (c.nombre_completo || '').substring(0, 16) + '...'
                : (c.nombre_completo || '');
            return `<div class="crm-hbar-row">
                <div class="crm-hbar-rank">${i + 1}</div>
                <div class="crm-hbar-label crm-hbar-name" title="${escapeHtml(c.nombre_completo || '')}">${escapeHtml(name)}</div>
                <div class="crm-hbar-track">
                    <div class="crm-hbar-fill" style="width:${Math.max(pct, 3)}%;background:${barColors[i]};"></div>
                </div>
                <div class="crm-hbar-value">${formatMoney(gasto)}</div>
            </div>`;
        }).join('');
    }

    /* ========== RENDER TABLE ========== */
    function renderTable() {
        if (!tableBody) return;

        if (filteredClients.length === 0) {
            tableBody.innerHTML = '';
            if (noResults) noResults.classList.remove('d-none');
            if (totalFiltered) totalFiltered.textContent = '0';
            return;
        }

        if (noResults) noResults.classList.add('d-none');
        if (totalFiltered) totalFiltered.textContent = String(filteredClients.length);

        tableBody.innerHTML = filteredClients.map((c) => {
            const clas = calcularClasificacion(c);
            const gasto = parseFloat(c.total_gastado) || 0;
            const compras = parseInt(c.num_compras) || 0;
            const ultima = c.ultima_compra ? new Date(c.ultima_compra).toLocaleDateString('es-EC') : '-';
            const cedula = c.cedula || '-';
            const correo = c.correo || '-';

            return `<tr data-id="${c.id}">
                <td><strong style="color:var(--verde-oscuro);">${c.id}</strong></td>
                <td>${escapeHtml(cedula)}</td>
                <td><strong>${escapeHtml(c.nombre_completo || '')}</strong></td>
                <td>${escapeHtml(correo)}</td>
                <td><span class="crm-badge crm-badge-${clas.css}">${clas.icono} ${clas.tipo}</span></td>
                <td><strong>${formatMoney(gasto)}</strong></td>
                <td style="text-align:center;">${compras}</td>
                <td>${ultima}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn-action btn-view crm-btn-detail" type="button" data-id="${c.id}" title="Ver detalle">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Ver
                        </button>
                        <button class="btn-action crm-btn-edit" type="button" data-id="${c.id}" title="Editar" style="border-color:#f59e0b;color:#f59e0b;background:#fffbeb;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </button>
                        <button class="btn-action crm-btn-delete" type="button" data-id="${c.id}" data-name="${escapeHtml(c.nombre_completo || '')}" title="Eliminar" style="border-color:#ef4444;color:#ef4444;background:#fef2f2;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            Eliminar
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    /* ========== DETAIL PANEL ========== */
    async function openDetail(clienteId) {
        currentDetailId = clienteId;
        detailOverlay.classList.add('show');
        detailPanel.classList.add('show');
        document.body.style.overflow = 'hidden';

        const body = document.getElementById('crmDetailBody');
        body.innerHTML = '<div class="crm-empty-state"><p>Cargando...</p></div>';

        try {
            const res = await fetch('backend/api_cliente_detalle.php?cliente_id=' + clienteId);
            const data = await res.json();
            if (data.estado !== 'success') throw new Error(data.mensaje);
            renderDetail(data);
        } catch (e) {
            body.innerHTML = '<div class="crm-empty-state"><p style="color:#ef4444;">Error al cargar los datos del cliente.</p></div>';
        }
    }

    function closeDetail() {
        detailOverlay.classList.remove('show');
        detailPanel.classList.remove('show');
        document.body.style.overflow = '';
        currentDetailId = null;
    }

    function renderDetail(data) {
        const c = data.cliente;
        const ventas = data.ventas || [];
        const prodTop = data.productos_top || [];
        const tendencia = data.tendencia_mensual || [];
        const clas = calcularClasificacion(c);
        const ind = calcularIndicadores(c);

        let html = '';

        // Info
        html += `<div class="crm-section">
            <div class="crm-section-title">Informacion del Cliente</div>
            <div class="crm-info-grid">
                <div class="crm-info-item"><small>Nombre</small><strong>${escapeHtml(c.nombre_completo || '')}</strong></div>
                <div class="crm-info-item"><small>Cedula</small><strong>${escapeHtml(c.cedula || '-')}</strong></div>
                <div class="crm-info-item"><small>Correo</small><strong>${escapeHtml(c.correo || '-')}</strong></div>
                <div class="crm-info-item"><small>Telefono</small><strong>${escapeHtml(c.telefono || '-')}</strong></div>
                <div class="crm-info-item full-width"><small>Direccion</small><strong>${escapeHtml(c.direccion || '-')}</strong></div>
                <div class="crm-info-item"><small>Registro</small><strong>${c.fecha_registro ? new Date(c.fecha_registro).toLocaleDateString('es-EC') : '-'}</strong></div>
                <div class="crm-info-item"><small>Dias como cliente</small><strong>${ind.diasCliente} dias</strong></div>
            </div>
        </div>`;

        // Classification
        html += `<div class="crm-section">
            <div class="crm-section-title">Clasificacion</div>
            <div class="crm-classification-display ${clas.css}">
                ${clas.icono} ${clas.tipo.toUpperCase()}
            </div>
        </div>`;

        // Indicators
        const frecText = ind.frecuencia !== null ? `cada ~${ind.frecuencia} dias` : 'Sin datos';
        const sinComprasText = ind.diasSinCompra !== null
            ? (ind.diasSinCompra === 0 ? 'Hoy' : ` hace ${ind.diasSinCompra} dias`)
            : 'N/A';

        let trendClass = 'crm-trend-stable';
        let trendText = 'Sin datos suficientes';
        if (tendencia.length >= 2) {
            const last3 = tendencia.slice(-3);
            const prev3 = tendencia.slice(-6, -3);
            const sumLast = last3.reduce((s, m) => s + (parseFloat(m.total) || 0), 0);
            const sumPrev = prev3.reduce((s, m) => s + (parseFloat(m.total) || 0), 0);
            if (sumPrev > 0) {
                if (sumLast > sumPrev * 1.1) { trendClass = 'crm-trend-up'; trendText = 'En aumento'; }
                else if (sumLast < sumPrev * 0.9) { trendClass = 'crm-trend-down'; trendText = 'En descenso'; }
                else { trendText = 'Estable'; }
            } else if (sumLast > 0) {
                trendClass = 'crm-trend-up'; trendText = 'En aumento';
            }
        } else if (parseInt(c.num_compras) > 0) {
            trendText = 'Datos insuficientes';
        }

        html += `<div class="crm-section">
            <div class="crm-section-title">Indicadores de Fidelizacion</div>
            <div class="crm-indicators-grid">
                <div class="crm-indicator">
                    <div class="crm-ind-icon">📊</div>
                    <div class="crm-ind-label">Ticket Promedio</div>
                    <div class="crm-ind-value">${formatMoney(ind.ticketPromedio)}</div>
                    <div class="crm-ind-sub">por compra</div>
                </div>
                <div class="crm-indicator">
                    <div class="crm-ind-icon">🔄</div>
                    <div class="crm-ind-label">Frecuencia</div>
                    <div class="crm-ind-value">${frecText}</div>
                    <div class="crm-ind-sub">entre compras</div>
                </div>
                <div class="crm-indicator">
                    <div class="crm-ind-icon">📈</div>
                    <div class="crm-ind-label">Tendencia</div>
                    <div class="crm-ind-value ${trendClass}">${trendText}</div>
                    <div class="crm-ind-sub">ultimos 3 meses</div>
                </div>
                <div class="crm-indicator">
                    <div class="crm-ind-icon">🎯</div>
                    <div class="crm-ind-label">Retencion</div>
                    <div class="crm-ind-value">${ind.retencion}%</div>
                    <div class="crm-ind-sub">meses activos</div>
                </div>
            </div>
        </div>`;

        // Top products
        if (prodTop.length > 0) {
            const maxGasto = Math.max(...prodTop.map(p => parseFloat(p.total_gastado) || 0));
            html += `<div class="crm-section">
                <div class="crm-section-title">Productos Favoritos</div>
                <div class="crm-bar-chart">
                    ${prodTop.map((p, i) => {
                        const pct = maxGasto > 0 ? ((parseFloat(p.total_gastado) || 0) / maxGasto * 100) : 0;
                        const colors = ['primary', 'purple', 'blue'];
                        return `<div class="crm-bar-row">
                            <div class="crm-bar-label" title="${escapeHtml(p.nombre_producto)}">${escapeHtml(p.nombre_producto)}</div>
                            <div class="crm-bar-track">
                                <div class="crm-bar-fill ${colors[i % 3]}" style="width:${Math.max(pct, 2)}%"></div>
                            </div>
                            <div class="crm-bar-value">${formatMoney(p.total_gastado)} <small>(${p.cantidad_total})</small></div>
                        </div>`;
                    }).join('')}
                </div>
            </div>`;
        }

        // Monthly trend
        if (tendencia.length > 0) {
            const maxMes = Math.max(...tendencia.map(m => parseFloat(m.total) || 0));
            html += `<div class="crm-section">
                <div class="crm-section-title">Tendencia Mensual</div>
                <div class="crm-bar-chart">
                    ${tendencia.map(m => {
                        const pct = maxMes > 0 ? ((parseFloat(m.total) || 0) / maxMes * 100) : 0;
                        const label = m.mes || '';
                        return `<div class="crm-bar-row">
                            <div class="crm-bar-label">${label}</div>
                            <div class="crm-bar-track">
                                <div class="crm-bar-fill primary" style="width:${Math.max(pct, 2)}%"></div>
                            </div>
                            <div class="crm-bar-value">${formatMoney(m.total)}</div>
                        </div>`;
                    }).join('')}
                </div>
            </div>`;
        }

        // Purchase history
        html += `<div class="crm-section">
            <div class="crm-section-title">Historial de Compras (${ventas.length})</div>`;

        if (ventas.length === 0) {
            html += '<div class="crm-empty-state"><p>Este cliente no tiene compras registradas.</p></div>';
        } else {
            ventas.forEach((v) => {
                const estadoClass = v.estado === 'Anulada' ? 'crm-badge-inactivo' : 'crm-badge-frecuente';
                const estadoLabel = v.estado === 'Anulada' ? 'Anulada' : 'Pagada';
                const detalles = v.detalles || [];

                html += `<div class="crm-purchase-item" style="${v.estado === 'Anulada' ? 'opacity:0.55;' : ''}">
                    <div class="crm-purchase-header">
                        <div>
                            <span class="crm-purchase-id">#${v.id}</span>
                            <span class="crm-purchase-date" style="margin-left:10px;">${v.fecha_emision ? new Date(v.fecha_emision).toLocaleString('es-EC') : '-'}</span>
                            <span class="crm-badge ${estadoClass}" style="margin-left:8px;">${estadoLabel}</span>
                        </div>
                        <span class="crm-purchase-total">${formatMoney(v.total_factura)}</span>
                    </div>`;

                if (detalles.length > 0) {
                    html += '<div class="crm-purchase-products">';
                    detalles.forEach((d) => {
                        html += `<div class="crm-purchase-product">
                            <span>${escapeHtml(d.nombre_producto)} x${d.cantidad}</span>
                            <span>${formatMoney(d.precio_congelado * d.cantidad)}</span>
                        </div>`;
                    });
                    html += '</div>';
                }

                html += '</div>';
            });
        }

        html += '</div>';

        // Actions
        html += `<div class="d-flex gap-2 mt-3">
            <button class="btn btn-verde" onclick="window._crmEditFromDetail(${c.id})" style="flex:1;">Editar Cliente</button>
            <button class="btn btn-outline-secondary" onclick="window._crmCloseDetail()" style="flex:1;">Cerrar</button>
        </div>`;

        document.getElementById('crmDetailBody').innerHTML = html;
    }

    window._crmCloseDetail = closeDetail;
    window._crmEditFromDetail = (id) => {
        closeDetail();
        const client = allClients.find(c => parseInt(c.id) === parseInt(id));
        if (client) openEditModal(client);
    };

    /* ========== VALIDACIONES ========== */
    function validarCedulaEC(cedula) {
        if (!/^\d{10}$/.test(cedula)) return { ok: false, msg: 'La cedula debe tener exactamente 10 digitos.' };
        const provincia = parseInt(cedula.substring(0, 2), 10);
        if (provincia < 1 || provincia > 24) return { ok: false, msg: 'Los dos primeros digitos deben ser una provincia valida (01-24).' };
        const digitos = cedula.split('').map(Number);
        const factores = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        let suma = 0;
        for (let i = 0; i < 9; i++) {
            let val = digitos[i] * factores[i];
            if (val >= 10) val -= 9;
            suma += val;
        }
        const resto = suma % 10;
        const verificador = resto === 0 ? 0 : 10 - resto;
        if (digitos[9] !== verificador) return { ok: false, msg: 'La cedula no es valida (digito verificador incorrecto).' };
        return { ok: true, msg: '' };
    }

    function validarNombre(nombre) {
        if (!nombre || !nombre.trim()) return { ok: false, msg: 'El nombre es obligatorio.' };
        if (!/^[a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s]+$/.test(nombre.trim()))
            return { ok: false, msg: 'El nombre solo puede contener letras, espacios y tildes.' };
        if (nombre.trim().length < 2) return { ok: false, msg: 'El nombre debe tener al menos 2 caracteres.' };
        return { ok: true, msg: '' };
    }

    function validarCorreo(correo) {
        if (!correo || !correo.trim()) return { ok: true, msg: '' };
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.trim()))
            return { ok: false, msg: 'Ingrese un correo electronico valido.' };
        return { ok: true, msg: '' };
    }

    function validarTelefono(telefono) {
        if (!telefono || !telefono.trim()) return { ok: true, msg: '' };
        const cleaned = telefono.replace(/[\s\-\(\)]/g, '');
        if (!/^\d{7,15}$/.test(cleaned))
            return { ok: false, msg: 'El telefono debe tener entre 7 y 15 digitos.' };
        return { ok: true, msg: '' };
    }

    function setFieldStatus(input, ok, msg) {
        if (!input) return;
        input.classList.remove('is-invalid', 'is-valid');
        if (ok === null) return;
        input.classList.add(ok ? 'is-valid' : 'is-invalid');
        let feedback = input.parentElement.querySelector('.crm-field-feedback');
        if (!ok && msg) {
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'crm-field-feedback';
                input.parentElement.appendChild(feedback);
            }
            feedback.textContent = msg;
            feedback.style.display = 'block';
        } else if (feedback) {
            feedback.style.display = 'none';
        }
    }

    function validarFormularioCliente() {
        const cedula = (clientFormCedula?.value || '').trim();
        const nombre = (clientFormNombre?.value || '').trim();
        const correo = (clientFormCorreo?.value || '').trim();
        const telefono = (clientFormTelefono?.value || '').trim();

        const rNombre = validarNombre(nombre);
        setFieldStatus(clientFormNombre, rNombre.ok, rNombre.msg);

        let rCedula = { ok: true, msg: '' };
        if (cedula !== '') {
            rCedula = validarCedulaEC(cedula);
        }
        setFieldStatus(clientFormCedula, cedula === '' ? null : rCedula.ok, rCedula.msg);

        const rCorreo = validarCorreo(correo);
        setFieldStatus(clientFormCorreo, rCorreo.ok || (!correo.trim() && correo === '') ? null : rCorreo.ok, rCorreo.msg);

        const rTelefono = validarTelefono(telefono);
        setFieldStatus(clientFormTelefono, rTelefono.ok || (!telefono.trim() && telefono === '') ? null : rTelefono.ok, rTelefono.msg);

        const allValid = rNombre.ok && rCedula.ok && rCorreo.ok && rTelefono.ok;
        const errores = [];
        if (!rNombre.ok) errores.push(rNombre.msg);
        if (!rCedula.ok) errores.push(rCedula.msg);
        if (!rCorreo.ok) errores.push(rCorreo.msg);
        if (!rTelefono.ok) errores.push(rTelefono.msg);

        return { ok: allValid, errores };
    }

    /* ========== CRUD MODALS ========== */
    function openAddModal() {
        if (clientForm) clientForm.reset();
        if (clientFormAction) clientFormAction.value = 'create';
        if (clientFormId) clientFormId.value = '0';
        if (clientModalTitle) clientModalTitle.textContent = 'Agregar Cliente';
        if (clientFormMessage) { clientFormMessage.textContent = ''; clientFormMessage.className = 'alert d-none'; }
        [clientFormCedula, clientFormNombre, clientFormCorreo, clientFormTelefono, clientFormDireccion].forEach(f => {
            if (f) { f.classList.remove('is-valid', 'is-invalid'); }
            const fb = f?.parentElement?.querySelector('.crm-field-feedback');
            if (fb) fb.style.display = 'none';
        });
        openClientModal();
    }

    function openEditModal(c) {
        if (clientFormAction) clientFormAction.value = 'update';
        if (clientFormId) clientFormId.value = c.id;
        if (clientFormCedula) clientFormCedula.value = c.cedula || '';
        if (clientFormNombre) clientFormNombre.value = c.nombre_completo || '';
        if (clientFormCorreo) clientFormCorreo.value = c.correo || '';
        if (clientFormTelefono) clientFormTelefono.value = c.telefono || '';
        if (clientFormDireccion) clientFormDireccion.value = c.direccion || '';
        if (clientModalTitle) clientModalTitle.textContent = 'Editar Cliente';
        if (clientFormMessage) { clientFormMessage.textContent = ''; clientFormMessage.className = 'alert d-none'; }
        [clientFormCedula, clientFormNombre, clientFormCorreo, clientFormTelefono, clientFormDireccion].forEach(f => {
            if (f) { f.classList.remove('is-valid', 'is-invalid'); }
            const fb = f?.parentElement?.querySelector('.crm-field-feedback');
            if (fb) fb.style.display = 'none';
        });
        openClientModal();
    }

    async function saveClient() {
        const validation = validarFormularioCliente();
        if (!validation.ok) {
            if (clientFormMessage) {
                clientFormMessage.textContent = validation.errores.join(' ');
                clientFormMessage.className = 'alert alert-danger';
            }
            return;
        }

        const action = clientFormAction?.value || 'create';
        const payload = {
            id: parseInt(clientFormId?.value) || 0,
            cedula: (clientFormCedula?.value || '').trim(),
            nombre: (clientFormNombre?.value || '').trim(),
            correo: (clientFormCorreo?.value || '').trim(),
            telefono: (clientFormTelefono?.value || '').trim(),
            direccion: (clientFormDireccion?.value || '').trim()
        };

        try {
            const method = action === 'create' ? 'POST' : 'PUT';
            const res = await fetch('backend/api_clientes.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (!res.ok || result.estado !== 'success') {
                throw new Error(result.mensaje || 'Error al guardar.');
            }

            closeClientModalFn();
            await loadClients();
        } catch (e) {
            if (clientFormMessage) {
                clientFormMessage.textContent = e.message;
                clientFormMessage.className = 'alert alert-danger';
            }
        }
    }

    async function deleteClient(id) {
        try {
            const res = await fetch('backend/api_clientes.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();

            if (!res.ok || result.estado !== 'success') {
                throw new Error(result.mensaje || 'Error al eliminar.');
            }

            await loadClients();
        } catch (e) {
            alert(e.message);
        }
    }

    /* ========== REPORTS ========== */
    function generateReport() {
        if (!reportContent) return;

        const total = filteredClients.length;
        const totalGastado = filteredClients.reduce((s, c) => s + (parseFloat(c.total_gastado) || 0), 0);
        const totalCompras = filteredClients.reduce((s, c) => s + (parseInt(c.num_compras) || 0), 0);

        const clasCounts = { premium: 0, frecuente: 0, nuevo: 0, inactivo: 0, 'sin-compras': 0 };
        filteredClients.forEach((c) => {
            const clas = calcularClasificacion(c);
            clasCounts[clas.css] = (clasCounts[clas.css] || 0) + 1;
        });

        const topClients = [...filteredClients]
            .sort((a, b) => (parseFloat(b.total_gastado) || 0) - (parseFloat(a.total_gastado) || 0))
            .slice(0, 10);

        const ingresoPorClasif = {};
        filteredClients.forEach((c) => {
            const clas = calcularClasificacion(c);
            if (!ingresoPorClasif[clas.tipo]) ingresoPorClasif[clas.tipo] = 0;
            ingresoPorClasif[clas.tipo] += parseFloat(c.total_gastado) || 0;
        });

        let html = '';

        html += `<div class="crm-report-card">
            <h6>Resumen General</h6>
            <div class="crm-report-stat"><span>Total Clientes (filtrados)</span><span>${total}</span></div>
            <div class="crm-report-stat"><span>Total Facturado</span><span>${formatMoney(totalGastado)}</span></div>
            <div class="crm-report-stat"><span>Total Transacciones</span><span>${totalCompras}</span></div>
            <div class="crm-report-stat"><span>Ticket Promedio</span><span>${totalCompras > 0 ? formatMoney(totalGastado / totalCompras) : '$0.00'}</span></div>
        </div>`;

        html += `<div class="crm-report-card">
            <h6>Distribucion por Clasificacion</h6>
            ${Object.entries(clasCounts).map(([k, v]) => {
                const labels = { premium: 'Premium', frecuente: 'Frecuente', nuevo: 'Nuevo', inactivo: 'Inactivo', 'sin-compras': 'Sin compras' };
                const pct = total > 0 ? (v / total * 100).toFixed(1) : '0';
                return `<div class="crm-report-stat"><span>${labels[k] || k}</span><span>${v} (${pct}%)</span></div>`;
            }).join('')}
        </div>`;

        if (Object.keys(ingresoPorClasif).length > 0) {
            html += `<div class="crm-report-card">
                <h6>Ingresos por Clasificacion</h6>
                ${Object.entries(ingresoPorClasif).map(([k, v]) => {
                    const pct = totalGastado > 0 ? (v / totalGastado * 100).toFixed(1) : '0';
                    return `<div class="crm-report-stat"><span>${k}</span><span>${formatMoney(v)} (${pct}%)</span></div>`;
                }).join('')}
            </div>`;
        }

        if (topClients.length > 0) {
            html += `<div class="crm-report-card">
                <h6>Top 10 Clientes por Gasto</h6>
                ${topClients.map((c, i) => {
                    return `<div class="crm-report-stat"><span>${i + 1}. ${escapeHtml(c.nombre_completo || '')}</span><span>${formatMoney(c.total_gastado)}</span></div>`;
                }).join('')}
            </div>`;
        }

        reportContent.innerHTML = html;
        openReportModal();
    }

    /* ========== HELPERS ========== */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let isClearing = false;

    /* ========== EVENT LISTENERS ========== */
    if (btnBuscar) btnBuscar.addEventListener('click', applyFilters);
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            isClearing = true;
            if (searchInput) searchInput.value = '';
            if (filterClasificacion) filterClasificacion.selectedIndex = 0;
            if (filterFechaDesde) filterFechaDesde.value = '';
            if (filterFechaHasta) filterFechaHasta.value = '';
            if (filterGastoMin) filterGastoMin.value = '';
            if (filterGastoMax) filterGastoMax.value = '';
            if (filterOrdenar) filterOrdenar.selectedIndex = 0;
            isClearing = false;
            applyFilters();
            return false;
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (!isClearing) applyFilters();
        });
        searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); applyFilters(); } });
    }
    [filterClasificacion, filterOrdenar].forEach((el) => { if (el) el.addEventListener('change', applyFilters); });
    [filterFechaDesde, filterFechaHasta].forEach((el) => { if (el) el.addEventListener('change', applyFilters); });

    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const detailBtn = e.target.closest('.crm-btn-detail');
            if (detailBtn) { openDetail(detailBtn.dataset.id); return; }

            const editBtn = e.target.closest('.crm-btn-edit');
            if (editBtn) {
                const client = allClients.find(c => parseInt(c.id) === parseInt(editBtn.dataset.id));
                if (client) openEditModal(client);
                return;
            }

            const deleteBtn = e.target.closest('.crm-btn-delete');
            if (deleteBtn) {
                const name = deleteBtn.dataset.name || 'este cliente';
                if (confirm('Eliminar "' + name + '"? Esta accion no se puede deshacer.')) {
                    deleteClient(deleteBtn.dataset.id);
                }
            }
        });
    }

    if (btnNewClient) btnNewClient.addEventListener('click', openAddModal);

    if (clientForm) {
        clientForm.addEventListener('submit', (e) => { e.preventDefault(); saveClient(); });
    }

    /* ========== REAL-TIME VALIDATION ========== */
    if (clientFormNombre) {
        clientFormNombre.addEventListener('blur', function() {
            const r = validarNombre(this.value);
            if (this.value.trim()) setFieldStatus(this, r.ok, r.msg);
            else setFieldStatus(this, null, '');
        });
        clientFormNombre.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s]/g, '');
            if (this.classList.contains('is-invalid')) {
                const r = validarNombre(this.value);
                setFieldStatus(this, r.ok, r.msg);
            }
        });
    }
    if (clientFormCedula) {
        clientFormCedula.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val === '') { setFieldStatus(this, null, ''); return; }
            this.value = val.replace(/\D/g, '');
            const r = validarCedulaEC(this.value);
            setFieldStatus(this, r.ok, r.msg);
        });
        clientFormCedula.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
            if (this.classList.contains('is-invalid') && this.value.length === 10) {
                const r = validarCedulaEC(this.value);
                setFieldStatus(this, r.ok, r.msg);
            }
        });
    }
    if (clientFormCorreo) {
        clientFormCorreo.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val === '') { setFieldStatus(this, null, ''); return; }
            const r = validarCorreo(val);
            setFieldStatus(this, r.ok, r.msg);
        });
    }
    if (clientFormTelefono) {
        clientFormTelefono.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val === '') { setFieldStatus(this, null, ''); return; }
            const r = validarTelefono(val);
            setFieldStatus(this, r.ok, r.msg);
        });
        clientFormTelefono.addEventListener('input', function() {
            this.value = this.value.replace(/[^\d\s\-\(\)+]/g, '').substring(0, 20);
        });
    }

    if (detailCloseBtn) detailCloseBtn.addEventListener('click', closeDetail);
    if (detailOverlay) detailOverlay.addEventListener('click', (e) => { if (e.target === detailOverlay) closeDetail(); });

    if (reportBtn) reportBtn.addEventListener('click', generateReport);

    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeClientModalFn();
            closeReportModalFn();
        });
    });
    if (clientModal) clientModal.addEventListener('click', (e) => { if (e.target === clientModal) closeClientModalFn(); });
    if (reportModal) reportModal.addEventListener('click', (e) => { if (e.target === reportModal) closeReportModalFn(); });

    /* ========== INIT ========== */
    loadClients();
});
