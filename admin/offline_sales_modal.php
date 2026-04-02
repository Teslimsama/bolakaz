<?php
require_once __DIR__ . '/../lib/product_sku.php';
$offlineScannerAssetVersion = (string) (@filemtime(__DIR__ . '/../plugins/html5-qrcode/html5-qrcode.min.js') ?: '1');
?>

<style>
.offline-camera-stage {
    position: relative;
    width: 100%;
    min-height: 320px;
    border: 1px solid #d2d6de;
    border-radius: 6px;
    overflow: hidden;
    background: #111;
}

.offline-camera-stage #offline_camera_reader {
    width: 100%;
    min-height: 320px;
}

.offline-camera-stage video,
.offline-camera-stage canvas {
    width: 100% !important;
    height: auto !important;
}

.offline-camera-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    width: min(90%, 420px);
    height: min(28%, 140px);
    transform: translate(-50%, -50%);
    border: 3px solid rgba(255, 255, 255, 0.92);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.22);
    pointer-events: none;
}

.offline-camera-overlay::before,
.offline-camera-overlay::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 18px;
    height: 18px;
    border-top: 3px solid #39cccc;
    border-left: 3px solid #39cccc;
    transform: translateY(-50%);
}

.offline-camera-overlay::before {
    left: -3px;
}

.offline-camera-overlay::after {
    right: -3px;
    transform: translateY(-50%) rotate(180deg);
}

.offline-camera-help {
    margin-top: 10px;
    margin-bottom: 0;
}
</style>

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
                      <div class="input-group input-group-lg">
                        <input type="text" class="form-control" id="offline_product_lookup" placeholder="Scan barcode or type SKU / product number, then press Enter">
                        <span class="input-group-btn">
                          <button type="button" class="btn btn-default" id="open_offline_camera_scanner">
                            <i class="fa fa-camera"></i> Scan with Camera
                          </button>
                        </span>
                      </div>
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

<div class="modal fade" id="offline_camera_scanner_modal" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><b>Scan Product With Camera</b></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="offline_camera_device" class="control-label">Camera</label>
                    <select class="form-control" id="offline_camera_device" style="width:100%;"></select>
                </div>
                <div id="offline_camera_feedback" class="help-block text-muted" aria-live="polite">Opening the camera will let you scan Code128 barcodes or QR codes that contain your SKU.</div>
                <div class="offline-camera-stage">
                    <div id="offline_camera_reader"></div>
                    <div class="offline-camera-overlay" aria-hidden="true"></div>
                </div>
                <p class="help-block offline-camera-help">Hold steady and move closer to the barcode. A wide barcode should sit inside the guide box.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
              <button type="button" class="btn btn-warning btn-flat" id="offline_camera_stop" disabled><i class="fa fa-stop"></i> Stop Scanning</button>
              <button type="button" class="btn btn-primary btn-flat" id="offline_camera_start"><i class="fa fa-play"></i> Start Scanning</button>
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

