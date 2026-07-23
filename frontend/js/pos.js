document.addEventListener('DOMContentLoaded', () => {
    const barcodeInput = document.getElementById('posBarcodeInput');
    const suggestionsBox = document.getElementById('posSuggestions');
    const clientSuggestionsBox = document.getElementById('clientSuggestions');
    const messageBox = document.getElementById('posMessage');
    const cartContainer = document.getElementById('posCartItems');
    const customerInput = document.getElementById('clienteSearch');
    const clienteIdInput = document.getElementById('clienteId');
    const amountInput = document.getElementById('montoPagado');
    const vueltoInput = document.getElementById('vuelto');
    const processButton = document.getElementById('btnProcesarVenta');
    const consumidorFinalButton = document.getElementById('btnConsumidorFinal');
    const receiptPreview = document.getElementById('receiptPreview');
    const subtotalValue = document.getElementById('subtotalValue');
    const ivaValue = document.getElementById('ivaValue');
    const totalValue = document.getElementById('totalValue');

    if (!barcodeInput || !cartContainer) {
        return;
    }

    function validarCedulaEcuatoriana(cedula) {
        if (!/^\d{10}$/.test(cedula)) return false;
        const provincia = parseInt(cedula.substring(0, 2), 10);
        if (provincia < 1 || provincia > 24) return false;
        if (parseInt(cedula[2], 10) >= 6) return false;
        let suma = 0;
        for (let i = 0; i < 9; i++) {
            let digito = parseInt(cedula[i], 10);
            let mult = (i % 2 === 0) ? digito * 2 : digito;
            suma += (mult >= 10) ? mult - 9 : mult;
        }
        const residuo = suma % 10;
        const verificador = (residuo === 0) ? 0 : 10 - residuo;
        return verificador === parseInt(cedula[9], 10);
    }

    function validarCedulaO_RUC(doc) {
        doc = doc.trim();
        if (doc.length === 10) return validarCedulaEcuatoriana(doc);
        if (doc.length === 13) return false;
        return false;
    }

    let cart = [];
    let currentSuggestions = [];
    let selectedClient = null;

    const formatCurrency = (value) => new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(value);

    const showMessage = (message, type = 'error') => {
        if (!messageBox) {
            return;
        }
        messageBox.textContent = message;
        messageBox.className = `pos-message ${type}`;
        messageBox.classList.remove('d-none');
    };

    const clearMessage = () => {
        if (!messageBox) {
            return;
        }
        messageBox.textContent = '';
        messageBox.className = 'pos-message d-none';
    };

    const sanitizeAmountInput = () => {
        if (!amountInput) {
            return 0;
        }

        const rawValue = amountInput.value;
        if (rawValue === '' || rawValue === null) {
            amountInput.value = '';
            return 0;
        }

        const numericValue = parseFloat(rawValue);
        if (!Number.isFinite(numericValue) || numericValue < 0) {
            amountInput.value = '0';
            return 0;
        }

        amountInput.value = String(numericValue);
        return numericValue;
    };

    const calculateTotals = () => {
        const subtotal = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const iva = subtotal * 0.15;
        const total = subtotal + iva;
        const paid = sanitizeAmountInput();
        const vuelto = paid >= total ? paid - total : 0;

        if (subtotalValue) subtotalValue.textContent = formatCurrency(subtotal);
        if (ivaValue) ivaValue.textContent = formatCurrency(iva);
        if (totalValue) totalValue.textContent = formatCurrency(total);
        if (vueltoInput) vueltoInput.value = formatCurrency(vuelto);
        return { subtotal, iva, total, vuelto };
    };

    const renderCart = () => {
        if (cart.length === 0) {
            cartContainer.innerHTML = '<div class="pos-placeholder">El carrito está vacío. Escanea un producto o búsquelo para comenzar.</div>';
            calculateTotals();
            return;
        }

        const rows = cart.map((item) => `
            <table class="pos-cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${item.nombre}</td>
                        <td>
                            <div class="pos-qty-buttons">
                                <button type="button" data-action="decrease" data-id="${item.id}">-</button>
                                <span>${item.cantidad}</span>
                                <button type="button" data-action="increase" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td>${formatCurrency(item.precio)}</td>
                        <td>${formatCurrency(item.precio * item.cantidad)}</td>
                    </tr>
                </tbody>
            </table>
        `).join('');

        cartContainer.innerHTML = rows;
        calculateTotals();
    };

    const updateQuantity = (productId, delta) => {
        const item = cart.find((entry) => entry.id === productId);
        if (!item) {
            return;
        }

        const nextQuantity = item.cantidad + delta;
        if (nextQuantity <= 0) {
            cart = cart.filter((entry) => entry.id !== productId);
            clearMessage();
            renderCart();
            return;
        }

        if (nextQuantity > item.stock) {
            showMessage(`No hay suficiente stock para ${item.nombre}. Stock disponible: ${item.stock}.`, 'error');
            return;
        }

        item.cantidad = nextQuantity;
        clearMessage();
        renderCart();
    };

    const addProductToCart = (product) => {
        const stock = Number(product.stock_disponible ?? 0);
        if (stock <= 0) {
            showMessage('El producto no tiene stock disponible.', 'error');
            return;
        }

        const existing = cart.find((item) => item.id === product.id);
        if (existing) {
            if (existing.cantidad + 1 > stock) {
                showMessage(`No hay suficiente stock para ${product.nombre_producto}.`, 'error');
                return;
            }
            existing.cantidad += 1;
        } else {
            cart.push({
                id: product.id,
                nombre: product.nombre_producto,
                precio: Number(product.precio_actual ?? 0),
                cantidad: 1,
                stock
            });
        }

        clearMessage();
        renderCart();
        barcodeInput.value = '';
        suggestionsBox.innerHTML = '';
    };

    const clientSuggestions = [];
    const findExactBarcodeMatch = (query, products) => {
        const normalized = String(query).trim();
        return products.find((product) => String(product.codigo_barras).trim() === normalized);
    };

    const searchProducts = async (query) => {
        const trimmed = query.trim();
        if (trimmed.length < 2) {
            suggestionsBox.innerHTML = '';
            currentSuggestions = [];
            return [];
        }

        try {
            const response = await fetch(`backend/api_productos.php?search=${encodeURIComponent(trimmed)}`);
            const products = await response.json();
            if (!Array.isArray(products) || products.length === 0) {
                suggestionsBox.innerHTML = '<div class="pos-suggestion">No se encontraron productos.</div>';
                currentSuggestions = [];
                return [];
            }

            currentSuggestions = products.slice(0, 4);
            suggestionsBox.innerHTML = currentSuggestions.map((product) => `
                <button type="button" class="pos-suggestion" data-product-id="${product.id}">
                    ${product.nombre_producto} · ${product.codigo_barras} · $${Number(product.precio_actual ?? 0).toFixed(2)}
                </button>
            `).join('');

            suggestionsBox.querySelectorAll('[data-product-id]').forEach((button) => {
                button.addEventListener('click', () => addProductToCart(currentSuggestions.find((product) => product.id === Number(button.getAttribute('data-product-id')))));
            });

            return currentSuggestions;
        } catch (error) {
            suggestionsBox.innerHTML = '<div class="pos-suggestion">No se pudo buscar el producto.</div>';
            currentSuggestions = [];
            return [];
        }
    };

    const updateClientFieldState = () => {
        const clientValue = customerInput?.value.trim() || '';
        const isConsumidorFinal = clientValue.toLowerCase() === 'consumidor final';

        if (!clientValue || isConsumidorFinal) {
            clientDetails?.classList.add('d-none');
            if (clienteCedula) {
                clienteCedula.value = '';
                clienteCedula.readOnly = false;
                clienteCedula.classList.remove('bg-light');
            }
            if (clienteCorreo) {
                clienteCorreo.value = '';
                clienteCorreo.readOnly = false;
                clienteCorreo.classList.remove('bg-light');
            }
            selectedClient = null;
            if (clienteIdInput) clienteIdInput.value = '';
            return;
        }

        clientDetails?.classList.remove('d-none');

        if (selectedClient && customerInput.value.trim().toLowerCase() === selectedClient.nombre_completo?.trim().toLowerCase()) {
            if (clienteCedula) {
                clienteCedula.value = selectedClient.cedula || '';
                clienteCedula.readOnly = true;
                clienteCedula.classList.add('bg-light');
            }
            if (clienteCorreo) {
                clienteCorreo.value = selectedClient.correo || '';
                clienteCorreo.readOnly = true;
                clienteCorreo.classList.add('bg-light');
            }
            if (clienteIdInput) clienteIdInput.value = String(selectedClient.id);
            return;
        }

        if (clienteCedula) {
            clienteCedula.readOnly = false;
            clienteCedula.classList.remove('bg-light');
        }
        if (clienteCorreo) {
            clienteCorreo.readOnly = false;
            clienteCorreo.classList.remove('bg-light');
        }
        selectedClient = null;
        if (clienteIdInput) clienteIdInput.value = '';
    };

    const searchClients = async (query) => {
        const trimmed = query.trim();
        if (trimmed.length < 2) {
            clientSuggestionsBox.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`backend/api_clientes.php?search=${encodeURIComponent(trimmed)}`);
            const clients = await response.json();
            if (!Array.isArray(clients) || clients.length === 0) {
                clientSuggestionsBox.innerHTML = '<div class="pos-suggestion">No se encontraron clientes.</div>';
                return;
            }

            clientSuggestions.length = 0;
            clientSuggestions.push(...clients.slice(0, 6));
            clientSuggestionsBox.innerHTML = clientSuggestions.map((cliente) => `
                <button type="button" class="pos-suggestion" data-client-id="${cliente.id}">
                    ${cliente.nombre_completo} · ${cliente.cedula || 'Sin cédula'} · ${cliente.correo || 'Sin correo'}
                </button>
            `).join('');

            clientSuggestionsBox.querySelectorAll('[data-client-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const selected = clientSuggestions.find((cliente) => cliente.id === Number(button.getAttribute('data-client-id')));
                    if (!selected) return;
                    customerInput.value = selected.nombre_completo;
                    selectedClient = selected;
                    if (clienteCedula) clienteCedula.value = selected.cedula || '';
                    if (clienteCorreo) clienteCorreo.value = selected.correo || '';
                    clientSuggestionsBox.innerHTML = '';
                    updateClientFieldState();
                });
            });
        } catch (error) {
            clientSuggestionsBox.innerHTML = '<div class="pos-suggestion">No se pudo buscar el cliente.</div>';
        }
    };

    barcodeInput.addEventListener('input', (event) => {
        searchProducts(event.target.value);
    });

    barcodeInput.addEventListener('keydown', async (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const query = barcodeInput.value.trim();
            if (!query) {
                showMessage('Ingrese un código o nombre para buscar un producto.', 'error');
                return;
            }

            const products = await searchProducts(query);
            const exactMatch = findExactBarcodeMatch(query, products);
            if (exactMatch) {
                addProductToCart(exactMatch);
                return;
            }

            if (currentSuggestions.length > 0) {
                addProductToCart(currentSuggestions[0]);
            } else {
                showMessage('No se encontró un producto con ese criterio.', 'error');
            }
        }
    });

    customerInput?.addEventListener('input', (event) => {
        toggleClientDetails();
        searchClients(event.target.value);
    });

    const invoiceModal = document.getElementById('invoiceModal');
    const invoiceModalBody = document.getElementById('invoiceModalBody');
    const closeInvoiceButton = document.getElementById('closeInvoiceModal');
    const showInvoiceButton = document.getElementById('btnMostrarFactura');
    const clientDetails = document.getElementById('clientDetails');
    const clienteCedula = document.getElementById('clienteCedula');
    const clienteCorreo = document.getElementById('clienteCorreo');
    const btnCameraScan = document.getElementById('btnCameraScan');
    const cameraModal = document.getElementById('cameraModal');
    const closeCameraModal = document.getElementById('closeCameraModal');
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraMessage = document.getElementById('cameraMessage');
    let lastInvoice = null;
    let codeReader = null;

    const stopCameraScan = async () => {
        if (!codeReader && !cameraVideo) return;
        try {
            if (codeReader) {
                await codeReader.reset();
            }
            if (cameraVideo && cameraVideo.srcObject) {
                const stream = cameraVideo.srcObject;
                const tracks = stream.getTracks();
                tracks.forEach((track) => track.stop());
                cameraVideo.srcObject = null;
            }
        } catch (error) {
            console.warn('Error deteniendo la cámara:', error);
        }
        codeReader = null;
        if (cameraMessage) {
            cameraMessage.textContent = '';
            cameraMessage.className = 'pos-message';
        }
    };

    const closeCameraDialog = async () => {
        if (!cameraModal) return;
        cameraModal.classList.remove('show');
        await stopCameraScan();
    };

    const setCameraMessage = (text, type = 'info') => {
        if (!cameraMessage) return;
        cameraMessage.textContent = text;
        cameraMessage.className = `pos-message ${type}`;
    };

    const startCameraScan = async () => {
        if (!cameraModal || !cameraVideo) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setCameraMessage('Tu navegador no soporta acceso a la cámara.', 'error');
            return;
        }

        cameraModal.classList.add('show');
        setCameraMessage('Buscando cámara... por favor espera.', 'info');

        try {
            codeReader = new ZXing.BrowserMultiFormatReader();
            const videoInputDevices = await codeReader.listVideoInputDevices();
            if (videoInputDevices.length === 0) {
                setCameraMessage('No se encontró ninguna cámara disponible.', 'error');
                return;
            }

            const selectedDeviceId = videoInputDevices[0].deviceId;
            setCameraMessage('Apunta al código de barras. Escaneando...', 'info');

            const result = await codeReader.decodeOnceFromVideoDevice(selectedDeviceId, cameraVideo);
            if (result && result.text) {
                const barcode = result.text.trim();
                setCameraMessage(`Código detectado: ${barcode}`, 'success');
                barcodeInput.value = barcode;
                const products = await searchProducts(barcode);
                const exactMatch = findExactBarcodeMatch(barcode, products);
                if (exactMatch) {
                    addProductToCart(exactMatch);
                    setCameraMessage(`Producto agregado: ${exactMatch.nombre_producto}`, 'success');
                    await closeCameraDialog();
                    return;
                }

                setCameraMessage('Código leído, pero no se encontró un producto exacto. Selecciona uno de los resultados.', 'error');
            }
        } catch (error) {
            console.error('Error en lectura de cámara:', error);
            setCameraMessage('No se pudo leer el código. Asegúrate de permitir el acceso a la cámara y vuelve a intentarlo.', 'error');
        }
    };

    const buildInvoiceHtml = (invoice) => {
        const rows = invoice.items.map((item) => `
            <tr>
                <td class="prod-nombre">${item.nombre}</td>
                <td>${item.cantidad}</td>
                <td>${formatCurrency(item.precio)}</td>
                <td>${formatCurrency(item.precio * item.cantidad)}</td>
            </tr>
        `).join('');

        return `
            <div>
                <div class="encabezado">
                    <div class="sello">F</div>
                    <h1 class="titulo">Factura</h1>
                    <div class="subtitulo">Comprobante de venta</div>
                </div>

                <hr class="linea-punteada">

                <div class="meta">
                    <div class="fila"><span>Factura ID</span><b>#${invoice.venta_id}</b></div>
                    <div class="fila"><span>Fecha</span><b>${invoice.fecha}</b></div>
                    <div class="fila"><span>Cliente</span><b>${invoice.cliente}</b></div>
                    <div class="fila"><span>Cajero</span><b>${invoice.cajero || 'N/A'}</b></div>
                </div>

                <table class="items">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>

                <div class="resumen">
                    <div class="fila"><span>Subtotal</span><span>${formatCurrency(invoice.subtotal)}</span></div>
                    <div class="fila"><span>IVA 15%</span><span>${formatCurrency(invoice.iva)}</span></div>
                    <div class="fila total"><span>Total</span><span>${formatCurrency(invoice.total)}</span></div>
                    <div class="fila pagado"><span>Pagado</span><b>${formatCurrency(invoice.pagado)}</b></div>
                    <div class="fila vuelto"><span>Vuelto</span><b>${formatCurrency(invoice.vuelto)}</b></div>
                </div>

                <div class="codigo-barras"></div>
                <div class="footer-msg">Gracias por su compra</div>

                <div class="acciones">
                    <button class="btn btn-cerrar-secundario" type="button" onclick="document.getElementById('invoiceModal').classList.remove('show')">Cerrar</button>
                    <button class="btn btn-imprimir" type="button" onclick="window.print()">Imprimir factura</button>
                </div>
            </div>
        `;
    };

    const openInvoiceModal = (invoice) => {
        if (!invoiceModal || !invoiceModalBody) return;
        invoiceModalBody.innerHTML = buildInvoiceHtml(invoice);
        invoiceModal.classList.add('show');
    };

    const closeInvoice = () => {
        if (!invoiceModal) return;
        invoiceModal.classList.remove('show');
    };

    const toggleClientDetails = () => {
        updateClientFieldState();
    };

    const processSale = async () => {
        if (cart.length === 0) {
            showMessage('El carrito está vacío. Agrega productos antes de vender.', 'error');
            return;
        }

        const { subtotal, iva, total, vuelto } = calculateTotals();
        const paid = sanitizeAmountInput();
        if (!Number.isFinite(paid) || paid < total) {
            showMessage('El monto pagado debe ser mayor o igual al total de la venta.', 'error');
            return;
        }

        const clientName = (customerInput?.value || 'Consumidor Final').trim() || 'Consumidor Final';
        const cedula = selectedClient ? (selectedClient.cedula || '') : (clienteCedula?.value.trim() || '');
        const correo = selectedClient ? (selectedClient.correo || '') : (clienteCorreo?.value.trim() || '');

        if (clientName.toLowerCase() !== 'consumidor final') {
            if (!selectedClient && cedula === '') {
                showMessage('Ingresa la cédula del cliente.', 'error');
                return;
            }
            if (!selectedClient && cedula !== '' && !validarCedulaO_RUC(cedula)) {
                showMessage('La cédula o RUC ingresado no es válido.', 'error');
                return;
            }
            if (!selectedClient && (correo === '' || !correo.includes('@'))) {
                showMessage('Ingresa un correo válido del cliente.', 'error');
                return;
            }
        }

        const payload = {
            items: cart.map((item) => ({
                id: item.id,
                cantidad: item.cantidad
            })),
            cliente: clientName,
            cliente_id: selectedClient ? selectedClient.id : null,
            cedula: clientName.toLowerCase() !== 'consumidor final' ? cedula : '',
            correo: clientName.toLowerCase() !== 'consumidor final' ? correo : '',
            monto_pagado: paid
        };

        processButton.disabled = true;
        processButton.textContent = 'Procesando...';

        try {
            const response = await fetch('backend/api_ventas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok || result.estado !== 'success') {
                throw new Error(result.mensaje || 'No se pudo procesar la venta.');
            }

            lastInvoice = {
                venta_id: result.venta_id,
                fecha: new Date().toLocaleString('es-ES'),
                cliente: payload.cliente,
                cajero: result.cajero || '',
                items: cart,
                subtotal,
                iva,
                total,
                pagado: paid,
                vuelto
            };

            if (receiptPreview) {
                receiptPreview.innerHTML = `
                    <h3>Recibo / factura</h3>
                    <p>Venta registrada correctamente.</p>
                    <p>Haz clic en <strong>Mostrar factura</strong> para ver el documento en la ventana emergente.</p>
                `;
            }

            if (showInvoiceButton) {
                showInvoiceButton.classList.remove('d-none');
            }

            cart = [];
            renderCart();
            if (customerInput) customerInput.value = 'Consumidor Final';
            if (amountInput) amountInput.value = '';
            if (vueltoInput) vueltoInput.value = formatCurrency(0);
            toggleClientDetails();
            showMessage(result.mensaje, 'success');
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            processButton.disabled = false;
            processButton.textContent = 'Procesar venta';
        }
    };

    cartContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }
        const { action, id } = button.dataset;
        updateQuantity(Number(id), action === 'increase' ? 1 : -1);
    });

    amountInput?.addEventListener('input', calculateTotals);
    amountInput?.addEventListener('blur', calculateTotals);
    consumidorFinalButton?.addEventListener('click', () => {
        if (customerInput) {
            customerInput.value = 'Consumidor Final';
        }
        toggleClientDetails();
    });
    customerInput?.addEventListener('input', toggleClientDetails);
    processButton?.addEventListener('click', processSale);
    showInvoiceButton?.addEventListener('click', () => {
        if (lastInvoice) {
            openInvoiceModal(lastInvoice);
        }
    });
    closeInvoiceButton?.addEventListener('click', closeInvoice);
    btnCameraScan?.addEventListener('click', startCameraScan);
    closeCameraModal?.addEventListener('click', closeCameraDialog);
    toggleClientDetails();
    barcodeInput.focus();
    renderCart();
    calculateTotals();
});