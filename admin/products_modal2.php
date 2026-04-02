<!-- Delete Modal -->
<div class="modal fade" id="delete">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Archiving...</b></h4>
      </div>
      <form class="form-horizontal" method="POST" action="products_delete.php">
        <div class="modal-body">
          <input type="hidden" class="prodid" name="id">
          <div class="text-center">
            <p>ARCHIVE PRODUCT</p>
            <h2 class="bold name"></h2>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
            <i class="fa fa-close"></i> Close
          </button>
          <button type="submit" class="btn btn-danger btn-flat" name="delete">
            <i class="fa fa-archive"></i> Archive
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
              <div class="image_show" id="image_show"></div>
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
            <label for="edit_sku" class="col-sm-1 control-label">SKU</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_sku" value="" readonly>
              <p class="help-block">SKU is generated automatically and locked once the product is created.</p>
            </div>
            <label class="col-sm-1 control-label"></label>
            <div class="col-sm-5"></div>
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
              <input type="text" class="form-control mt-1" id="edit_custom_material_values" name="edit_custom_material_values" placeholder="Custom materials (comma separated)">
            </div>

            <label for="edit_quantity" class="col-sm-1 control-label">Quantity</label>
            <div class="col-sm-5">
              <input type="number" min="0" class="form-control" id="edit_quantity" name="quantity">
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
              <input type="text" class="form-control mt-1" id="edit_custom_size_values" name="edit_custom_size_values" placeholder="Custom sizes (comma separated)">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_color_custom" class="col-sm-1 control-label">Color</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_custom_color_values" name="edit_custom_color_values" placeholder="Custom colors (comma separated)">
            </div>
          </div>

          <div class="form-group">
            <label for="edit_product_status" class="col-sm-1 control-label">Status</label>
            <div class="col-sm-5">
              <select class="form-control" id="edit_product_status" name="product_status">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-12 control-label text-left"><b>Additional Info</b></label>
          </div>
          <div class="form-group">
            <label for="edit_spec_fit" class="col-sm-1 control-label">Fit</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_fit" name="edit_spec_fit">
            </div>
            <label for="edit_spec_composition" class="col-sm-1 control-label">Composition</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_composition" name="edit_spec_composition">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_spec_care_instructions" class="col-sm-1 control-label">Care</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_care_instructions" name="edit_spec_care_instructions">
            </div>
            <label for="edit_spec_dimensions" class="col-sm-1 control-label">Dimensions</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_dimensions" name="edit_spec_dimensions">
            </div>
          </div>
          <div class="form-group">
            <label for="edit_spec_shipping_class" class="col-sm-1 control-label">Shipping</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_shipping_class" name="edit_spec_shipping_class">
            </div>
            <label for="edit_spec_origin" class="col-sm-1 control-label">Origin</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="edit_spec_origin" name="edit_spec_origin">
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
