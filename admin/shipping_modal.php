<!-- Add -->
<div class="modal fade" id="addnew">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Add New Shipping Method</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="shipping_add.php">
          <div class="form-group">
            <label for="type" class="col-sm-3 control-label">Type</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="type" name="type" required>
            </div>
          </div>
          <div class="form-group">
            <label for="price" class="col-sm-3 control-label">Price</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="price" name="price" required>
            </div>
          </div>
          <div class="form-group">
            <label for="status" class="col-sm-3 control-label">Status</label>
            <div class="col-sm-9">
              <select class="form-control" id="status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-primary btn-flat" name="add"><i class="fa fa-save"></i> Save</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit -->
<div class="modal fade" id="edit">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Edit Shipping Method</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="shipping_edit.php">
          <input type="hidden" class="shippingid" name="id">
          <div class="form-group">
            <label for="edit_type" class="col-sm-3 control-label">Type</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_type" name="type">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_price" class="col-sm-3 control-label">Price</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_price" name="price">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_status" class="col-sm-3 control-label">Status</label>
            <div class="col-sm-9">
              <select class="form-control" id="edit_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-success btn-flat" name="edit"><i class="fa fa-check-square-o"></i> Update</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete -->
<div class="modal fade" id="delete">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Deleting...</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="shipping_delete.php">
          <input type="hidden" class="shippingid" name="id">
          <div class="text-center">
            <p>DELETE SHIPPING METHOD</p>
            <h2 class="bold shippingtype"></h2>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-danger btn-flat" name="delete"><i class="fa fa-trash"></i> Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
