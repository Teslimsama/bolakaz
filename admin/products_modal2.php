<!-- Delete Modal -->
<div class="modal fade" id="delete">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Deleting...</b></h4>
      </div>
      <form class="form-horizontal" method="POST" action="products_delete.php">
        <div class="modal-body">
          <input type="hidden" class="prodid" name="id">
          <div class="text-center">
            <p>DELETE PRODUCT</p>
            <h2 class="bold name"></h2>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
            <i class="fa fa-close"></i> Close
          </button>
          <button type="submit" class="btn btn-danger btn-flat" name="delete">
            <i class="fa fa-trash"></i> Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Image Edit Modal -->
<div class="modal fade" id="image_edit">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b><span class="name"></span></b></h4>
      </div>
      <div class="modal-body">
        <div class="container">
          <div class="row">
            <div class="col-md-6">
              <form method="post" action="image_actions.php" enctype="multipart/form-data">
                <!-- Uncomment below to enable file upload -->
                <!--
                <div class="form-group">
                  <label>Images:</label>
                  <input type="file" name="images[]" class="form-control" multiple>
                </div>
                -->
                <div class="image_show" id="image_show"></div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
          <i class="fa fa-close"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="edit">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Edit Product</b></h4>
      </div>
      <form class="form-horizontal" method="POST" action="products_edit.php">
        <div class="modal-body">
          <input type="hidden" class="prodid" name="id">
          <div class="form-group">
            <label for="edit_name" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_name" name="name">
            </div>

            <label for="edit_category" class="col-sm-1 control-label">Category</label>
            <div class="col-sm-5">
              <select class="form-control" id="edit_category" name="category">
                <option selected id="catselected"></option>
              </select>
            </div>
          </div>

          <div class="form-group" id="edit_child_cat_div">
            <label for="edit_child_cat_id" class="col-sm-1 control-label">Sub Category</label>
            <div class="col-sm-5">
              <select class="form-control" id="edit_child_cat_id" name="edit_child_cat_id">
                <option value="">--Select any sub category--</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="edit_price" class="col-sm-1 control-label">Price</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_price" name="price">
            </div>

            <label for="edit_color" class="col-sm-1 control-label">Color</label>
            <div class="col-sm-5">
              <select name="color[]" id="edit_color_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any color--</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="edit_material" class="col-sm-1 control-label">Material</label>
            <div class="col-sm-5">
              <select name="material[]" id="edit_material_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any material--</option>
              </select>
            </div>

            <label for="edit_quantity" class="col-sm-1 control-label">Quantity</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_quantity" name="quantity">
            </div>
          </div>

          <div class="form-group">
            <label for="edit_brand" class="col-sm-1 control-label">Brand</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_brand" name="brand" required>
            </div>

            <label for="edit_size" class="col-sm-1 control-label">Size</label>
            <div class="col-sm-5">
              <select name="size[]" id="edit_size_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any size--</option>
              </select>
            </div>
          </div>

          <p><b>Description</b></p>
          <div class="form-group">
            <div class="col-sm-12">
              <textarea id="editor2" name="description" rows="10" cols="80"></textarea>
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
        </div>
      </form>
    </div>
  </div>
</div>