<!-- Add -->
<div class="modal fade" id="addnew">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Add New Coupon</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="coupon_add.php">
          <div class="form-group">
            <label for="code" class="col-sm-3 control-label">Code</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="code" name="code" required>
            </div>
          </div>
          <div class="form-group">
            <label for="type" class="col-sm-3 control-label">Type</label>
            <div class="col-sm-9">
              <select class="form-control" id="type" name="type" required>
                <option value="fixed">Fixed</option>
                <option value="percent">Percent</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="value" class="col-sm-3 control-label">Value</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="value" name="value" required>
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
          <div class="form-group">
            <label for="expire_date" class="col-sm-3 control-label">Expire Date</label>
            <div class="col-sm-9">
              <input type="date" class="form-control" id="expire_date" name="expire_date">
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
          <i class="fa fa-close"></i> Close
        </button>
        <button type="submit" class="btn btn-primary btn-flat" name="add">
          <i class="fa fa-save"></i> Save
        </button>
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
        <h4 class="modal-title"><b>Edit Coupon</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="coupon_edit.php">
          <input type="hidden" class="coupon_id" name="id">
          <div class="form-group">
            <label for="edit_code" class="col-sm-3 control-label">Code</label>
            <div class="col-sm-9">
              <input type="text" class="form-control code" id="edit_code" name="code">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_type" class="col-sm-3 control-label">Type</label>
            <div class="col-sm-9">
              <select class="form-control" id="edit_type" name="type">
                <option value="fixed">Fixed</option>
                <option value="percent">Percent</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_value" class="col-sm-3 control-label">Value</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_value" name="value">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_status" class="col-sm-3 control-label">Status</label>
            <div class="col-sm-9">
              <select class="form-control" id="edit_status" name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_expire_date" class="col-sm-3 control-label">Expire Date</label>
            <div class="col-sm-9">
              <input type="date" class="form-control" id="edit_expire_date" name="expire_date">
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
          <i class="fa fa-close"></i> Close
        </button>
        <button type="submit" class="btn btn-success btn-flat" name="edit">
          <i class="fa fa-check-square-o"></i> Update
        </button>
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
        <form class="form-horizontal" method="POST" action="coupon_delete.php">
          <input type="hidden" class="coupon_id" name="id">
          <div class="text-center">
            <p>DELETE COUPON</p>
            <h2 class="bold coupon_code"></h2>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
          <i class="fa fa-close"></i> Close
        </button>
        <button type="submit" class="btn btn-danger btn-flat" name="delete">
          <i class="fa fa-trash"></i> Delete
        </button>
        </form>
      </div>
    </div>
  </div>
</div>