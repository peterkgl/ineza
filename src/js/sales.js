/**
 * Sales Module Frontend Controller
 */
(function() {
  // Shared state
  var salesList = [];
  var addedLines = [];
  var currentStep = 1;
  var totalSteps = 4;
  
  // Helpers
  function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return text.toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showAlert(type, message, placeholderId) {
    var placeholder = document.getElementById(placeholderId || 'alertPlaceholder');
    if (!placeholder) return;
    
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    // Use project default styles or standard alert container
    placeholder.innerHTML = '<div class="alert ' + alertClass + '" style="padding: 12px; margin-bottom: 16px; border-radius: 4px; font-size: 13px; font-weight: 500; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0,0,0,0.08);' + 
      (type === 'success' ? 'background:rgba(40,167,69,0.12); color:var(--green);' : 'background:rgba(220,53,69,0.12); color:var(--red);') + '">' +
      '<span>' + escapeHtml(message) + '</span>' +
      '<button type="button" class="alert-close-btn" style="background:none; border:none; color:inherit; font-size: 16px; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>' +
      '</div>';
  }

  // --- SALES ORDER LIST PAGE (index.php) ---
  if (document.getElementById('salesContent')) {
    var salesToken = document.getElementById('salesToken').value;
    var searchInput = document.getElementById('searchInput');
    var refreshBtn = document.getElementById('refreshBtn');
    
    // View Modal
    var viewModal = document.getElementById('viewModal');
    var closeViewModal = document.getElementById('closeViewModal');
    var closeViewModalBtn = document.getElementById('closeViewModalBtn');
    
    // Fetch and populate list
    function loadSales() {
      fetch('sales_api.php?action=list')
        .then(function(res) { return res.json(); })
        .then(function(res) {
          if (res.success) {
            salesList = res.data;
            renderSalesTable(salesList);
          } else {
            showAlert('danger', 'Failed to fetch sales: ' + res.message);
          }
        })
        .catch(function(err) {
          console.error(err);
          showAlert('danger', 'An error occurred while loading sales.');
        });
    }

    function renderSalesTable(list) {
      var tbody = document.getElementById('salesList');
      if (!tbody) return;
      
      if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="table-empty">No sales records found.</td></tr>';
        return;
      }

      var html = '';
      list.forEach(function(row, index) {
        var statusClass = 'status-' + row.status.toLowerCase();
        var statusLabel = row.status.toUpperCase();
        var outstanding = row.total_value_usd - row.total_paid;
        
        html += '<tr data-id="' + row.id + '">';
        html += '<td>' + (index + 1) + '</td>';
        html += '<td style="font-weight: 500; font-family: monospace;">' + escapeHtml(row.sale_no) + '</td>';
        html += '<td>';
        html += '<div style="font-weight: 500;">' + escapeHtml(row.customer_name) + '</div>';
        html += '<div style="font-size: 10px; color: var(--text3); font-family: monospace;">' + escapeHtml(row.customer_code) + '</div>';
        html += '</td>';
        html += '<td>' + escapeHtml(row.sale_date) + '</td>';
        html += '<td>';
        html += '<div>' + escapeHtml(row.warehouse_name) + '</div>';
        html += '</td>';
        html += '<td style="text-align: right; font-weight: 500;">' + row.total_qty_kg.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>';
        html += '<td style="text-align: right; font-weight: 500; color: var(--text1);">' + formatCurrency(row.total_value_usd, 'USD') + '</td>';
        html += '<td style="text-align: right; font-weight: 500; color: var(--green);">' + formatCurrency(row.total_paid, 'USD') + '</td>';
        html += '<td style="text-align: right; font-weight: 500; color: ' + (outstanding > 0 ? 'var(--red)' : 'var(--text3)') + ';">' + formatCurrency(outstanding, 'USD') + '</td>';
        html += '<td style="text-align: center;"><span class="status-badge ' + statusClass + '">' + statusLabel + '</span></td>';
        html += '<td style="text-align: center;">';
        html += '<div style="display: flex; gap: 6px; justify-content: center;">';
        html += '<button class="btn-sm btn-outline view-btn" data-id="' + row.id + '" title="View Details"><svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
        html += '<button class="btn-sm btn-outline delete-btn text-danger" data-id="' + row.id + '" data-no="' + escapeHtml(row.sale_no) + '" title="Delete"><svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg></button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;
      attachListEvents();
    }

    function formatCurrency(amount, currency) {
      if (currency === 'USD') {
        return '$' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
      return 'Frw ' + amount.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function attachListEvents() {
      // View details
      document.querySelectorAll('.view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var id = parseInt(btn.getAttribute('data-id'), 10);
          var sale = salesList.find(function(x) { return x.id === id; });
          if (sale) {
            showDetailsModal(sale);
          }
        });
      });

      // Delete action
      document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var id = parseInt(btn.getAttribute('data-id'), 10);
          var saleNo = btn.getAttribute('data-no');
          
          if (confirm('Are you sure you want to delete sales order "' + saleNo + '"? This will revert the stock quantities in the warehouse and delete associated payments.')) {
            var formData = new FormData();
            formData.append('id', id);
            formData.append('token', salesToken);

            fetch('sales_api.php?action=delete', {
              method: 'POST',
              body: formData
            })
              .then(function(res) { return res.json(); })
              .then(function(res) {
                if (res.success) {
                  showAlert('success', res.message);
                  loadSales();
                } else {
                  showAlert('danger', 'Deletion failed: ' + res.message);
                }
              })
              .catch(function(err) {
                console.error(err);
                showAlert('danger', 'An error occurred during deletion.');
              });
          }
        });
      });
    }

    function showDetailsModal(sale) {
      document.getElementById('modalSaleNo').textContent = 'Sales Order Details: ' + sale.sale_no;
      
      var itemsHtml = '';
      if (sale.items && sale.items.length > 0) {
        itemsHtml += '<table class="data-table" style="margin-top: 15px; font-size:12px;">' +
          '<thead>' +
          '<tr>' +
          '<th>Product</th>' +
          '<th>Warehouse</th>' +
          '<th>Lot</th>' +
          '<th style="text-align:right;">Qty (kg)</th>' +
          '<th style="text-align:right;">Price/kg (USD)</th>' +
          '<th style="text-align:right;">Total (USD)</th>' +
          '</tr>' +
          '</thead>' +
          '<tbody>';
        
        sale.items.forEach(function(i) {
          itemsHtml += '<tr>' +
            '<td><strong>' + escapeHtml(i.product_name) + '</strong> (' + escapeHtml(i.product_code) + ')</td>' +
            '<td>' + escapeHtml(i.warehouse_name) + '</td>' +
            '<td>' + escapeHtml(i.lots_code) + '</td>' +
            '<td style="text-align:right;">' + i.quantity_kg.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>' +
            '<td style="text-align:right;">$' + i.price_per_kg_usd.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>' +
            '<td style="text-align:right;">$' + i.line_value_usd.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>' +
            '</tr>';
        });
        
        itemsHtml += '</tbody></table>';
      }

      var detailsBody = document.getElementById('modalDetailsBody');
      var outstanding = sale.total_value_usd - sale.total_paid;
      var statusClass = 'status-' + sale.status.toLowerCase();
      var statusLabel = sale.status.toUpperCase();

      detailsBody.innerHTML = 
        '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size:13px; margin-bottom: 20px;">' +
          '<div>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Customer Name:</span> <strong>' + escapeHtml(sale.customer_name) + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Customer Code:</span> <code style="background:var(--bg2); padding:2px 4px; border-radius:3px;">' + escapeHtml(sale.customer_code) + '</code></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Billing Currency:</span> <strong>' + escapeHtml(sale.currency) + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Exchange Rate:</span> <strong>' + sale.exchange_rate + ' RWF/USD</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Export Permit:</span> <strong>' + escapeHtml(sale.export_permit_no || '—') + '</strong></p>' +
          '</div>' +
          '<div>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Order Date:</span> <strong>' + escapeHtml(sale.sale_date) + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Incoterms:</span> <strong>' + escapeHtml(sale.incoterms || '—') + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Payment Terms:</span> <strong>' + escapeHtml(sale.payment_terms || '—') + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Destination:</span> <strong>' + escapeHtml(sale.destination_country || '—') + '</strong></p>' +
            '<p style="margin: 4px 0;"><span style="color:var(--text3);">Status:</span> <span class="status-badge ' + statusClass + '">' + statusLabel + '</span></p>' +
          '</div>' +
        '</div>' +
        '<div style="background: var(--bg2); border: 1px solid var(--border); border-radius: 8px; padding: 12px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-bottom: 20px; font-size:13px;">' +
          '<div>' +
            '<div style="color: var(--text3); font-size: 11px; text-transform:uppercase;">Invoice Total</div>' +
            '<div style="font-size: 16px; font-weight: 600; color:var(--text1);">' + formatCurrency(sale.total_value_usd, 'USD') + '</div>' +
            '<div style="font-size: 11px; color: var(--text3);">' + formatCurrency(sale.total_value_rwf, 'RWF') + '</div>' +
          '</div>' +
          '<div>' +
            '<div style="color: var(--text3); font-size: 11px; text-transform:uppercase;">Amount Collected</div>' +
            '<div style="font-size: 16px; font-weight: 600; color:var(--green);">' + formatCurrency(sale.total_paid, 'USD') + '</div>' +
          '</div>' +
          '<div>' +
            '<div style="color: var(--text3); font-size: 11px; text-transform:uppercase;">Outstanding Receivables</div>' +
            '<div style="font-size: 16px; font-weight: 600; color:' + (outstanding > 0 ? 'var(--red)' : 'var(--text3)') + ';">' + formatCurrency(outstanding, 'USD') + '</div>' +
          '</div>' +
        '</div>' +
        '<div>' +
          '<div style="font-size: 12px; font-weight: 600; color: var(--text2); text-transform: uppercase;">Sold Mineral Products</div>' +
          itemsHtml +
        '</div>' +
        (sale.notes ? '<div style="margin-top: 15px; font-size: 13px;"><strong style="display:block;">Notes:</strong><span style="color:var(--text3);">' + escapeHtml(sale.notes) + '</span></div>' : '');

      viewModal.classList.add('active');
    }

    if (closeViewModal) {
      closeViewModal.addEventListener('click', function() { viewModal.classList.remove('active'); });
    }
    if (closeViewModalBtn) {
      closeViewModalBtn.addEventListener('click', function() { viewModal.classList.remove('active'); });
    }

    // Search and filter
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        var term = searchInput.value.toLowerCase().trim();
        if (term === '') {
          renderSalesTable(salesList);
          return;
        }

        var filtered = salesList.filter(function(row) {
          return row.sale_no.toLowerCase().indexOf(term) !== -1 ||
                 row.customer_name.toLowerCase().indexOf(term) !== -1 ||
                 row.customer_code.toLowerCase().indexOf(term) !== -1 ||
                 row.warehouse_name.toLowerCase().indexOf(term) !== -1;
        });
        renderSalesTable(filtered);
      });
    }

    if (refreshBtn) {
      refreshBtn.addEventListener('click', function() { loadSales(); });
    }

    // Initial load
    loadSales();
  }

  // --- RECORD SALES PAGE WIZARD (record.php) ---
  if (document.getElementById('salesRecordContent')) {
    var salesForm = document.getElementById('salesForm');
    var customerModeRadios = document.querySelectorAll('input[name="customer_mode"]');
    var customerSelectSection = document.getElementById('customerSelectSection');
    var customerNewSection = document.getElementById('customerNewSection');

    // Builder line items inputs
    var lineWarehouse = document.getElementById('lineWarehouseId');
    var lineProduct = document.getElementById('lineProductId');
    var lineLot = document.getElementById('lineLotId');
    var lineQuantity = document.getElementById('lineQuantity');
    var linePrice = document.getElementById('linePrice');
    var addLineBtn = document.getElementById('addLineBtn');
    var stockCheckStatus = document.getElementById('stockCheckStatus');
    
    // Header Inputs
    var currencySelect = document.getElementById('currency');
    var exchangeRateInput = document.getElementById('exchangeRate');
    var amountPaidInput = document.getElementById('amountPaid');
    var accountSelect = document.getElementById('accountId');
    
    // Wizard navigation controls
    var prevBtn = document.getElementById('prevBtn');
    var nextBtn = document.getElementById('nextBtn');
    var wizardSteps = document.querySelectorAll('.wizard-step');
    var stepContents = document.querySelectorAll('.wizard-step-content');
    var progressBar = document.querySelector('.wizard-progress-bar');
    
    var selectedStockQty = 0.0;
    var selectedStockCost = 0.0;

    // Toggle customer creation mode
    customerModeRadios.forEach(function(radio) {
      radio.addEventListener('change', function() {
        if (radio.value === 'new') {
          customerSelectSection.style.display = 'none';
          customerNewSection.style.display = 'block';
          document.getElementById('customerId').required = false;
          document.getElementById('custName').required = true;
        } else {
          customerSelectSection.style.display = 'block';
          customerNewSection.style.display = 'none';
          document.getElementById('customerId').required = true;
          document.getElementById('custName').required = false;
        }
      });
    });

    // Query stock availability dynamically
    function checkStockAvailability() {
      var whId = lineWarehouse.value;
      var prodId = lineProduct.value;
      var lotId = lineLot.value;

      if (!lotId) {
        stockCheckStatus.textContent = 'Select Lot';
        stockCheckStatus.style.color = 'var(--text3)';
        selectedStockQty = 0.0;
        selectedStockCost = 0.0;
        return;
      }
      if (!prodId) {
        stockCheckStatus.textContent = 'Select Product';
        stockCheckStatus.style.color = 'var(--text3)';
        selectedStockQty = 0.0;
        selectedStockCost = 0.0;
        return;
      }
      if (!whId) {
        stockCheckStatus.textContent = 'Select Warehouse';
        stockCheckStatus.style.color = 'var(--text3)';
        selectedStockQty = 0.0;
        selectedStockCost = 0.0;
        return;
      }

      stockCheckStatus.textContent = 'Checking stock...';
      stockCheckStatus.style.color = 'var(--text3)';

      fetch('sales_api.php?action=get_stock&product_id=' + prodId + '&warehouse_id=' + whId + '&lot_id=' + lotId)
        .then(function(res) { return res.json(); })
        .then(function(res) {
          if (res.success) {
            selectedStockQty = res.data.qty_on_hand;
            selectedStockCost = res.data.avg_cost_per_kg_usd;
            stockCheckStatus.textContent = 'Available Stock: ' + selectedStockQty.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' kg';
            
            if (selectedStockQty <= 0) {
              stockCheckStatus.style.color = 'var(--red)';
            } else {
              stockCheckStatus.style.color = 'var(--green)';
            }
          } else {
            stockCheckStatus.textContent = 'Error checking stock';
            stockCheckStatus.style.color = 'var(--red)';
            selectedStockQty = 0.0;
            selectedStockCost = 0.0;
          }
        })
        .catch(function(err) {
          console.error(err);
          stockCheckStatus.textContent = 'Error checking stock';
          stockCheckStatus.style.color = 'var(--red)';
          selectedStockQty = 0.0;
          selectedStockCost = 0.0;
        });
    }

    var currentLotInventory = [];

    lineLot.addEventListener('change', function() {
      var lotId = lineLot.value;
      
      // Clear and disable downstream dropdowns
      lineProduct.innerHTML = '<option value="">-- Select Product --</option>';
      lineProduct.disabled = true;
      lineWarehouse.innerHTML = '<option value="">-- Select Warehouse --</option>';
      lineWarehouse.disabled = true;
      currentLotInventory = [];
      
      checkStockAvailability();

      if (!lotId) {
        return;
      }

      stockCheckStatus.textContent = 'Loading products...';
      stockCheckStatus.style.color = 'var(--text3)';

      fetch('sales_api.php?action=get_lot_inventory&lot_id=' + lotId)
        .then(function(res) { return res.json(); })
        .then(function(res) {
          if (res.success) {
            currentLotInventory = res.data;
            
            // Get unique products
            var productsMap = {};
            currentLotInventory.forEach(function(item) {
              productsMap[item.product_id] = {
                id: item.product_id,
                name: item.product_name,
                code: item.product_code
              };
            });
            
            var uniqueProducts = Object.values(productsMap);
            
            if (uniqueProducts.length === 0) {
              lineProduct.innerHTML = '<option value="">No products available in this lot</option>';
              stockCheckStatus.textContent = 'No stock available in this lot';
              stockCheckStatus.style.color = 'var(--red)';
            } else {
              var prodHtml = '<option value="">-- Select Product --</option>';
              uniqueProducts.forEach(function(p) {
                prodHtml += '<option value="' + p.id + '">' + escapeHtml(p.name) + '</option>';
              });
              lineProduct.innerHTML = prodHtml;
              lineProduct.disabled = false;
              stockCheckStatus.textContent = 'Select Product';
              stockCheckStatus.style.color = 'var(--text3)';
            }
          } else {
            stockCheckStatus.textContent = 'Error loading products';
            stockCheckStatus.style.color = 'var(--red)';
          }
        })
        .catch(function(err) {
          console.error(err);
          stockCheckStatus.textContent = 'Error loading products';
          stockCheckStatus.style.color = 'var(--red)';
        });
    });

    lineProduct.addEventListener('change', function() {
      var prodId = parseInt(lineProduct.value, 10);
      
      lineWarehouse.innerHTML = '<option value="">-- Select Warehouse --</option>';
      lineWarehouse.disabled = true;
      
      checkStockAvailability();

      if (!prodId) {
        return;
      }

      // Filter warehouses for the selected product
      var warehouses = currentLotInventory.filter(function(item) {
        return item.product_id === prodId;
      });

      if (warehouses.length === 0) {
        lineWarehouse.innerHTML = '<option value="">No warehouses have this product</option>';
      } else {
        var whHtml = '<option value="">-- Select Warehouse --</option>';
        warehouses.forEach(function(w) {
          whHtml += '<option value="' + w.warehouse_id + '">' + escapeHtml(w.warehouse_name) + ' (' + escapeHtml(w.warehouse_code) + ')</option>';
        });
        lineWarehouse.innerHTML = whHtml;
        lineWarehouse.disabled = false;
        stockCheckStatus.textContent = 'Select Warehouse';
        stockCheckStatus.style.color = 'var(--text3)';
      }
    });

    lineWarehouse.addEventListener('change', checkStockAvailability);

    // Add Product Line Builder Item
    if (addLineBtn) {
      addLineBtn.addEventListener('click', function() {
        var whId = parseInt(lineWarehouse.value, 10);
        var whText = lineWarehouse.options[lineWarehouse.selectedIndex].text;
        var prodId = parseInt(lineProduct.value, 10);
        var prodText = lineProduct.options[lineProduct.selectedIndex].text;
        var lotId = parseInt(lineLot.value, 10);
        var lotText = lineLot.options[lineLot.selectedIndex].text;
        
        var qty = parseFloat(lineQuantity.value);
        var price = parseFloat(linePrice.value);

        if (isNaN(whId) || isNaN(prodId) || isNaN(lotId)) {
          alert('Please select Warehouse, Product, and Lot.');
          return;
        }
        if (isNaN(qty) || qty <= 0) {
          alert('Please enter a valid quantity greater than zero.');
          return;
        }
        if (isNaN(price) || price <= 0) {
          alert('Please enter a valid price per kg greater than zero.');
          return;
        }

        // Validate against fetched stock
        if (qty > selectedStockQty) {
          alert('Requested quantity (' + qty + ' kg) exceeds available stock on hand (' + selectedStockQty + ' kg).');
          return;
        }

        // Check if item already added (same warehouse, product, lot)
        var exists = addedLines.some(function(item) {
          return item.warehouse_id === whId && item.product_id === prodId && item.lot_id === lotId;
        });
        if (exists) {
          alert('This product from the same warehouse and lot has already been added. Modify or delete the existing line.');
          return;
        }

        var lineTotal = qty * price;

        addedLines.push({
          warehouse_id: whId,
          warehouse_name: whText,
          product_id: prodId,
          product_name: prodText,
          lot_id: lotId,
          lots_code: lotText,
          quantity_kg: qty,
          price_per_kg: price,
          total: lineTotal
        });

        // Reset inputs
        lineLot.value = '';
        lineProduct.innerHTML = '<option value="">-- Select Lot First --</option>';
        lineProduct.disabled = true;
        lineWarehouse.innerHTML = '<option value="">-- Select Product First --</option>';
        lineWarehouse.disabled = true;
        lineQuantity.value = '';
        linePrice.value = '';
        currentLotInventory = [];
        
        renderLinesTable();
        checkStockAvailability(); // Refresh stock label
      });
    }

    function renderLinesTable() {
      var tbody = document.getElementById('linesList');
      if (!tbody) return;

      if (addedLines.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="7" style="text-align: center; color: var(--text3); padding: 15px;">No products added yet. Use the builder above.</td></tr>';
        updatePaymentDetails();
        return;
      }

      var html = '';
      addedLines.forEach(function(line, idx) {
        html += '<tr>';
        html += '<td>' + escapeHtml(line.warehouse_name) + '</td>';
        html += '<td>' + escapeHtml(line.product_name) + '</td>';
        html += '<td>' + escapeHtml(line.lots_code) + '</td>';
        html += '<td style="text-align: right;">' + line.quantity_kg.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>';
        html += '<td style="text-align: right;">' + line.price_per_kg.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>';
        html += '<td style="text-align: right; font-weight:600;">' + line.total.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</td>';
        html += '<td style="text-align: center;"><button type="button" class="btn-sm btn-outline text-danger remove-line-btn" data-index="' + idx + '">&times;</button></td>';
        html += '</tr>';
      });
      tbody.innerHTML = html;

      // Attach remove triggers
      tbody.querySelectorAll('.remove-line-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var idx = parseInt(btn.getAttribute('data-index'), 10);
          addedLines.splice(idx, 1);
          renderLinesTable();
        });
      });

      updatePaymentDetails();
    }

    function updatePaymentDetails() {
      var total = 0;
      addedLines.forEach(function(line) {
        total += line.total;
      });

      var currencySymbol = '$';
      var activeCurrency = currencySelect.value;
      var curOption = currencySelect.options[currencySelect.selectedIndex];
      if (curOption) {
        currencySymbol = curOption.getAttribute('data-symbol') || '$';
      }

      document.getElementById('displayOrderTotal').textContent = currencySymbol + ' ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    currencySelect.addEventListener('change', function() {
      // If RWF selected, exchange rate input should show appropriate placeholder (e.g. 1450.00), if USD set to 1.00
      var currency = currencySelect.value;
      if (currency === 'USD') {
        exchangeRateInput.value = '1.00';
      } else {
        exchangeRateInput.value = '1450.00'; // Default guess, user can modify
      }
      updatePaymentDetails();
    });

    amountPaidInput.addEventListener('input', function() {
      var val = parseFloat(amountPaidInput.value);
      if (val > 0) {
        accountSelect.required = true;
        accountSelect.parentElement.querySelector('label').textContent = 'Cash/Bank Account *';
      } else {
        accountSelect.required = false;
        accountSelect.parentElement.querySelector('label').textContent = 'Cash/Bank Account';
      }
    });

    // Step Validation
    function validateStep(step) {
      if (step === 1) {
        var mode = document.querySelector('input[name="customer_mode"]:checked').value;
        if (mode === 'select') {
          var select = document.getElementById('customerId');
          if (!select.value) {
            showAlert('danger', 'Please select a customer before continuing.', 'formAlertPlaceholder');
            return false;
          }
        } else {
          var name = document.getElementById('custName');
          if (!name.value.trim()) {
            showAlert('danger', 'Please enter a name for the new customer.', 'formAlertPlaceholder');
            return false;
          }
        }
      } else if (step === 2) {
        var saleDate = document.getElementById('saleDate').value;
        if (!saleDate) {
          showAlert('danger', 'Please select a sale date.', 'formAlertPlaceholder');
          return false;
        }
        if (addedLines.length === 0) {
          showAlert('danger', 'Please add at least one product line item to the sale.', 'formAlertPlaceholder');
          return false;
        }
      } else if (step === 3) {
        var amountPaid = parseFloat(amountPaidInput.value);
        if (amountPaid > 0 && !accountSelect.value) {
          showAlert('danger', 'Please select a Cash/Bank Account for recording the payment amount.', 'formAlertPlaceholder');
          return false;
        }
      }
      // Clear alert placeholder
      var placeholder = document.getElementById('formAlertPlaceholder');
      if (placeholder) placeholder.innerHTML = '';
      return true;
    }

    // Setup Wizard UI State
    function updateWizardState() {
      // Progress bar percentage
      var pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
      progressBar.style.width = pct + '%';

      // Update Step Indicators
      wizardSteps.forEach(function(el, idx) {
        var stepNum = idx + 1;
        el.classList.remove('active', 'completed');
        if (stepNum === currentStep) {
          el.classList.add('active');
        } else if (stepNum < currentStep) {
          el.classList.add('completed');
        }
      });

      // Update Step Contents
      stepContents.forEach(function(el) {
        el.classList.remove('active');
      });
      document.getElementById('step' + currentStep).classList.add('active');

      // Update Buttons
      if (currentStep === 1) {
        prevBtn.style.display = 'none';
      } else {
        prevBtn.style.display = 'block';
      }

      if (currentStep === totalSteps) {
        nextBtn.textContent = 'Save Sale Order';
        nextBtn.classList.remove('btn-primary');
        nextBtn.classList.add('btn-success', 'btn-save');
        populateSummary();
      } else {
        nextBtn.textContent = 'Next: ' + getStepNextLabel(currentStep);
        nextBtn.classList.remove('btn-success', 'btn-save');
        nextBtn.classList.add('btn-primary');
      }
    }

    function getStepNextLabel(step) {
      if (step === 1) return 'Product Lines';
      if (step === 2) return 'Payment Receipt';
      if (step === 3) return 'Review & Save';
      return '';
    }

    function populateSummary() {
      // Customer
      var mode = document.querySelector('input[name="customer_mode"]:checked').value;
      var custName = '';
      if (mode === 'select') {
        custName = document.getElementById('customerId').options[document.getElementById('customerId').selectedIndex].text;
      } else {
        custName = document.getElementById('custName').value + ' [New]';
      }
      document.getElementById('sumCustomer').textContent = custName;

      // Sale details
      var saleNo = document.getElementById('saleNo').value;
      document.getElementById('sumSaleNo').textContent = saleNo ? saleNo : '(Auto-Generated)';
      document.getElementById('sumSaleDate').textContent = document.getElementById('saleDate').value;
      document.getElementById('sumTotalItems').textContent = addedLines.length + ' item line(s)';

      // Financials
      var currencySymbol = '$';
      var activeCurrency = currencySelect.value;
      var curOption = currencySelect.options[currencySelect.selectedIndex];
      if (curOption) {
        currencySymbol = curOption.getAttribute('data-symbol') || '$';
      }

      var total = 0;
      addedLines.forEach(function(line) {
        total += line.total;
      });

      var amountPaid = parseFloat(amountPaidInput.value) || 0;
      var balance = total - amountPaid;

      document.getElementById('sumTotalVal').textContent = currencySymbol + ' ' + total.toLocaleString(undefined, { minimumFractionDigits: 2 });
      document.getElementById('sumAmountPaid').textContent = currencySymbol + ' ' + amountPaid.toLocaleString(undefined, { minimumFractionDigits: 2 });
      document.getElementById('sumBalance').textContent = currencySymbol + ' ' + balance.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }

    // Step Nav Listeners
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        if (!validateStep(currentStep)) return;

        if (currentStep < totalSteps) {
          currentStep++;
          updateWizardState();
        } else {
          // Submit form
          submitSalesForm();
        }
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function() {
        if (currentStep > 1) {
          currentStep--;
          updateWizardState();
        }
      });
    }

    // AJAX form submit
    function submitSalesForm() {
      nextBtn.disabled = true;
      nextBtn.textContent = 'Saving...';

      var formData = new FormData(salesForm);
      // Append lines array as JSON
      formData.append('items', JSON.stringify(addedLines));

      fetch('sales_api.php?action=create', {
        method: 'POST',
        body: formData
      })
        .then(function(res) { return res.json(); })
        .then(function(res) {
          if (res.success) {
            alert('Sales order created successfully!');
            window.location.href = 'index.php';
          } else {
            showAlert('danger', 'Failed to save sales order: ' + res.message, 'formAlertPlaceholder');
            nextBtn.disabled = false;
            nextBtn.textContent = 'Save Sale Order';
          }
        })
        .catch(function(err) {
          console.error(err);
          showAlert('danger', 'An error occurred while saving the sale order.', 'formAlertPlaceholder');
          nextBtn.disabled = false;
          nextBtn.textContent = 'Save Sale Order';
        });
    }

    // Initial setup
    updateWizardState();
    renderLinesTable();
  }
})();
