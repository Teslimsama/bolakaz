<?php require_once __DIR__ . '/../lib/product_sku.php'; ?>

<!-- Add Offline Sale Modal -->
<div class="modal fade" id="add_offline">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><b>Add New Offline Sale</b></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <form class="form-horizontal" method="POST" action="offline_sales_add.php">
                <div class="form-group">
                    <label for="customer" class="col-sm-3 control-label">Customer</label>
                    <div class="col-sm-9">
                      <select class="form-control select2" id="offline_customer" name="user_id" style="width: 100%;">
                        <option value="0">Auto-create New Customer</option>
                        <?php
                          $conn = $pdo->open();
                          $stmt = $conn->prepare("SELECT id, firstname, lastname, phone FROM users WHERE type=0 ORDER BY firstname ASC");
                          $stmt->execute();
                          foreach($stmt as $urow){
                            $fullName = trim((string)$urow['firstname'].' '.(string)$urow['lastname']);
                            echo "<option value='".$urow['id']."' data-fullname='".e($fullName)."' data-phone='".e((string)($urow['phone'] ?? ''))."'>".e($fullName)."</option>";
                          }
                          $pdo->close();
                        ?>
                      </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="customer_name" class="col-sm-3 control-label">Customer Name</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Customer full name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="customer_phone" class="col-sm-3 control-label">Customer Phone</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="customer_phone" name="customer_phone" placeholder="Optional phone number for follow-up">
                      <p class="help-block">If you do not pick an existing customer, saving this sale will create an incomplete reusable customer profile automatically.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="date" class="col-sm-3 control-label">Sale Date</label>
                    <div class="col-sm-9">
                      <input type="date" class="form-control" name="sales_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="due_date" class="col-sm-3 control-label">Expected Full Payment By</label>
                    <div class="col-sm-9">
                      <input type="date" class="form-control" name="due_date">
                    </div>
                </div>
                
                <hr>
                <h4>Products</h4>
                <div class="form-group">
                    <label for="offline_product_lookup" class="col-sm-3 control-label">Scan or Type</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control input-lg" id="offline_product_lookup" placeholder="Scan barcode or type SKU / product number, then press Enter">
                      <p class="help-block">Barcode scanners type like a keyboard. You can type the full SKU like BLKZ-000123, or just the number part like 123.</p>
                      <div id="offline_product_lookup_feedback" class="help-block" aria-live="polite"></div>
                      <div id="offline_product_lookup_suggestions" class="list-group" style="margin-top:8px; display:none;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Fallback Picker</label>
                    <div class="col-sm-9">
                      <p class="help-block">If needed, you can still add products manually from the list below.</p>
                    </div>
                </div>
                <div id="product-list">
                    <div class="row product-row" style="margin-bottom: 10px;">
                        <div class="col-sm-7">
                            <select class="form-control select2" name="products[]" required style="width:100%;">
                                <option value="">Select Product</option>
                                <?php
                                  $conn = $pdo->open();
                                  $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE product_status = 1 ORDER BY name ASC");
                                  $stmt->execute();
                                  foreach($stmt as $prow){
                                    $resolvedSku = product_sku_resolve_for_row((array) $prow);
                                    $label = trim((string) ($prow['name'] ?? 'Product'));
                                    if ($resolvedSku !== '') {
                                      $label .= ' | ' . $resolvedSku;
                                    }
                                    $label .= ' (' . app_money((float) ($prow['price'] ?? 0)) . ')';
                                    echo "<option value='".(int) $prow['id']."' data-price='".e((string) ($prow['price'] ?? 0))."' data-sku='".e($resolvedSku)."' data-name='".e((string) ($prow['name'] ?? ''))."'>".e($label)."</option>";
                                  }
                                  $pdo->close();
                                ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <input type="number" class="form-control" name="qty[]" value="1" min="1" placeholder="Qty">
                        </div>
                        <div class="col-sm-2">
                            <button type="button" class="btn btn-danger btn-flat btn-remove-product"><i class="fa fa-remove"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-info btn-flat btn-sm" id="btn-add-product"><i class="fa fa-plus"></i> Add Another Product</button>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-sm-4">
                        <div class="well well-sm text-center">
                            <div class="text-muted">Current Total</div>
                            <div id="offline_sale_total_preview" style="font-size: 1.25em; font-weight: bold;"><?php echo app_money(0); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="well well-sm text-center">
                            <div class="text-muted">Initial Payment</div>
                            <div id="offline_sale_paid_preview" style="font-size: 1.25em; font-weight: bold; color: green;"><?php echo app_money(0); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="well well-sm text-center">
                            <div class="text-muted">Balance</div>
                            <div id="offline_sale_balance_preview" style="font-size: 1.25em; font-weight: bold; color: #c9302c;"><?php echo app_money(0); ?></div>
                        </div>
                    </div>
                </div>
                
                <hr>
                <div class="form-group">
                    <label for="initial_payment" class="col-sm-3 control-label">Initial Payment</label>
                    <div class="col-sm-9">
                      <input type="number" step="0.01" class="form-control" name="initial_payment" placeholder="Amount paid today (optional)">
                    </div>
                </div>
                <div class="form-group">
                    <label for="payment_method" class="col-sm-3 control-label">Payment Method</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="payment_method">
                          <option value="Cash">Cash</option>
                          <option value="Bank Transfer">Bank Transfer</option>
                          <option value="POS">POS</option>
                      </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
              <button type="submit" class="btn btn-primary btn-flat" name="add"><i class="fa fa-save"></i> Save Sale</button>
              </form>
            </div>
        </div>
    </div>
