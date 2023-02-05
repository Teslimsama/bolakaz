<!-- Transaction History -->
<div class="modal fade" id="transaction">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><b>Transaction Full Details</b></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body table-responsive">
        <p>
          Date: <span id="date"></span>
          <span class="pull-right">Transaction#: <span id="transid"></span></span>
        </p>
        <table class="table table-bordered">
          <thead>
            <th>Product</th>
            <th width="20%">Price</th>
            <th width="2%">Quantity</th>
            <th width="25%">Subtotal</th>
          </thead>
          <tbody id="detail">
            <tr>
              <td colspan="3" align="left"><b>Total</b></td>
              <td><span id="total"></span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile -->
<div class="modal fade" id="edit">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><b>Update Account</b></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="profile_edit.php" enctype="multipart/form-data">
          <div class="form-group">
            <label for="firstname" class="col-sm-3 control-label">Firstname</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $user['firstname']; ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="lastname" class="col-sm-3 control-label">Lastname</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $user['lastname']; ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="email" class="col-sm-3 control-label">Email</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="password" class="col-sm-3 control-label">Password</label>

            <div class="col-sm-9">
              <input type="password" class="form-control" id="password" name="password" value="<?php echo $user['password']; ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="phone" class="col-sm-3 control-label">Phone No</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="address" class="col-sm-3 control-label">Address</label>

            <div class="col-sm-9">
              <textarea class="form-control" id="address" name="address"><?php echo $user['address']; ?></textarea>
            </div>
          </div>
          <div class="form-group">
            <label for="photo" class="col-sm-3 control-label">Photo</label>

            <div class="col-sm-9">
              <input type="file" id="photo" name="photo">
            </div>
          </div>
          <hr>

          <div class="form-group">
            <label for="curr_password" class="col-sm-3 control-label">Current Password</label>

            <div class="col-sm-9">
              <input type="password" class="form-control" id="curr_password" name="curr_password" placeholder="input current password to save changes" required>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="float-start btn btn-secondary btn-flat " data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-primary btn-flat" name="edit"><i class="fa fa-check-square-o"></i> Update</button>
        </form>
      </div>
    </div>
  </div>
</div>