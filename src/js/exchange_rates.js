document.addEventListener('DOMContentLoaded', function () {
  var rateForm = document.getElementById('rateForm');
  var ratesList = document.getElementById('ratesList');
  var alertPlaceholder = document.getElementById('alertPlaceholder');
  var formAlertPlaceholder = document.getElementById('formAlertPlaceholder');
  var formTitle = document.getElementById('formTitle');
  var rateIdInput = document.getElementById('rateId');
  var rateTokenInput = document.getElementById('rateToken');
  var cancelBtn = document.getElementById('cancelBtn');
  var saveBtn = document.getElementById('saveBtn');
  var refreshBtn = document.getElementById('refreshBtn');

  var confirmOverlay = document.getElementById('confirmOverlay');
  var confirmBody = document.getElementById('confirmBody');
  var confirmCancelBtn = document.getElementById('confirmCancelBtn');
  var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  
  var activeDeleteId = null;
  var allRates = [];
  var searchInput = document.getElementById('searchInput');

  function applySearchFilter() {
    var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    var rows = Array.prototype.slice.call(ratesList.querySelectorAll('tr'));
    
    var existingNoMatch = ratesList.querySelector('.no-match-row');
    if (existingNoMatch) {
      existingNoMatch.parentNode.removeChild(existingNoMatch);
    }
    
    var emptyRow = ratesList.querySelector('.table-empty');
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
      noMatchRow.innerHTML = '<td colspan="7" class="table-empty" style="text-align: center;">No matching exchange rates found.</td>';
      ratesList.appendChild(noMatchRow);
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applySearchFilter);
  }

  function fetchRates() {
    fetch('rates_api.php?action=list')
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          allRates = result.data;
          renderRates(result.data);
          updateStats(result.data);
          if (result.token) {
            updateToken(result.token);
          }
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching rates:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading exchange rates.');
      });
  }

  function renderRates(rates) {
    if (!rates || rates.length === 0) {
      ratesList.innerHTML = '<tr><td colspan="7" class="table-empty">No exchange rates registered yet.</td></tr>';
      return;
    }

    var html = '';
    rates.forEach(function (r, index) {
      var sourceVal = r.source ? escapeHtml(r.source) : '—';
      
      html += '<tr>' +
        '<td>' + (index + 1) + '</td>' +
        '<td>' + escapeHtml(r.rate_date) + '</td>' +
        '<td><span class="code-badge">' + escapeHtml(r.from_code) + '</span></td>' +
        '<td><span class="code-badge">' + escapeHtml(r.to_code) + '</span></td>' +
        '<td>1 ' + escapeHtml(r.to_code) + ' = <strong>' + escapeHtml(parseFloat(r.rate).toString()) + '</strong> ' + escapeHtml(r.from_code) + '</td>' +
        '<td>' + sourceVal + '</td>' +
        '<td style="text-align: right;">' +
          '<div class="action-buttons" style="justify-content: flex-end;">' +
            '<button class="btn-icon-only edit" title="Edit Rate" data-id="' + r.id + '">' +
              '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
            '</button>' +
            '<button class="btn-icon-only delete" title="Delete Rate" data-id="' + r.id + '">' +
              '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    ratesList.innerHTML = html;
    attachRowEventListeners(rates);
    applySearchFilter();
  }

  function updateStats(rates) {
    var total = rates.length;
    
    var uniquePairs = {};
    var uniqueSources = {};
    var maxDate = '-';

    rates.forEach(function (r) {
      var pair = r.from_code + '>' + r.to_code;
      uniquePairs[pair] = true;
      
      if (r.source && r.source.trim() !== '') {
        uniqueSources[r.source.trim().toLowerCase()] = true;
      }
      
      if (maxDate === '-' || r.rate_date > maxDate) {
        maxDate = r.rate_date;
      }
    });

    var activePairsCount = Object.keys(uniquePairs).length;
    var sourcesCount = Object.keys(uniqueSources).length;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-active').textContent = activePairsCount;
    document.getElementById('stat-base').textContent = maxDate;
    document.getElementById('stat-inactive').textContent = sourcesCount;
  }

  function updateToken(token) {
    if (rateTokenInput) {
      rateTokenInput.value = token;
    }
  }

  function attachRowEventListeners(rates) {
    var editButtons = document.querySelectorAll('.action-buttons .edit');
    var deleteButtons = document.querySelectorAll('.action-buttons .delete');

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var r = rates.find(function (x) { return x.id === id; });
        if (r) {
          setEditMode(r);
        }
      });
    });

    deleteButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var r = rates.find(function (x) { return x.id === id; });
        if (r) {
          activeDeleteId = id;
          confirmBody.textContent = 'Are you sure you want to delete the exchange rate record of 1 ' + r.to_code + ' = ' + parseFloat(r.rate) + ' ' + r.from_code + ' dated ' + r.rate_date + '?';
          confirmOverlay.style.display = 'flex';
        }
      });
    });
  }

  function setEditMode(r) {
    if (!rateForm) return;
    
    rateIdInput.value = r.id;
    document.getElementById('rateFrom').value = r.from_currency_id;
    document.getElementById('rateTo').value = r.to_currency_id;
    document.getElementById('rateValue').value = parseFloat(r.rate);
    document.getElementById('rateDate').value = r.rate_date;
    document.getElementById('rateSource').value = r.source || '';

    formTitle.textContent = 'Edit Exchange Rate';
    saveBtn.textContent = 'Update Rate';
    cancelBtn.style.display = 'inline-block';
    
    if (window.innerWidth <= 992) {
      document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function resetForm() {
    if (!rateForm) return;
    rateForm.reset();
    rateIdInput.value = '';
    
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('rateDate').value = today;

    formTitle.textContent = 'Add Exchange Rate';
    saveBtn.textContent = 'Save Rate';
    cancelBtn.style.display = 'none';
    formAlertPlaceholder.innerHTML = '';
  }

  if (rateForm) {
    rateForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var id = rateIdInput.value;
      var fromId = document.getElementById('rateFrom').value;
      var toId = document.getElementById('rateTo').value;
      var rateVal = document.getElementById('rateValue').value.trim();
      var rateDate = document.getElementById('rateDate').value.trim();
      var source = document.getElementById('rateSource').value.trim();
      var token = rateTokenInput.value;

      if (!fromId || !toId || !rateVal || !rateDate) {
        showAlert(formAlertPlaceholder, 'error', 'From currency, to currency, rate value, and date are required.');
        return;
      }

      if (fromId === toId) {
        showAlert(formAlertPlaceholder, 'error', 'From and to currencies must be different.');
        return;
      }

      if (parseFloat(rateVal) <= 0) {
        showAlert(formAlertPlaceholder, 'error', 'Exchange rate must be a positive number.');
        return;
      }

      var action = id ? 'update' : 'create';
      
      var formData = new URLSearchParams();
      if (id) formData.append('id', id);
      formData.append('from_currency_id', fromId);
      formData.append('to_currency_id', toId);
      formData.append('rate', rateVal);
      formData.append('rate_date', rateDate);
      formData.append('source', source);
      formData.append('token', token);

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('rates_api.php?action=' + action, {
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
        saveBtn.textContent = id ? 'Update Rate' : 'Save Rate';

        if (result.token) {
          updateToken(result.token);
        }

        if (result.success) {
          showAlert(alertPlaceholder, 'success', result.message);
          resetForm();
          fetchRates();
        } else {
          showAlert(formAlertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error saving rate:', error);
        saveBtn.disabled = false;
        saveBtn.textContent = id ? 'Update Rate' : 'Save Rate';
        showAlert(formAlertPlaceholder, 'error', 'An error occurred while saving the exchange rate.');
      });
    });
  }

  confirmCancelBtn.addEventListener('click', function () {
    confirmOverlay.style.display = 'none';
    activeDeleteId = null;
  });

  confirmDeleteBtn.addEventListener('click', function () {
    if (!activeDeleteId) return;

    var token = rateTokenInput.value;
    var formData = new URLSearchParams();
    formData.append('id', activeDeleteId);
    formData.append('token', token);

    confirmOverlay.style.display = 'none';
    
    fetch('rates_api.php?action=delete', {
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
        fetchRates();
      } else {
        showAlert(alertPlaceholder, 'error', result.message);
      }
      activeDeleteId = null;
    })
    .catch(function (error) {
      console.error('Error deleting rate:', error);
      showAlert(alertPlaceholder, 'error', 'An error occurred while deleting the exchange rate.');
      activeDeleteId = null;
    });
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchRates();
      showAlert(alertPlaceholder, 'success', 'Exchange rates list reloaded.');
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

  fetchRates();
});
