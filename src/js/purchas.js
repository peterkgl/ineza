document.addEventListener("DOMContentLoaded", function () {
  var purchasesList = document.getElementById("purchasesList");
  var alertPlaceholder = document.getElementById("alertPlaceholder");
  
  var addBtn = document.getElementById("addBtn");
  var refreshBtn = document.getElementById("refreshBtn");
  
  // Delete Modal Components
  var confirmOverlay = document.getElementById("confirmOverlay");
  var confirmBody = document.getElementById("confirmBody");
  var confirmCancelBtn = document.getElementById("confirmCancelBtn");
  var confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  
  // Detail Modal Components
  var detailOverlay = document.getElementById("detailModalOverlay");
  var detailCloseBtn = document.getElementById("detailCloseBtn");
  var detailContent = document.getElementById("detailContent");

  // Status Modal Components
  var statusModalOverlay = document.getElementById("statusModalOverlay");
  var statusCloseBtn = document.getElementById("statusCloseBtn");
  var statusCancelBtn = document.getElementById("statusCancelBtn");
  var statusSaveBtn = document.getElementById("statusSaveBtn");
  var statusCurrentPill = document.getElementById("statusCurrentPill");
  var activeStatusId = null;
  var activeStatusCurrent = null;
  
  // Search & Filter
  var searchInput = document.getElementById("searchInput");
  var statusFilter = document.getElementById("statusFilter");
  
  var activeDeleteId = null;
  var allPurchases = [];
  
  // Fetch lists
  function getFilteredPurchases() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : "";
    var status = statusFilter ? statusFilter.value : "";

    return allPurchases.filter(function (p) {
      var matchQuery = true;
      if (query) {
        var refVal = (p.purchase_no || "").toLowerCase();
        var lotVal = (p.lots_code || "").toLowerCase();
        var prodVal = (p.product_name || "").toLowerCase();
        var suppVal = (p.supplier_name || "").toLowerCase();
        var whVal = (p.warehouse_name || "").toLowerCase();
        matchQuery = refVal.indexOf(query) !== -1 || lotVal.indexOf(query) !== -1 || prodVal.indexOf(query) !== -1 || suppVal.indexOf(query) !== -1 || whVal.indexOf(query) !== -1;
      }

      var matchStatus = true;
      if (status !== "") {
        matchStatus = p.status === status;
      }

      return matchQuery && matchStatus;
    });
  }

  if (searchInput) searchInput.addEventListener("input", renderPurchases);
  if (statusFilter) statusFilter.addEventListener("change", renderPurchases);

  var isFirstLoad = true;
  function fetchPurchases() {
    if (isFirstLoad && window.initialPurchasesData) {
      isFirstLoad = false;
      allPurchases = window.initialPurchasesData;
      renderPurchases();
      updateStats(allPurchases);
      return;
    }
    isFirstLoad = false;
    
    fetch("purchas_api.php?action=list")
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.success) {
          allPurchases = result.data;
          renderPurchases();
          updateStats(result.data);
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
      })
      .catch(function (error) {
        console.error("Error fetching purchases:", error);
        showAlert(alertPlaceholder, "error", "An error occurred while loading purchases.");
      });
  }

  function renderPurchases() {
    var filtered = getFilteredPurchases();
    var totalItems = filtered.length;

    if (totalItems === 0) {
      purchasesList.innerHTML =
        '<tr><td colspan="11" class="table-empty">' +
        (searchInput && searchInput.value.trim()
          ? "No matching purchases found."
          : "No purchases recorded yet.") +
        "</td></tr>";
      return;
    }

    var html = "";
    filtered.forEach(function (p, index) {
      var globalIndex = index + 1;
      var purchaseNo = escapeHtml(p.purchase_no);
      var lotCode = escapeHtml(p.lots_code);
      var productName = escapeHtml(p.product_name);
      var supplierName = escapeHtml(p.supplier_name);
      var warehouseName = escapeHtml(p.warehouse_name);
      var qty = parseFloat(p.quantity_kg).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + ' ' + escapeHtml(p.uom_code || 'kg');
      var val = p.purchase_value_usd ? '$' + parseFloat(p.purchase_value_usd).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
      var pDate = escapeHtml(p.purchase_date);
      
      var statusClass = 'pill-gray';
      if (p.status === 'confirmed') statusClass = 'pill-blue';
      else if (p.status === 'received') statusClass = 'pill-green';
      else if (p.status === 'cancelled') statusClass = 'pill-red';
      
      var statusLabel = '<span class="status-pill ' + statusClass + '">' + p.status.toUpperCase() + '</span>';

      var actionBtns = "";
      actionBtns += '<button class="btn-icon-only view-btn" title="View Details" data-id="' + p.id + '">' +
        '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' +
        '</button>';

      // Status button — always visible
      actionBtns += '<button class="btn-icon-only status-btn" title="Change Status" data-id="' + p.id + '">' +
        '<svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' +
        '</button>';
      
      if (p.status === 'draft' || p.status === 'confirmed') {
        actionBtns += '<button class="btn-icon-only edit" title="Edit Purchase" data-id="' + p.id + '">' +
          '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
          '</button>';
      }
      if (p.status === 'draft') {
        actionBtns += '<button class="btn-icon-only delete" title="Delete Purchase" data-id="' + p.id + '">' +
          '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
          '</button>';
      }

      html += "<tr>" +
        "<td>" + globalIndex + "</td>" +
        "<td><strong>" + purchaseNo + "</strong></td>" +
        "<td><span class=\"code-badge\">" + lotCode + "</span></td>" +
        "<td>" + productName + "</td>" +
        "<td>" + supplierName + "</td>" +
        "<td>" + warehouseName + "</td>" +
        "<td>" + qty + "</td>" +
        "<td>" + val + "</td>" +
        "<td>" + statusLabel + "</td>" +
        "<td>" + pDate + "</td>" +
        '<td style="text-align: right;">' +
        '<div class="action-buttons" style="justify-content: flex-end;">' +
        actionBtns +
        "</div>" +
        "</td>" +
        "</tr>";
    });

    purchasesList.innerHTML = html;
    attachRowEventListeners(filtered);
  }

  function updateStats(purchases) {
    var total = purchases.length;
    var totalValUsd = 0.0;
    var totalQtyKg = 0.0;

    purchases.forEach(function (p) {
      totalQtyKg += parseFloat(p.quantity_kg || 0);
      totalValUsd += parseFloat(p.purchase_value_usd || 0);
    });

    document.getElementById("stat-total").textContent = total;
    document.getElementById("stat-qty").textContent = totalQtyKg.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' kg';
    document.getElementById("stat-value").textContent = '$' + totalValUsd.toLocaleString(undefined, { maximumFractionDigits: 2 });
  }

  function attachRowEventListeners(purchases) {
    var viewBtns = document.querySelectorAll(".action-buttons .view-btn");
    var editBtns = document.querySelectorAll(".action-buttons .edit");
    var deleteBtns = document.querySelectorAll(".action-buttons .delete");
    var statusBtns = document.querySelectorAll(".action-buttons .status-btn");

    viewBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var p = purchases.find(function (x) { return x.id === id; });
        if (p) {
          showPurchaseDetails(p);
        }
      });
    });

    editBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        window.location.href = "record.php?id=" + id;
      });
    });

    deleteBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var p = purchases.find(function (x) { return x.id === id; });
        if (p) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete purchase reference "' + p.purchase_no + '"? This will reverse the stock levels in the warehouse.';
          confirmOverlay.style.display = "flex";
        }
      });
    });

    statusBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var p = purchases.find(function (x) { return x.id === id; });
        if (p) {
          openStatusModal(p);
        }
      });
    });
  }

  // Show detailed summary of a purchase
  function showPurchaseDetails(p) {
    var gradesHtml = "";
    if (p.grades && p.grades.length > 0) {
      gradesHtml += '<table class="data-table" style="margin-top: 10px;"><thead><tr><th>Element</th><th>Grade %</th><th>Notes</th></tr></thead><tbody>';
      p.grades.forEach(function(g) {
        var primaryTxt = (g.product_element_id === p.primary_element_id) ? ' <span class="grade-badge badge-orange">Primary</span>' : '';
        gradesHtml += '<tr><td><strong>' + escapeHtml(g.element_code) + '</strong> (' + escapeHtml(g.element_name) + ')' + primaryTxt + '</td><td>' + g.grade_pct + '%</td><td>' + escapeHtml(g.notes || '—') + '</td></tr>';
      });
      gradesHtml += '</tbody></table>';
    } else {
      gradesHtml = '<div style="color:var(--text3); font-size:12px; font-style:italic;">No grades recorded.</div>';
    }

    var html = '<div class="detail-modal-list">' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Purchase Reference</div><div class="detail-modal-val">' + escapeHtml(p.purchase_no) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Status</div><div class="detail-modal-val">' + escapeHtml(p.status.toUpperCase()) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Purchase Date</div><div class="detail-modal-val">' + escapeHtml(p.purchase_date) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Delivery Date</div><div class="detail-modal-val">' + escapeHtml(p.delivery_date) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Supplier</div><div class="detail-modal-val">' + escapeHtml(p.supplier_name) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Warehouse</div><div class="detail-modal-val">' + escapeHtml(p.warehouse_name) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Product</div><div class="detail-modal-val">' + escapeHtml(p.product_name) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Lot</div><div class="detail-modal-val">' + escapeHtml(p.lots_code) + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Quantity</div><div class="detail-modal-val">' + parseFloat(p.quantity_kg).toLocaleString() + ' ' + escapeHtml(p.uom_code || 'kg') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Exchange Rate</div><div class="detail-modal-val">' + (p.exchange_rate ? parseFloat(p.exchange_rate).toLocaleString() + ' RWF / USD' : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Price per kg (USD)</div><div class="detail-modal-val">' + (p.price_per_kg_usd ? '$' + parseFloat(p.price_per_kg_usd).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Price per kg (RWF)</div><div class="detail-modal-val">' + (p.price_per_kg_rwf ? parseFloat(p.price_per_kg_rwf).toLocaleString() + ' RWF' : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Total Value (USD)</div><div class="detail-modal-val">' + (p.purchase_value_usd ? '$' + parseFloat(p.purchase_value_usd).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Total Value (RWF)</div><div class="detail-modal-val">' + (p.purchase_value_rwf ? parseFloat(p.purchase_value_rwf).toLocaleString() + ' RWF' : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">RRA Tax (USD)</div><div class="detail-modal-val">' + (p.tax_rra ? '$' + parseFloat(p.tax_rra).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">RMA Tax (USD)</div><div class="detail-modal-val">' + (p.tax_rma ? '$' + parseFloat(p.tax_rma).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Inkomane Tax (USD)</div><div class="detail-modal-val">' + (p.tax_inkomane ? '$' + parseFloat(p.tax_inkomane).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item"><div class="detail-modal-label">Net Paid (USD)</div><div class="detail-modal-val" style="color:var(--orange)">' + (p.net_paid_supplier_usd ? '$' + parseFloat(p.net_paid_supplier_usd).toLocaleString() : '—') + '</div></div>' +
      '<div class="detail-modal-item detail-modal-full"><div class="detail-modal-label">Notes</div><div class="detail-modal-val">' + escapeHtml(p.notes || 'No notes.') + '</div></div>' +
      '</div>' +
      '<div style="margin-top: 20px;">' +
      '<div style="font-size: 11px; text-transform: uppercase; color: var(--text3); font-weight: 500; margin-bottom: 8px;">Product Elements Analysis</div>' +
      gradesHtml +
      '</div>';
      
    detailContent.innerHTML = html;
    detailOverlay.style.display = "flex";
  }

  // Delete Action confirmation
  if (confirmCancelBtn) confirmCancelBtn.addEventListener("click", function() {
    confirmOverlay.style.display = "none";
    activeDeleteId = null;
  });

  if (confirmDeleteBtn) confirmDeleteBtn.addEventListener("click", function() {
    if (activeDeleteId <= 0) return;
    
    var formData = new FormData();
    formData.append("id", activeDeleteId);
    formData.append("token", document.querySelector('input[name="token"]').value);
    
    confirmDeleteBtn.setAttribute("disabled", "true");
    confirmDeleteBtn.textContent = "Deleting...";
    
    fetch("purchas_api.php?action=delete", {
      method: "POST",
      body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(result) {
      confirmDeleteBtn.removeAttribute("disabled");
      confirmDeleteBtn.textContent = "Delete";
      confirmOverlay.style.display = "none";
      activeDeleteId = null;
      
      if (result.success) {
        showAlert(alertPlaceholder, "success", result.message);
        fetchPurchases();
      } else {
        showAlert(alertPlaceholder, "error", result.message);
      }
    })
    .catch(function(err) {
      confirmDeleteBtn.removeAttribute("disabled");
      confirmDeleteBtn.textContent = "Delete";
      confirmOverlay.style.display = "none";
      activeDeleteId = null;
      console.error("Error deleting purchase:", err);
      showAlert(alertPlaceholder, "error", "An error occurred while deleting the purchase.");
    });
  });

  // Details Modal close
  if (detailCloseBtn) detailCloseBtn.addEventListener("click", function() {
    detailOverlay.style.display = "none";
  });

  // Status Modal Actions
  function openStatusModal(p) {
    activeStatusId = p.id;
    activeStatusCurrent = p.status;
    
    var pillClass = 'pill-gray';
    if (p.status === 'confirmed') pillClass = 'pill-blue';
    else if (p.status === 'received') pillClass = 'pill-green';
    else if (p.status === 'cancelled') pillClass = 'pill-red';
    
    statusCurrentPill.innerHTML = '<span class="status-pill ' + pillClass + '">' + p.status.toUpperCase() + '</span>';
    
    // Reset selection highlighting
    var allOptions = statusModalOverlay.querySelectorAll('.status-option');
    allOptions.forEach(function(opt) {
      opt.classList.remove('selected');
      var radio = opt.querySelector('input[type="radio"]');
      if (radio.value === p.status) {
        radio.checked = true;
        opt.classList.add('selected');
      } else {
        radio.checked = false;
      }
    });
    
    statusModalOverlay.classList.add('active');
  }

  function closeStatusModal() {
    statusModalOverlay.classList.remove('active');
    activeStatusId = null;
    activeStatusCurrent = null;
  }

  var statusOptions = statusModalOverlay ? statusModalOverlay.querySelectorAll('.status-option') : [];
  statusOptions.forEach(function(opt) {
    opt.addEventListener('click', function() {
      statusOptions.forEach(function(o) { o.classList.remove('selected'); });
      opt.classList.add('selected');
      var radio = opt.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    });
  });

  if (statusCloseBtn) statusCloseBtn.addEventListener('click', closeStatusModal);
  if (statusCancelBtn) statusCancelBtn.addEventListener('click', closeStatusModal);

  if (statusSaveBtn) statusSaveBtn.addEventListener('click', function() {
    var selectedRadio = statusModalOverlay.querySelector('input[name="new_status"]:checked');
    if (!selectedRadio) {
      showAlert(alertPlaceholder, 'error', 'Please select a status.');
      return;
    }
    var newStatus = selectedRadio.value;

    if (newStatus === activeStatusCurrent) {
      closeStatusModal();
      return;
    }

    statusSaveBtn.setAttribute('disabled', 'true');
    statusSaveBtn.textContent = 'Updating...';

    var formData = new FormData();
    formData.append('id', activeStatusId);
    formData.append('status', newStatus);
    formData.append('token', document.querySelector('input[name="token"]').value);

    fetch('purchas_api.php?action=update_status', {
      method: 'POST',
      body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(result) {
      statusSaveBtn.removeAttribute('disabled');
      statusSaveBtn.textContent = 'Update Status';

      if (result.success) {
        closeStatusModal();
        // Update local data immediately
        allPurchases.forEach(function(p) {
          if (p.id === activeStatusId) {
            p.status = newStatus;
          }
        });
        renderPurchases();
        showAlert(alertPlaceholder, 'success', result.message);
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
    })
    .catch(function(err) {
      statusSaveBtn.removeAttribute('disabled');
      statusSaveBtn.textContent = 'Update Status';
      console.error('Error updating status:', err);
      showAlert(alertPlaceholder, 'error', 'An error occurred while updating the status.');
    });
  });

  // Refresh
  if (refreshBtn) refreshBtn.addEventListener("click", fetchPurchases);

  // Helper functions
  function escapeHtml(text) {
    if (!text) return "";
    text = String(text);
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  function showAlert(container, type, message) {
    if (!container) return;
    var icon = (type === "success") 
      ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
      : '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    container.innerHTML = '<div class="alert-msg ' + type + '">' +
      icon +
      '<span>' + escapeHtml(message) + '</span>' +
      '</div>';
    
    if (type === "success") {
      setTimeout(function() {
        container.innerHTML = "";
      }, 5000);
    }
  }

  // Init
  fetchPurchases();
});
