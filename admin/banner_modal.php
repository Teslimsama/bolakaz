<!-- Add Banner Item Modal -->
<div class="modal fade" id="addnewBanner">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Add New Banner Item</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="banner_add.php" enctype="multipart/form-data">
          <div class="form-group">
            <label for="banner_name" class="col-sm-3 control-label">Name</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="banner_name" name="name" required>
            </div>
          </div>

          <div class="form-group">
            <label for="banner_image" class="col-sm-3 control-label">Image</label>
            <div class="col-sm-9">
              <input type="file" class="form-control" id="banner_image" name="banner_image" required>
            </div>
          </div>
          <div class="form-group">
            <label for="caption_heading" class="col-sm-3 control-label">Caption Heading</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="caption_heading" name="caption_heading" required>
            </div>
          </div>
          <div class="form-group">
            <label for="caption_text" class="col-sm-3 control-label">Caption Text</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="caption_text" name="caption_text" required>
            </div>
          </div>
          <div class="form-group">
            <label for="link" class="col-sm-3 control-label">Link</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="link" name="link" required>
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

<!-- Edit Banner Item Modal -->
<div class="modal fade" id="editBanner">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Edit Banner Item</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="banner_edit.php" enctype="multipart/form-data">
          <input type="hidden" class="bannerid" name="id">
          <div class="form-group">
            <label for="edit_banner_name" class="col-sm-3 control-label">Name</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_banner_name" name="name">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_banner_image" class="col-sm-3 control-label">Image</label>
            <div class="col-sm-9">
              <input type="file" class="form-control" id="edit_banner_image" name="banner_image">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_caption_heading" class="col-sm-3 control-label">Caption Heading</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_caption_heading" name="caption_heading">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_caption_text" class="col-sm-3 control-label">Caption Text</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_caption_text" name="caption_text">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_link" class="col-sm-3 control-label">Link</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_link" name="link">
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

<!-- Delete Banner Item Modal -->
<div class="modal fade" id="deleteBanner">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Deleting...</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="banner_delete.php">
          <input type="hidden" class="bannerid" name="id">
          <div class="text-center">
            <p>DELETE BANNER ITEM</p>
            <h2 class="bold bannername"></h2>
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