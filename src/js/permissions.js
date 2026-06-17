document.addEventListener('DOMContentLoaded', function () {
  var permissionForm = document.getElementById('permissionForm');
  var permissionsList = document.getElementById('permissionsList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var permissionIdInput = document.getElementById('permissionIdInput');
  var permissionTokenInput = document.getElementById('permissionToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allPermissions = [];
  var searchInput = document.getElementById('searchInput');

  function applySearchFilter() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    var rows = Array.prototype.slice.call(permissionsList.querySelectorAll('tr'));
    
    var existingNoMatch = permissionsList.querySelector('.no-match-row');
    if (existingNoMatch) {
      existingNoMatch.parentNode.removeChild(existingNoMatch);
    }
    
    var emptyRow = permissionsList.querySelector('.table-empty');
    if (emptyRow && rows.length === 1 && !existingNoMatch) {
      return;
    }
    
    var visibleCount = 0;
    rows.forEach(function (row) {
      if (row.classList.contains('no-match-row')) return;
      var cells = Array.prototype.slice.call(row.querySelectorAll('td'));
      if (cells.length < 2) return;
      
      var textContent = '';
      for (var i = 1; i < cells.length; i++) {
        textContent += ' ' + cells[i].textContent.toLowerCase();
      }
      
      if (textContent.indexOf(query) !== -1) {
        row.style.display = '';
        visibleCount++;
        cells[0].textContent = visibleCount;
      } else {
        row.style.display = 'none';
      }
    });
    
    if (visibleCount === 0 && rows.length > 0) {
      var noMatchRow = document.createElement('tr');
      noMatchRow.className = 'no-match-row';
      noMatchRow.innerHTML = '<td colspan="6" class="table-empty" style="text-align: center;">No matching permissions found.</td>';
      permissionsList.appendChild(noMatchRow);
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applySearchFilter);
  }

  function fetchPermissions() {
    fetch('permissions_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allPermissions = result.data;
          renderPermissions(result.data);
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching permissions:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading permissions.');
      });
  }

  function renderPermissions(perms) {
    if (!perms || perms.length === 0) {
      permissionsList.innerHTML = '<tr><td colspan="6" class="table-empty">No permissions defined yet.</td></tr>';
      return;
    }

    var html = '';
    perms.forEach(function (p, index) {
      var nameVal = escapeHtml(p.name);
      var codeVal = escapeHtml(p.code);
      var createdVal = escapeHtml(p.created_at.split(' ')[0]);
      
      var roleBadgeClass = p.role_count > 0 ? 'pill-green' : '';
      var roleBadge = '<span class="status-pill ' + roleBadgeClass + '">' + p.role_count + ' Roles</span>';

      var actionsHtml = '';
      if (!p.is_core) {
        actionsHtml = '<button class="btn-icon-only edit" title="Edit Permission" data-id="' + p.id + '">' +
            '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
          '</button>' +
          '<button class="btn-icon-only delete" title="Delete Permission" data-id="' + p.id + '">' +
            '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
          '</button>';
      } else {
        actionsHtml = '<span title="Core Permission System Lock" style="color:var(--text3); cursor:not-allowed; display:inline-flex; align-items:center; gap:4px; font-size:11px;"><svg viewBox="0 0 24 24" style="width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> System</span>';
      }

      html += '<tr>' +
        '<td>' + (index + 1) + '</td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td><span class="code-badge">' + codeVal + '</span></td>' +
        '<td>' + roleBadge + '</td>' +
        '<td>' + createdVal + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end; align-items:center; min-height:26px;">' +
            actionsHtml +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    permissionsList.innerHTML = html;
    attachRowEventListeners(perms);
    applySearchFilter();
  }

  function updateStats(perms) {
    var total = perms.length;
    var mapped = 0;
    var unmapped = 0;
    var core = 0;

    perms.forEach(function (p) {
      if (p.role_count > 0) {
        mapped++;
      } else {
        unmapped++;
      }
      if (p.is_core) {
        core++;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = mapped;
    document.getElementById('stat-inactive').textContent = unmapped;
    document.getElementById('stat-base').textContent = core;
  }

  function updateToken(token) {
    if (permissionTokenInput) {
      permissionTokenInput.value = token;
    }
  }

  function attachRowEventListeners(perms) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var p = perms.find(function (x) { return x.id === id; });
        if (p) {
          setEditMode(p);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var p = perms.find(function (x) { return x.id === id; });
        if (p) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the permission "' + p.name + ' (' + p.code + ')"? This will revoke it from any assigned roles.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(p) {
    if (!permissionForm) return;
    
    permissionIdInput.value = p.id;
    document.getElementById('permissionName').value = p.name;
    document.getElementById('permissionCode').value = p.code;

    formTitle.textContent = 'Edit Permission: ' + p.name;
    saveBtn.textContent = 'Update Permission';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!permissionForm) return;
    permissionForm.reset();
    permissionIdInput.value = '';

    formTitle.textContent = 'Add Permission';
    saveBtn.textContent = 'Save Permission';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (permissionForm) {
    permissionForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = permissionIdInput.value;
      var name = document.getElementById('permissionName').value.trim();
      var code = document.getElementById('permissionCode').value.trim();
      var token = permissionTokenInput.value;

      if (!name || !code) {
        showAlert(formAlertPlaceholder, 'error', 'Permission name and code are required.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('name', name);
      formData.append('code', code);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('permissions_api.php?action=' + action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Permission' : 'Save Permission';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchPermissions();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving permission:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Permission' : 'Save Permission';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the permission.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = permissionTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('permissions_api.php?action=delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: formData.toString()
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (result) {
      if (result.token) {
        updateToken(result.token);
      }

      if (result.success) {
        showAlert(alertPlaceholder, 'success', result.message);
        resetForm();
        fetchPermissions();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting permission:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the permission.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchPermissions();
      showAlert(alertPlaceholder, 'success', 'Permissions list reloaded.');
    });
  }

  function showAlert(container, type, message) {
    var icon = type === 'success' 
      ? '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
      : '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    container.innerHTML = '<div class="alert-banner ' + type + '">' + icon + '<span>' + escapeHtml(message) + '</span></div>';
    
    if (type === 'success') {
      setTimeout(function () {
        container.innerHTML = '';
      }, 4000);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  fetchPermissions();
});
