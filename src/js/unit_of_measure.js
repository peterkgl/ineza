document.addEventListener("DOMContentLoaded", function () {
  var unitForm = document.getElementById("unitForm");
  var unitsList = document.getElementById("unitsList");
  var alertPlaceholder = document.getElementById("alertPlaceholder");
  var formAlertPlaceholder = document.getElementById("formAlertPlaceholder");
  var formTitle = document.getElementById("formTitle");
  var unitIdInput = document.getElementById("unitIdInput");
  var unitTokenInput = document.getElementById("unitToken");
  var cancelBtn = document.getElementById("cancelBtn");
  var saveBtn = document.getElementById("saveBtn");
  var refreshBtn = document.getElementById("refreshBtn");

  var confirmOverlay = document.getElementById("confirmOverlay");
  var confirmBody = document.getElementById("confirmBody");
  var confirmCancelBtn = document.getElementById("confirmCancelBtn");
  var confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

  var activeDeleteId = null;
  var allUnits = [];
  var searchInput = document.getElementById("searchInput");
  var statusFilter = document.getElementById("statusFilter");

  function getFilteredUnits() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : "";
    var status = statusFilter ? statusFilter.value : "";

    return allUnits.filter(function (u) {
      var matchQuery = true;
      if (query) {
        var codeVal = (u.code || "").toLowerCase();
        var nameVal = (u.name || "").toLowerCase();
        var symbolVal = (u.symbol || "").toLowerCase();
        matchQuery =
          codeVal.indexOf(query) !== -1 ||
          nameVal.indexOf(query) !== -1 ||
          symbolVal.indexOf(query) !== -1;
      }

      var matchStatus = true;
      if (status !== "") {
        matchStatus = u.is_active === parseInt(status);
      }

      return matchQuery && matchStatus;
    });
  }

  if (searchInput) searchInput.addEventListener("input", filterAndReset);
  if (statusFilter) statusFilter.addEventListener("change", filterAndReset);

  function filterAndReset() {
    renderUnits();
  }

  var isFirstLoad = true;
  function fetchUnits() {
    if (isFirstLoad && window.initialUnitsData) {
      isFirstLoad = false;
      allUnits = window.initialUnitsData;
      renderUnits();
      updateStats(allUnits);
      return;
    }
    isFirstLoad = false;
    fetch("unit_of_measure_api.php?action=list")
      .then(function (res) {
        return res.json();
      })
      .then(function (result) {
        if (result.success) {
          allUnits = result.data;
          renderUnits();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
      })
      .catch(function (error) {
        console.error("Error fetching units:", error);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while loading units of measure.",
        );
      });
  }

  function renderUnits() {
    var filtered = getFilteredUnits();
    var totalItems = filtered.length;

    if (totalItems === 0) {
      unitsList.innerHTML =
        '<tr><td colspan="6" class="table-empty">' +
        (searchInput && searchInput.value.trim()
          ? "No matching units found."
          : "No units of measure configured yet.") +
        "</td></tr>";
      return;
    }

    var html = "";
    filtered.forEach(function (u, index) {
      var globalIndex = index + 1;
      var code = escapeHtml(u.code);
      var name = escapeHtml(u.name);
      var symbol = u.symbol ? escapeHtml(u.symbol) : "—";
      var statusLabel = u.is_active
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      var actionBtns = "";
      actionBtns +=
        '<button class="btn-icon-only edit" title="Edit Unit" data-id="' +
        u.id +
        '">' +
        '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
        "</button>";
      actionBtns +=
        '<button class="btn-icon-only delete" title="Delete Unit" data-id="' +
        u.id +
        '">' +
        '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
        "</button>";

      html +=
        "<tr>" +
        "<td>" +
        globalIndex +
        "</td>" +
        "<td><strong>" +
        code +
        "</strong></td>" +
        "<td>" +
        name +
        "</td>" +
        "<td>" +
        symbol +
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

    unitsList.innerHTML = html;
    attachRowEventListeners(filtered);
  }

  function updateStats(units) {
    var total = units.length;
    var active = 0;

    units.forEach(function (u) {
      if (u.is_active) {
        active++;
      }
    });

    document.getElementById("stat-total").textContent = total;
    document.getElementById("stat-active").textContent = active;
  }

  function updateToken(token) {
    if (unitTokenInput) {
      unitTokenInput.value = token;
    }
  }

  function attachRowEventListeners(units) {
    var editBtns = document.querySelectorAll(".action-buttons .edit");
    var deleteBtns = document.querySelectorAll(".action-buttons .delete");

    editBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var u = units.find(function (x) {
          return x.id === id;
        });
        if (u) {
          setEditMode(u);
        }
      });
    });

    deleteBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.getAttribute("data-id"), 10);
        var u = units.find(function (x) {
          return x.id === id;
        });
        if (u) {
          activeDeleteId = id;
          confirmBody.textContent =
            'Are you sure you want to delete unit "' +
            u.code +
            '"? This will fail if there are products using this unit.';
          confirmOverlay.style.display = "flex";
        }
      });
    });
  }

  function setEditMode(u) {
    if (!unitForm) return;

    unitIdInput.value = u.id;
    document.getElementById("unitCode").value = u.code;
    document.getElementById("unitName").value = u.name;
    document.getElementById("unitSymbol").value = u.symbol || "";
    document.getElementById("unitIsActive").checked = u.is_active === 1;

    formTitle.textContent = "Edit Unit: " + u.code;
    saveBtn.textContent = "Update Unit";
    cancelBtn.style.display = "inline-block";

    if (window.innerWidth <= 1100) {
      document
        .getElementById("formCard")
        .scrollIntoView({ behavior: "smooth" });
    }
  }

  function resetForm() {
    if (!unitForm) return;
    unitForm.reset();
    unitIdInput.value = "";
    document.getElementById("unitIsActive").checked = true;

    formTitle.textContent = "Add New Unit";
    saveBtn.textContent = "Save Unit";
    cancelBtn.style.display = "none";
    formAlertPlaceholder.innerHTML = "";
  }

  if (unitForm) {
    unitForm.addEventListener("submit", function (ev) {
      ev.preventDefault();

      var id = unitIdInput.value;
      var code = document.getElementById("unitCode").value.trim().toUpperCase();
      var name = document.getElementById("unitName").value.trim();
      var symbol = document.getElementById("unitSymbol").value.trim();
      var is_active = document.getElementById("unitIsActive").checked
        ? "1"
        : "0";
      var token = unitTokenInput.value;

      if (!code || !name) {
        showAlert(formAlertPlaceholder, "error", "Code and name are required.");
        return;
      }

      var action = id ? "update" : "create";
      var formData = new URLSearchParams();

      if (id) formData.append("id", id);
      formData.append("code", code);
      formData.append("name", name);
      formData.append("symbol", symbol);
      formData.append("is_active", is_active);
      formData.append("token", token);

      saveBtn.disabled = true;
      saveBtn.textContent = "Saving...";

      fetch("unit_of_measure_api.php?action=" + action, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData.toString(),
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (result) {
          saveBtn.disabled = false;
          saveBtn.textContent = id ? "Update Unit" : "Save Unit";

          if (result.token) updateToken(result.token);

          if (result.success) {
            showAlert(alertPlaceholder, "success", result.message);
            resetForm();
            fetchUnits();
          } else {
            showAlert(formAlertPlaceholder, "error", result.message);
          }
        })
        .catch(function (error) {
          console.error("Error saving unit:", error);
          saveBtn.disabled = false;
          saveBtn.textContent = id ? "Update Unit" : "Save Unit";
          showAlert(
            formAlertPlaceholder,
            "error",
            "An error occurred while saving the unit of measure.",
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

    var token = unitTokenInput.value;
    var formData = new URLSearchParams();
    formData.append("id", activeDeleteId);
    formData.append("token", token);

    confirmOverlay.style.display = "none";

    fetch("unit_of_measure_api.php?action=delete", {
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
          fetchUnits();
        } else {
          showAlert(alertPlaceholder, "error", result.message);
        }
        activeDeleteId = null;
      })
      .catch(function (err) {
        console.error("Error deleting unit:", err);
        showAlert(
          alertPlaceholder,
          "error",
          "An error occurred while deleting the unit of measure.",
        );
        activeDeleteId = null;
      });
  });

  if (cancelBtn) cancelBtn.addEventListener("click", resetForm);
  if (refreshBtn) {
    refreshBtn.addEventListener("click", function () {
      fetchUnits();
      showAlert(alertPlaceholder, "success", "Units list reloaded.");
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

  fetchUnits();
});
