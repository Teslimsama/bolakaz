<!-- Description -->
<div class="modal fade" id="description">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b><span class="name"></span></b></h4>
      </div>
      <div class="modal-body">
        <p id="desc"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add -->
<div class="modal fade" id="addnew">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title"><b>Add New Product</b></h4>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="products_add.php" enctype="multipart/form-data">

          <!-- Product Name and Category -->
          <div class="form-group">
            <label for="name" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <label for="category" class="col-sm-1 control-label">Category</label>
            <div class="col-sm-5">
              <select class="form-control" id="category" name="category" required>
                <option value="" selected>- Select -<?php echo ""; ?></option>
              </select>
            </div>
          </div>
          <!-- subcategory -->

          <div class="form-group d-none" id="child_cat_div">
            <label for="child_cat_id" class="col-sm-1 control-label">Sub Category</label>
            <div class="col-sm-5">
              <select name="child_cat_id" id="child_cat_id" class="form-control">
                <option value="">--Select any category--</option>
              </select>
            </div>
          </div>
          <!-- Price and Material -->
          <div class="form-group">
            <label for="price" class="col-sm-1 control-label">Price</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="price" name="price" required>
            </div>
            <label for="material" class="col-sm-1 control-label">Material</label>
            <div class="col-sm-5">
              <select name="material[]" id="material_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any color--</option>
              </select>
            </div>
          </div>

          <!-- Main Photo and Gallery Images -->
          <div class="form-group">
            <label for="photo" class="col-sm-1 control-label">Main Photo</label>
            <div class="col-sm-5">
              <input type="file" id="photo" name="photo" accept=".jpg, .jpeg, .png, .gif , .webp">
            </div>
            <label for="images" class="col-sm-1 control-label">Gallery Images (Multiple)</label>
            <div class="col-sm-5">
              <input type="file" id="images" name="images[]" accept=".jpg, .jpeg, .png, .gif, .webp" multiple>
            </div>
          </div>

          <!-- Quantity -->
          <div class="form-group">
            <label for="quantity" class="col-sm-1 control-label">Quantity</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="quantity_1" name="quantity">
            </div>
            <label for="brand" class="col-sm-1 control-label">Brand</label>
            <div class="col-sm-5">
              <input type="text" class="form-control" id="brand_1" name="brand" required>
            </div>
          </div>

          <!--  Size and Color -->
          <div class="form-group">
            <label for="size" class="col-sm-1 control-label">Size</label>
            <div class="col-sm-5">
              <select name="size[]" id="size_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any size--</option>
              </select>
            </div>
            <label for="color" class="col-sm-1 control-label">Color</label>
            <div class="col-sm-5 mt-2">
              <select name="color[]" id="color_1" class="form-control selectpicker" multiple data-live-search="true">
                <option value="">--Select any color--</option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div class="form-group">
            <p><b>Description</b></p>
            <div class="col-sm-12">
              <textarea id="editor1" name="description" rows="10" cols="80" required></textarea>
            </div>
          </div>


      </div>

      <!-- Modal Footer -->
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

<!-- Update Photo -->
<div class="modal fade" id="edit_photo">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><b><span class="name"></span></b></h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="products_photo.php" enctype="multipart/form-data">
          <input type="hidden" class="prodid" name="id">
          <div class="form-group">
            <label for="photo" class="col-sm-3 control-label">Photo</label>

            <div class="col-sm-9">
              <input type="file" id="photo" name="photo" required>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
        <button type="submit" class="btn btn-success btn-flat" name="upload"><i class="fa fa-check-square-o"></i> Update</button>
        </form>
      </div>
    </div>
  </div>
</div>