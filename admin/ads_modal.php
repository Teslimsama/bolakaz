<!-- Add -->
<div class="modal fade" id="addAd">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Add New Ad</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="ads_add.php" enctype="multipart/form-data">
          <div class="form-group">
            <label for="text_align" class="col-sm-3 control-label">Text Align</label>
            <div class="col-sm-9">
              <select class="form-control" id="text_align" name="text_align" required>
                <option value="text-md-right">Right</option>
                <option value="text-md-left">Left</option>
                <option value="text-md-center">Center</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="image_path" class="col-sm-3 control-label">Image</label>
            <div class="col-sm-9">
              <input type="file" class="form-control" id="image_path" name="image_path" accept="image/*" required>
            </div>
          </div>
          <div class="form-group">
            <label for="discount" class="col-sm-3 control-label">Discount</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="discount" name="discount" required>
            </div>
          </div>
          <div class="form-group">
            <label for="category" class="col-sm-3 control-label">Collection</label>
            <div class="col-sm-9">
              <select class="form-control" id="category" name="category_id" required>
                <?php
                // Fetch categories from the database
                $conn = $pdo->open();
                $stmt = $conn->prepare("SELECT * FROM category");
                $stmt->execute();
                foreach ($stmt as $row) {
                  echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                }
                $pdo->close();
                ?>
              </select>
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
<div class="modal fade" id="editAd">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Edit Ad</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="ads_edit.php" enctype="multipart/form-data">
          <input type="hidden" class="adid" name="id">
          <div class="form-group">
            <label for="edit_text_align" class="col-sm-3 control-label">Text Align</label>
            <div class="col-sm-9">
              <select class="form-control" id="edit_text_align" name="text_align" required>
                <option value="text-md-right">Right</option>
                <option value="text-md-left">Left</option>
                <option value="text-md-center">Center</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_image_path" class="col-sm-3 control-label">Image</label>
            <div class="col-sm-9">
              <input type="file" class="form-control" id="edit_image_path" name="image_path" accept="image/*">
              <img id="current_image" src="" alt="Current Image" style="width: 100px; height: auto;">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_discount" class="col-sm-3 control-label">Discount</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_discount" name="discount" required>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_category" class="col-sm-3 control-label">Collection</label>
            <div class="col-sm-9">
              <select class="form-control" id="edit_category" name="category_id" required>
                <?php
                // Fetch categories from the database
                $conn = $pdo->open();
                $stmt = $conn->prepare("SELECT * FROM category");
                $stmt->execute();
                foreach ($stmt as $row) {
                  echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                }
                $pdo->close();
                ?>
              </select>
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
<div class="modal fade" id="deleteAd">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Deleting...</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="ads_delete.php">
          <input type="hidden" class="adid" name="id">
          <div class="text-center">
            <p>DELETE AD</p>
            <h2 class="bold adname"></h2>
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