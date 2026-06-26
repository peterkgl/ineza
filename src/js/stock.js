document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const stockTable = document.getElementById('stockTable');
    const stockRows = document.querySelectorAll('.stock-row');
    
    // Search filter
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            stockRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show empty message if all rows are hidden
            const visibleRows = Array.from(stockRows).filter(row => row.style.display !== 'none');
            let emptyMsg = document.getElementById('stock-empty-msg');
            if (visibleRows.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('tr');
                    emptyMsg.id = 'stock-empty-msg';
                    emptyMsg.innerHTML = '<td colspan="11" class="table-empty">No matching stock records found.</td>';
                    stockTable.querySelector('tbody').appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) {
                    emptyMsg.remove();
                }
            }
        });
    }

    // Modal elements
    const movementModal = document.getElementById('movementModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const movementList = document.getElementById('movementList');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const refreshBtn = document.getElementById('refreshBtn');

    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    function escapeHtml(text) {
        if (!text) return "";
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Open Modal and Fetch Movements
    document.querySelectorAll('.view-movement-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            // Set headers
            modalSubtitle.innerHTML = `<strong>Product:</strong> ${productName}`;

            // Show loader
            movementList.innerHTML = '<tr><td colspan="10" class="table-empty">Loading movement history...</td></tr>';
            movementModal.style.display = 'flex';

            // Fetch data
            fetch(`stock_api.php?action=movements&product_id=${productId}`)
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        renderMovements(res.data);
                    } else {
                        movementList.innerHTML = `<tr><td colspan="10" class="table-empty" style="color:var(--red);">${res.message}</td></tr>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    movementList.innerHTML = '<tr><td colspan="10" class="table-empty" style="color:var(--red);">Failed to load movements. Please try again.</td></tr>';
                });
        });
    });

    // Render movement rows
    function renderMovements(movements) {
        if (!movements || movements.length === 0) {
            movementList.innerHTML = '<tr><td colspan="10" class="table-empty">No movements recorded for this inventory.</td></tr>';
            return;
        }

        movementList.innerHTML = movements.map(m => {
            let typeBadge = '';
            switch (m.movement_type) {
                case 'PURCHASE_IN':
                    typeBadge = '<span class="status-pill pill-green">PURCHASE IN</span>';
                    break;
                case 'SALE_OUT':
                    typeBadge = '<span class="status-pill pill-red">SALE OUT</span>';
                    break;
                case 'TRANSFER_IN':
                    typeBadge = '<span class="status-pill pill-blue">TRANSFER IN</span>';
                    break;
                case 'TRANSFER_OUT':
                    typeBadge = '<span class="status-pill pill-orange">TRANSFER OUT</span>';
                    break;
                case 'ADJUSTMENT_IN':
                    typeBadge = '<span class="status-pill pill-green">ADJ IN</span>';
                    break;
                case 'ADJUSTMENT_OUT':
                    typeBadge = '<span class="status-pill pill-red">ADJ OUT</span>';
                    break;
                default:
                    typeBadge = `<span class="status-pill">${m.movement_type}</span>`;
            }

            // Format dates and values
            const qtyVal = Number(m.qty_kg).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const costVal = m.unit_cost_usd !== null ? '$' + Number(m.unit_cost_usd).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }) : '—';
            const totalVal = m.total_value_usd !== null ? '$' + Number(m.total_value_usd).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
            
            // Reference display
            let refDisplay = m.reference_type ? `${m.reference_type.toUpperCase()} #${m.reference_id}` : '—';
            if (m.reference_type === 'purchasing' && m.purchase_no) {
                refDisplay = `${m.purchase_no}`;
            }

            const notesVal = m.notes ? escapeHtml(m.notes) : '—';
            const creatorVal = escapeHtml(m.creator_name);
            const warehouseName = escapeHtml(m.warehouse_name || '—');
            const lotCode = escapeHtml(m.lots_code || '—');

            return `
                <tr>
                    <td style="white-space: nowrap;">${m.movement_date}</td>
                    <td>${typeBadge}</td>
                    <td>${warehouseName}</td>
                    <td><span class="status-pill pill-purple" style="font-weight: 600;">${lotCode}</span></td>
                    <td style="text-align: right; font-weight: 500;">${qtyVal} ${m.uom_code}</td>
                    <td style="text-align: right; color: var(--text2);">${costVal}</td>
                    <td style="text-align: right; font-weight: 500; color: var(--green);">${totalVal}</td>
                    <td style="font-weight: 500; font-size: 11px;">${refDisplay}</td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${notesVal}">${notesVal}</td>
                    <td style="color: var(--text2);">${creatorVal}</td>
                </tr>
            `;
        }).join('');
    }

    // Close Modal
    const closeModal = () => {
        movementModal.style.display = 'none';
    };

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);

    // Close modal on click outside
    window.addEventListener('click', function(e) {
        if (e.target === movementModal) {
            closeModal();
        }
    });
});
