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

  var confirmOverlay = document.getElementById("confirmOverlay");
  var confirmBody = document.getElementById("confirmBody");
  var confirmCancelBtn = document.getElementById("confirmCancelBtn");
  var confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

  var activeDeleteId = null;
  var allLots = [];
  var searchInput = document.getElementById("searchInput");
  var statusFilter = document.getElementById("statusFilter");

  function getFilteredLots() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : "";
    var status = statusFilter ? statusFilter.value : "";

    return allLots.filter(function (l) {
      var matchQuery = true;
      if (query) {
        var lotCodeVal = (l.lots_code || "").toLowerCase();
        matchQuery = lotCodeVal.indexOf(query) !== -1;
      }

      var matchStatus = true;
      if (status) {
        var lotStatus = l.closing_date ? "CLOSED" : "OPEN";
        matchStatus = lotStatus.toUpperCase() === status.toUpperCase();
      }

      return matchQuery && matchStatus;
    });
  }

  if (searchInput) searchInput.addEventListener("input", filterAndReset);
  if (statusFilter) statusFilter.addEventListener("change", filterAndReset);

  function filterAndReset() {
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

    if (totalItems === 0) {
      lotsList.innerHTML =
        '<tr><td colspan="6" class="table-empty">' +
        (searchInput && searchInput.value.trim()
          ? "No matching lots found."
          : "No lots configured yet.") +
        "</td></tr>";
      return;
    }

    var html = "";
    filtered.forEach(function (l, index) {
      var globalIndex = index + 1;
      var lotCode = escapeHtml(l.lots_code);
      var openingDate = l.opening_date ? escapeHtml(l.opening_date) : "\u2014";
      var closingDate = l.closing_date ? escapeHtml(l.closing_date) : "\u2014";
      var isOpen = !l.closing_date;
      var statusLabel = isOpen
        ? '<span class="status-pill pill-green">Open</span>'
        : '<span class="status-pill pill-red">Closed</span>';

      var actionBtns =
        '<button class="btn-icon-only view-details" title="View Details" data-id="' +
        l.id +
        '">' +
        '<svg viewBox="0 0 24 24" style="stroke: var(--blue);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' +
        "</button>";
      if (isOpen) {
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
        lotCode +
        "</strong></td>" +
        "<td>" +
        openingDate +
        "</td>" +
        "<td>" +
        closingDate +
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
    attachRowEventListeners(filtered);
  }

  function updateStats(lots) {
    var total = lots.length;
    var open = 0;
    var closed = 0;

    lots.forEach(function (l) {
      if (l.closing_date) {
        closed++;
      } else {
        open++;
      }
    });

    document.getElementById("stat-total").textContent = total;
    document.getElementById("stat-open").textContent = open;
    document.getElementById("stat-closed").textContent = closed;
  }

  function updateToken(token) {
    if (lotTokenInput) {
      lotTokenInput.value = token;
    }
  }

  function attachRowEventListeners(lots) {
    var viewBtns = document.querySelectorAll(".action-buttons .view-details");
    var editBtns = document.querySelectorAll(".action-buttons .edit");
    var deleteBtns = document.querySelectorAll(".action-buttons .delete");
    var closeBtns = document.querySelectorAll(".action-buttons .close-btn");

    viewBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        openDetailsModal(id);
      });
    });

    editBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var l = lots.find(function (x) {
          return x.id === id;
        });
        if (l) {
          // Don't allow editing closed lots
          if (l.closing_date) {
            showAlert(
              alertPlaceholder,
              "error",
              "Closed lots cannot be edited.",
            );
            return;
          }
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
            l.lots_code +
            '"? This will fail if there are recorded purchases associated with it.';
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
          closeLot(l.id);
        }
      });
    });
  }

  function closeLot(id) {
    var token = lotTokenInput.value;
    var formData = new URLSearchParams();
    formData.append("id", id);
    formData.append("token", token);

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
      })
      .catch(function (err) {
        console.error("Error closing lot:", err);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while closing the lot.",
        );
      });
  }

  function setEditMode(l) {
    if (!lotForm) return;

    // Don't allow editing closed lots
    if (l.closing_date) {
      showAlert(alertPlaceholder, "error", "Closed lots cannot be edited.");
      return;
    }

    lotIdInput.value = l.id;
    document.getElementById("lotCode").value = l.lots_code;
    document.getElementById("lotOpeningDate").value = l.opening_date || "";
    document.getElementById("lotClosingDate").value = l.closing_date || "";

    formTitle.textContent = "Edit Lot: " + l.lots_code;
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
    document.getElementById("lotOpeningDate").value = new Date()
      .toISOString()
      .substring(0, 10);

    formTitle.textContent = "Add New Lot";
    saveBtn.textContent = "Save Lot";
    cancelBtn.style.display = "none";
    formAlertPlaceholder.innerHTML = "";
  }

  if (lotForm) {
    lotForm.addEventListener("submit", function (ev) {
      ev.preventDefault();

      var id = lotIdInput.value;
      var lotsCode = document.getElementById("lotCode").value.trim();
      var openingDate = document.getElementById("lotOpeningDate").value;
      var closingDate = document.getElementById("lotClosingDate").value.trim();
      var token = lotTokenInput.value;

      if (!lotsCode) {
        showAlert(formAlertPlaceholder, "error", "Lot code is required.");
        return;
      }
      if (!openingDate) {
        showAlert(formAlertPlaceholder, "error", "Opening date is required.");
        return;
      }

      // If updating, check if the lot is closed first
      if (id) {
        var lot = allLots.find(function (x) {
          return x.id === parseInt(id, 10);
        });
        if (lot && lot.closing_date) {
          showAlert(
            formAlertPlaceholder,
            "error",
            "Closed lots cannot be edited.",
          );
          return;
        }
      }

      var action = id ? "update" : "create";
      var formData = new URLSearchParams();

      if (id) formData.append("id", id);
      formData.append("lots_code", lotsCode);
      formData.append("opening_date", openingDate);
      if (closingDate) {
        formData.append("closing_date", closingDate);
      }
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

  if (cancelBtn) cancelBtn.addEventListener("click", resetForm);
  if (refreshBtn) {
    refreshBtn.addEventListener("click", function () {
      fetchLots();
      showAlert(alertPlaceholder, "success", "Lots list reloaded.");
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

  // Details Modal Handlers
  var detailsModalOverlay = document.getElementById("detailsModalOverlay");
  var closeDetailsModalBtn = document.getElementById("closeDetailsModalBtn");

  if (closeDetailsModalBtn) {
    closeDetailsModalBtn.addEventListener("click", function() {
      detailsModalOverlay.style.display = "none";
    });
  }

  function openDetailsModal(lotId) {
    fetch("lots_api.php?action=details&id=" + lotId)
      .then(function (res) { return res.json(); })
      .then(function (result) {
        if (result.success) {
          var data = result.data;
          document.getElementById("detailsLotCode").textContent = data.lot.lots_code;
          document.getElementById("detailsOpeningDate").textContent = data.lot.opening_date || "—";
          document.getElementById("detailsClosingDate").textContent = data.lot.closing_date || "—";
          
          var statusPill = data.lot.status === "OPEN" 
            ? '<span class="status-pill pill-green">Open</span>' 
            : '<span class="status-pill pill-red">Closed</span>';
          document.getElementById("detailsStatus").innerHTML = statusPill;

          var stockList = document.getElementById("detailsStockList");
          if (data.stock.length === 0) {
            stockList.innerHTML = '<tr><td colspan="4" class="table-empty">No stock records found for this lot.</td></tr>';
          } else {
            var html = "";
            data.stock.forEach(function (s) {
              html += '<tr>' +
                '<td>' + escapeHtml(s.warehouse_name) + '</td>' +
                '<td><strong>' + escapeHtml(s.product_name) + '</strong></td>' +
                '<td style="text-align: right; color: var(--text2); font-weight: 500;">' + s.opening.toFixed(2) + ' ' + escapeHtml(s.uom_code) + '</td>' +
                '<td style="text-align: right; font-weight: 600; color: var(--green);">' + s.closing.toFixed(2) + ' ' + escapeHtml(s.uom_code) + '</td>' +
                '</tr>';
            });
            stockList.innerHTML = html;
          }

          detailsModalOverlay.style.display = "flex";
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
      })
      .catch(function (err) {
        console.error("Error fetching lot details:", err);
        showAlert(alertPlaceholder, "error", "An error occurred while loading lot details.");
      });
  }

  fetchLots();
});
