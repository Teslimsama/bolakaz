<!-- Add -->
<div class="modal fade" id="addWeb_details">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Add New Web Details</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="web_details_add.php">
          <div class="form-group">
            <label for="site_name" class="col-sm-3 control-label">Site Name</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="site_name" name="site_name" required>
            </div>
          </div>
          <div class="form-group">
            <label for="site_number" class="col-sm-3 control-label">Site Number</label>
            <div class="col-sm-9">
              <input type="tel" class="form-control" id="site_number" name="site_number" required>
            </div>
          </div>
          <div class="form-group">
            <label for="site_email" class="col-sm-3 control-label">Site Email</label>
            <div class="col-sm-9">
              <input type="email" class="form-control" id="site_email" name="site_email" required>
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-6">
              <label for="site_address" class=" control-label">Site Address</label>
              <textarea id="editor1" name="site_address" rows="10" cols="80" required></textarea>
            </div>
          
            <div class="col-sm-6">
              <label for="discount" class=" control-label">Short Description</label>
              <textarea id="short_desc" name="short_desc" rows="10" cols="80" required></textarea>
            </div>
          </div>
          <div class="form-group">
            <label for="description" class="col-sm-2 control-label">Description</label>
            <div class="col-sm-9">
              <textarea id="desc" name="desc" rows="10" cols="80" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
            <button type="submit" class="btn btn-primary btn-flat" name="add"><i class="fa fa-save"></i> Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Edit -->
<div class="modal fade" id="editWeb_details">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Edit web_details</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="web_details_edit.php" enctype="multipart/form-data">
          <input type="hidden" class="web_detailsid" name="id">
          <div class="form-group">
            <label for="site_name" class="col-sm-3 control-label">Site Name</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_site_name" name="site_name" required>
            </div>
          </div>
          <div class="form-group">
            <label for="site_number" class="col-sm-3 control-label">Site Number</label>
            <div class="col-sm-9">
              <input type="tel" class="form-control" id="edit_site_number" name="site_number" required>
            </div>
          </div>
          <div class="form-group">
            <label for="site_email" class="col-sm-3 control-label">Site Email</label>
            <div class="col-sm-9">
              <input type="email" class="form-control" id="edit_site_email" name="site_email" required>
            </div>
          </div>
          <div class="form-group">
            <label for="site_address" class="col-sm-3 control-label">Site Address</label>
            <div class="col-sm-9">
              <textarea id="editor2" name="edit_site_address" rows="10" cols="80" required></textarea>
            </div>
          </div>
          <div class="form-group">
            <label for="Short_Description" class="col-sm-3 control-label">Short Description</label>
            <div class="col-sm-9">
              <textarea id="edit_short_desc" name="short_desc" rows="10" cols="80" required></textarea>
            </div>
          </div>
          <div class="form-group">
            <label for="description" class="col-sm-3 control-label">Description</label>
            <div class="col-sm-9">
              <textarea id="edit_desc" name="desc" rows="10" cols="80" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
            <button type="submit" class="btn btn-primary btn-flat" name="edit"><i class="fa fa-save"></i> Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete -->
<div class="modal fade" id="deleteWeb_details">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Deleting...</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="web_details_delete.php">
          <input type="hidden" class="web_detailsid" name="id">
          <div class="text-center">
            <p>DELETE Web Details</p>
            <h2 class="bold web_details_name"></h2>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
            <button type="submit" class="btn btn-danger btn-flat" name="delete"><i class="fa fa-trash"></i> Delete</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>