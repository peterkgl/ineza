document.addEventListener('DOMContentLoaded', function () {
  var roleForm = document.getElementById('roleForm');
  var rolesList = document.getElementById('rolesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var roleIdInput = document.getElementById('roleIdInput');
  var roleTokenInput = document.getElementById('roleToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allRoles = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('rolesTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredRoles() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allRoles;
    return allRoles.filter(function (r) {
      var nameVal = (r.name || '').toLowerCase();
      var descVal = (r.description || '').toLowerCase();
      var permCountVal = String(r.permission_ids ? r.permission_ids.length : 0) + ' privileges';
      var createdVal = (r.created_at || '').split(' ')[0].toLowerCase();
      return nameVal.indexOf(query) !== -1 ||
             descVal.indexOf(query) !== -1 ||
             permCountVal.indexOf(query) !== -1 ||
             createdVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderRoles();
    });
  }

  var isFirstLoad = true;
  function fetchRoles() {
    if (isFirstLoad && window.initialRolesData) {
      isFirstLoad = false;
      allRoles = window.initialRolesData;
      renderRoles();
      updateStats(allRoles);
      return;
    }
    isFirstLoad = false;
    fetch('role_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allRoles = result.data;
          renderRoles();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching roles:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading roles.');
      });
  }

  function renderRoles() {
    var filtered = getFilteredRoles();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      rolesList.innerHTML = '<tr><td colspan="6" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching roles found.' : 'No roles defined yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var html = '';
    paginated = filtered.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);
    paginated.forEach(function (r, index) {
      var globalIndex = (currentPage - 1) * itemsPerPage + index + 1;
      var nameVal = escapeHtml(r.name);
      var descVal = r.description ? escapeHtml(r.description) : '—';
      var createdVal = escapeHtml(r.created_at.split(' ')[0]);
      var permCount = r.permission_ids ? r.permission_ids.length : 0;
      
      var permBadgeClass = permCount > 0 ? 'pill-green' : '';
      var permBadge = '<span class="status-pill ' + permBadgeClass + '">' + permCount + ' Privileges</span>';

      var deleteBtn = '';
      if (r.name.toLowerCase() !== 'admin') {
        deleteBtn = '<button class="btn-icon-only delete" title="Delete Role" data-id="' + r.id + '">' +
          '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
        '</button>';
      }

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td>' + descVal + '</td>' +
        '<td>' + permBadge + '</td>' +
        '<td>' + createdVal + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Role" data-id="' + r.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            deleteBtn +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    rolesList.innerHTML = html;
    attachRowEventListeners(paginated);
    renderPagination(totalItems, totalPages);
  }

  function renderPagination(totalItems, totalPages) {
    if (totalPages <= 1) {
      paginationContainer.style.display = 'none';
      return;
    }
    paginationContainer.style.display = 'flex';

    var startIndex = (currentPage - 1) * itemsPerPage + 1;
    var endIndex = Math.min(startIndex + itemsPerPage - 1, totalItems);

    var infoHtml = 'Showing ' + startIndex + ' to ' + endIndex + ' of ' + totalItems + ' entries';
    
    var buttonsHtml = '';
    
    buttonsHtml += '<button class="pagination-btn" ' + (currentPage === 1 ? 'disabled' : '') + ' data-page="' + (currentPage - 1) + '">&laquo; Prev</button>';

    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
      startPage = Math.max(1, endPage - 4);
    }

    if (startPage > 1) {
      buttonsHtml += '<button class="pagination-btn" data-page="1">1</button>';
      if (startPage > 2) {
        buttonsHtml += '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      buttonsHtml += '<button class="pagination-btn ' + (p === currentPage ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        buttonsHtml += '<span style="padding: 0 4px; color: var(--text3);">...</span>';
      }
      buttonsHtml += '<button class="pagination-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
    }

    buttonsHtml += '<button class="pagination-btn" ' + (currentPage === totalPages ? 'disabled' : '') + ' data-page="' + (currentPage + 1) + '">Next &raquo;</button>';

    paginationContainer.innerHTML = 
      '<div class="pagination-info">' + infoHtml + '</div>' +
      '<div class="pagination-buttons">' + buttonsHtml + '</div>';

    var buttons = paginationContainer.querySelectorAll('.pagination-btn');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var page = parseInt(btn.getAttribute('data-page'), 10);
        if (page && page !== currentPage) {
          currentPage = page;
          renderRoles();
        }
      });
    });
  }

  function updateStats(roles) {
    var total = roles.length;
    var assigned = 0;
    var maxPerms = 0;
    var totalPermsCount = document.querySelectorAll('.perm-checkbox').length;

    roles.forEach(function (r) {
      if (r.user_count > 0) {
        assigned++;
      }
      
      var permCount = r.permission_ids ? r.permission_ids.length : 0;
      if (permCount > maxPerms) {
        maxPerms = permCount;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = assigned;
    document.getElementById('stat-inactive').textContent = totalPermsCount;
    document.getElementById('stat-base').textContent = maxPerms;
  }

  function updateToken(token) {
    if (roleTokenInput) {
      roleTokenInput.value = token;
    }
  }

  function attachRowEventListeners(roles) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var r = roles.find(function (x) { return x.id === id; });
        if (r) {
          setEditMode(r);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var r = roles.find(function (x) { return x.id === id; });
        if (r) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the security role "' + r.name + '"? This will fail if the role is assigned to users.';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(r) {
    if (!roleForm) return;
    
    roleIdInput.value = r.id;
    var nameInput = document.getElementById('roleName');
    nameInput.value = r.name;
    
    if (r.name.toLowerCase() === 'admin') {
      nameInput.setAttribute('readonly', 'true');
      nameInput.style.opacity = '0.7';
    } else {
      nameInput.removeAttribute('readonly');
      nameInput.style.opacity = '1';
    }

    document.getElementById('roleDescription').value = r.description || '';

    var checkBoxes = document.querySelectorAll('.perm-checkbox');
    checkBoxes.forEach(function (cb) {
      var val = parseInt(cb.value, 10);
      cb.checked = r.permission_ids && r.permission_ids.indexOf(val) !== -1;
    });

    formTitle.textContent = 'Edit Role: ' + r.name;
    saveBtn.textContent = 'Update Role';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!roleForm) return;
    roleForm.reset();
    roleIdInput.value = '';
    
    var nameInput = document.getElementById('roleName');
    nameInput.removeAttribute('readonly');
    nameInput.style.opacity = '1';

    var checkBoxes = document.querySelectorAll('.perm-checkbox');
    checkBoxes.forEach(function (cb) {
      cb.checked = false;
    });

    formTitle.textContent = 'Add Security Role';
    saveBtn.textContent = 'Save Role';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (roleForm) {
    roleForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = roleIdInput.value;
      var name = document.getElementById('roleName').value.trim();
      var description = document.getElementById('roleDescription').value.trim();
      var token = roleTokenInput.value;

      if (!name) {
        showAlert(formAlertPlaceholder, 'error', 'Role name is required.');
        return;
      }

      var selectedPerms = [];
      var checkBoxes = document.querySelectorAll('.perm-checkbox:checked');
      checkBoxes.forEach(function (cb) {
        selectedPerms.push(cb.value);
      });

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('name', name);
      formData.append('description', description);
      formData.append('permissions', selectedPerms.join(','));
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('role_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Role' : 'Save Role';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchRoles();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving role:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Role' : 'Save Role';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the role.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = roleTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('role_api.php?action=delete', {
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
        fetchRoles();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting role:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the role.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchRoles();
      showAlert(alertPlaceholder, 'success', 'Roles list reloaded.');
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

  fetchRoles();
});