</div>

<!-- Manage Payments Modal -->
<div class="modal fade" id="manage_payments_modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><b>Manage Payments</b></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4 text-center">
                        <h5>Total Sale</h5>
                        <p id="total_sale_amount" style="font-weight:bold; font-size:1.2em;"></p>
                    </div>
                    <div class="col-sm-4 text-center">
                        <h5>Paid</h5>
                        <p id="total_paid_amount" style="font-weight:bold; font-size:1.2em; color:green;"></p>
                    </div>
                    <div class="col-sm-4 text-center">
                        <h5>Balance</h5>
                        <p id="remaining_balance" style="font-weight:bold; font-size:1.2em; color:red;"></p>
                    </div>
                </div>
                <hr>
                <h4>Payment History</h4>
                <div id="payment_history" class="table-responsive">
                    <!-- Loaded via AJAX -->
                </div>
                <hr>
                <h4>Add New Payment</h4>
                <form class="form-horizontal" method="POST" action="offline_payment_add.php">
                    <input type="hidden" class="sales_id" name="sales_id">
                    <div class="form-group">
                        <label for="amount" class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                          <input type="number" step="0.01" class="form-control" name="amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="method" class="col-sm-3 control-label">Method</label>
                        <div class="col-sm-9">
                          <select class="form-control" name="payment_method" required>
                              <option value="Cash">Cash</option>
                              <option value="Bank Transfer">Bank Transfer</option>
                              <option value="POS">POS</option>
                          </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pdate" class="col-sm-3 control-label">Date</label>
                        <div class="col-sm-9">
                          <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
              <button type="submit" class="btn btn-success btn-flat" name="add_payment"><i class="fa fa-plus"></i> Add Payment</button>
              </form>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="offline_details_modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><b>Sale Details</b></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p><b>Customer:</b> <span id="detail_customer"></span></p>
                <p><b>Date:</b> <span id="detail_date"></span></p>
                <p><b>Status:</b> <span id="detail_status"></span></p>
                <hr>
                <table class="table table-bordered">
                    <thead>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </thead>
                    <tbody id="detail_items">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" align="right"><b>Total</b></td>
                            <td><span id="detail_total"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var $addOfflineModal = $('#add_offline');
    var $lookupInput = $('#offline_product_lookup');
    var $lookupFeedback = $('#offline_product_lookup_feedback');
    var $lookupSuggestions = $('#offline_product_lookup_suggestions');
    var $saleTotalPreview = $('#offline_sale_total_preview');
    var $salePaidPreview = $('#offline_sale_paid_preview');
    var $saleBalancePreview = $('#offline_sale_balance_preview');
    var lookupDebounceTimer = null;
    var latestSuggestionToken = 0;
    var lookupQueue = [];
    var processingLookup = false;
    var audioContext = null;

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function hydrateModalSelect($select, placeholder) {
        if (!$select.length || !$.fn.select2) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            dropdownParent: $addOfflineModal,
            minimumResultsForSearch: 0,
            placeholder: placeholder || '',
            allowClear: false
        });
    }

    function hydrateProductSelect($select) {
        hydrateModalSelect($select, 'Select Product');
    }

    function parseAmount(value) {
        var amount = parseFloat(value);
        return isNaN(amount) ? 0 : amount;
    }

    function formatMoney(amount) {
        var normalized = parseAmount(amount);

        if (window.Intl && typeof window.Intl.NumberFormat === 'function') {
            return new window.Intl.NumberFormat('en-NG', {
                style: 'currency',
                currency: 'NGN',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(normalized);
        }

        return 'NGN ' + normalized.toFixed(2);
    }

    function updateSalePreview() {
        var total = 0;

        $('#product-list .product-row').each(function() {
            var $row = $(this);
            var productId = String($row.find('select[name="products[]"]').val() || '');
            if (!productId) {
                return;
            }

            var $selectedOption = $row.find('select[name="products[]"] option:selected');
            var price = parseAmount($selectedOption.data('price'));
            var qty = parseInt($row.find('input[name="qty[]"]').val(), 10);

            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }

            total += price * qty;
        });

        var paid = parseAmount($('input[name="initial_payment"]').val());
        var balance = total - paid;

        $saleTotalPreview.text(formatMoney(total));
        $salePaidPreview.text(formatMoney(paid));
        $saleBalancePreview.text(formatMoney(balance > 0 ? balance : 0));
    }

    function focusLookupInput() {
        window.setTimeout(function() {
            $lookupInput.trigger('focus');
            $lookupInput[0].select();
        }, 0);
    }

    function getAudioContext() {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) {
            return null;
        }
        if (!audioContext) {
            audioContext = new Ctx();
        }
        return audioContext;
    }

    function playLookupTone(type) {
        try {
            var ctx = getAudioContext();
            if (!ctx) {
                return;
            }

            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            var oscillator = ctx.createOscillator();
            var gainNode = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = (type === 'success') ? 1046.5 : 220;
            gainNode.gain.setValueAtTime(0.0001, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.08, ctx.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + ((type === 'success') ? 0.15 : 0.28));
            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + ((type === 'success') ? 0.16 : 0.3));
        } catch (e) {
            // Audio feedback is best-effort only.
        }
    }

    function setLookupFeedback(type, message) {
        $lookupFeedback
            .removeClass('text-success text-danger text-muted')
            .addClass(type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : 'text-muted'))
            .text(message || '');
    }

    function clearLookupSuggestions() {
        $lookupSuggestions.empty().hide();
    }

    function normalizeProductRow($row) {
        var productId = String($row.find('select[name="products[]"]').val() || '');
        if (productId) {
            $row.attr('data-product-id', productId);
        } else {
            $row.removeAttr('data-product-id');
        }
    }

    function createEmptyProductRow() {
        var $row = $('#product-list .product-row:first').clone(false, false);
        $row.find('.select2-container').remove();
        var $select = $row.find('select[name="products[]"]');
        $select
            .removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id')
            .removeAttr('aria-hidden')
            .removeAttr('tabindex')
            .val('');
        $select.find('option').prop('selected', false);
        $row.find('input[name="qty[]"]').val('1');
        $row.removeAttr('data-product-id');
        hydrateProductSelect($select);
        return $row;
    }

    function findRowByProductId(productId) {
        return $('#product-list .product-row[data-product-id="' + productId + '"]').first();
    }

    function findEmptyRow() {
        return $('#product-list .product-row').filter(function() {
            return !String($(this).find('select[name="products[]"]').val() || '');
        }).first();
    }

    function highlightRow($row) {
        $row.stop(true, true).css('backgroundColor', '#fff8d5');
        window.setTimeout(function() {
            $row.css('transition', 'background-color 0.35s ease');
            $row.css('backgroundColor', '');
            window.setTimeout(function() {
                $row.css('transition', '');
            }, 400);
        }, 20);
    }

    function addOrIncrementProduct(product) {
        var productId = String(product.id || '');
        if (!productId) {
            return;
        }

        var $row = findRowByProductId(productId);
        if ($row.length) {
            var $qty = $row.find('input[name="qty[]"]');
            var currentQty = parseInt($qty.val(), 10);
            $qty.val((isNaN(currentQty) || currentQty < 1 ? 1 : currentQty) + 1);
        } else {
            $row = findEmptyRow();
            if (!$row.length) {
                $row = createEmptyProductRow();
                $('#product-list').append($row);
            }

            var $select = $row.find('select[name="products[]"]');
            $select.val(productId).trigger('change');
            $row.find('input[name="qty[]"]').val('1');
            $row.attr('data-product-id', productId);
        }

        highlightRow($row);
        updateSalePreview();
        clearLookupSuggestions();
        $lookupInput.val('');
        setLookupFeedback('success', product.name + ' added to the sale.');
        playLookupTone('success');
        focusLookupInput();
    }

    function renderLookupSuggestions(items) {
        if (!items || !items.length) {
            clearLookupSuggestions();
            return;
        }

        $lookupSuggestions.empty();
        $.each(items, function(_, item) {
            var $button = $('<button type="button" class="list-group-item"></button>');
            $button.html(
                '<strong>' + escapeHtml(item.name) + '</strong><br>' +
                '<small>' + escapeHtml(item.sku || '') + ' | ' + escapeHtml(item.price_formatted || '') + '</small>'
            );
            $button.data('product', item);
            $lookupSuggestions.append($button);
        });
        $lookupSuggestions.show();
    }

    function requestLookup(query, onDone) {
        $.ajax({
            url: 'products_lookup.php',
            type: 'GET',
            dataType: 'json',
            data: { q: query }
        }).done(function(response) {
            onDone(response || null);
        }).fail(function() {
            onDone({
                success: false,
                message: 'Unable to search products right now.'
            });
        });
    }

    function processLookupQueue() {
        if (processingLookup || !lookupQueue.length) {
            return;
        }

        processingLookup = true;
        var job = lookupQueue.shift();
        requestLookup(job.query, function(response) {
            processingLookup = false;

            if (!response || !response.success) {
                clearLookupSuggestions();
                $lookupInput.val('');
            setLookupFeedback('error', (response && response.message) ? response.message : 'Unable to search products right now.');
            playLookupTone('error');
            focusLookupInput();
            processLookupQueue();
            return;
            }

            if (response.exact) {
                addOrIncrementProduct(response.exact);
                processLookupQueue();
                return;
            }

            if (job.preferFirstSuggestion && response.suggestions && response.suggestions.length) {
                addOrIncrementProduct(response.suggestions[0]);
                processLookupQueue();
                return;
            }

            clearLookupSuggestions();
            $lookupInput.val('');
            setLookupFeedback('error', response.message || 'Product not found. Scan again or type SKU/number.');
            playLookupTone('error');
            focusLookupInput();
            processLookupQueue();
        });
    }

    function queueExactLookup(query, preferFirstSuggestion) {
        var normalized = $.trim(query || '');
        if (!normalized) {
            setLookupFeedback('error', 'Type or scan a product SKU or number first.');
            playLookupTone('error');
            focusLookupInput();
            return;
        }

        lookupQueue.push({
            query: normalized,
            preferFirstSuggestion: !!preferFirstSuggestion
        });
        processLookupQueue();
    }

    function fetchSuggestions(query) {
        var normalized = $.trim(query || '');
        if (!normalized) {
            clearLookupSuggestions();
            setLookupFeedback('muted', '');
            return;
        }

        latestSuggestionToken += 1;
        var token = latestSuggestionToken;

        requestLookup(normalized, function(response) {
            if (token !== latestSuggestionToken || $.trim($lookupInput.val()) !== normalized) {
                return;
            }

            if (!response || !response.success) {
                clearLookupSuggestions();
                setLookupFeedback('error', (response && response.message) ? response.message : 'Unable to search products right now.');
                return;
            }

            renderLookupSuggestions(response.suggestions || []);
            if (response.exact) {
                setLookupFeedback('muted', 'Press Enter to add ' + response.exact.name + '.');
            } else if (response.suggestions && response.suggestions.length) {
                setLookupFeedback('muted', 'Press Enter to add the first match, or click a suggestion.');
            } else {
                clearLookupSuggestions();
                setLookupFeedback('error', response.message || 'Product not found. Scan again or type SKU/number.');
            }
        });
    }

    function populateOfflineCustomerFields() {
        var $selected = $('#offline_customer').find(':selected');
        var fullName = $.trim($selected.data('fullname') || '');
        var phone = $.trim($selected.data('phone') || '');

        if ($('#offline_customer').val() === '0') {
            $('#customer_name').val('');
            $('#customer_phone').val('');
            return;
        }

        if (fullName !== '') {
            $('#customer_name').val(fullName);
        }
        $('#customer_phone').val(phone);
    }

    $('#offline_customer').on('change', populateOfflineCustomerFields);
    populateOfflineCustomerFields();
    hydrateModalSelect($('#offline_customer'), 'Select Customer');

    $('#btn-add-product').click(function() {
        $('#product-list').append(createEmptyProductRow());
        updateSalePreview();
    });

    $(document).on('click', '.btn-remove-product', function() {
        if ($('.product-row').length > 1) {
            $(this).closest('.product-row').remove();
        } else {
            var $row = $(this).closest('.product-row');
            $row.find('select[name="products[]"]').val('').trigger('change');
            $row.find('input[name="qty[]"]').val('1');
            $row.removeAttr('data-product-id');
        }
        updateSalePreview();
        focusLookupInput();
    });

    $(document).on('change', '#product-list select[name="products[]"]', function() {
        normalizeProductRow($(this).closest('.product-row'));
        updateSalePreview();
    });

    $(document).on('input change', '#product-list input[name="qty[]"]', function() {
        var $input = $(this);
        var qty = parseInt($input.val(), 10);
        if (isNaN(qty) || qty < 1) {
            $input.val('1');
        }
        updateSalePreview();
    });

    $(document).on('input change', 'input[name="initial_payment"]', function() {
        updateSalePreview();
    });

    $(document).on('click', '#offline_product_lookup_suggestions .list-group-item', function() {
        addOrIncrementProduct($(this).data('product') || {});
    });

    $lookupInput.on('input', function() {
        var query = $(this).val();
        clearTimeout(lookupDebounceTimer);
        lookupDebounceTimer = window.setTimeout(function() {
            fetchSuggestions(query);
        }, 300);
    });

    $lookupInput.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(lookupDebounceTimer);
            queueExactLookup($(this).val(), true);
        }
    });

    $('#add_offline form').on('submit', function(e) {
        $('#product-list .product-row').each(function() {
            var $row = $(this);
            var selectedProduct = String($row.find('select[name="products[]"]').val() || '');
            if (!selectedProduct && $('#product-list .product-row').length > 1) {
                $row.remove();
            }
        });

        var hasSelectedProducts = $('#product-list select[name="products[]"]').filter(function() {
            return String($(this).val() || '') !== '';
        }).length > 0;

        if (!hasSelectedProducts) {
            e.preventDefault();
            setLookupFeedback('error', 'Add at least one product before saving the sale.');
            playLookupTone('error');
            focusLookupInput();
        }
    });

    $('#add_offline').on('shown.bs.modal', function() {
        clearLookupSuggestions();
        setLookupFeedback('muted', '');
        updateSalePreview();
        focusLookupInput();
    });

    $('#add_offline').on('hidden.bs.modal', function() {
        clearLookupSuggestions();
        setLookupFeedback('muted', '');
        $lookupInput.val('');
        lookupQueue = [];
        processingLookup = false;
    });

    $('#product-list .product-row').each(function() {
        hydrateProductSelect($(this).find('select[name="products[]"]'));
        normalizeProductRow($(this));
    });
    updateSalePreview();
});
</script>
