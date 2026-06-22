document.addEventListener('DOMContentLoaded', function () {
  var auditSearch = document.getElementById('auditSearch');
  var auditLimit = document.getElementById('auditLimit');
  var auditList = document.getElementById('auditList');
  var paginationInfo = document.getElementById('paginationInfo');
  var paginationNav = document.getElementById('paginationNav');
  var alertPlaceholder = document.getElementById('alertPlaceholder');

  var detailsOverlay = document.getElementById('detailsOverlay');
  var detailsBody = document.getElementById('detailsBody');
  var detailsCloseBtn = document.getElementById('detailsCloseBtn');

  var currentPage = 1;
  var currentLimit = 25;
  var currentSearch = '';
  var searchTimeout = null;
  var loadedLogs = [];

  var isFirstLoad = true;
  function fetchLogs() {
    if (isFirstLoad && window.initialAuditLogsData) {
      isFirstLoad = false;
      loadedLogs = window.initialAuditLogsData.data;
      renderLogs(loadedLogs);
      renderPagination(window.initialAuditLogsData.pagination);
      updateStats(window.initialAuditLogsData.stats);
      return;
    }
    isFirstLoad = false;
    var url = 'audit_api.php?action=list&page=' + currentPage + '&limit=' + currentLimit;
    if (currentSearch !== '') {
      url += '&search=' + encodeURIComponent(currentSearch);
    }

    fetch(url)
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          loadedLogs = result.data;
          renderLogs(result.data);
          renderPagination(result.pagination);
          updateStats(result.stats);
        } else {
          showAlert(alertPlaceholder, 'error', result.message);
        }
      })
      .catch(function (error) {
        console.error('Error fetching logs:', error);
        showAlert(alertPlaceholder, 'error', 'An error occurred while loading audit logs.');
      });
  }

  function renderLogs(logs) {
    if (!logs || logs.length === 0) {
      auditList.innerHTML = '<tr><td colspan="8" class="table-empty">No matching log entries found.</td></tr>';
      return;
    }

    var html = '';
    logs.forEach(function (log, index) {
      var timeVal = escapeHtml(log.performed_at);
      var userVal = escapeHtml(log.user_full_name);
      
      var act = log.action.toUpperCase();
      var badgeClass = 'badge-view';
      if (act === 'CREATE') badgeClass = 'badge-create';
      else if (act === 'UPDATE') badgeClass = 'badge-update';
      else if (act === 'DELETE') badgeClass = 'badge-delete';
      else if (act === 'LOGIN') badgeClass = 'badge-login';
      else if (act === 'LOGOUT') badgeClass = 'badge-logout';

      var actBadge = '<span class="badge-action ' + badgeClass + '">' + escapeHtml(log.action) + '</span>';
      var tableVal = log.target_table ? escapeHtml(log.target_table) : '—';
      var nameVal = log.target_name ? escapeHtml(log.target_name) : '—';
      var descVal = log.target_description ? escapeHtml(log.target_description) : '—';

      var globalIndex = (currentPage - 1) * currentLimit + index + 1;

      html += '<tr>' +
        '<td>' + globalIndex + '</td>' +
        '<td style="font-family:monospace; font-size:11.5px;">' + timeVal + '</td>' +
        '<td>' + userVal + '</td>' +
        '<td>' + actBadge + '</td>' +
        '<td><span class="code-badge">' + tableVal + '</span></td>' +
        '<td>' + nameVal + '</td>' +
        '<td>' + descVal + '</td>' +
        '<td style="text-align: right;">' +
          '<button class="btn-icon-only view-details-btn" title="View Payload Details" data-id="' + log.id + '">' +
            '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
          '</button>' +
        '</td>' +
      '</tr>';
    });

    auditList.innerHTML = html;

    var detailButtons = document.querySelectorAll('.view-details-btn');
    detailButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var log = loadedLogs.find(function (x) { return x.id === id; });
        if (log) {
          showLogDetails(log);
        }
      });
    });
    applySearchFilter();
  }

  function renderPagination(paging) {
    if (!paging) return;
    
    var start = (paging.page - 1) * paging.limit + 1;
    var end = Math.min(paging.page * paging.limit, paging.total_records);
    if (paging.total_records === 0) {
      start = 0;
      end = 0;
    }
    
    paginationInfo.textContent = 'Showing ' + start + ' to ' + end + ' of ' + paging.total_records + ' entries';

    var html = '';
    
    html += '<button id="prevPageBtn" ' + (paging.page === 1 ? 'disabled' : '') + '>Prev</button>';
    
    var startPage = Math.max(1, paging.page - 2);
    var endPage = Math.min(paging.total_pages, paging.page + 2);
    
    if (startPage > 1) {
      html += '<button class="page-num-btn" data-page="1">1</button>';
      if (startPage > 2) {
        html += '<span style="padding:4px 8px; color:var(--text3);">...</span>';
      }
    }
    
    for (var p = startPage; p <= endPage; p++) {
      var activeClass = p === paging.page ? 'active' : '';
      html += '<button class="page-num-btn ' + activeClass + '" data-page="' + p + '">' + p + '</button>';
    }
    
    if (endPage < paging.total_pages) {
      if (endPage < paging.total_pages - 1) {
        html += '<span style="padding:4px 8px; color:var(--text3);">...</span>';
      }
      html += '<button class="page-num-btn" data-page="' + paging.total_pages + '">' + paging.total_pages + '</button>';
    }
    
    html += '<button id="nextPageBtn" ' + (paging.page === paging.total_pages ? 'disabled' : '') + '>Next</button>';

    paginationNav.innerHTML = html;

    var pageButtons = document.querySelectorAll('.page-num-btn');
    pageButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        currentPage = parseInt(btn.getAttribute('data-page'), 10);
        fetchLogs();
      });
    });

    var prevBtn = document.getElementById('prevPageBtn');
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (currentPage > 1) {
          currentPage--;
          fetchLogs();
        }
      });
    }

    var nextBtn = document.getElementById('nextPageBtn');
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (currentPage < paging.total_pages) {
          currentPage++;
          fetchLogs();
        }
      });
    }
  }

  function updateStats(stats) {
    if (!stats) return;
    document.getElementById('stat-total').textContent = stats.total;
    document.getElementById('stat-creates').textContent = stats.creates;
    document.getElementById('stat-updates').textContent = stats.updates;
    document.getElementById('stat-deletes').textContent = stats.deletes;
  }

  function showLogDetails(log) {
    var html = '';
    
    var notesVal = log.notes ? escapeHtml(log.notes) : '—';
    var userAgentVal = log.user_agent ? escapeHtml(log.user_agent) : '—';
    var sessVal = log.session_id ? escapeHtml(log.session_id) : '—';

    html += '<div class="details-grid">' +
      '<div class="details-grid-label">Timestamp</div><div>' + escapeHtml(log.performed_at) + '</div>' +
      '<div class="details-grid-label">Performed By</div><div><strong>' + escapeHtml(log.user_full_name) + '</strong></div>' +
      '<div class="details-grid-label">Action</div><div>' + escapeHtml(log.action) + '</div>' +
      '<div class="details-grid-label">Target Table</div><div>' + (log.target_table ? escapeHtml(log.target_table) : '—') + '</div>' +
      '<div class="details-grid-label">Record Name</div><div>' + (log.target_name ? escapeHtml(log.target_name) : '—') + '</div>' +
      '<div class="details-grid-label">IP Address</div><div>' + escapeHtml(log.ip_address) + '</div>' +
      '<div class="details-grid-label">Session ID</div><div style="font-family:monospace; font-size:11px; word-break:break-all;">' + sessVal + '</div>' +
      '<div class="details-grid-label">Notes</div><div>' + notesVal + '</div>' +
      '<div class="details-grid-label">User Agent</div><div style="font-size:11px; color:var(--text3); word-break:break-all;">' + userAgentVal + '</div>' +
    '</div>';

    if (log.old_values) {
      html += '<div class="details-payload-section">' +
        '<div class="details-payload-title">Values Before Change (Old Values)</div>' +
        '<pre class="details-payload-pre">' + escapeHtml(JSON.stringify(log.old_values, null, 2)) + '</pre>' +
      '</div>';
    }

    if (log.new_values) {
      html += '<div class="details-payload-section">' +
        '<div class="details-payload-title">Values After Change (New Values)</div>' +
        '<pre class="details-payload-pre">' + escapeHtml(JSON.stringify(log.new_values, null, 2)) + '</pre>' +
      '</div>';
    }

    detailsBody.innerHTML = html;
    detailsOverlay.style.display = 'flex';
  }

  function closeDetails() {
    detailsOverlay.style.display = 'none';
    detailsBody.innerHTML = '';
  }

  if (detailsCloseBtn) {
    detailsCloseBtn.addEventListener('click', closeDetails);
  }

  if (detailsOverlay) {
    detailsOverlay.addEventListener('click', function (e) {
      if (e.target === detailsOverlay) {
        closeDetails();
      }
    });
  }

  function applySearchFilter() {
    var query = auditSearch ? auditSearch.value.toLowerCase().trim() : '';
    var rows = Array.prototype.slice.call(auditList.querySelectorAll('tr'));
    
    var existingNoMatch = auditList.querySelector('.no-match-row');
    if (existingNoMatch) {
      existingNoMatch.parentNode.removeChild(existingNoMatch);
    }
    
    var emptyRow = auditList.querySelector('.table-empty');
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
        var globalStart = (currentPage - 1) * currentLimit;
        cells[0].textContent = globalStart + visibleCount;
      } else {
        row.style.display = 'none';
      }
    });
    
    if (visibleCount === 0 && rows.length > 0) {
      var noMatchRow = document.createElement('tr');
      noMatchRow.className = 'no-match-row';
      noMatchRow.innerHTML = '<td colspan="8" class="table-empty" style="text-align: center;">No matching log entries found.</td>';
      auditList.appendChild(noMatchRow);
    }
  }

  if (auditSearch) {
    auditSearch.addEventListener('input', function () {
      // Instant client-side filter for immediate visual response
      applySearchFilter();
      
      // Debounced database fetch to update the complete results set
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function () {
        currentSearch = auditSearch.value.trim();
        currentPage = 1;
        fetchLogs();
      }, 250);
    });
  }

  if (auditLimit) {
    auditLimit.addEventListener('change', function () {
      currentLimit = parseInt(auditLimit.value, 10);
      currentPage = 1;
      fetchLogs();
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

  fetchLogs();
});
