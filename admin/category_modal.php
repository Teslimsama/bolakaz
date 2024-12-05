<!-- Add -->
<div class="modal fade" id="addnew">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b>Add New Category</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="category_add.php" enctype="multipart/form-data">
          <div class="form-group">
            <label for="name" class="col-sm-3 control-label">Name</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
          </div>
          <div class="form-group">
            <label for="slug" class="col-sm-3 control-label">Slug</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="slug" name="slug" required>
            </div>
          </div>
          <div class="form-group">
            <label for="parent" class="col-sm-3 control-label">Is Parent ?</label>

            <div class="col-sm-9">
              <input type="checkbox" name='is_parent' id='is_parent' value='1' checked> Yes
            </div>
          </div>
          <div class="form-group d-none" id="parent_cat_div">
            <label for="parent_id" class="col-sm-3 control-label">Parent Category</label>
            <div class="col-sm-9">
              <select name="parent_id" class="form-control">
                <option value="">--Select any category--</option>
                <?php foreach ($parent_cats as $category): ?>
                  <option value="<?php echo htmlspecialchars($category['id']); ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="status" class="col-sm-3 control-label">Status</label>

            <div class="col-sm-9">
              <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="cat-image" class="col-sm-3 control-label">Category Image</label>

            <div class="col-sm-9">
              <input type="file" class="form-control" id="cat-image" name="cat-image" required>
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
        <h4 class="modal-title"><b>Edit Category</b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="category_edit.php" enctype="multipart/form-data">
          <input type="hidden" class="catid" name="id">
          <div class="form-group">
            <label for="edit_name" class="col-sm-3 control-label">Name</label>

            <div class="col-sm-9">
              <input type="text" class="form-control" id="edit_name" name="name">
            </div>
          </div>
          <div class="form-group">
            <label for="is_parent" class="col-sm-3 control-label">Is Parent</label>

            <div class="col-sm-9">
              <input type="checkbox" name="is_parent" id="is_parent_edit" value="1"
                <?php echo ($category['is_parent'] == 1) ? 'checked' : ''; ?>> Yes
            </div>
          </div>


          <div class="form-group <?php echo ($category['is_parent'] == 1) ? 'd-none' : ''; ?>" id="parent_cat_div_edit">
            <label for="parent_id" class="col-sm-3 control-label">Parent Category</label>
            <div class="col-sm-9">
              <select name="parent_id" class="form-control">
                <option value="">--Select any category--</option>
                <?php foreach ($parent_cats as $parent_cat): ?>
                  <option value="<?php echo htmlspecialchars($parent_cat['id']); ?>"
                    <?php echo ($parent_cat['id'] == $category['parent_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($parent_cat['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="edit_cat-image" class="col-sm-3 control-label">Category Image</label>

            <div class="col-sm-9">
              <input type="file" class="form-control" id="edit_cat-image" name="cat-image">
            </div>
          </div>
          <div class="form-group">
            <label for="status" class="col-sm-3 control-label">Status</label>

            <div class="col-sm-9">
              <select name="status" class="form-control" id="status" required>
                <option value="">--Select Status--</option>
                <!-- Options will be populated here via AJAX -->
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
        <form class="form-horizontal" method="POST" action="category_delete.php">
          <input type="hidden" class="catid" name="id">
          <div class="text-center">
            <p>DELETE CATEGORY</p>
            <h2 class="bold catname"></h2>
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