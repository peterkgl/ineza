/* ==========================================
   INEZA AFRICAN MINING — Dashboard Page Logic
   ========================================== */

document.addEventListener('DOMContentLoaded', function () {
  // Add Entry Button handler
  var addEntryBtn = document.getElementById('addEntryBtn');
  if (addEntryBtn) {
    addEntryBtn.addEventListener('click', function () {
      alert('Add Entry feature: This will open a form to record a new transaction or mineral purchase.');
    });
  }

  // Export Button handler
  var exportBtn = document.getElementById('exportBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      alert('Export feature: Preparing cash lead schedule data for CSV/Excel download.');
    });
  }

  // Add slight hover animations to stat cards
  var statCards = document.querySelectorAll('.stat-card');
  statCards.forEach(function (card) {
    card.addEventListener('mouseenter', function () {
      card.style.transform = 'translateY(-2px)';
      card.style.transition = 'all 0.2s ease-in-out';
    });
    card.addEventListener('mouseleave', function () {
      card.style.transform = 'translateY(0)';
    });
  });

  // Live search and numbering for Accounts Payable table
  var dashboardSearch = document.getElementById('dashboardSearch');
  var payableList = document.getElementById('payableList');
  
  if (dashboardSearch && payableList) {
    var originalRows = Array.prototype.slice.call(payableList.querySelectorAll('tr'));
    
    dashboardSearch.addEventListener('input', function () {
      var query = dashboardSearch.value.toLowerCase().trim();
      var visibleCount = 0;
      
      // Remove any existing "no match" row
      var existingNoMatch = payableList.querySelector('.no-match-row');
      if (existingNoMatch) {
        existingNoMatch.parentNode.removeChild(existingNoMatch);
      }
      
      originalRows.forEach(function (row) {
        var cells = Array.prototype.slice.call(row.querySelectorAll('td'));
        if (cells.length < 2) return;
        
        // Collect text content from all cells except the first (# column)
        var textContent = '';
        for (var i = 1; i < cells.length; i++) {
          textContent += ' ' + cells[i].textContent.toLowerCase();
        }
        
        if (textContent.indexOf(query) !== -1) {
          row.style.display = '';
          visibleCount++;
          // Update sequential numbering starting from 1
          cells[0].textContent = visibleCount;
        } else {
          row.style.display = 'none';
        }
      });
      
      if (visibleCount === 0) {
        var noMatchRow = document.createElement('tr');
        noMatchRow.className = 'no-match-row';
        noMatchRow.innerHTML = '<td colspan="8" class="table-empty" style="text-align: center;">No matching suppliers found.</td>';
        payableList.appendChild(noMatchRow);
      }
    });
  }
});
