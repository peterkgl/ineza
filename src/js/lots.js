document.addEventListener("DOMContentLoaded", function () {
  var lotForm = document.getElementById("lotForm");
  var lotsList = document.getElementById("lotsList");
  var alertPlaceholder = document.getElementById("alertPlaceholder");
  var formAlertPlaceholder = document.getElementById("formAlertPlaceholder");
  var formTitle = document.getElementById("formTitle");
  var lotIdInput = document.getElementById("lotIdInput");
  var lotTokenInput = document.getElementById("lotToken");
  var cancelBtn = document.getElementById("cancelBtn");
  var saveBtn = document.getElementById("saveBtn");
  var refreshBtn = document.getElementById("refreshBtn");
  var addLotBtn = document.getElementById("addLotBtn");

  var confirmOverlay = document.getElementById("confirmOverlay");
  var confirmBody = document.getElementById("confirmBody");
  var confirmCancelBtn = document.getElementById("confirmCancelBtn");
  var confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

  var confirmCloseOverlay = document.getElementById("confirmCloseOverlay");
  var confirmCloseBody = document.getElementById("confirmCloseBody");
  var closeCancelBtn = document.getElementById("closeCancelBtn");
  var closeConfirmBtn = document.getElementById("closeConfirmBtn");

  var viewModalOverlay = document.getElementById("viewModalOverlay");
  var viewCloseBtn = document.getElementById("viewCloseBtn");
  var viewLotBody = document.getElementById("viewLotBody");
  var viewLotNo = document.getElementById("viewLotNo");

  var productSelect = document.getElementById("lotProduct");
  var carryoverIndicator = document.getElementById("carryoverIndicator");
  var currencySelect = document.getElementById("lotCurrency");
  var exchangeRateGroup = document.getElementById("exchangeRateGroup");

  var activeDeleteId = null;
  var activeCloseId = null;
  var allLots = [];
  var searchInput = document.getElementById("searchInput");
  var statusFilter = document.getElementById("statusFilter");
  var productFilter = document.getElementById("productFilter");

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById("lotsTable").parentElement;
  var paginationContainer = document.createElement("div");
  paginationContainer.className = "table-pagination";
  tableContainer.parentNode.insertBefore(
    paginationContainer,
    tableContainer.nextSibling,
  );

  // Currency selection exchange rate visibility toggle
  if (currencySelect) {
    currencySelect.addEventListener("change", function () {
      var val = currencySelect.value;
      if (val && val !== "") {
        var cur = window.currenciesList.find(function (c) {
          return c.id == val;
        });
        if (cur && cur.code.toUpperCase() !== "USD") {
          exchangeRateGroup.style.display = "block";
          document.getElementById("lotExchangeRate").required = true;
        } else {
          exchangeRateGroup.style.display = "none";
          document.getElementById("lotExchangeRate").required = false;
        }
      } else {
        exchangeRateGroup.style.display = "none";
        document.getElementById("lotExchangeRate").required = false;
      }
    });
  }

  // Carryover balance checking on product selection
  if (productSelect) {
    productSelect.addEventListener("change", function () {
      var productId = productSelect.value;
      var lotId = lotIdInput.value;
      if (!lotId && productId) {
        // Only prefill for new lots
        fetch("lots_api.php?action=get_carryover&product_id=" + productId)
          .then(function (res) {
            return res.json();
          })
          .then(function (result) {
            if (
              result.success &&
              result.carryover !== null &&
              result.carryover > 0
            ) {
              document.getElementById("lotQtyReceived").value =
                result.carryover.toFixed(3);
              document.getElementById("lotQtyRemaining").value =
                result.carryover.toFixed(3);
              carryoverIndicator.style.display = "block";
              carryoverIndicator.textContent =
                "Carried over balance: " + result.carryover.toFixed(3) + " kg";
            } else {
              document.getElementById("lotQtyReceived").value = "";
              document.getElementById("lotQtyRemaining").value = "";
              carryoverIndicator.style.display = "none";
            }
          })
          .catch(function (err) {
            console.error("Error fetching carryover balance:", err);
          });
      } else {
        carryoverIndicator.style.display = "none";
      }
    });
  }

  function getFilteredLots() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : "";
    var status = statusFilter ? statusFilter.value : "";
    var product = productFilter ? productFilter.value : "";

    return allLots.filter(function (l) {
      var matchQuery = true;
      if (query) {
        var lotNoVal = (l.lot_number || "").toLowerCase();
        var descVal = (l.description || "").toLowerCase();
        matchQuery =
          lotNoVal.indexOf(query) !== -1 || descVal.indexOf(query) !== -1;
      }

      var matchStatus = true;
      if (status) {
        matchStatus = l.status.toUpperCase() === status.toUpperCase();
      }

      var matchProduct = true;
      if (product) {
        matchProduct = l.product_id == product;
      }

      return matchQuery && matchStatus && matchProduct;
    });
  }

  if (searchInput) searchInput.addEventListener("input", filterAndReset);
  if (statusFilter) statusFilter.addEventListener("change", filterAndReset);
  if (productFilter) productFilter.addEventListener("change", filterAndReset);

  function filterAndReset() {
    currentPage = 1;
    renderLots();
  }

  var isFirstLoad = true;
  function fetchLots() {
    if (isFirstLoad && window.initialLotsData) {
      isFirstLoad = false;
      allLots = window.initialLotsData;
      renderLots();
      updateStats(allLots);
      return;
    }
    isFirstLoad = false;
    fetch("lots_api.php?action=list")
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.success) {
          allLots = result.data;
          renderLots();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
      })
      .catch(function (error) {
        console.error("Error fetching lots:", error);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while loading lots.",
        );
      });
  }

  function renderLots() {
    var filtered = getFilteredLots();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);

    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }

    if (totalItems === 0) {
      lotsList.innerHTML =
        '<tr><td colspan="9" class="table-empty">' +
        (searchInput && searchInput.value.trim()
          ? "No matching lots found."
          : "No lots configured yet.") +
        "</td></tr>";
      renderPagination(totalItems, totalPages);
      return;
    }

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = "";
    paginated.forEach(function (l, index) {
      var globalIndex = startIndex + index + 1;
      var lotNo = escapeHtml(l.lot_number);
      var prodName = escapeHtml(l.product_name || "\u2014");
      var date = l.received_date ? escapeHtml(l.received_date) : "\u2014";
      var qtyRec = l.quantity_received.toFixed(3);
      var qtyRem = l.remaining_quantity.toFixed(3);
      var whName = escapeHtml(l.warehouse_name || "\u2014");
      var statusLabel =
        l.status.toUpperCase() === "OPEN"
          ? '<span class="status-pill pill-green">Open</span>'
          : '<span class="status-pill pill-red">Closed</span>';

      var actionBtns = "";
      actionBtns +=
        '<button class="btn-icon-only view-btn" title="View Details" data-id="' +
        l.id +
        '">' +
        '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
        "</button>";

      if (l.status.toUpperCase() === "OPEN") {
        actionBtns +=
          '<button class="btn-icon-only edit" title="Edit Lot" data-id="' +
          l.id +
          '">' +
          '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
          "</button>";
        actionBtns +=
          '<button class="btn-icon-only close-btn" title="Close Lot" data-id="' +
          l.id +
          '">' +
          '<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
          "</button>";
      } else {
        actionBtns +=
          '<button class="btn-icon-only open-btn" title="Re-open Lot" data-id="' +
          l.id +
          '">' +
          '<svg viewBox="0 0 24 24"><polyline points="22 4 12 14.01 9 11.01"/><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg>' +
          "</button>";
      }

      actionBtns +=
        '<button class="btn-icon-only delete" title="Delete Lot" data-id="' +
        l.id +
        '">' +
        '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
        "</button>";

      html +=
        "<tr>" +
        "<td>" +
        globalIndex +
        "</td>" +
        "<td><strong>" +
        lotNo +
        "</strong></td>" +
        '<td><span class="code-badge">' +
        prodName +
        "</span></td>" +
        "<td>" +
        date +
        "</td>" +
        "<td>" +
        qtyRec +
        "</td>" +
        "<td>" +
        qtyRem +
        "</td>" +
        "<td>" +
        whName +
        "</td>" +
        "<td>" +
        statusLabel +
        "</td>" +
        '<td style="text-align: right;">' +
        '<div class="action-buttons" style="justify-content: flex-end;">' +
        actionBtns +
        "</div>" +
        "</td>" +
        "</tr>";
    });

    lotsList.innerHTML = html;
    attachRowEventListeners(paginated);
    renderPagination(totalItems, totalPages);
  }

  function renderPagination(totalItems, totalPages) {
    if (totalPages <= 1) {
      paginationContainer.style.display = "none";
      return;
    }
    paginationContainer.style.display = "flex";

    var startIndex = (currentPage - 1) * itemsPerPage + 1;
    var endIndex = Math.min(startIndex + itemsPerPage - 1, totalItems);

    var infoHtml =
      "Showing " +
      startIndex +
      " to " +
      endIndex +
      " of " +
      totalItems +
      " entries";
    var buttonsHtml = "";

    buttonsHtml +=
      '<button class="pagination-btn" ' +
      (currentPage === 1 ? "disabled" : "") +
      ' data-page="' +
      (currentPage - 1) +
      '">&laquo; Prev</button>';

    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
    }

    if (startPage > 1) {
      buttonsHtml += '<button class="pagination-btn" data-page="1">1</button>';
      if (startPage > 2) {
        buttonsHtml +=
          '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      buttonsHtml +=
        '<button class="pagination-btn ' +
        (p === currentPage ? "active" : "") +
        '" data-page="' +
        p +
        '">' +
        p +
        "</button>";
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        buttonsHtml +=
          '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
      buttonsHtml +=
        '<button class="pagination-btn" data-page="' +
        totalPages +
        '">' +
        totalPages +
        "</button>";
    }

    buttonsHtml +=
      '<button class="pagination-btn" ' +
      (currentPage === totalPages ? "disabled" : "") +
      ' data-page="' +
      (currentPage + 1) +
      '">Next &raquo;</button>';

    paginationContainer.innerHTML =
      '<div class="pagination-info">' +
      infoHtml +
      "</div>" +
      '<div class="pagination-buttons">' +
      buttonsHtml +
      "</div>";

    var buttons = paginationContainer.querySelectorAll(".pagination-btn");
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var page = parseInt(btn.getAttribute("data-page"), 10);
        if (page && page !== currentPage) {
          currentPage = page;
          renderLots();
        }
      });
    });
  }

  function updateStats(lots) {
    var total = lots.length;
    var open = 0;
    var closed = 0;
    var totalStock = 0;

    lots.forEach(function (l) {
      if (l.status.toUpperCase() === "OPEN") {
        open++;
      } else {
        closed++;
      }
      totalStock += l.remaining_quantity;
    });

    document.getElementById("stat-total").textContent = total;
    document.getElementById("stat-open").textContent = open;
    document.getElementById("stat-closed").textContent = closed;
    document.getElementById("stat-total-stock").textContent =
      totalStock.toFixed(1);
  }

  function updateToken(token) {
    if (lotTokenInput) {
      lotTokenInput.value = token;
    }
  }

  function attachRowEventListeners(lots) {
    var viewBtns = document.querySelectorAll(".action-buttons .view-btn");
    var editBtns = document.querySelectorAll(".action-buttons .edit");
    var deleteBtns = document.querySelectorAll(".action-buttons .delete");
    var closeBtns = document.querySelectorAll(".action-buttons .close-btn");
    var openBtns = document.querySelectorAll(".action-buttons .open-btn");

    viewBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          showViewDetails(l);
        }
      });
    });

    editBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          setEditMode(l);
        }
      });
    });

    deleteBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          activeDeleteId = id;
          confirmBody.textContent =
            'Are you sure you want to delete lot "' +
            l.lot_number +
            '"? This will fail if there are recorded purchases, sales, or stock movements associated with it.';
          confirmOverlay.style.display = "flex";
        }
      });
    });

    closeBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          activeCloseId = id;
          if (l.remaining_quantity > 0) {
            confirmCloseBody.textContent =
              'Lot "' +
              l.lot_number +
              '" has a remaining stock balance of ' +
              l.remaining_quantity.toFixed(3) +
              " kg. Are you sure you want to close this lot and confirm its stock balances?";
          } else {
            confirmCloseBody.textContent =
              'Are you sure you want to close the lot "' +
              l.lot_number +
              '"? Once closed, it will become read-only.';
          }
          confirmCloseOverlay.style.display = "flex";
        }
      });
    });

    openBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          reopenLot(l.id);
        }
      });
    });
  }

  function reopenLot(id) {
    var token = lotTokenInput.value;
    var formData = new URLSearchParams();
    formData.append("id", id);
    formData.append("token", token);

    fetch("lots_api.php?action=open", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.token) updateToken(result.token);
        if (result.success) {
          showAlert(alertPlaceholder, "success", result.message);
          fetchLots();
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
      })
      .catch(function (err) {
        console.error("Error reopening lot:", err);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while reopening the lot.",
        );
      });
  }

  function showViewDetails(l) {
    viewLotNo.textContent = "Lot: " + l.lot_number;

    var uCost = l.unit_cost ? l.unit_cost.toFixed(2) : "\u2014";
    var exRate = l.exchange_rate ? l.exchange_rate.toFixed(6) : "\u2014";
    var cur = l.currency_code ? l.currency_code : "\u2014";
    var closeDate = l.closing_date ? l.closing_date : "\u2014";
    var desc = l.description ? l.description : "\u2014";

    var html =
      '<div class="detail-row"><span class="detail-label">Lot ID</span><span class="detail-value">' +
      l.id +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Lot Number</span><span class="detail-value">' +
      l.lot_number +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Product mineral</span><span class="detail-value">' +
      (l.product_name || "\u2014") +
      " (" +
      (l.product_code || "\u2014") +
      ")</span></div>" +
      '<div class="detail-row"><span class="detail-label">Supplier entity</span><span class="detail-value">' +
      (l.supplier_name || "\u2014") +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Warehouse location</span><span class="detail-value">' +
      (l.warehouse_name || "\u2014") +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Opening Date</span><span class="detail-value">' +
      l.received_date +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Closing Date</span><span class="detail-value">' +
      closeDate +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">' +
      l.status +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Opening Balance</span><span class="detail-value">' +
      l.quantity_received.toFixed(3) +
      " kg</span></div>" +
      '<div class="detail-row"><span class="detail-label">Remaining Balance</span><span class="detail-value">' +
      l.remaining_quantity.toFixed(3) +
      " kg</span></div>" +
      '<div class="detail-row"><span class="detail-label">Unit Cost (Base)</span><span class="detail-value">' +
      uCost +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Currency</span><span class="detail-value">' +
      cur +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Exchange Rate</span><span class="detail-value">' +
      exRate +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Description / Notes</span><span class="detail-value">' +
      desc +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Configured By</span><span class="detail-value">' +
      l.created_by_name +
      "</span></div>" +
      '<div class="detail-row"><span class="detail-label">Created At</span><span class="detail-value">' +
      l.created_at +
      "</span></div>";

    viewLotBody.innerHTML = html;
    viewModalOverlay.style.display = "flex";
  }

  function setEditMode(l) {
    if (!lotForm) return;

    lotIdInput.value = l.id;
    document.getElementById("lotNumber").value = l.lot_number;
    document.getElementById("lotProduct").value = l.product_id;
    document.getElementById("lotProduct").disabled = true; // Cannot edit product once created
    document.getElementById("lotSupplier").value = l.supplier_id || "";
    document.getElementById("lotWarehouse").value = l.warehouse_id || "";
    document.getElementById("lotOpeningDate").value = l.received_date || "";
    document.getElementById("lotQtyReceived").value =
      l.quantity_received.toFixed(3);
    document.getElementById("lotQtyRemaining").value =
      l.remaining_quantity.toFixed(3);
    document.getElementById("lotUnitCost").value =
      l.unit_cost !== null ? l.unit_cost.toFixed(2) : "";
    document.getElementById("lotCurrency").value = l.currency_id || "";
    document.getElementById("lotDescription").value = l.description || "";
    document.getElementById("lotStatus").value = l.status;

    // Trigger currency select change event for exchange rate block visibility
    var event = new Event("change");
    currencySelect.dispatchEvent(event);
    if (l.exchange_rate) {
      document.getElementById("lotExchangeRate").value =
        l.exchange_rate.toFixed(6);
    }

    formTitle.textContent = "Edit Lot: " + l.lot_number;
    saveBtn.textContent = "Update Lot";
    cancelBtn.style.display = "inline-block";

    if (window.innerWidth <= 1100) {
      document
        .getElementById("formCard")
        .scrollIntoView({ behavior: "smooth" });
    }
  }

  function resetForm() {
    if (!lotForm) return;
    lotForm.reset();
    lotIdInput.value = "";
    document.getElementById("lotProduct").disabled = false;
    document.getElementById("lotStatus").value = "OPEN";
    document.getElementById("lotOpeningDate").value = new Date()
      .toISOString()
      .substring(0, 10);
    carryoverIndicator.style.display = "none";
    exchangeRateGroup.style.display = "none";

    formTitle.textContent = "Add New Lot";
    saveBtn.textContent = "Save Lot";
    cancelBtn.style.display = "none";
    formAlertPlaceholder.innerHTML = "";
  }

  if (lotForm) {
    lotForm.addEventListener("submit", function (ev) {
      ev.preventDefault();

      var id = lotIdInput.value;
      var productId = document.getElementById("lotProduct").value;
      var supplierId = document.getElementById("lotSupplier").value;
      var warehouseId = document.getElementById("lotWarehouse").value;
      var receivedDate = document.getElementById("lotOpeningDate").value;
      var qtyReceived = document.getElementById("lotQtyReceived").value.trim();
      var qtyRemaining = document
        .getElementById("lotQtyRemaining")
        .value.trim();
      var unitCost = document.getElementById("lotUnitCost").value.trim();
      var currencyId = document.getElementById("lotCurrency").value;
      var exchangeRate = document
        .getElementById("lotExchangeRate")
        .value.trim();
      var description = document.getElementById("lotDescription").value.trim();
      var status = document.getElementById("lotStatus").value;
      var token = lotTokenInput.value;

      if (!productId) {
        showAlert(
          formAlertPlaceholder,
          "error",
          "Product mineral is required.",
        );
        return;
      }
      if (!receivedDate) {
        showAlert(formAlertPlaceholder, "error", "Opening date is required.");
        return;
      }
      if (!qtyReceived) {
        showAlert(
          formAlertPlaceholder,
          "error",
          "Opening balance quantity is required.",
        );
        return;
      }

      var action = id ? "update" : "create";
      var formData = new URLSearchParams();

      if (id) formData.append("id", id);
      formData.append("product_id", productId);
      formData.append("supplier_id", supplierId);
      formData.append("warehouse_id", warehouseId);
      formData.append("received_date", receivedDate);
      formData.append("quantity_received", qtyReceived);
      formData.append("remaining_quantity", qtyRemaining || qtyReceived); // Default remaining to opening
      formData.append("unit_cost", unitCost);
      formData.append("currency_id", currencyId);
      formData.append("exchange_rate", exchangeRate);
      formData.append("description", description);
      formData.append("status", status);
      formData.append("token", token);

      saveBtn.disabled = true;
      saveBtn.textContent = "Saving...";

      fetch("lots_api.php?action=" + action, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData.toString(),
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (result) {
          saveBtn.disabled = false;
          saveBtn.textContent = id ? "Update Lot" : "Save Lot";

          if (result.token) updateToken(result.token);

          if (result.success) {
            showAlert(alertPlaceholder, "success", result.message);
            resetForm();
            fetchLots();
          } else {
            showAlert(formAlertPlaceholder, "error", result.message);
          }
        })
        .catch(function (error) {
          console.error("Error saving lot:", error);
          saveBtn.disabled = false;
          saveBtn.textContent = id ? "Update Lot" : "Save Lot";
          showAlert(
            formAlertPlaceholder,
            "error",
            "An error occurred while saving the lot.",
          );
        });
    });
  }

  // Confirm delete handlers
  confirmCancelBtn.addEventListener("click", function () {
    confirmOverlay.style.display = "none";
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener("click", function () {
    if (!activeDeleteId) return;

    var token = lotTokenInput.value;
    var formData = new URLSearchParams();
    formData.append("id", activeDeleteId);
    formData.append("token", token);

    confirmOverlay.style.display = "none";

    fetch("lots_api.php?action=delete", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.token) updateToken(result.token);
        if (result.success) {
          showAlert(alertPlaceholder, "success", result.message);
          resetForm();
          fetchLots();
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
        activeDeleteId = null;
      })
      .catch(function (err) {
        console.error("Error deleting lot:", err);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while deleting the lot.",
        );
        activeDeleteId = null;
      });
  });

  // Confirm close handlers
  closeCancelBtn.addEventListener("click", function () {
    confirmCloseOverlay.style.display = "none";
    activeCloseId = null;
  });

  closeConfirmBtn.addEventListener("click", function () {
    if (!activeCloseId) return;

    var token = lotTokenInput.value;
    var formData = new URLSearchParams();
    formData.append("id", activeCloseId);
    formData.append("token", token);
    formData.append("force_close", "1"); // Force closing positive balance if confirmed

    confirmCloseOverlay.style.display = "none";

    fetch("lots_api.php?action=close", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.token) updateToken(result.token);
        if (result.success) {
          showAlert(alertPlaceholder, "success", result.message);
          fetchLots();
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
        activeCloseId = null;
      })
      .catch(function (err) {
        console.error("Error closing lot:", err);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while closing the lot.",
        );
        activeCloseId = null;
      });
  });

  viewCloseBtn.addEventListener("click", function () {
    viewModalOverlay.style.display = "none";
  });

  if (cancelBtn) cancelBtn.addEventListener("click", resetForm);
  if (refreshBtn) {
    refreshBtn.addEventListener("click", function () {
      fetchLots();
      showAlert(alertPlaceholder, "success", "Lots list reloaded.");
    });
  }
  if (addLotBtn) {
    addLotBtn.addEventListener("click", function () {
      resetForm();
      if (window.innerWidth <= 1100) {
        document
          .getElementById("formCard")
          .scrollIntoView({ behavior: "smooth" });
      }
    });
  }

  function showAlert(container, type, message) {
    var icon =
      type === "success"
        ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
        : '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

    container.innerHTML =
      '<div class="alert-banner ' +
      type +
      '">' +
      icon +
      "<span>" +
      escapeHtml(message) +
      "</span></div>";

    if (type === "success") {
      setTimeout(function () {
        container.innerHTML = "";
      }, 4000);
    }
  }

  function escapeHtml(str) {
    if (!str) return "";
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  fetchLots();
});