<script src="../plugins/html5-qrcode/html5-qrcode.min.js?v=<?php echo e($offlineScannerAssetVersion); ?>"></script>
<script>
$(document).ready(function() {
    var SCAN_DEDUPE_MS = 1200;
    var $addOfflineModal = $('#add_offline');
    var $cameraScannerModal = $('#offline_camera_scanner_modal');
    var $cameraOpenButton = $('#open_offline_camera_scanner');
    var $cameraStartButton = $('#offline_camera_start');
    var $cameraStopButton = $('#offline_camera_stop');
    var $cameraDeviceSelect = $('#offline_camera_device');
    var $cameraFeedback = $('#offline_camera_feedback');
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
    var scannerInstance = null;
    var scannerCameras = [];
    var scannerStarting = false;
    var scannerActive = false;
    var scannerPopulatingSelect = false;
    var scannerFeatureEnabled = false;
    var lastScannerValue = '';
    var lastScannerValueAt = 0;

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function hydrateModalSelect($select, placeholder) {
        if (!$select.length || !$.fn.select2) {
            return;
        }

        var $dropdownParent = $select.closest('.modal-body');
        if (!$dropdownParent.length) {
            $dropdownParent = $select.closest('.modal-content');
        }
        if (!$dropdownParent.length) {
            $dropdownParent = $select.closest('.modal.in');
        }
        if (!$dropdownParent.length) {
            $dropdownParent = $select.closest('.modal');
        }
        if (!$dropdownParent.length) {
            $dropdownParent = $addOfflineModal;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            dropdownParent: $dropdownParent,
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
            if (!$lookupInput.length) {
                return;
            }

            $lookupInput.trigger('focus');
            if ($lookupInput[0] && typeof $lookupInput[0].select === 'function') {
                $lookupInput[0].select();
            }
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

    function setCameraFeedback(type, message) {
        $cameraFeedback
            .removeClass('text-success text-danger text-muted text-info')
            .addClass(type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'info' ? 'text-info' : 'text-muted')))
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

    function resetProductSelect($select) {
        if (!$select.length) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            try {
                $select.select2('destroy');
            } catch (e) {
                // Ignore partially initialized select2 instances.
            }
        }

        $select
            .removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id')
            .removeAttr('aria-hidden')
            .removeAttr('tabindex')
            .removeAttr('style')
            .val('');
        $select.find('option').prop('selected', false).removeAttr('data-select2-id');
    }

    function createEmptyProductRow() {
        var $row = $('#product-list .product-row:first').clone(false, false);
        $row.find('.select2-container').remove();
        var $select = $row.find('select[name="products[]"]');
        resetProductSelect($select);
        $row.find('input[name="qty[]"]').val('1');
        $row.removeAttr('data-product-id');
        return $row;
    }

    function appendEmptyProductRow() {
        var $row = createEmptyProductRow();
        $('#product-list').append($row);
        hydrateProductSelect($row.find('select[name="products[]"]'));
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

    function addOrIncrementProduct(product, options) {
        options = options || {};

        var productId = String(product.id || '');
        if (!productId) {
            return null;
        }

        var $row = findRowByProductId(productId);
        var rowAlreadyExists = $row.length > 0;
        var newQty = 1;

        if (rowAlreadyExists) {
            var $qty = $row.find('input[name="qty[]"]');
            var currentQty = parseInt($qty.val(), 10);
            newQty = (isNaN(currentQty) || currentQty < 1 ? 1 : currentQty) + 1;
            $qty.val(newQty);
        } else {
            $row = findEmptyRow();
            if (!$row.length) {
                $row = appendEmptyProductRow();
            }

            var $select = $row.find('select[name="products[]"]');
            $select.val(productId).trigger('change');
            $row.find('input[name="qty[]"]').val('1');
            $row.attr('data-product-id', productId);
            newQty = 1;
        }

        highlightRow($row);
        updateSalePreview();
        clearLookupSuggestions();
        $lookupInput.val('');

        var productName = $.trim(product.name || 'Product');
        var successMessage = rowAlreadyExists
            ? ('Quantity updated: ' + productName + ' (x' + newQty + ')')
            : ('Added: ' + productName);

        setLookupFeedback('success', successMessage);
        if (options.source === 'camera') {
            setCameraFeedback('success', successMessage);
        }
        playLookupTone('success');
        if (!(options.source === 'camera' && $cameraScannerModal.hasClass('in'))) {
            focusLookupInput();
        }

        return {
            rowAlreadyExists: rowAlreadyExists,
            quantity: newQty,
            message: successMessage
        };
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
                var failedMessage = (response && response.message) ? response.message : 'Unable to search products right now.';
                setLookupFeedback('error', failedMessage);
                if (job.source === 'camera') {
                    setCameraFeedback('error', failedMessage);
                }
                playLookupTone('error');
                focusLookupInput();
                processLookupQueue();
                return;
            }

            if (response.exact) {
                addOrIncrementProduct(response.exact, {
                    source: job.source
                });
                processLookupQueue();
                return;
            }

            if (job.preferFirstSuggestion && response.suggestions && response.suggestions.length) {
                addOrIncrementProduct(response.suggestions[0], {
                    source: job.source
                });
                processLookupQueue();
                return;
            }

            clearLookupSuggestions();
            $lookupInput.val('');
            var notFoundMessage = response.message || 'Product not found. Scan again or type SKU/number.';
            setLookupFeedback('error', notFoundMessage);
            if (job.source === 'camera') {
                setCameraFeedback('error', notFoundMessage);
            }
            playLookupTone('error');
            focusLookupInput();
            processLookupQueue();
        });
    }

    function queueExactLookup(query, preferFirstSuggestion, options) {
        var normalized = $.trim(query || '');
        options = options || {};

        if (!normalized) {
            var emptyMessage = 'Type or scan a product SKU or number first.';
            setLookupFeedback('error', emptyMessage);
            if (options.source === 'camera') {
                setCameraFeedback('error', emptyMessage);
            }
            playLookupTone('error');
            focusLookupInput();
            return;
        }

        lookupQueue.push({
            query: normalized,
            preferFirstSuggestion: !!preferFirstSuggestion,
            source: options.source || 'manual'
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

    function isProbablyMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(window.navigator.userAgent || '');
    }

    function isCameraContextSecure() {
        if (window.isSecureContext) {
            return true;
        }

        var hostname = String(window.location.hostname || '').toLowerCase();
        return hostname === 'localhost' || hostname === '127.0.0.1';
    }

    function humanizeCameraError(error, fallbackMessage) {
        var raw = String((error && error.message) || error || '').trim();
        var lowered = raw.toLowerCase();

        if (!isCameraContextSecure()) {
            return 'Camera scanning needs HTTPS or localhost. You can still type SKU or use the picker.';
        }
        if (lowered.indexOf('notallowed') !== -1 || lowered.indexOf('permission') !== -1 || lowered.indexOf('denied') !== -1) {
            return 'Camera permission was denied. You can still type SKU or use the picker.';
        }
        if (lowered.indexOf('notfound') !== -1 || lowered.indexOf('no camera') !== -1 || lowered.indexOf('requested device not found') !== -1) {
            return 'No camera was found on this device. You can still type SKU or use the picker.';
        }
        if (lowered.indexOf('insecure') !== -1 || lowered.indexOf('secure context') !== -1) {
            return 'Camera scanning needs HTTPS or localhost. You can still type SKU or use the picker.';
        }

        return fallbackMessage || 'Unable to start the camera right now. You can still type SKU or use the picker.';
    }

    function getSupportedCameraFormats() {
        var formats = [];
        if (window.Html5QrcodeSupportedFormats) {
            if (window.Html5QrcodeSupportedFormats.CODE_128) {
                formats.push(window.Html5QrcodeSupportedFormats.CODE_128);
            }
            if (window.Html5QrcodeSupportedFormats.QR_CODE) {
                formats.push(window.Html5QrcodeSupportedFormats.QR_CODE);
            }
        }
        return formats;
    }

    function updateCameraButtons() {
        $cameraStartButton.prop('disabled', !scannerFeatureEnabled || scannerStarting || scannerActive || !scannerCameras.length);
        $cameraStopButton.prop('disabled', !scannerActive && !scannerStarting);
        $cameraDeviceSelect.prop('disabled', !scannerFeatureEnabled || scannerStarting || !scannerCameras.length);
    }

    function updateCameraFeatureAvailability() {
        var hasBrowserCameraSupport = !!(window.navigator.mediaDevices && window.navigator.mediaDevices.getUserMedia);
        var hasLibrarySupport = typeof window.Html5Qrcode === 'function' && typeof window.Html5Qrcode.getCameras === 'function';

        scannerFeatureEnabled = hasBrowserCameraSupport && hasLibrarySupport && isCameraContextSecure();

        if (!hasLibrarySupport) {
            $cameraOpenButton.prop('disabled', true).attr('title', 'Camera scanner library failed to load.');
        } else if (!hasBrowserCameraSupport) {
            $cameraOpenButton.prop('disabled', true).attr('title', 'This browser does not support camera scanning.');
        } else if (!isCameraContextSecure()) {
            $cameraOpenButton.prop('disabled', true).attr('title', 'Camera scanning needs HTTPS or localhost.');
        } else {
            $cameraOpenButton.prop('disabled', false).removeAttr('title');
        }

        updateCameraButtons();
    }

    function normalizeDecodedValue(decodedText) {
        return $.trim(String(decodedText || '')).toUpperCase();
    }

    function shouldSuppressScannerValue(decodedValue) {
        var now = Date.now();
        if (decodedValue === lastScannerValue && (now - lastScannerValueAt) < SCAN_DEDUPE_MS) {
            return true;
        }

        lastScannerValue = decodedValue;
        lastScannerValueAt = now;
        return false;
    }

    function resetScannerValueCooldown() {
        lastScannerValue = '';
        lastScannerValueAt = 0;
    }

    function ensureScannerInstance() {
        if (!scannerFeatureEnabled) {
            return null;
        }

        if (!scannerInstance) {
            scannerInstance = new Html5Qrcode('offline_camera_reader', {
                formatsToSupport: getSupportedCameraFormats(),
                useBarCodeDetectorIfSupported: false,
                verbose: false
            });
        }

        return scannerInstance;
    }

    function clearScannerInstance() {
        if (!scannerInstance) {
            return Promise.resolve();
        }

        var instance = scannerInstance;
        scannerInstance = null;
        return instance.clear().catch(function() {
            return null;
        });
    }

    function stopCameraScanner(options) {
        options = options || {};
        var shouldKeepFeedback = !!options.keepFeedback;

        if (!scannerInstance) {
            scannerStarting = false;
            scannerActive = false;
            updateCameraButtons();
            if (!shouldKeepFeedback) {
                setCameraFeedback('muted', 'Camera stopped. Click Start Scanning to resume.');
            }
            return Promise.resolve();
        }

        var instance = scannerInstance;

        if (scannerActive || scannerStarting) {
            return instance.stop().catch(function() {
                return null;
            }).then(function() {
                scannerActive = false;
                scannerStarting = false;
                return clearScannerInstance();
            }).then(function() {
                updateCameraButtons();
                if (!shouldKeepFeedback) {
                    setCameraFeedback('muted', 'Camera stopped. Click Start Scanning to resume.');
                }
            });
        }

        scannerActive = false;
        scannerStarting = false;
        return clearScannerInstance().then(function() {
            updateCameraButtons();
            if (!shouldKeepFeedback) {
                setCameraFeedback('muted', 'Camera stopped. Click Start Scanning to resume.');
            }
        });
    }

    function scannerQrBox(viewfinderWidth, viewfinderHeight) {
        return {
            width: Math.max(250, Math.floor(Math.min(viewfinderWidth * 0.9, 420))),
            height: Math.max(100, Math.floor(Math.min(viewfinderHeight * 0.28, 140)))
        };
    }

    function buildScannerConfig() {
        var config = {
            fps: 8,
            qrbox: scannerQrBox,
            rememberLastUsedCamera: true,
            aspectRatio: 1.7777778
        };
        var formats = getSupportedCameraFormats();
        if (formats.length) {
            config.formatsToSupport = formats;
        }
        return config;
    }

    function handleDecodedText(decodedText) {
        var normalized = normalizeDecodedValue(decodedText);
        if (!normalized || shouldSuppressScannerValue(normalized)) {
            return;
        }

        setCameraFeedback('info', 'Scanned ' + normalized + '. Looking up product...');
        queueExactLookup(normalized, false, {
            source: 'camera'
        });
    }

    function performScannerStart(cameraSource, label) {
        var instance = ensureScannerInstance();
        if (!instance) {
            setCameraFeedback('error', 'Camera scanner is unavailable right now. You can still type SKU or use the picker.');
            return Promise.resolve(false);
        }

        scannerStarting = true;
        scannerActive = false;
        updateCameraButtons();
        resetScannerValueCooldown();
        setCameraFeedback('muted', 'Starting ' + label + '...');

        return instance.start(cameraSource, buildScannerConfig(), handleDecodedText, function() {
            // Frequent decode misses are expected while framing the barcode.
        }).then(function() {
            scannerStarting = false;
            scannerActive = true;
            updateCameraButtons();
            setCameraFeedback('muted', 'Camera is live. Hold steady and move closer to the barcode.');
            return true;
        }).catch(function(error) {
            scannerStarting = false;
            scannerActive = false;
            updateCameraButtons();
            return clearScannerInstance().then(function() {
                throw error;
            });
        });
    }

    function populateCameraSelect(cameras) {
        scannerPopulatingSelect = true;
        $cameraDeviceSelect.empty();

        if (!cameras.length) {
            $cameraDeviceSelect.append('<option value="">No camera found</option>');
            scannerPopulatingSelect = false;
            updateCameraButtons();
            return;
        }

        if (cameras.length > 1 && isProbablyMobileDevice()) {
            $cameraDeviceSelect.append('<option value="">Auto (Rear Camera Preferred)</option>');
        }

        $.each(cameras, function(index, camera) {
            var cameraLabel = $.trim(camera.label || '');
            if (!cameraLabel) {
                cameraLabel = 'Camera ' + (index + 1);
            }

            $cameraDeviceSelect.append(
                $('<option></option>').val(camera.id).text(cameraLabel)
            );
        });

        if (cameras.length === 1) {
            $cameraDeviceSelect.val(cameras[0].id);
        } else if (isProbablyMobileDevice()) {
            $cameraDeviceSelect.val('');
        } else {
            $cameraDeviceSelect.val(cameras[0].id);
        }

        scannerPopulatingSelect = false;
        updateCameraButtons();
    }

    function loadCameraDevices() {
        if (!scannerFeatureEnabled) {
            setCameraFeedback('error', humanizeCameraError(null, 'Camera scanning is unavailable in this browser.'));
            return Promise.resolve([]);
        }

        setCameraFeedback('muted', 'Checking available cameras...');

        return window.Html5Qrcode.getCameras().then(function(cameras) {
            scannerCameras = $.isArray(cameras) ? cameras : [];
            populateCameraSelect(scannerCameras);

            if (!scannerCameras.length) {
                setCameraFeedback('error', 'No camera was found on this device. You can still type SKU or use the picker.');
                return [];
            }

            setCameraFeedback('muted', 'Camera ready. Click Start Scanning if it does not begin automatically.');
            return scannerCameras;
        }).catch(function(error) {
            scannerCameras = [];
            populateCameraSelect(scannerCameras);
            setCameraFeedback('error', humanizeCameraError(error, 'Unable to access cameras right now. You can still type SKU or use the picker.'));
            return [];
        });
    }

    function startSelectedCamera() {
        if (!scannerFeatureEnabled) {
            setCameraFeedback('error', humanizeCameraError(null, 'Camera scanning is unavailable in this browser.'));
            return Promise.resolve(false);
        }

        if (!scannerCameras.length) {
            setCameraFeedback('error', 'No camera was found on this device. You can still type SKU or use the picker.');
            return Promise.resolve(false);
        }

        var selectedCameraId = $.trim($cameraDeviceSelect.val() || '');

        if (selectedCameraId) {
            return performScannerStart(selectedCameraId, 'the selected camera').catch(function(error) {
                setCameraFeedback('error', humanizeCameraError(error, 'Unable to start the selected camera. Try another one or use the manual flow.'));
                return false;
            });
        }

        if (isProbablyMobileDevice()) {
            return performScannerStart({ facingMode: { exact: 'environment' } }, 'the rear camera').catch(function() {
                var fallbackCamera = scannerCameras[0];
                if (!fallbackCamera) {
                    setCameraFeedback('error', 'No camera was found on this device. You can still type SKU or use the picker.');
                    return false;
                }

                $cameraDeviceSelect.val(fallbackCamera.id);
                return performScannerStart(fallbackCamera.id, 'the fallback camera').catch(function(fallbackError) {
                    setCameraFeedback('error', humanizeCameraError(fallbackError, 'Unable to start the camera right now. You can still type SKU or use the picker.'));
                    return false;
                });
            });
        }

        var defaultCamera = scannerCameras[0];
        if (!defaultCamera) {
            setCameraFeedback('error', 'No camera was found on this device. You can still type SKU or use the picker.');
            return Promise.resolve(false);
        }

        $cameraDeviceSelect.val(defaultCamera.id);
        return performScannerStart(defaultCamera.id, 'the default camera').catch(function(error) {
            setCameraFeedback('error', humanizeCameraError(error, 'Unable to start the camera right now. You can still type SKU or use the picker.'));
            return false;
        });
    }

    function restartSelectedCamera() {
        return stopCameraScanner({
            keepFeedback: true
        }).then(function() {
            return startSelectedCamera();
        });
    }

    $('#offline_customer').on('change', populateOfflineCustomerFields);
    populateOfflineCustomerFields();
    hydrateModalSelect($('#offline_customer'), 'Select Customer');

    $('#btn-add-product').click(function() {
        appendEmptyProductRow();
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

    $cameraOpenButton.on('click', function() {
        if ($(this).prop('disabled')) {
            return;
        }
        $cameraScannerModal.modal('show');
    });

    $cameraStartButton.on('click', function() {
        restartSelectedCamera();
    });

    $cameraStopButton.on('click', function() {
        stopCameraScanner();
    });

    $cameraDeviceSelect.on('change', function() {
        if (scannerPopulatingSelect || !$cameraScannerModal.hasClass('in')) {
            return;
        }
        restartSelectedCamera();
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
        $cameraScannerModal.modal('hide');
        stopCameraScanner({
            keepFeedback: true
        });
    });

    $cameraScannerModal.on('shown.bs.modal', function() {
        updateCameraFeatureAvailability();
        resetScannerValueCooldown();

        if (!scannerFeatureEnabled) {
            setCameraFeedback('error', humanizeCameraError(null, 'Camera scanning is unavailable in this browser.'));
            return;
        }

        loadCameraDevices().then(function(cameras) {
            if (!cameras.length) {
                return;
            }
            return startSelectedCamera();
        });
    });

    $cameraScannerModal.on('hidden.bs.modal', function() {
        stopCameraScanner({
            keepFeedback: true
        }).then(function() {
            setCameraFeedback('muted', 'Opening the camera will let you scan Code128 barcodes or QR codes that contain your SKU.');
        });

        if ($addOfflineModal.hasClass('in')) {
            $('body').addClass('modal-open');
            focusLookupInput();
        }
    });

    $('#product-list .product-row').each(function() {
        hydrateProductSelect($(this).find('select[name="products[]"]'));
        normalizeProductRow($(this));
    });
    updateCameraFeatureAvailability();
    updateSalePreview();
});
</script>
