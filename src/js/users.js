document.addEventListener('DOMContentLoaded', function () {
  var userForm = document.getElementById('userForm');
  var usersList = document.getElementById('usersList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var userIdInput = document.getElementById('userIdInput');
  var userTokenInput = document.getElementById('userToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var passwordInput = document.getElementById('password');
  var passwordTip = document.getElementById('passwordTip');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allUsers = [];
  var searchInput = document.getElementById('searchInput');

  var currentPage = 1;
  var itemsPerPage = 25;
  var tableContainer = document.getElementById('usersTable').parentElement;
  var paginationContainer = document.createElement('div');
  paginationContainer.className = 'table-pagination';
  tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);

  function getFilteredUsers() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (!query) return allUsers;
    return allUsers.filter(function (u) {
      var nameVal = (u.first_name + ' ' + u.last_name).toLowerCase();
      var emailVal = (u.email || '').toLowerCase();
      var phoneVal = (u.phone_number || '').toLowerCase();
      var roleVal = (u.role_name || '').toLowerCase();
      var statusVal = (u.is_active === 1 ? 'active' : 'inactive').toLowerCase();
      return nameVal.indexOf(query) !== -1 ||
             emailVal.indexOf(query) !== -1 ||
             phoneVal.indexOf(query) !== -1 ||
             roleVal.indexOf(query) !== -1 ||
             statusVal.indexOf(query) !== -1;
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentPage = 1;
      renderUsers();
    });
  }

  var isFirstLoad = true;
  function fetchUsers() {
    if (isFirstLoad && window.initialUsersData) {
      isFirstLoad = false;
      allUsers = window.initialUsersData;
      renderUsers();
      updateStats(allUsers);
      return;
    }
    isFirstLoad = false;
    fetch('users_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allUsers = result.data;
          renderUsers();
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching users:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading users.');
      });
  }

  function renderUsers() {
    var filtered = getFilteredUsers();
    var totalItems = filtered.length;
    var totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (currentPage > totalPages) {
      currentPage = Math.max(1, totalPages);
    }
    
    if (totalItems === 0) {
      usersList.innerHTML = '<tr><td colspan="7" class="table-empty">' + (searchInput && searchInput.value.trim() ? 'No matching users found.' : 'No users registered yet.') + '</td></tr>';
      renderPagination(totalItems, totalPages);
      return;
    }

    var usersTableElement = document.getElementById('usersTable');
    var currentUserId = usersTableElement ? parseInt(usersTableElement.getAttribute('data-current-user-id'), 10) : 0;

    var startIndex = (currentPage - 1) * itemsPerPage;
    var endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    var paginated = filtered.slice(startIndex, endIndex);

    var html = '';
    paginated.forEach(function (u, index) {
      var globalIndex = startIndex + index + 1;
      var nameVal = escapeHtml(u.first_name + ' ' + u.last_name);
      var phoneVal = u.phone_number ? escapeHtml(u.phone_number) : '—';
      var roleVal = escapeHtml(u.role_name);
      
      var statusLabel = u.is_active === 1
        ? '<span class="status-pill pill-green">Active</span>'
        : '<span class="status-pill pill-red">Inactive</span>';

      var deleteBtn = '';
      if (u.id !== currentUserId) {
        deleteBtn = '<button class="btn-icon-only delete" title="Delete User" data-id="' + u.id + '">' +
          '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
        '</button>';
      }

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td><strong>' + nameVal + '</strong></td>' +
        '<td>' + escapeHtml(u.email) + '</td>' +
        '<td>' + phoneVal + '</td>' +
        '<td><span class="code-badge">' + roleVal + '</span></td>' +
        '<td>' + statusLabel + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit User" data-id="' + u.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            deleteBtn +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    usersList.innerHTML = html;
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
          renderUsers();
        }
      });
    });
  }

  function updateStats(users) {
    var total = users.length;
    var active = 0;
    var inactive = 0;
    var uniqueRoles = {};

    users.forEach(function (u) {
      if (u.is_active === 1) {
        active++;
        if (u.role_name && u.role_name !== 'No Role') {
          uniqueRoles[u.role_name] = true;
        }
      } else {
        inactive++;
      }
    });

    var rolesCount = Object.keys(uniqueRoles).length;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = active;
    document.getElementById('stat-inactive').textContent = inactive;
    document.getElementById('stat-base').textContent = rolesCount;
  }

  function updateToken(token) {
    if (userTokenInput) {
      userTokenInput.value = token;
    }
  }

  function attachRowEventListeners(users) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var u = users.find(function (x) { return x.id === id; });
        if (u) {
          setEditMode(u);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var u = users.find(function (x) { return x.id === id; });
        if (u) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the user profile of "' + u.first_name + ' ' + u.last_name + ' (' + u.email + ')"?';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(u) {
    if (!userForm) return;
    
    userIdInput.value = u.id;
    document.getElementById('firstName').value = u.first_name;
    document.getElementById('lastName').value = u.last_name;
    document.getElementById('userEmail').value = u.email;
    document.getElementById('phone').value = u.phone_number || '';
    document.getElementById('userRole').value = u.role_id || '';
    document.getElementById('userActive').checked = u.is_active === 1;

    passwordInput.value = '';
    passwordInput.removeAttribute('required');
    passwordInput.placeholder = 'Enter new password to change it';
    passwordTip.style.display = 'inline';

    formTitle.textContent = 'Edit User: ' + u.first_name;
    saveBtn.textContent = 'Update User';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!userForm) return;
    userForm.reset();
    userIdInput.value = '';
    
    passwordInput.value = '';
    passwordInput.setAttribute('required', 'required');
    passwordInput.placeholder = 'Enter password';
    passwordTip.style.display = 'none';

    formTitle.textContent = 'Add New User';
    saveBtn.textContent = 'Save User';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (userForm) {
    userForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = userIdInput.value;
      var first = document.getElementById('firstName').value.trim();
      var last = document.getElementById('lastName').value.trim();
      var email = document.getElementById('userEmail').value.trim();
      var phone = document.getElementById('phone').value.trim();
      var password = passwordInput.value;
      var role = document.getElementById('userRole').value;
      var active = document.getElementById('userActive').checked ? '1' : '0';
      var token = userTokenInput.value;

      if (!first || !last || !email || !role) {
        showAlert(formAlertPlaceholder, 'error', 'First name, last name, email, and role are required.');
        return;
      }

      if (!id && !password) {
        showAlert(formAlertPlaceholder, 'error', 'Password is required for new users.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('first_name', first);
      formData.append('last_name', last);
      formData.append('email', email);
      formData.append('phone_number', phone);
      formData.append('password', password);
      formData.append('role_id', role);
      formData.append('is_active', active);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('users_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update User' : 'Save User';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchUsers();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving user:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update User' : 'Save User';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the user profile.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = userTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('users_api.php?action=delete', {
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
        fetchUsers();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting user:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the user profile.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchUsers();
      showAlert(alertPlaceholder, 'success', 'Users list reloaded.');
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

  fetchUsers();
});
