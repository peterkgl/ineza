document.addEventListener("DOMContentLoaded", function () {
  var purchasForm = document.getElementById("purchasForm");
  var formAlertPlaceholder = document.getElementById("formAlertPlaceholder");
  var purchaseIdInput = document.getElementById("purchaseIdInput");
  var tokenInput = document.getElementById("purchasToken");
  
  // Step Navigation Buttons
  var prevBtn = document.getElementById("prevBtn");
  var nextBtn = document.getElementById("nextBtn");
  
  var currentStep = 1;
  var totalSteps = 4;
  
  // Step 2 & 3 Dynamic Elements Data
  var productSelect = document.getElementById("productId");
  var lotSelect = document.getElementById("lotId");
  var uomSelect = document.getElementById("uomId");
  var qtyInput = document.getElementById("quantityKg");
  var elementsListContainer = document.getElementById("elementsListContainer");
  var gradesInputContainer = document.getElementById("gradesInputContainer");
  
  var productElements = []; // Store fetched elements for the selected product
  var selectedElements = {}; // elementId -> {val, notes}
  var primaryElementId = null;
  
  // Financial Inputs
  var exRateInput = document.getElementById("exchangeRate");
  var priceKgUsd = document.getElementById("pricePerKgUsd");
  var priceKgRwf = document.getElementById("pricePerKgRwf");
  var valUsd = document.getElementById("purchaseValueUsd");
  var valRwf = document.getElementById("purchaseValueRwf");
  var chargesKg = document.getElementById("chargesPerKg");
  var netPaidUsd = document.getElementById("netPaidSupplierUsd");
  var rraTax = document.getElementById("taxRra");
  var rmaTax = document.getElementById("taxRma");
  var inkoTax = document.getElementById("taxInkomane");
  var prodCharges = document.getElementById("productionCharges");
  var lmePriceInput = document.getElementById("lmePrice");
  var tcChargesInput = document.getElementById("tcCharges");
  var pricingMethodRadios = document.getElementsByName("pricing_method");

  // New Custom Currency & Exchange Rate Reference DOM elements
  var purchaseCurrencySelect = document.getElementById("purchaseCurrencyId");
  var purchaseCurrencyValueInput = document.getElementById("purchaseCurrencyValue");
  var exchangeCurrencySelect = document.getElementById("exchangeCurrencyId");
  var exchangeRateValueInput = document.getElementById("exchangeRateValue");
  var purchaseAmountInCurrencyInput = document.getElementById("purchaseAmountInCurrency");
  var amountInCurrencyLabel = document.getElementById("amountInCurrencyLabel");
  var equivalentPriceUsdDisplay = document.getElementById("equivalentPriceUsdDisplay");
  var equivalentPriceRwfDisplay = document.getElementById("equivalentPriceRwfDisplay");

  function getPricingMethod() {
    var selected = document.querySelector('input[name="pricing_method"]:checked');
    return selected ? selected.value : 'lme';
  }

  function updateCurrencyUI() {
    if (!purchaseCurrencySelect || !amountInCurrencyLabel) return;
    var code = purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex].text.split(' - ')[0];
    amountInCurrencyLabel.textContent = "Price per kg in Selected Currency (" + code + ") *";
  }

  function updatePricingMethodUI() {
    var method = getPricingMethod();
    if (method === 'manual') {
      priceKgUsd.removeAttribute("readonly");
      priceKgRwf.removeAttribute("readonly");
      if (purchaseAmountInCurrencyInput) purchaseAmountInCurrencyInput.removeAttribute("readonly");
      lmePriceInput.setAttribute("disabled", "true");
      tcChargesInput.setAttribute("disabled", "true");
    } else {
      priceKgUsd.setAttribute("readonly", "true");
      priceKgRwf.setAttribute("readonly", "true");
      // Price field stays editable in LME mode for manual override
      if (purchaseAmountInCurrencyInput) purchaseAmountInCurrencyInput.removeAttribute("readonly");
      lmePriceInput.removeAttribute("disabled");
      tcChargesInput.removeAttribute("disabled");
    }
  }

  function handlePricingMethodChange() {
    updatePricingMethodUI();
    var method = getPricingMethod();
    if (method === 'lme') {
      [rraTax, rmaTax, inkoTax].forEach(function(input) {
        if (input) {
          input.removeAttribute("data-overridden");
        }
      });
      // Clear price override when switching back to LME so formula takes over
      if (purchaseAmountInCurrencyInput) {
        purchaseAmountInCurrencyInput.removeAttribute("data-overridden");
      }
    }
    calculateTotals();
  }

  // Inline Validation Helpers
  function showFieldError(input, message) {
    if (!input) return;
    input.classList.add("is-invalid");
    
    var parent = input.closest(".form-group");
    if (!parent) {
      var p = input.parentElement;
      if (p && p.parentElement && p.parentElement.style.flexDirection === 'column') {
        parent = p.parentElement;
      } else {
        parent = p || input;
      }
    }
    
    var errorEl = parent.querySelector(".field-error-msg");
    if (!errorEl) {
      errorEl = document.createElement("div");
      errorEl.className = "field-error-msg";
      parent.appendChild(errorEl);
    }
    errorEl.textContent = message;
  }

  function clearFieldError(input) {
    if (!input) return;
    input.classList.remove("is-invalid");
    
    var parent = input.closest(".form-group");
    if (!parent) {
      var p = input.parentElement;
      if (p && p.parentElement && p.parentElement.style.flexDirection === 'column') {
        parent = p.parentElement;
      } else {
        parent = p || input;
      }
    }
    
    var errorEl = parent.querySelector(".field-error-msg");
    if (errorEl) {
      errorEl.remove();
    }
  }

  function showBoxError(boxElement, message) {
    if (!boxElement) return;
    boxElement.classList.add("is-invalid");
    var parent = boxElement.parentElement;
    var errorEl = parent.querySelector(".field-error-msg");
    if (!errorEl) {
      errorEl = document.createElement("div");
      errorEl.className = "field-error-msg";
      parent.appendChild(errorEl);
    }
    errorEl.textContent = message;
  }
  
  function clearBoxError(boxElement) {
    if (!boxElement) return;
    boxElement.classList.remove("is-invalid");
    var parent = boxElement.parentElement;
    var errorEl = parent.querySelector(".field-error-msg");
    if (errorEl) {
      errorEl.remove();
    }
  }

  // Multi-step navigation
  function setStep(stepNum) {
    currentStep = stepNum;
    
    // Hide all step content sections
    document.querySelectorAll(".wizard-step-content").forEach(function(section) {
      section.classList.remove("active");
    });
    
    // Show active step section
    document.getElementById("step" + stepNum).classList.add("active");
    
    // Update progress nodes
    document.querySelectorAll(".wizard-step").forEach(function(node, idx) {
      var nodeStep = idx + 1;
      node.classList.remove("active", "completed");
      if (nodeStep < stepNum) {
        node.classList.add("completed");
      } else if (nodeStep === stepNum) {
        node.classList.add("active");
      }
    });

    // Update connecting progress bar width
    var progressPercent = ((stepNum - 1) / (totalSteps - 1)) * 100;
    document.querySelector(".wizard-progress-bar").style.width = progressPercent + "%";
    
    // Update navigation buttons
    if (stepNum === 1) {
      prevBtn.style.display = "none";
      nextBtn.textContent = "Next: Product Selection";
    } else if (stepNum === 2) {
      prevBtn.style.display = "inline-block";
      nextBtn.textContent = "Next: Element Grades";
    } else if (stepNum === 3) {
      prevBtn.style.display = "inline-block";
      nextBtn.textContent = "Next: Pricing & Save";
    } else if (stepNum === 4) {
      prevBtn.style.display = "inline-block";
      nextBtn.textContent = "Save Purchase";
      
      // Calculate/show financial summary on final step
      calculateTotals();
    }
  }

  // Validate step fields before going next
  function validateStep(stepNum) {
    var isValid = true;
    
    if (stepNum === 1) {
      var purchaseDateInput = document.getElementById("purchaseDate");
      var supplierIdInput = document.getElementById("supplierId");
      var warehouseIdInput = document.getElementById("warehouseId");
      var lotIdInput = document.getElementById("lotId");
      
      if (!purchaseDateInput.value) {
        showFieldError(purchaseDateInput, "Purchase Date is required.");
        isValid = false;
      } else {
        clearFieldError(purchaseDateInput);
      }
      
      if (!supplierIdInput.value) {
        showFieldError(supplierIdInput, "Supplier is required.");
        isValid = false;
      } else {
        clearFieldError(supplierIdInput);
      }
      
      if (!warehouseIdInput.value) {
        showFieldError(warehouseIdInput, "Warehouse is required.");
        isValid = false;
      } else {
        clearFieldError(warehouseIdInput);
      }
      
      if (!lotIdInput.value) {
        showFieldError(lotIdInput, "Lot is required.");
        isValid = false;
      } else {
        clearFieldError(lotIdInput);
      }
      
    } else if (stepNum === 2) {
      var productIdInput = productSelect;
      var qtyInputBox = qtyInput;
      
      if (!productIdInput.value) {
        showFieldError(productIdInput, "Product selection is required.");
        isValid = false;
      } else {
        clearFieldError(productIdInput);
      }
      
      var qty = parseFloat(qtyInputBox.value);
      if (isNaN(qty) || qty <= 0) {
        showFieldError(qtyInputBox, "Quantity must be a valid positive number.");
        isValid = false;
      } else {
        clearFieldError(qtyInputBox);
      }
      
      // Verify at least one element is checked
      var checkedElements = getCheckedElements();
      if (checkedElements.length === 0) {
        showBoxError(elementsListContainer.parentElement, "At least one chemical element must be checked.");
        isValid = false;
      } else {
        clearBoxError(elementsListContainer.parentElement);
        
        // Verify primary element is selected
        var primarySelected = document.querySelector('input[name="primary_element"]:checked');
        if (!primarySelected) {
          showBoxError(elementsListContainer.parentElement, "One element must be selected as primary.");
          isValid = false;
        } else {
          primaryElementId = parseInt(primarySelected.value, 10);
        }
      }
      
    } else if (stepNum === 3) {
      var gradeInputs = gradesInputContainer.querySelectorAll(".grade-input-field");
      
      gradeInputs.forEach(function(input) {
        var val = parseFloat(input.value);
        if (isNaN(val) || val < 0 || val > 100) {
          isValid = false;
          showFieldError(input, "Grade must be between 0% and 100%.");
        } else {
          clearFieldError(input);
        }
      });
    }
    
    return isValid;
  }

  function getCheckedElements() {
    var checked = [];
    var checkboxes = elementsListContainer.querySelectorAll('input[type="checkbox"]:checked');
    checkboxes.forEach(function(cb) {
      checked.push(parseInt(cb.value, 10));
    });
    return checked;
  }

  // Dynamic Product selection filtering based on selected Lot
  function filterProductsByLot(isInit) {
    if (!lotSelect || !productSelect) return;
    
    var selectedOpt = lotSelect.options[lotSelect.selectedIndex];
    var lotProductId = selectedOpt ? selectedOpt.getAttribute("data-product-id") : "";
    
    var productOptions = productSelect.options;
    var matchedOption = null;
    
    for (var i = 0; i < productOptions.length; i++) {
      var opt = productOptions[i];
      var prodId = opt.value;
      
      if (!prodId) {
        opt.style.display = lotProductId ? "none" : "block";
        continue;
      }
      
      if (lotProductId) {
        if (prodId == lotProductId) {
          opt.style.display = "block";
          matchedOption = opt;
        } else {
          opt.style.display = "none";
        }
      } else {
        opt.style.display = "block";
      }
    }
    
    // Automatically select matching product, trigger change if not during init
    if (matchedOption) {
      productSelect.value = matchedOption.value;
      if (!isInit) {
        productSelect.dispatchEvent(new Event("change"));
      }
    } else if (!isInit) {
      productSelect.value = "";
      productSelect.dispatchEvent(new Event("change"));
    }
  }

  if (lotSelect) {
    lotSelect.addEventListener("change", function() {
      filterProductsByLot(false);
    });
  }

  // Populate dynamic element checkboxes
  function fetchProductElements(productId, callback) {
    elementsListContainer.innerHTML = '<div style="color:var(--text3); font-size:12px; padding:10px;">Loading elements...</div>';
    
    fetch("purchas_api.php?action=get_product_elements&product_id=" + productId)
      .then(function(res) { return res.json(); })
      .then(function(result) {
        if (result.success) {
          productElements = result.data;
          
          // Pre-check elements in default composition if selectedElements is empty (new purchase)
          if (Object.keys(selectedElements).length === 0) {
            productElements.forEach(function(el) {
              if (el.is_default_composition === 1) {
                selectedElements[el.id] = true;
              }
            });
          }
          
          renderProductElements();
          if (callback) callback();
        } else {
          elementsListContainer.innerHTML = '<div style="color:var(--red); font-size:12px; padding:10px;">Failed to load elements.</div>';
        }
      })
      .catch(function(err) {
        console.error("Error fetching product elements:", err);
        elementsListContainer.innerHTML = '<div style="color:var(--red); font-size:12px; padding:10px;">Error loading elements.</div>';
      });
  }

  function renderProductElements() {
    if (productElements.length === 0) {
      elementsListContainer.innerHTML = '<div style="color:var(--text3); font-size:12px; padding:10px;">No elements configured.</div>';
      return;
    }

    var html = "";
    productElements.forEach(function(el) {
      var isChecked = selectedElements[el.id] ? "checked" : "";
      var isPrimary = "";
      if (primaryElementId !== null) {
        isPrimary = (el.id === primaryElementId) ? "checked" : "";
      } else if (el.is_primary_grade === 1) {
        isPrimary = "checked";
        primaryElementId = el.id;
      }

      var checkedClass = isChecked ? "element-item-checked" : "";
      
      html += '<div class="element-item ' + checkedClass + '" id="el-item-' + el.id + '">' +
        '<div><input type="checkbox" class="el-checkbox" value="' + el.id + '" ' + isChecked + '></div>' +
        '<div class="element-name-wrap">' +
        '<span class="element-code-txt">' + escapeHtml(el.element_code) + '</span>' +
        '<span class="element-desc-txt">' + escapeHtml(el.element_name) + ' (' + escapeHtml(el.symbol || '') + ')</span>' +
        '</div>' +
        '<div style="text-align: right;">' +
        '<label class="element-primary-label">' +
        '<input type="radio" name="primary_element" class="el-primary-radio" value="' + el.id + '" ' + isPrimary + ' ' + (isChecked ? '' : 'disabled') + '>' +
        ' Primary' +
        '</label>' +
        '</div>' +
        '</div>';
    });

    elementsListContainer.innerHTML = html;
    attachElementEventListeners();
  }

  function attachElementEventListeners() {
    var checkboxes = elementsListContainer.querySelectorAll(".el-checkbox");
    
    checkboxes.forEach(function(cb) {
      cb.addEventListener("change", function() {
        var id = parseInt(cb.value, 10);
        var radio = cb.closest(".element-item").querySelector(".el-primary-radio");
        var itemCard = document.getElementById("el-item-" + id);
        
        if (cb.checked) {
          selectedElements[id] = true;
          if (radio) radio.removeAttribute("disabled");
          if (itemCard) itemCard.classList.add("element-item-checked");
          
          // Auto select primary if none selected
          var currentPrimary = document.querySelector('input[name="primary_element"]:checked');
          if (!currentPrimary) {
            if (radio) radio.checked = true;
            primaryElementId = id;
          }
        } else {
          delete selectedElements[id];
          if (radio) {
            radio.setAttribute("disabled", "true");
            radio.checked = false;
          }
          if (itemCard) itemCard.classList.remove("element-item-checked");
          
          if (primaryElementId === id) {
            primaryElementId = null;
            // Check if there are other checked elements to make primary
            var remaining = Object.keys(selectedElements);
            if (remaining.length > 0) {
              var nextId = remaining[0];
              var nextRadio = elementsListContainer.querySelector('.el-primary-radio[value="' + nextId + '"]');
              if (nextRadio) {
                nextRadio.checked = true;
                primaryElementId = parseInt(nextId, 10);
              }
            }
          }
        }
        // Run validation check dynamically on change
        if (elementsListContainer.parentElement.classList.contains('is-invalid')) {
          validateStep(2);
        }
      });
    });
  }

  // Step 3: Render Grade Input Cards
  function renderGradeInputs() {
    var checkedIds = getCheckedElements();
    gradesInputContainer.innerHTML = "";
    
    checkedIds.forEach(function(id) {
      var el = productElements.find(function(x) { return x.id === id; });
      if (!el) return;
      
      var isPrimary = (id === primaryElementId);
      var badgeClass = isPrimary ? "badge-orange" : "badge-gray";
      var badgeLabel = isPrimary ? "Primary" : "Secondary";
      
      var prevVal = "";
      var prevNote = "";
      var selData = selectedElements[id];
      if (selData && typeof selData === 'object' && selData.val !== undefined) {
        prevVal = selData.val;
        prevNote = selData.notes || "";
      } else if (typeof selData === 'number') {
        prevVal = selData;
      }
      
      var card = document.createElement("div");
      card.className = "grade-input-card" + (isPrimary ? " primary-grade" : "");
      card.innerHTML = '<div class="grade-label">' +
        '<strong>' + escapeHtml(el.element_code) + '</strong>' +
        '<span class="grade-badge ' + badgeClass + '">' + badgeLabel + '</span>' +
        '</div>' +
        '<div class="form-group" style="margin: 0;">' +
        '<input type="text" class="form-control" placeholder="Notes (optional)" name="el_notes_' + id + '" value="' + escapeHtml(String(prevNote)) + '" style="margin:0;">' +
        '</div>' +
        '<div style="display: flex; flex-direction: column; gap: 4px; width: 100%;">' +
        '<div style="display: flex; align-items: center; gap: 8px; width: 100%;">' +
        '<input type="number" class="form-control grade-input-field" placeholder="Grade %" name="el_grade_' + id + '" value="' + prevVal + '" step="any" min="0" max="100" style="margin:0; text-align:right;" required>' +
        '<span style="font-weight:600; color:var(--text2);">%</span>' +
        '</div>' +
        '</div>';
        
      gradesInputContainer.appendChild(card);
    });
    bindRealTimeValidation();
  }

  // Handle Product Dropdown selection change
  if (productSelect) {
    productSelect.addEventListener("change", function () {
      var productId = productSelect.value;
      selectedElements = {};
      primaryElementId = null;
      if (productId) {
        fetchProductElements(productId);
        
        // Auto default UOM matching the product's UOM if available
        var selectedOpt = productSelect.options[productSelect.selectedIndex];
        var uomId = selectedOpt.getAttribute("data-uom-id");
        if (uomId && uomSelect) {
          uomSelect.value = uomId;
        }
      } else {
        elementsListContainer.innerHTML = '<div style="color:var(--text3); font-size:12px; padding:10px;">Select a product first.</div>';
      }
    });
  }

  // AJAX calculations for pricing using server-side PHP formulas
  function calculateTotals() {
    var method = getPricingMethod();
    var qty = parseFloat(qtyInput.value) || 0.0;
    
    // Calculate legacy exchange rate from user inputs
    var pVal = purchaseCurrencyValueInput ? parseFloat(purchaseCurrencyValueInput.value) || 1.0 : 1.0;
    var eRateVal = exchangeRateValueInput ? parseFloat(exchangeRateValueInput.value) || 0.0 : 0.0;
    var pCode = purchaseCurrencySelect ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex].text.split(' - ')[0] : 'RWF';
    var eCode = exchangeCurrencySelect ? exchangeCurrencySelect.options[exchangeCurrencySelect.selectedIndex].text.split(' - ')[0] : 'USD';
    
    var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
    var exRate = dbRate; // Legacy exchange rate RWF/USD
    if (pCode === 'RWF' && eCode === 'USD') {
      exRate = eRateVal > 0 ? (pVal / eRateVal) : dbRate;
    } else if (pCode === 'USD' && eCode === 'RWF') {
      exRate = pVal > 0 ? (eRateVal / pVal) : dbRate;
    } else {
      exRate = 1.0;
    }
    
    if (exRateInput) {
      exRateInput.value = exRate.toFixed(6);
    }
    
    var productId = productSelect ? productSelect.value : "";
    var lme = parseFloat(lmePriceInput.value) || 0.0;
    var tc = parseFloat(tcChargesInput.value) || 0.0;
    
    var prodChargesPerKgInput = document.getElementById("productionChargesPerKg");
    var prodChargesRate = prodChargesPerKgInput ? parseFloat(prodChargesPerKgInput.value) || 0.0 : 0.0;
    
    var pricePerTaUnitInput = document.getElementById("pricePerTaUnit");
    var taUnit = pricePerTaUnitInput ? parseFloat(pricePerTaUnitInput.value) || 0.0 : 0.0;
    
    var selectedCurrencyId = purchaseCurrencySelect ? purchaseCurrencySelect.value : "2";
    var currencyCode = pCode;
    
    var amountInCurr = parseFloat(purchaseAmountInCurrencyInput.value) || 0.0;
    
    if (method === 'manual') {
      var priceUsd = 0.0;
      var priceRwf = 0.0;
      
      if (currencyCode === 'RWF') {
        priceRwf = amountInCurr;
        priceUsd = exRate > 0 ? priceRwf / exRate : 0.0;
      } else {
        priceUsd = amountInCurr;
        priceRwf = priceUsd * exRate;
      }
      
      priceKgUsd.value = priceUsd > 0 ? parseFloat(priceUsd.toFixed(4)) : "";
      priceKgRwf.value = priceRwf > 0 ? parseFloat(priceRwf.toFixed(4)) : "";
      
      var purchaseValueUsd = priceUsd * qty;
      var purchaseValueRwf = priceRwf * qty;
      
      valUsd.value = purchaseValueUsd.toFixed(2);
      valRwf.value = purchaseValueRwf.toFixed(2);
      
      if (equivalentPriceUsdDisplay) {
        equivalentPriceUsdDisplay.textContent = "$" + priceUsd.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
      }
      if (equivalentPriceRwfDisplay) {
        equivalentPriceRwfDisplay.textContent = priceRwf.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 }) + " Frw";
      }
      
      var taxRraVal = parseFloat(rraTax.value) || 0.0;
      var taxRmaVal = parseFloat(rmaTax.value) || 0.0;
      var taxInkoVal = parseFloat(inkoTax.value) || 0.0;
      
      var prodChargesVal = qty * prodChargesRate;
      prodCharges.value = prodChargesVal.toFixed(4);
      
      var totalTaxes = taxRraVal + taxRmaVal + taxInkoVal + prodChargesVal;
      var netPaidUsdVal = purchaseValueUsd - totalTaxes;
      
      netPaidUsd.value = netPaidUsdVal.toFixed(4);
      
      var previewHtml = '<div class="summary-row"><span>Quantity:</span><span class="summary-val-usd">' + qty.toLocaleString(undefined, { maximumFractionDigits: 4 }) + ' kg</span></div>' +
        '<div class="summary-row"><span>Unit Price:</span><span class="summary-val-usd">$' + priceUsd.toFixed(4) + ' <span class="summary-val-rwf">(' + priceRwf.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' RWF)</span></span></div>' +
        '<div class="summary-row"><span>Purchase Value:</span><span class="summary-val-usd">$' + purchaseValueUsd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="summary-val-rwf">(' + purchaseValueRwf.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RWF)</span></span></div>' +
        '<div class="summary-row"><span>Total Deductions/Taxes/Charges:</span><span class="summary-val-usd" style="color:var(--red)">-$' + totalTaxes.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + '</span></div>' +
        '<div class="summary-row"><span>Net Payable Amount:</span><span class="summary-val-usd" style="color:var(--green)">$' + netPaidUsdVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span></div>';
        
      document.getElementById("pricingSummaryPreview").innerHTML = previewHtml;
      
    } else {
      var gradePct = 0.0;
      if (primaryElementId) {
        var primaryGradeInput = gradesInputContainer ? gradesInputContainer.querySelector('input[name="el_grade_' + primaryElementId + '"]') : null;
        if (primaryGradeInput) {
          gradePct = parseFloat(primaryGradeInput.value) || 0.0;
        }
      }

      // If user has manually overridden the price, pass it to the API
      var manualPriceUsd = 0.0;
      var priceIsOverridden = purchaseAmountInCurrencyInput && purchaseAmountInCurrencyInput.hasAttribute("data-overridden");
      if (priceIsOverridden && amountInCurr > 0) {
        if (currencyCode === 'RWF') {
          manualPriceUsd = exRate > 0 ? amountInCurr / exRate : 0.0;
        } else {
          manualPriceUsd = amountInCurr;
        }
      }

      var url = "purchas_api.php?action=calculate" +
        "&product_id=" + encodeURIComponent(productId) +
        "&quantity_kg=" + encodeURIComponent(qty) +
        "&exchange_rate=" + encodeURIComponent(exRate) +
        "&lme_price=" + encodeURIComponent(lme) +
        "&tc_charges=" + encodeURIComponent(tc) +
        "&production_charges_per_kg=" + encodeURIComponent(prodChargesRate) +
        "&price_per_ta_unit=" + encodeURIComponent(taUnit) +
        "&grade_pct=" + encodeURIComponent(gradePct) +
        "&price_per_kg_usd=" + encodeURIComponent(manualPriceUsd);

      fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(result) {
          if (result.success) {
            var metrics = result.data;
            
            var usdVal = parseFloat(metrics.price_per_kg_usd) || 0.0;
            var rwfVal = parseFloat(metrics.price_per_kg_rwf) || 0.0;
            
            priceKgUsd.value = usdVal > 0 ? usdVal : "";
            priceKgRwf.value = rwfVal > 0 ? rwfVal : "";
            
            // Only update the price field if the user hasn't manually overridden it
            if (!priceIsOverridden) {
              if (currencyCode === 'RWF') {
                purchaseAmountInCurrencyInput.value = rwfVal > 0 ? parseFloat(rwfVal.toFixed(4)) : "";
              } else {
                purchaseAmountInCurrencyInput.value = usdVal > 0 ? parseFloat(usdVal.toFixed(4)) : "";
              }
            }
            
            if (equivalentPriceUsdDisplay) {
              equivalentPriceUsdDisplay.textContent = "$" + usdVal.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
            }
            if (equivalentPriceRwfDisplay) {
              equivalentPriceRwfDisplay.textContent = rwfVal.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 }) + " Frw";
            }
            
            var purchaseValueUsd = parseFloat(metrics.purchase_value_usd);
            var purchaseValueRwf = parseFloat(metrics.purchase_value_rwf);
            
            valUsd.value = purchaseValueUsd.toFixed(2);
            valRwf.value = purchaseValueRwf.toFixed(2);
            
            var taxRraVal = rraTax.hasAttribute("data-overridden") ? parseFloat(rraTax.value) || 0.0 : parseFloat(metrics.tax_rra);
            var taxRmaVal = rmaTax.hasAttribute("data-overridden") ? parseFloat(rmaTax.value) || 0.0 : parseFloat(metrics.tax_rma);
            var taxInkoVal = inkoTax.hasAttribute("data-overridden") ? parseFloat(inkoTax.value) || 0.0 : parseFloat(metrics.tax_inkomane);
            
            if (!rraTax.hasAttribute("data-overridden")) {
              rraTax.value = taxRraVal.toFixed(4);
            }
            if (!rmaTax.hasAttribute("data-overridden")) {
              rmaTax.value = taxRmaVal.toFixed(4);
            }
            if (!inkoTax.hasAttribute("data-overridden")) {
              inkoTax.value = taxInkoVal.toFixed(4);
            }
            
            var prodChargesVal = parseFloat(metrics.production_charges);
            prodCharges.value = prodChargesVal.toFixed(4);
            
            var totalTaxes = taxRraVal + taxRmaVal + taxInkoVal + prodChargesVal;
            var netPaidUsdVal = purchaseValueUsd - totalTaxes;
            
            netPaidUsd.value = netPaidUsdVal.toFixed(4);

            var previewHtml = '<div class="summary-row"><span>Quantity:</span><span class="summary-val-usd">' + qty.toLocaleString(undefined, { maximumFractionDigits: 4 }) + ' kg</span></div>' +
              '<div class="summary-row"><span>Unit Price:</span><span class="summary-val-usd">$' + usdVal.toFixed(4) + ' <span class="summary-val-rwf">(' + rwfVal.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' RWF)</span></span></div>' +
              '<div class="summary-row"><span>Purchase Value:</span><span class="summary-val-usd">$' + purchaseValueUsd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="summary-val-rwf">(' + purchaseValueRwf.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RWF)</span></span></div>' +
              '<div class="summary-row"><span>Total Deductions/Taxes/Charges:</span><span class="summary-val-usd" style="color:var(--red)">-$' + totalTaxes.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + '</span></div>' +
              '<div class="summary-row"><span>Net Payable Amount:</span><span class="summary-val-usd" style="color:var(--green)">$' + netPaidUsdVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span></div>';
              
            document.getElementById("pricingSummaryPreview").innerHTML = previewHtml;
          }
        })
        .catch(function(err) {
          console.error("Error calculating pricing:", err);
        });
    }
  }

  // Attach calculation event listeners to all pricing/quantity/product-related inputs
  var prodChargesPerKgInput = document.getElementById("productionChargesPerKg");
  var pricePerTaUnitInput = document.getElementById("pricePerTaUnit");

  [qtyInput, purchaseCurrencyValueInput, exchangeRateValueInput, lmePriceInput, tcChargesInput, prodChargesPerKgInput, pricePerTaUnitInput].forEach(function(input) {
    if (input) {
      input.addEventListener("input", calculateTotals);
    }
  });

  // Separate listener for the price field to track manual override
  if (purchaseAmountInCurrencyInput) {
    purchaseAmountInCurrencyInput.addEventListener("input", function() {
      var method = getPricingMethod();
      if (method === 'lme') {
        purchaseAmountInCurrencyInput.setAttribute("data-overridden", "true");
      }
      calculateTotals();
    });
  }

  if (purchaseCurrencySelect) {
    purchaseCurrencySelect.addEventListener("change", function() {
      var selectedCode = purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex].text.split(' - ')[0];
      if (exchangeCurrencySelect) {
        // Toggle opposite
        for (var i = 0; i < exchangeCurrencySelect.options.length; i++) {
          var optCode = exchangeCurrencySelect.options[i].text.split(' - ')[0];
          if (optCode !== selectedCode) {
            exchangeCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      
      // Default rate presets
      var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
      if (selectedCode === 'RWF') {
        purchaseCurrencyValueInput.value = "1";
        exchangeRateValueInput.value = (1.0 / dbRate).toFixed(8);
      } else {
        purchaseCurrencyValueInput.value = "1";
        exchangeRateValueInput.value = dbRate.toFixed(4);
      }
      
      updateCurrencyUI();
      
      // Sync manual values on currency switch
      var method = getPricingMethod();
      if (method === 'manual') {
        var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
        var rate = parseFloat(exRateInput.value) || dbRate;
        var amount = parseFloat(purchaseAmountInCurrencyInput.value) || 0.0;
        
        if (selectedCode === 'RWF') {
          priceKgRwf.value = amount;
          priceKgUsd.value = rate > 0 ? parseFloat((amount / rate).toFixed(4)) : 0;
        } else {
          priceKgUsd.value = amount;
          priceKgRwf.value = parseFloat((amount * rate).toFixed(4));
        }
      }
      
      calculateTotals();
    });
  }

  if (exchangeCurrencySelect) {
    exchangeCurrencySelect.addEventListener("change", function() {
      var selectedCode = exchangeCurrencySelect.options[exchangeCurrencySelect.selectedIndex].text.split(' - ')[0];
      if (purchaseCurrencySelect) {
        // Toggle opposite
        for (var i = 0; i < purchaseCurrencySelect.options.length; i++) {
          var optCode = purchaseCurrencySelect.options[i].text.split(' - ')[0];
          if (optCode !== selectedCode) {
            purchaseCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      updateCurrencyUI();
      calculateTotals();
    });
  }

  if (pricingMethodRadios) {
    pricingMethodRadios.forEach(function(radio) {
      radio.addEventListener("change", handlePricingMethodChange);
    });
  }

  [rraTax, rmaTax, inkoTax].forEach(function(input) {
    if (input) {
      input.addEventListener("input", function() {
        if (input.value === "") {
          input.removeAttribute("data-overridden");
        } else {
          input.setAttribute("data-overridden", "true");
        }
        calculateTotals();
      });
    }
  });

  // Next/Prev Buttons
  nextBtn.addEventListener("click", function() {
    if (currentStep < totalSteps) {
      if (validateStep(currentStep)) {
        if (currentStep === 2) {
          // Prepare Step 3
          renderGradeInputs();
        } else if (currentStep === 3) {
          // Save grades state
          var checkedIds = getCheckedElements();
          checkedIds.forEach(function(id) {
            var gradeVal = parseFloat(gradesInputContainer.querySelector('input[name="el_grade_' + id + '"]').value);
            var noteVal = gradesInputContainer.querySelector('input[name="el_notes_' + id + '"]').value;
            selectedElements[id] = { val: gradeVal, notes: noteVal };
          });
        }
        setStep(currentStep + 1);
      }
    } else {
      // Submit the form
      submitPurchase();
    }
  });

  prevBtn.addEventListener("click", function() {
    if (currentStep > 1) {
      setStep(currentStep - 1);
    }
  });

  // Submit form request
  function submitPurchase() {
    var priceUsdInput = document.getElementById("pricePerKgUsd");
    var priceUsd = parseFloat(priceUsdInput.value);
    if (isNaN(priceUsd) || priceUsd <= 0) {
      showFieldError(priceUsdInput, "Please enter a valid positive price per kg.");
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
      return;
    } else {
      clearFieldError(priceUsdInput);
    }

    var id = purchaseIdInput.value;
    var actionUrl = "purchas_api.php?action=" + (id && parseInt(id) > 0 ? "update" : "create");
    
    var formData = new FormData(purchasForm);
    
    // Append the selected elements & grades manually
    var checkedIds = getCheckedElements();
    checkedIds.forEach(function(elId) {
      var gradeVal = gradesInputContainer.querySelector('input[name="el_grade_' + elId + '"]').value;
      var noteVal = gradesInputContainer.querySelector('input[name="el_notes_' + elId + '"]').value;
      
      formData.append("elements[" + elId + "]", gradeVal);
      formData.append("element_notes[" + elId + "]", noteVal);
    });
    formData.append("primary_element_id", primaryElementId);
    
    nextBtn.setAttribute("disabled", "true");
    nextBtn.textContent = "Processing...";
    
    fetch(actionUrl, {
      method: "POST",
      body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(result) {
      nextBtn.removeAttribute("disabled");
      nextBtn.textContent = id && parseInt(id) > 0 ? "Save Purchase" : "Save Purchase";
      
      if (result.success) {
        // Show local success feedback
        formAlertPlaceholder.innerHTML = '<div class="alert-msg success">' +
          '<svg class="alert-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>' +
          '<span>' + escapeHtml(result.message) + '</span>' +
          '</div>';
        setTimeout(function() {
          window.location.href = "index.php";
        }, 1200);
      } else {
        // Show error message
        formAlertPlaceholder.innerHTML = '<div class="alert-msg error">' +
          '<svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
          '<span>' + escapeHtml(result.message) + '</span>' +
          '</div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
      if (result.token) {
        updateToken(result.token);
      }
    })
    .catch(function(err) {
      nextBtn.removeAttribute("disabled");
      nextBtn.textContent = id && parseInt(id) > 0 ? "Save Purchase" : "Save Purchase";
      console.error("Error saving purchase:", err);
      formAlertPlaceholder.innerHTML = '<div class="alert-msg error">' +
        '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
        '<span>' + escapeHtml(err) + '</span>' +
        '</div>';
    });
  }

  function updateToken(token) {
    if (tokenInput) {
      tokenInput.value = token;
    }
  }

  // Helper functions
  function escapeHtml(text) {
    if (!text) return "";
    text = String(text);
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  // Real-Time clearing
  function bindRealTimeValidation() {
    purchasForm.querySelectorAll('input, select, textarea').forEach(function(input) {
      var eventType = (input.tagName === 'SELECT' || input.type === 'checkbox' || input.type === 'radio') ? 'change' : 'input';
      
      // Remove old listener first to avoid duplication
      input.removeEventListener(eventType, input._valHandler);
      
      input._valHandler = function() {
        if (input.classList.contains('is-invalid') || input.closest('.is-invalid')) {
          validateStep(currentStep);
        }
      };
      
      input.addEventListener(eventType, input._valHandler);
    });
  }

  // Initialization
  function initForm() {
    var p = window.editingPurchaseData;
    if (p) {
      // EDIT MODE
      // Step 1: General Details
      document.getElementById("purchaseNo").value = p.purchase_no;
      document.getElementById("purchaseDate").value = p.purchase_date;
      document.getElementById("deliveryDate").value = p.delivery_date;
      document.getElementById("deliveryNo").value = p.delivery_no || "";
      document.getElementById("inventoryCode").value = p.inventory_code || "";
      if (document.getElementById("negociant")) {
        document.getElementById("negociant").value = p.negociant || "";
      }
      if (document.getElementById("accountId")) {
        document.getElementById("accountId").value = p.account_id || "";
      }
      document.getElementById("supplierId").value = p.supplier_id;
      document.getElementById("warehouseId").value = p.warehouse_id;
      document.getElementById("lotId").value = p.lot_id;
      filterProductsByLot(true);
      
      // Step 2: Product & Elements
      productSelect.value = p.product_id;
      if (uomSelect) uomSelect.value = p.uom_id;
      qtyInput.value = p.quantity_kg;
      
      primaryElementId = p.primary_element_id;
      if (p.grades && p.grades.length > 0) {
        p.grades.forEach(function(g) {
          selectedElements[g.product_element_id] = { val: g.grade_pct, notes: g.notes || "" };
        });
      }
      
      fetchProductElements(p.product_id, function() {
        productElements.forEach(function(el) {
          var cb = elementsListContainer.querySelector('.el-checkbox[value="' + el.id + '"]');
          if (cb && selectedElements[el.id]) {
            cb.checked = true;
            var radio = cb.closest(".element-item").querySelector(".el-primary-radio");
            var itemCard = document.getElementById("el-item-" + el.id);
            if (radio) radio.removeAttribute("disabled");
            if (itemCard) itemCard.classList.add("element-item-checked");
            if (el.id === primaryElementId && radio) {
              radio.checked = true;
            }
          }
        });
        renderGradeInputs();
      });

      // Step 4: Pricing & Financials
      var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
      exRateInput.value = p.exchange_rate || dbRate;
      priceKgUsd.value = (p.price_per_kg_usd && parseFloat(p.price_per_kg_usd) !== 0) ? parseFloat(p.price_per_kg_usd) : "";
      priceKgRwf.value = (p.price_per_kg_rwf && parseFloat(p.price_per_kg_rwf) !== 0) ? parseFloat(p.price_per_kg_rwf) : "";
      valUsd.value = p.purchase_value_usd || "";
      valRwf.value = p.purchase_value_rwf || "";
      chargesKg.value = p.charges_per_kg || "";
      netPaidUsd.value = p.net_paid_supplier_usd || "";
      rraTax.value = p.tax_rra || "";
      rmaTax.value = p.tax_rma || "";
      inkoTax.value = p.tax_inkomane || "";
      if (document.getElementById("productionChargesPerKg")) {
        document.getElementById("productionChargesPerKg").value = p.production_charges_per_kg || "";
      }
      prodCharges.value = p.production_charges || "";
      lmePriceInput.value = p.lme_price || "";
      tcChargesInput.value = p.tc_charges || "";
      document.getElementById("pricePerTaUnit").value = p.price_per_ta_unit || "";
      
      if (purchaseCurrencySelect) {
        purchaseCurrencySelect.value = p.purchase_currency_id || "2"; // Default to USD (2)
      }
      updateCurrencyUI();
      
      var pCode = purchaseCurrencySelect ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex].text.split(' - ')[0] : 'USD';
      if (exchangeCurrencySelect) {
        for (var i = 0; i < exchangeCurrencySelect.options.length; i++) {
          var optCode = exchangeCurrencySelect.options[i].text.split(' - ')[0];
          if (optCode !== pCode) {
            exchangeCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      
      var eCode = exchangeCurrencySelect ? exchangeCurrencySelect.options[exchangeCurrencySelect.selectedIndex].text.split(' - ')[0] : '';
      
      if (p.exchange_rate) {
        var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
        var legacyRate = parseFloat(p.exchange_rate) || dbRate;
        if (pCode === 'RWF' && eCode === 'USD') {
          purchaseCurrencyValueInput.value = "1";
          exchangeRateValueInput.value = (1.0 / legacyRate).toFixed(8);
        } else if (pCode === 'USD' && eCode === 'RWF') {
          purchaseCurrencyValueInput.value = "1";
          exchangeRateValueInput.value = legacyRate.toFixed(4);
        } else {
          purchaseCurrencyValueInput.value = "1";
          exchangeRateValueInput.value = "1";
        }
      } else {
        var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
        if (pCode === 'RWF') {
          purchaseCurrencyValueInput.value = "1";
          exchangeRateValueInput.value = (1.0 / dbRate).toFixed(8);
        } else {
          purchaseCurrencyValueInput.value = "1";
          exchangeRateValueInput.value = dbRate.toFixed(4);
        }
      }

      if (pCode === 'RWF') {
        purchaseAmountInCurrencyInput.value = (p.price_per_kg_rwf && parseFloat(p.price_per_kg_rwf) !== 0) ? parseFloat(p.price_per_kg_rwf) : "";
      } else {
        purchaseAmountInCurrencyInput.value = (p.price_per_kg_usd && parseFloat(p.price_per_kg_usd) !== 0) ? parseFloat(p.price_per_kg_usd) : "";
      }
      
      var method = p.pricing_method || 'lme';
      var methodRadio = document.querySelector('input[name="pricing_method"][value="' + method + '"]');
      if (methodRadio) {
        methodRadio.checked = true;
      }
      updatePricingMethodUI();

      if (method === 'lme') {
        var qty = parseFloat(p.quantity_kg) || 0.0;
        var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
        var exRate = parseFloat(p.exchange_rate) || dbRate;
        var lme = parseFloat(p.lme_price) || 0.0;
        var tc = parseFloat(p.tc_charges) || 0.0;
        var prodChargesPerKg = parseFloat(p.production_charges_per_kg) || 0.0;
        var taUnit = parseFloat(p.price_per_ta_unit) || 0.0;
        var manualPrice = parseFloat(p.price_per_kg_usd) || 0.0;
        var gradePct = 0.0;
        if (p.primary_element_id && p.grades) {
          var pg = p.grades.find(function(g) { return g.product_element_id === p.primary_element_id; });
          if (pg) gradePct = parseFloat(pg.grade_pct) || 0.0;
        }

        var url = "purchas_api.php?action=calculate" +
          "&product_id=" + encodeURIComponent(p.product_id) +
          "&quantity_kg=" + encodeURIComponent(qty) +
          "&exchange_rate=" + encodeURIComponent(exRate) +
          "&lme_price=" + encodeURIComponent(lme) +
          "&tc_charges=" + encodeURIComponent(tc) +
          "&production_charges_per_kg=" + encodeURIComponent(prodChargesPerKg) +
          "&price_per_ta_unit=" + encodeURIComponent(taUnit) +
          "&grade_pct=" + encodeURIComponent(gradePct) +
          "&price_per_kg_usd=" + encodeURIComponent(manualPrice);

        fetch(url)
          .then(function(res) { return res.json(); })
          .then(function(result) {
            if (result.success) {
              var metrics = result.data;
              var tolerance = 0.0001;
              if (Math.abs((parseFloat(p.tax_rra) || 0.0) - (parseFloat(metrics.tax_rra) || 0.0)) > tolerance) {
                rraTax.setAttribute("data-overridden", "true");
              }
              if (Math.abs((parseFloat(p.tax_rma) || 0.0) - (parseFloat(metrics.tax_rma) || 0.0)) > tolerance) {
                rmaTax.setAttribute("data-overridden", "true");
              }
              if (Math.abs((parseFloat(p.tax_inkomane) || 0.0) - (parseFloat(metrics.tax_inkomane) || 0.0)) > tolerance) {
                inkoTax.setAttribute("data-overridden", "true");
              }
            }
          });
      }

      document.getElementById("status").value = p.status;
      document.getElementById("notes").value = p.notes || "";
    } else {
      // ADD MODE
      document.getElementById("purchaseDate").value = new Date().toISOString().split('T')[0];
      document.getElementById("deliveryDate").value = "";
      
      // Default to RWF for Purchase, USD for Exchange
      if (purchaseCurrencySelect) {
        for (var i = 0; i < purchaseCurrencySelect.options.length; i++) {
          if (purchaseCurrencySelect.options[i].text.split(' - ')[0] === 'RWF') {
            purchaseCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      if (exchangeCurrencySelect) {
        for (var i = 0; i < exchangeCurrencySelect.options.length; i++) {
          if (exchangeCurrencySelect.options[i].text.split(' - ')[0] === 'USD') {
            exchangeCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
      if (purchaseCurrencyValueInput) purchaseCurrencyValueInput.value = "1";
      if (exchangeRateValueInput) exchangeRateValueInput.value = (1.0 / dbRate).toFixed(8);
      
      updateCurrencyUI();
      
      if (exRateInput) exRateInput.value = dbRate.toFixed(6);
      
      var methodRadio = document.querySelector('input[name="pricing_method"][value="lme"]');
      if (methodRadio) {
        methodRadio.checked = true;
      }
      updatePricingMethodUI();
      filterProductsByLot(true);
    }
    
    setStep(1);
    bindRealTimeValidation();
  }

  initForm();
});
