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
  
  // Financial Inputs (legacy hidden fields still used for form submission)
  var exRateInput = document.getElementById("exchangeRate");
  // The following vars reference elements that may not exist in new product-specific forms; null-guard them.
  var priceKgUsd = document.getElementById("pricePerKgUsd");    // shared hidden
  var priceKgRwf = document.getElementById("pricePerKgRwf");    // shared hidden
  var valUsd = document.getElementById("purchaseValueUsd");     // shared hidden
  var valRwf = document.getElementById("purchaseValueRwf");     // shared hidden
  var chargesKg = document.getElementById("chargesPerKg");      // shared hidden
  var netPaidUsd = document.getElementById("netPaidSupplierUsd"); // shared hidden
  var rraTax = document.getElementById("taxRra");               // removed in new form - null expected
  var rmaTax = document.getElementById("taxRma");               // removed in new form - null expected
  var inkoTax = document.getElementById("taxInkomane");         // removed in new form - null expected
  var prodCharges = document.getElementById("productionCharges");// removed in new form - null expected
  var lmePriceInput = document.getElementById("lmePrice");      // removed in new form - null expected
  var tcChargesInput = document.getElementById("tcCharges");    // removed in new form - null expected
  var flucInput = document.getElementById("fluc");              // removed in new form - null expected
  var lmePaidInput = document.getElementById("lmePaid");        // removed in new form - null expected
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
    var selectedOpt = purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
    var code = selectedOpt ? selectedOpt.text.split(' - ')[0] : 'RWF';
    amountInCurrencyLabel.textContent = "Price per kg in Selected Currency (" + code + ") *";
  }

  function updatePricingMethodUI() {
    var method = getPricingMethod();
    if (purchaseAmountInCurrencyInput) {
      if (method === 'manual') {
        purchaseAmountInCurrencyInput.removeAttribute('readonly');
        purchaseAmountInCurrencyInput.style.background = '';
      } else {
        purchaseAmountInCurrencyInput.setAttribute('readonly', 'true');
        purchaseAmountInCurrencyInput.style.background = 'var(--bg)';
      }
    }

    var manualEditableIds = [
      'sn_lme_paid', 'sn_tax_rra', 'sn_tax_rma', 'sn_tax_inkomane', 'sn_prod_charges', 'sn_net_paid',
      'ta_tax_rra', 'ta_tax_rma', 'ta_tax_inkomane', 'ta_prod_charges', 'ta_net_paid',
      'w03_tax_rra', 'w03_tax_rma', 'w03_tax_inkomane', 'w03_prod_charges', 'w03_net_paid'
    ];

    manualEditableIds.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        if (method === 'manual') {
          el.removeAttribute('readonly');
          el.style.background = '';
        } else {
          el.setAttribute('readonly', 'true');
          el.style.background = 'var(--bg)';
        }
      }
    });
  }

  function handlePricingMethodChange() {
    var method = getPricingMethod();
    if (method === 'lme') {
      document.querySelectorAll('#pricingFormSN input, #pricingFormTA input, #pricingFormW03 input').forEach(function(el) {
        delete el.dataset.userEdited;
      });
    }
    updatePricingMethodUI();
    var pType = getProductType();
    showPricingFormForProduct(pType);
    triggerProductCalc(pType);
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
      
      // Show pricing form based on selected product type
      var pType = getProductType();
      showPricingFormForProduct(pType);
      updateGradeDisplays(pType);
      triggerProductCalc(pType);
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
    
    var selectedOpt = lotSelect.selectedIndex >= 0 ? lotSelect.options[lotSelect.selectedIndex] : null;
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

  // =========================================================================
  // PRODUCT TYPE DETECTION & PRICING FORM SWITCHING
  // =========================================================================

  function getProductType() {
    if (!productSelect || !productSelect.value) return 'unknown';
    var selectedOpt = productSelect.options[productSelect.selectedIndex];
    var code = (selectedOpt.getAttribute('data-product-code') || '').toUpperCase();
    if (code.indexOf('TA') !== -1) return 'ta';
    if (code.indexOf('W') !== -1 || code.indexOf('W03') !== -1) return 'w03';
    if (code.indexOf('SN') !== -1 || code.indexOf('TIN') !== -1 || code.indexOf('CAS') !== -1) return 'sn';
    return 'sn'; // default to SN if unrecognized
  }

  function showPricingFormForProduct(pType) {
    var noProductDiv = document.getElementById('pricingNoProduct');
    var formSN = document.getElementById('pricingFormSN');
    var formTA = document.getElementById('pricingFormTA');
    var formW03 = document.getElementById('pricingFormW03');
    var commonFields = document.getElementById('pricingCommonFields');

    if (noProductDiv) noProductDiv.style.display = 'none';
    if (formSN) formSN.style.display = 'none';
    if (formTA) formTA.style.display = 'none';
    if (formW03) formW03.style.display = 'none';
    if (commonFields) commonFields.style.display = 'none';

    if (pType !== 'unknown') {
      if (commonFields) commonFields.style.display = 'block';
      if (pType === 'sn' && formSN) formSN.style.display = 'block';
      else if (pType === 'ta' && formTA) formTA.style.display = 'block';
      else if (pType === 'w03' && formW03) formW03.style.display = 'block';
    } else {
      if (noProductDiv) noProductDiv.style.display = 'block';
    }
  }

  function getExchangeRate() {
    var pVal = purchaseCurrencyValueInput ? parseFloat(purchaseCurrencyValueInput.value) || 1.0 : 1.0;
    var eRateVal = exchangeRateValueInput ? parseFloat(exchangeRateValueInput.value) || 0.0 : 0.0;
    var pOpt = purchaseCurrencySelect && purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
    var pCode = pOpt ? pOpt.text.split(' - ')[0] : 'RWF';
    var eOpt = exchangeCurrencySelect && exchangeCurrencySelect.selectedIndex >= 0 ? exchangeCurrencySelect.options[exchangeCurrencySelect.selectedIndex] : null;
    var eCode = eOpt ? eOpt.text.split(' - ')[0] : 'USD';
    var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
    var exRate = dbRate;
    if (pCode === 'RWF' && eCode === 'USD') {
      exRate = eRateVal > 0 ? (pVal / eRateVal) : dbRate;
    } else if (pCode === 'USD' && eCode === 'RWF') {
      exRate = pVal > 0 ? (eRateVal / pVal) : dbRate;
    } else {
      exRate = 1.0;
    }
    // Update hidden legacy exchange rate field
    if (exRateInput) exRateInput.value = exRate.toFixed(6);
    return exRate;
  }

  function getPrimaryGradePct() {
    if (!primaryElementId) return 0.0;
    var gradeInput = gradesInputContainer ? gradesInputContainer.querySelector('input[name="el_grade_' + primaryElementId + '"]') : null;
    return gradeInput ? (parseFloat(gradeInput.value) || 0.0) : 0.0;
  }

  function updateGradeDisplays(pType) {
    var gradePct = getPrimaryGradePct();
    var gradeText = gradePct > 0 ? gradePct.toFixed(4) + '%' : '— (enter grade in Step 3)';
    if (pType === 'sn') {
      var el = document.getElementById('sn_grade_display');
      if (el) el.textContent = gradeText;
    } else if (pType === 'ta') {
      var el = document.getElementById('ta_grade_display');
      if (el) el.textContent = gradeText;
    } else if (pType === 'w03') {
      var el = document.getElementById('w03_grade_display');
      if (el) el.textContent = gradeText;
    }
  }

  function triggerProductCalc(pType) {
    if (pType === 'sn') calcSN();
    else if (pType === 'ta') calcTA();
    else if (pType === 'w03') calcW03();
  }

  // =========================================================================
  // SN (TIN / CASSITERITE) CALCULATION
  // Excel: S = ((Q*T) - O) / 1000   where Q=LME_paid, T=Sn%, O=TC
  //        AR = max(0, ((Q*T) - 800) / 1000 * I * 3%)
  //        AS = (I * 50) / L   [RMA: 50 RWF/kg]
  //        AT = (I * 20) / L   [INKOMANE: 20 RWF/kg]
  //        AV = I * P          [Prod fees: P = prod_rate USD/kg]
  //        N  = M - AR - AS - AT - AV
  // =========================================================================
  window.calcSN = function() {
    var settings = window.taxSettings || {};
    var rraRate = (settings['tax_rate_rra'] || 3.0) / 100.0;
    var rmaRwf = settings['tax_rate_rma_tin'] || 50.0;
    var inkoRwf = settings['tax_rate_inkomane_tin'] || 20.0;

    var exRate = getExchangeRate();
    var qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0.0;
    var lme = parseFloat(document.getElementById('sn_lme_price') ? document.getElementById('sn_lme_price').value : 0) || 0.0;
    var fluc = parseFloat(document.getElementById('sn_fluc') ? document.getElementById('sn_fluc').value : 0) || 0.0;
    var tc = parseFloat(document.getElementById('sn_tc_charges') ? document.getElementById('sn_tc_charges').value : 0) || 0.0;
    var prodRate = parseFloat(document.getElementById('sn_prod_charges_rate') ? document.getElementById('sn_prod_charges_rate').value : 0) || 0.0;
    var gradePct = getPrimaryGradePct(); // e.g. 54.69 means 54.69%
    var gradeFrac = gradePct / 100.0;    // 0.5469

    var method = getPricingMethod();

    var lmePaidEl = document.getElementById('sn_lme_paid');
    var lmePaid = lme - fluc;
    if (lmePaidEl) {
      if (method === 'manual' && lmePaidEl.dataset.userEdited === 'true' && lmePaidEl.value !== '') {
        lmePaid = parseFloat(lmePaidEl.value) || 0.0;
      } else {
        lmePaidEl.value = lmePaid > 0 ? parseFloat(lmePaid.toFixed(4)) : (lmePaid < 0 ? parseFloat(lmePaid.toFixed(4)) : '');
      }
    }

    // Update grade display
    var gradeDisplayEl = document.getElementById('sn_grade_display');
    if (gradeDisplayEl) gradeDisplayEl.textContent = gradePct > 0 ? gradePct.toFixed(4) + '%' : '— (enter grade in Step 3)';

    var pricePerKgUsdVal = 0.0;
    var pricePerKgRwfVal = 0.0;

    var pOpt = purchaseCurrencySelect && purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
    var pCode = pOpt ? pOpt.text.split(' - ')[0] : 'RWF';

    if (method === 'manual') {
      var manualPrice = parseFloat(purchaseAmountInCurrencyInput ? purchaseAmountInCurrencyInput.value : 0) || 0.0;
      if (manualPrice <= 0 && (lme > 0 || lmePaid > 0) && qty > 0) {
        pricePerKgUsdVal = ((lmePaid * gradeFrac) - tc) / 1000.0;
        pricePerKgRwfVal = pricePerKgUsdVal * exRate;
        if (purchaseAmountInCurrencyInput) {
          purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
        }
      } else {
        if (pCode === 'RWF') {
          pricePerKgRwfVal = manualPrice;
          pricePerKgUsdVal = exRate > 0 ? manualPrice / exRate : 0.0;
        } else {
          pricePerKgUsdVal = manualPrice;
          pricePerKgRwfVal = manualPrice * exRate;
        }
      }
    } else {
      // Price per kg USD: = ((LME_Paid * Grade%) - TC) / 1000
      pricePerKgUsdVal = qty > 0 && lmePaid > 0 ? ((lmePaid * gradeFrac) - tc) / 1000.0 : 0.0;
      pricePerKgRwfVal = pricePerKgUsdVal * exRate;
      
      // Sync back to manual entry field
      if (purchaseAmountInCurrencyInput) {
        purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
      }
    }

    var pvUsd = pricePerKgUsdVal * qty;
    var pvRwf = pvUsd * exRate;

    var rraBase = (lmePaid * gradeFrac) - 800.0;
    var defaultTaxRra = (method === 'manual' && lmePaid <= 0) ? (pvUsd * rraRate) : (rraBase > 0 ? (rraBase / 1000.0) * qty * rraRate : 0.0);
    var defaultTaxRma = exRate > 0 ? (qty * rmaRwf) / exRate : 0.0;
    var defaultTaxInko = exRate > 0 ? (qty * inkoRwf) / exRate : 0.0;
    var defaultProdCharges = qty * prodRate;
    var defaultNetPaid = pvUsd - defaultTaxRra - defaultTaxRma - defaultTaxInko - defaultProdCharges;

    function getValOrSet(id, defVal) {
      var el = document.getElementById(id);
      if (!el) return defVal;
      if (method === 'manual' && el.dataset.userEdited === 'true' && el.value !== '') {
        var v = parseFloat(el.value);
        return isNaN(v) ? defVal : v;
      }
      el.value = defVal.toFixed(4);
      return defVal;
    }

    var taxRra = getValOrSet('sn_tax_rra', defaultTaxRra);
    var taxRma = getValOrSet('sn_tax_rma', defaultTaxRma);
    var taxInko = getValOrSet('sn_tax_inkomane', defaultTaxInko);
    var prodChargesTotal = getValOrSet('sn_prod_charges', defaultProdCharges);
    var netPaid = getValOrSet('sn_net_paid', defaultNetPaid);

    // Update displays
    var pricePerKgEl = document.getElementById('sn_price_per_kg');
    if (pricePerKgEl) pricePerKgEl.textContent = '$' + pricePerKgUsdVal.toFixed(4);
    var pvUsdEl = document.getElementById('sn_purchase_value_usd');
    if (pvUsdEl) pvUsdEl.textContent = '$' + pvUsd.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    var priceRwfEl = document.getElementById('sn_price_per_kg_rwf');
    if (priceRwfEl) priceRwfEl.textContent = pricePerKgRwfVal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' RWF';

    // Store into shared hidden fields for form submission
    function sh(id, val) { var el = document.getElementById(id); if (el) el.value = val; }
    sh('pricePerKgUsd', pricePerKgUsdVal.toFixed(6));
    sh('pricePerKgRwf', pricePerKgRwfVal.toFixed(6));
    sh('sharedPurchaseValueUsd', pvUsd.toFixed(4));
    sh('sharedPurchaseValueRwf', pvRwf.toFixed(4));
    sh('sharedTaxRra', taxRra.toFixed(4));
    sh('sharedTaxRma', taxRma.toFixed(4));
    sh('sharedTaxInkomane', taxInko.toFixed(4));
    sh('sharedNetPaid', netPaid.toFixed(4));
    sh('sharedProductionCharges', prodChargesTotal.toFixed(4));
    sh('sharedLmePrice', lme.toFixed(4));
    sh('sharedFluc', fluc.toFixed(4));
    sh('sharedTcCharges', tc.toFixed(4));
    sh('sharedLmePaid', lmePaid.toFixed(4));
    sh('sharedProductionChargesPerKg', prodRate.toFixed(6));

    // Summary
    updatePricingSummary(qty, pricePerKgUsdVal, pvUsd, pvRwf, taxRra + taxRma + taxInko + prodChargesTotal, netPaid);
  };

  // =========================================================================
  // TA (TANTALUM / COLTAN) CALCULATION
  // =========================================================================
  window.calcTA = function() {
    var settings = window.taxSettings || {};
    var rraRate = (settings['tax_rate_rra'] || 3.0) / 100.0;
    var rmaRwf = settings['tax_rate_rma_coltan'] || 125.0;
    var inkoRwf = settings['tax_rate_inkomane_coltan'] || 40.0;

    var exRate = getExchangeRate();
    var qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0.0;
    var pricePerTa = parseFloat(document.getElementById('ta_price_per_ta') ? document.getElementById('ta_price_per_ta').value : 0) || 0.0;
    var prodRate = parseFloat(document.getElementById('ta_prod_charges_rate') ? document.getElementById('ta_prod_charges_rate').value : 0) || 0.0;
    var gradePct = getPrimaryGradePct(); // e.g. 34.31 means 34.31%

    var method = getPricingMethod();

    // Update grade display
    var gradeDisplayEl = document.getElementById('ta_grade_display');
    if (gradeDisplayEl) gradeDisplayEl.textContent = gradePct > 0 ? gradePct.toFixed(4) + '%' : '— (enter grade in Step 3)';

    var pricePerKgUsdVal = 0.0;
    var pricePerKgRwfVal = 0.0;

    var pOpt = purchaseCurrencySelect && purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
    var pCode = pOpt ? pOpt.text.split(' - ')[0] : 'RWF';

    if (method === 'manual') {
      var manualPrice = parseFloat(purchaseAmountInCurrencyInput ? purchaseAmountInCurrencyInput.value : 0) || 0.0;
      if (manualPrice <= 0 && pricePerTa > 0 && gradePct > 0) {
        pricePerKgUsdVal = gradePct * pricePerTa;
        pricePerKgRwfVal = pricePerKgUsdVal * exRate;
        if (purchaseAmountInCurrencyInput) {
          purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
        }
      } else {
        if (pCode === 'RWF') {
          pricePerKgRwfVal = manualPrice;
          pricePerKgUsdVal = exRate > 0 ? manualPrice / exRate : 0.0;
        } else {
          pricePerKgUsdVal = manualPrice;
          pricePerKgRwfVal = manualPrice * exRate;
        }
      }
    } else {
      // Price per kg USD = grade_pct * price_per_ta (grade_pct in percent units)
      pricePerKgUsdVal = gradePct * pricePerTa;
      pricePerKgRwfVal = pricePerKgUsdVal * exRate;
      
      // Sync back to manual entry field
      if (purchaseAmountInCurrencyInput) {
        purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
      }
    }

    var pvUsd = pricePerKgUsdVal * qty;
    var pvRwf = pvUsd * exRate;

    var defaultTaxRra = pvUsd * rraRate;
    var defaultTaxRma = exRate > 0 ? (qty * rmaRwf) / exRate : 0.0;
    var defaultTaxInko = exRate > 0 ? (qty * inkoRwf) / exRate : 0.0;
    var defaultProdCharges = qty * prodRate;
    var defaultNetPaid = pvUsd - defaultTaxRra - defaultTaxRma - defaultTaxInko - defaultProdCharges;

    function getValOrSet(id, defVal) {
      var el = document.getElementById(id);
      if (!el) return defVal;
      if (method === 'manual' && el.dataset.userEdited === 'true' && el.value !== '') {
        var v = parseFloat(el.value);
        return isNaN(v) ? defVal : v;
      }
      el.value = defVal.toFixed(4);
      return defVal;
    }

    var taxRra = getValOrSet('ta_tax_rra', defaultTaxRra);
    var taxRma = getValOrSet('ta_tax_rma', defaultTaxRma);
    var taxInko = getValOrSet('ta_tax_inkomane', defaultTaxInko);
    var prodChargesTotal = getValOrSet('ta_prod_charges', defaultProdCharges);
    var netPaid = getValOrSet('ta_net_paid', defaultNetPaid);

    // Update displays
    var pricePerKgEl = document.getElementById('ta_price_per_kg');
    if (pricePerKgEl) pricePerKgEl.textContent = '$' + pricePerKgUsdVal.toFixed(4);
    var pvUsdEl = document.getElementById('ta_purchase_value_usd');
    if (pvUsdEl) pvUsdEl.textContent = '$' + pvUsd.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

    // Store price_per_ta_unit in the shared hidden field
    var shTa = document.getElementById('pricePerTaUnit'); if (shTa) shTa.value = pricePerTa;

    // Store into shared hidden fields for form submission
    function sh(id, val) { var el = document.getElementById(id); if (el) el.value = val; }
    sh('pricePerKgUsd', pricePerKgUsdVal.toFixed(6));
    sh('pricePerKgRwf', pricePerKgRwfVal.toFixed(6));
    sh('sharedPurchaseValueUsd', pvUsd.toFixed(4));
    sh('sharedPurchaseValueRwf', pvRwf.toFixed(4));
    sh('sharedTaxRra', taxRra.toFixed(4));
    sh('sharedTaxRma', taxRma.toFixed(4));
    sh('sharedTaxInkomane', taxInko.toFixed(4));
    sh('sharedNetPaid', netPaid.toFixed(4));
    sh('sharedProductionCharges', prodChargesTotal.toFixed(4));
    sh('sharedLmePrice', '0');          // TA has no LME price
    sh('sharedFluc', '0');
    sh('sharedTcCharges', '0');
    sh('sharedLmePaid', '0');
    sh('sharedProductionChargesPerKg', prodRate.toFixed(6));

    // Summary
    updatePricingSummary(qty, pricePerKgUsdVal, pvUsd, pvRwf, taxRra + taxRma + taxInko + prodChargesTotal, netPaid);
  };

  // =========================================================================
  // W03 (WOLFRAMITE / TUNGSTEN) CALCULATION
  // =========================================================================
  window.calcW03 = function() {
    var settings = window.taxSettings || {};
    var rraRate = (settings['tax_rate_rra'] || 3.0) / 100.0;
    var rmaRwf = settings['tax_rate_rma_wolframite'] || 50.0;
    var inkoRwf = settings['tax_rate_inkomane_wolframite'] || 20.0;

    var exRate = getExchangeRate();
    var qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0.0;
    var mtu = parseFloat(document.getElementById('w03_lme_price') ? document.getElementById('w03_lme_price').value : 0) || 0.0;
    var rmb = parseFloat(document.getElementById('w03_rmb_price') ? document.getElementById('w03_rmb_price').value : 0) || 0.0;
    var tc = parseFloat(document.getElementById('w03_tc_charges') ? document.getElementById('w03_tc_charges').value : 0) || 0.0;
    var prodRate = parseFloat(document.getElementById('w03_prod_charges_rate') ? document.getElementById('w03_prod_charges_rate').value : 0) || 0.0;
    var gradePct = getPrimaryGradePct(); // e.g. 37.0 means 37%
    var gradeFrac = gradePct / 100.0;    // 0.37

    var method = getPricingMethod();

    // Update grade display
    var gradeDisplayEl = document.getElementById('w03_grade_display');
    if (gradeDisplayEl) gradeDisplayEl.textContent = gradePct > 0 ? gradePct.toFixed(4) + '%' : '— (enter grade in Step 3)';

    var pricePerKgUsdVal = 0.0;
    var pricePerKgRwfVal = 0.0;

    var pOpt = purchaseCurrencySelect && purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
    var pCode = pOpt ? pOpt.text.split(' - ')[0] : 'RWF';

    if (method === 'manual') {
      var manualPrice = parseFloat(purchaseAmountInCurrencyInput ? purchaseAmountInCurrencyInput.value : 0) || 0.0;
      if (manualPrice <= 0 && mtu > 0 && qty > 0) {
        pricePerKgUsdVal = ((mtu * gradeFrac) - tc) / 10.0;
        pricePerKgRwfVal = pricePerKgUsdVal * exRate;
        if (purchaseAmountInCurrencyInput) {
          purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
        }
      } else {
        if (pCode === 'RWF') {
          pricePerKgRwfVal = manualPrice;
          pricePerKgUsdVal = exRate > 0 ? manualPrice / exRate : 0.0;
        } else {
          pricePerKgUsdVal = manualPrice;
          pricePerKgRwfVal = manualPrice * exRate;
        }
      }
    } else {
      // Price per kg USD: = ((MTU * WO3%) - TC) / 10
      pricePerKgUsdVal = qty > 0 && mtu > 0 ? ((mtu * gradeFrac) - tc) / 10.0 : 0.0;
      pricePerKgRwfVal = pricePerKgUsdVal * exRate;
      
      // Sync back to manual entry field
      if (purchaseAmountInCurrencyInput) {
        purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? pricePerKgRwfVal.toFixed(4) : pricePerKgUsdVal.toFixed(4);
      }
    }

    var pvUsd = pricePerKgUsdVal * qty;
    var pvRwf = pvUsd * exRate;

    var defaultTaxRra = (method === 'manual' && rmb <= 0) ? (pvUsd * rraRate) : (qty * gradeFrac * (rmb / 10.0) * rraRate);
    var defaultTaxRma = exRate > 0 ? (qty * rmaRwf) / exRate : 0.0;
    var defaultTaxInko = exRate > 0 ? (qty * inkoRwf) / exRate : 0.0;
    var defaultProdCharges = qty * prodRate;
    var defaultNetPaid = pvUsd - defaultTaxRra - defaultTaxRma - defaultTaxInko - defaultProdCharges;
    var fluc = rmb - mtu;

    function getValOrSet(id, defVal) {
      var el = document.getElementById(id);
      if (!el) return defVal;
      if (method === 'manual' && el.dataset.userEdited === 'true' && el.value !== '') {
        var v = parseFloat(el.value);
        return isNaN(v) ? defVal : v;
      }
      el.value = defVal.toFixed(4);
      return defVal;
    }

    var taxRra = getValOrSet('w03_tax_rra', defaultTaxRra);
    var taxRma = getValOrSet('w03_tax_rma', defaultTaxRma);
    var taxInko = getValOrSet('w03_tax_inkomane', defaultTaxInko);
    var prodChargesTotal = getValOrSet('w03_prod_charges', defaultProdCharges);
    var netPaid = getValOrSet('w03_net_paid', defaultNetPaid);

    // Update displays
    var pricePerKgEl = document.getElementById('w03_price_per_kg');
    if (pricePerKgEl) pricePerKgEl.textContent = '$' + pricePerKgUsdVal.toFixed(4);
    var pvUsdEl = document.getElementById('w03_purchase_value_usd');
    if (pvUsdEl) pvUsdEl.textContent = '$' + pvUsd.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    var pvRwfEl = document.getElementById('w03_purchase_value_rwf');
    if (pvRwfEl) pvRwfEl.textContent = pvRwf.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' RWF';

    // Store into shared hidden fields for form submission
    function sh(id, val) { var el = document.getElementById(id); if (el) el.value = val; }
    sh('pricePerKgUsd', pricePerKgUsdVal.toFixed(6));
    sh('pricePerKgRwf', pricePerKgRwfVal.toFixed(6));
    sh('sharedPurchaseValueUsd', pvUsd.toFixed(4));
    sh('sharedPurchaseValueRwf', pvRwf.toFixed(4));
    sh('sharedTaxRra', taxRra.toFixed(4));
    sh('sharedTaxRma', taxRma.toFixed(4));
    sh('sharedTaxInkomane', taxInko.toFixed(4));
    sh('sharedNetPaid', netPaid.toFixed(4));
    sh('sharedProductionCharges', prodChargesTotal.toFixed(4));
    sh('sharedLmePrice', rmb.toFixed(4));      // Store RMB Price in DB lme_price
    sh('sharedFluc', fluc.toFixed(4));          // Store fluc in DB fluc
    sh('sharedTcCharges', tc.toFixed(4));
    sh('sharedLmePaid', mtu.toFixed(4));        // Store MTU Paid in DB lme_paid
    sh('sharedProductionChargesPerKg', prodRate.toFixed(6));
    var h = document.getElementById('w03_prod_charges_per_kg_hidden'); if (h) h.value = prodRate.toFixed(6);

    // Summary
    updatePricingSummary(qty, pricePerKgUsdVal, pvUsd, pvRwf, taxRra + taxRma + taxInko + prodChargesTotal, netPaid);
  };

  function updatePricingSummary(qty, pricePerKg, pvUsd, pvRwf, totalDeductions, netPaid) {
    var el = document.getElementById('pricingSummaryPreview');
    if (!el) return;
    el.innerHTML =
      '<div class="summary-row"><span>Quantity:</span><span class="summary-val-usd">' + qty.toLocaleString(undefined, {maximumFractionDigits:4}) + ' kg</span></div>' +
      '<div class="summary-row"><span>Unit Price:</span><span class="summary-val-usd">$' + pricePerKg.toFixed(4) + '</span></div>' +
      '<div class="summary-row"><span>Purchase Value:</span><span class="summary-val-usd">$' + pvUsd.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' <span class="summary-val-rwf">(' + pvRwf.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' RWF)</span></span></div>' +
      '<div class="summary-row"><span>Total Deductions/Taxes:</span><span class="summary-val-usd" style="color:var(--red)">-$' + totalDeductions.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:4}) + '</span></div>' +
      '<div class="summary-row"><span>Net Payable Amount:</span><span class="summary-val-usd" style="color:var(--green)">$' + netPaid.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></div>';
  }

  // Wire manual entry inputs to flag user edit and re-trigger calculation
  var manualEditableIds = [
    'sn_lme_price', 'sn_fluc', 'sn_lme_paid', 'sn_tc_charges', 'sn_prod_charges_rate',
    'sn_tax_rra', 'sn_tax_rma', 'sn_tax_inkomane', 'sn_prod_charges', 'sn_net_paid',
    'ta_price_per_ta', 'ta_prod_charges_rate', 'ta_tax_rra', 'ta_tax_rma', 'ta_tax_inkomane',
    'ta_prod_charges', 'ta_net_paid',
    'w03_lme_price', 'w03_rmb_price', 'w03_tc_charges', 'w03_prod_charges_rate',
    'w03_tax_rra', 'w03_tax_rma', 'w03_tax_inkomane', 'w03_prod_charges', 'w03_net_paid'
  ];
  manualEditableIds.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', function() {
        el.dataset.userEdited = 'true';
        if (currentStep === 4) triggerProductCalc(getProductType());
      });
    }
  });

  // Exchange rate inputs trigger recalculation for current product type
  [purchaseCurrencyValueInput, exchangeRateValueInput].forEach(function(inp) {
    if (inp) inp.addEventListener('input', function() {
      if (currentStep === 4) triggerProductCalc(getProductType());
    });
  });

  if (purchaseCurrencySelect) {
    purchaseCurrencySelect.addEventListener('change', function() {
      var selectedOpt = purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
      var selectedCode = selectedOpt ? selectedOpt.text.split(' - ')[0] : 'RWF';
      if (exchangeCurrencySelect) {
        for (var i = 0; i < exchangeCurrencySelect.options.length; i++) {
          var optCode = exchangeCurrencySelect.options[i].text.split(' - ')[0];
          if (optCode !== selectedCode) { exchangeCurrencySelect.selectedIndex = i; break; }
        }
      }
      var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
      if (selectedCode === 'RWF') {
        purchaseCurrencyValueInput.value = '1';
        exchangeRateValueInput.value = (1.0 / dbRate).toFixed(8);
      } else {
        purchaseCurrencyValueInput.value = '1';
        exchangeRateValueInput.value = dbRate.toFixed(4);
      }
      if (currentStep === 4) triggerProductCalc(getProductType());
    });
  }

  if (exchangeCurrencySelect) {
    exchangeCurrencySelect.addEventListener('change', function() {
      if (currentStep === 4) triggerProductCalc(getProductType());
    });
  }

  // Legacy calculateTotals kept as no-op to avoid errors from old references
  function calculateTotals() {
    var pType = getProductType();
    triggerProductCalc(pType);
  }

  // Wire qtyInput changes to recalculate on step 4
  if (qtyInput) {
    qtyInput.addEventListener('input', function() {
      if (currentStep === 4) triggerProductCalc(getProductType());
    });
  }

  // Wire pricing method radio buttons to toggle manual vs automatic modes
  if (pricingMethodRadios) {
    pricingMethodRadios.forEach(function(radio) {
      radio.addEventListener('change', handlePricingMethodChange);
    });
  }

  // Wire manual price input changes to recalculate immediately
  if (purchaseAmountInCurrencyInput) {
    purchaseAmountInCurrencyInput.addEventListener('input', function() {
      if (currentStep === 4) triggerProductCalc(getProductType());
    });
  }



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
            var gradeInput = gradesInputContainer.querySelector('input[name="el_grade_' + id + '"]');
            var noteInput = gradesInputContainer.querySelector('input[name="el_notes_' + id + '"]');
            var gradeVal = gradeInput ? parseFloat(gradeInput.value) || 0.0 : 0.0;
            var noteVal = noteInput ? noteInput.value : "";
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

  function submitPurchase() {
    var pType = getProductType();
    var method = getPricingMethod();
    var pricingOk = true;

    if (method === 'manual') {
      if (!purchaseAmountInCurrencyInput || !parseFloat(purchaseAmountInCurrencyInput.value)) {
        if (purchaseAmountInCurrencyInput) { purchaseAmountInCurrencyInput.classList.add('is-invalid'); }
        formAlertPlaceholder.innerHTML = '<div class="alert-msg error"><svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Please enter the manual unit price.</span></div>';
        pricingOk = false;
      }
    } else {
      if (pType === 'sn') {
        var lmeEl = document.getElementById('sn_lme_price');
        if (!lmeEl || !parseFloat(lmeEl.value)) {
          if (lmeEl) { lmeEl.classList.add('is-invalid'); }
          formAlertPlaceholder.innerHTML = '<div class="alert-msg error"><svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Please enter LME Price for Tin.</span></div>';
          pricingOk = false;
        }
      } else if (pType === 'ta') {
        var taEl = document.getElementById('ta_price_per_ta');
        if (!taEl || !parseFloat(taEl.value)) {
          if (taEl) { taEl.classList.add('is-invalid'); }
          formAlertPlaceholder.innerHTML = '<div class="alert-msg error"><svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Please enter Price per Ta unit.</span></div>';
          pricingOk = false;
        }
      } else if (pType === 'w03') {
        var mtuEl = document.getElementById('w03_lme_price');
        var rmbEl = document.getElementById('w03_rmb_price');
        if (!mtuEl || !parseFloat(mtuEl.value)) {
          if (mtuEl) { mtuEl.classList.add('is-invalid'); }
          formAlertPlaceholder.innerHTML = '<div class="alert-msg error"><svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Please enter MTU Paid for Wolframite.</span></div>';
          pricingOk = false;
        } else if (!rmbEl || !parseFloat(rmbEl.value)) {
          if (rmbEl) { rmbEl.classList.add('is-invalid'); }
          formAlertPlaceholder.innerHTML = '<div class="alert-msg error"><svg class="alert-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>Please enter RMB Price for Wolframite.</span></div>';
          pricingOk = false;
        }
      }
    }
    if (!pricingOk) { window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'}); return; }

    var id = purchaseIdInput.value;
    var actionUrl = "purchas_api.php?action=" + (id && parseInt(id) > 0 ? "update" : "create");
    
    var formData = new FormData(purchasForm);
    
    // Append the selected elements & grades manually
    var checkedIds = getCheckedElements();
    checkedIds.forEach(function(elId) {
      var gradeInput = gradesInputContainer.querySelector('input[name="el_grade_' + elId + '"]');
      var noteInput = gradesInputContainer.querySelector('input[name="el_notes_' + elId + '"]');
      var gradeVal = gradeInput ? gradeInput.value : "0";
      var noteVal = noteInput ? noteInput.value : "";
      
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
        var invoiceId = (result.data && result.data.id) ? result.data.id : id;
        if (invoiceId) {
          window.open("index.php?action=invoice&id=" + invoiceId, "_blank");
        }
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

      // Step 4: Pricing & Financials - restore into product-specific fields
      var pType = getProductType(); // might not know yet; we'll restore by raw field names
      
      // Restore exchange rate
      var dbRate = parseFloat(window.latestExchangeRate) || 1400.0;
      if (exRateInput) exRateInput.value = p.exchange_rate || dbRate;
      
      if (purchaseCurrencySelect) {
        purchaseCurrencySelect.value = p.purchase_currency_id || "2"; // Default to USD (2)
      }
      updateCurrencyUI();
      
      var pOpt = purchaseCurrencySelect && purchaseCurrencySelect.selectedIndex >= 0 ? purchaseCurrencySelect.options[purchaseCurrencySelect.selectedIndex] : null;
      var pCode = pOpt ? pOpt.text.split(' - ')[0] : 'USD';
      if (exchangeCurrencySelect) {
        for (var i = 0; i < exchangeCurrencySelect.options.length; i++) {
          var optCode = exchangeCurrencySelect.options[i].text.split(' - ')[0];
          if (optCode !== pCode) {
            exchangeCurrencySelect.selectedIndex = i;
            break;
          }
        }
      }
      
      var eOpt = exchangeCurrencySelect && exchangeCurrencySelect.selectedIndex >= 0 ? exchangeCurrencySelect.options[exchangeCurrencySelect.selectedIndex] : null;
      var eCode = eOpt ? eOpt.text.split(' - ')[0] : '';
      
      if (p.exchange_rate) {
        var legacyRate = parseFloat(p.exchange_rate) || dbRate;
        if (pCode === 'RWF' && eCode === 'USD') {
          if (purchaseCurrencyValueInput) purchaseCurrencyValueInput.value = "1";
          if (exchangeRateValueInput) exchangeRateValueInput.value = (1.0 / legacyRate).toFixed(8);
        } else if (pCode === 'USD' && eCode === 'RWF') {
          if (purchaseCurrencyValueInput) purchaseCurrencyValueInput.value = "1";
          if (exchangeRateValueInput) exchangeRateValueInput.value = legacyRate.toFixed(4);
        } else {
          if (purchaseCurrencyValueInput) purchaseCurrencyValueInput.value = "1";
          if (exchangeRateValueInput) exchangeRateValueInput.value = "1";
        }
      } else {
        if (purchaseCurrencyValueInput) purchaseCurrencyValueInput.value = "1";
        if (exchangeRateValueInput) exchangeRateValueInput.value = pCode === 'RWF' ? (1.0 / dbRate).toFixed(8) : dbRate.toFixed(4);
      }

      // Restore product-specific pricing fields (best-effort based on saved values)
      // SN fields
      var snLme = document.getElementById('sn_lme_price'); if (snLme) snLme.value = p.lme_price || '';
      var snFluc = document.getElementById('sn_fluc'); if (snFluc) snFluc.value = p.fluc || '0';
      var snTc = document.getElementById('sn_tc_charges'); if (snTc) snTc.value = p.tc_charges || '';
      var snLmePaid = document.getElementById('sn_lme_paid'); if (snLmePaid) snLmePaid.value = p.lme_paid || '';
      var snProdRate = document.getElementById('sn_prod_charges_rate'); if (snProdRate) snProdRate.value = p.production_charges_per_kg || '';
      // TA fields
      var taPriceTa = document.getElementById('ta_price_per_ta'); if (taPriceTa) taPriceTa.value = p.price_per_ta_unit || '';
      var taProdRate = document.getElementById('ta_prod_charges_rate'); if (taProdRate) taProdRate.value = p.production_charges_per_kg || '';
      // W03 fields
      var w03Mtu = document.getElementById('w03_lme_price'); if (w03Mtu) w03Mtu.value = p.lme_paid || '';
      var w03Rmb = document.getElementById('w03_rmb_price'); if (w03Rmb) w03Rmb.value = p.lme_price || '';
      var w03Tc = document.getElementById('w03_tc_charges'); if (w03Tc) w03Tc.value = p.tc_charges || '';
      var w03ProdRate = document.getElementById('w03_prod_charges_rate'); if (w03ProdRate) w03ProdRate.value = p.production_charges_per_kg || '';
      // Shared hidden fields
      if (priceKgUsd) priceKgUsd.value = p.price_per_kg_usd || '';
      if (priceKgRwf) priceKgRwf.value = p.price_per_kg_rwf || '';
      if (valUsd) valUsd.value = p.purchase_value_usd || '';
      if (valRwf) valRwf.value = p.purchase_value_rwf || '';
      if (chargesKg) chargesKg.value = p.charges_per_kg || '';
      if (netPaidUsd) netPaidUsd.value = p.net_paid_supplier_usd || '';
      // Restore pricing method selection
      var method = p.pricing_method || 'lme';
      var methodRadio = document.querySelector('input[name="pricing_method"][value="' + method + '"]');
      if (methodRadio) {
        methodRadio.checked = true;
      }
      updatePricingMethodUI();

      if (method === 'manual') {
        if (purchaseAmountInCurrencyInput) {
          purchaseAmountInCurrencyInput.value = pCode === 'RWF' ? (p.price_per_kg_rwf ? parseFloat(p.price_per_kg_rwf).toFixed(4) : '') : (p.price_per_kg_usd ? parseFloat(p.price_per_kg_usd).toFixed(4) : '');
        }
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
