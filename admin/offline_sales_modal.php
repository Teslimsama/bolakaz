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
                      <select class="form-control select2" name="user_id" style="width: 100%;">
                        <option value="0">Guest / Manual Entry</option>
                        <?php
                          $conn = $pdo->open();
                          $stmt = $conn->prepare("SELECT id, firstname, lastname FROM users WHERE type=0 ORDER BY firstname ASC");
                          $stmt->execute();
                          foreach($stmt as $urow){
                            echo "<option value='".$urow['id']."'>".e($urow['firstname'].' '.$urow['lastname'])."</option>";
                          }
                          $pdo->close();
                        ?>
                      </select>
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
                <div id="product-list">
                    <div class="row product-row" style="margin-bottom: 10px;">
                        <div class="col-sm-7">
                            <select class="form-control select2" name="products[]" required style="width:100%;">
                                <option value="">Select Product</option>
                                <?php
                                  $conn = $pdo->open();
                                  $stmt = $conn->prepare("SELECT id, name, price FROM products ORDER BY name ASC");
                                  $stmt->execute();
                                  foreach($stmt as $prow){
                                    echo "<option value='".$prow['id']."' data-price='".$prow['price']."'>".e($prow['name'])." (".app_money($prow['price']).")</option>";
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
    $('#btn-add-product').click(function() {
        var row = $('.product-row:first').clone();
        row.find('input').val('1');
        row.find('.select2-container').remove();
        row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').val('').select2();
        $('#product-list').append(row);
    });

    $(document).on('click', '.btn-remove-product', function() {
        if ($('.product-row').length > 1) {
            $(this).closest('.product-row').remove();
        }
    });
});
</script>
