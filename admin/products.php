<?php include 'session.php'; ?>
<?php
$catid = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$catid = ($catid !== false && $catid !== null) ? $catid : null;
?>
<?php include 'header.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
  <div class="wrapper">

    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <h1>
          Product List
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li>Products</li>
          <li class="active">Product List</li>
        </ol>
      </section>

      <!-- Main content -->
      <section class="content">
        <?php
        if (isset($_SESSION['error'])) {
          echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              " . e($_SESSION['error']) . "
            </div>
          ";
          unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              " . e($_SESSION['success']) . "
            </div>
          ";
          unset($_SESSION['success']);
        }
        ?>
        <div class="row">
          <div class="col-xs-12">
            <div class="box">
              <div class="box-header with-border">
                <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm btn-flat" id="addproduct"><i class="fa fa-plus"></i> New</a>
                <div class="pull-right">
                  <form class="form-inline">
                    <div class="form-group">
                      <label>Category: </label>
                      <select class="form-control input-sm" id="select_category">
                        <option value="0">ALL</option>
                        <?php
                        $conn = $pdo->open();

                        $stmt = $conn->prepare("SELECT * FROM category");
                        $stmt->execute();

                        foreach ($stmt as $crow) {
                          $selected = ((int)$crow['id'] === (int)$catid) ? 'selected' : '';
                          echo "
                            <option value='" . (int)$crow['id'] . "' " . $selected . ">" . e($crow['name']) . "</option>
                          ";
                        }

                        $pdo->close();
                        ?>
                      </select>
                    </div>
                  </form>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Name</th>
                    <th>Photo</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Views Today</th>
                    <th>Tools</th>
                    <th>Color</th>
                    <th>Brand</th>
                    <th>Size</th>
                    <th>Material</th>
                    <th>Quantity</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $now = date('Y-m-d');
                      $sql = "SELECT * FROM products";
                      if ($catid !== null) {
                        $sql .= " WHERE category_id = :category_id";
                      }
                      $stmt = $conn->prepare($sql);
                      $stmt->execute($catid !== null ? ['category_id' => $catid] : []);
                      foreach ($stmt as $row) {
                        $image = (!empty($row['photo'])) ? '../images/' . $row['photo'] : '../images/noimage.jpg';
                        $counter = ($row['date_view'] == $now) ? $row['counter'] : 0;
                        echo "
                          <tr>
                            <td>" . e($row['name']) . "</td>
                            <td>
                                <!-- Image Thumbnail -->
                                <img src='" . e($image) . " ' height='30px' width='30px' alt='Product Image' class='img-thumbnail'>
                            
                                <!-- Action Links -->
                                <div class='action-links text-right'>
                                    <!-- Single Edit Link -->
                                    <a href='#edit_photo' class='photo btn btn-sm btn-link' data-toggle='modal' data-id='" . (int)$row['id'] . "'>
                                        Single <i class='fa fa-edit'></i>
                                    </a>
                            
                                    <!-- Multiple Edit Link -->
                                    <a href='#image_edit' class='photo image btn btn-sm btn-link' data-id='" . (int)$row['id'] . "'>
                                        Multiple <i class='fa fa-edit'></i>
                                    </a>
                                </div>
                            </td>
                            <td><a href='#description' data-toggle='modal' class='btn btn-info btn-sm btn-flat desc' data-id='" . (int)$row['id'] . "'><i class='fa fa-search'></i> View</a></td>
                            <td>₦" . number_format((float)$row['price'], 2) . "</td>
                            <td>" . $counter . "</td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
                            <td>" . e($row['color']) . "</td>
                            <td>" . e($row['brand']) . "</td>
                            <td>" . e($row['size']) . "</td>
                            <td>" . e($row['material']) . "</td>
                            <td>" . (int)$row['qty'] . "</td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo 'Unable to load products.';
                    }

                    $pdo->close();
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
    <?php include 'footer.php'; ?>
    <?php include 'products_modal.php'; ?>
    <?php include 'products_modal2.php'; ?>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
  <script>
    $(function() {


      $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        $('#delete').modal('show');
        var id = $(this).data('id');
        getRow(id);
      });

      $(document).on('click', '.photo', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        getRow(id);
      });
      $(document).on('click', '.image', function(e) {
        e.preventDefault();
        $('#image_edit').modal('show');
        var id = $(this).data('id');
        getImages(id);
      });
      $(document).on('click', '.desc', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        getRow(id);
      });

      $('#select_category').change(function() {
        var val = $(this).val();
        if (val == 0) {
          window.location = 'products.php';
        } else {
          window.location = 'products.php?category=' + val;
        }
      });

      $('#addproduct').click(function(e) {
        e.preventDefault();
        getCategory();
      });

      $("#addnew").on("hidden.bs.modal", function() {
        $('.append_items').remove();
      });

      $("#edit").on("hidden.bs.modal", function() {
        $('.append_items').remove();
      });

    });
    // Event listener for the 'edit' button
    $(document).on('click', '.edit', function(e) {
      e.preventDefault();
      $('#edit').modal('show');
      var id = $(this).data('id'); // Get the ID from data attribute
      getRow(id); // Fetch product row data
    });

    function getCategory() {
      $.ajax({
        type: 'POST',
        url: 'category_fetch.php',
        dataType: 'json',
        success: function(response) {
          $('#category').append(response);
        }
      });
    }

    function getCategoryEdit(selectedCategoryId, selectedSubcategoryId) {
      $.ajax({
        type: 'POST',
        url: 'category_fetch.php',
        dataType: 'json',
        success: function(response) {
          $('#edit_category').append(response);

          // Pre-select subcategory if already set
          var childCatId = selectedSubcategoryId;
          if (childCatId) {
            $('#edit_child_cat_div').removeClass('d-none');
            console.log(selectedCategoryId, selectedSubcategoryId)
            // Fetch and populate subcategories
            $.ajax({
              url: 'subcategory_fetch.php',
              type: 'POST',
              data: {
                id: selectedCategoryId
              },
              dataType: 'json',
              success: function(subResponse) {
                if (subResponse.status) {
                  var subCategoryOptions = "<option value=''>--Select Subcategory--</option>";
                  $.each(subResponse.data, function(index, subcategory) {
                    subCategoryOptions +=
                      "<option value='" + subcategory.id + "' " +
                      (childCatId == subcategory.id ? 'selected' : '') +
                      ">" + subcategory.name + "</option>";
                  });
                  $('#edit_child_cat_id').html(subCategoryOptions);
                }
              },
              error: function() {
                alert('Failed to fetch subcategories. Please try again.');
              }
            });
          } else {
            $('#edit_child_cat_div').addClass('d-none');
          }
        },
        error: function() {
          alert('Failed to fetch categories. Please try again.');
        }
      });
    }

    // function EditCatgory() {
      // Handle category change
      $('#edit_category').change(function() {
        var selectedCategoryId = $(this).val();
        if (selectedCategoryId) {
          // Fetch subcategories for the selected category
          $.ajax({
            url: 'subcategory_fetch.php',
            type: 'POST',
            data: {
              id: selectedCategoryId
            },
            dataType: 'json',
            success: function(subResponse) {
              if (subResponse.status) {
                var subCategoryOptions = "<option value=''>--Select Subcategory--</option>";
                $.each(subResponse.data, function(index, subcategory) {
                  subCategoryOptions +=
                    "<option value='" + subcategory.id + "'>" + subcategory.name + "</option>";
                });
                $('#edit_child_cat_id').html(subCategoryOptions);
                $('#edit_child_cat_div').removeClass('d-none');
              } else {
                $('#edit_child_cat_id').html("");
                $('#edit_child_cat_div').addClass('d-none');
              }
            },
            error: function() {
              alert('Failed to fetch subcategories. Please try again.');
            }
          });
        } else {
          $('#edit_child_cat_id').html("");
          $('#edit_child_cat_div').addClass('d-none');
        }
      });

     
    // }

    function getImages(id) {
      $.ajax({
        type: 'POST',
        url: 'image_modal.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          $('#image_show').html(response);
          // $('#edit_category').append(response);
        }
      });
    }

    function deleteImage(id) {
      var result = confirm("Are you sure to delete?");
      if (result) {
        $.post("image_actions.php", {
          action_type: "img_delete",
          id: id
        }, function(resp) {
          if (resp == 'ok') {
            console.log(resp);
            $('#imgb_' + id).remove();
            alert('The image has been removed from the gallery');
          } else {
            alert('Some problem occurred, please try again.');
          }
        });
      }
    }
    $('#category').change(function() {
      var cat_id = $(this).val();
      // console.log(cat_id);
      if (cat_id) {
        // AJAX request
        $.ajax({
          url: 'subcategory_fetch.php', // Path to your PHP script
          type: 'POST',
          data: {
            id: cat_id
          },
          success: function(response) {
            var html_option = "<option value=''>--Select sub category--</option>";
            response = JSON.parse(response);

            if (response.status) {
              var data = response.data;
              if (data) {
                $('#child_cat_div').removeClass('d-none');
                $.each(data, function(index, item) {
                  html_option += "<option value='" + item.id + "'>" + item.name + "</option>";
                });
              }
            } else {
              $('#child_cat_div').addClass('d-none');
            }
            $('#child_cat_id').html(html_option);
          }
        });
      } else {
        $('#child_cat_div').addClass('d-none');
        $('#child_cat_id').html("<option value=''>--Select sub category--</option>");
      }
    });
    $(document).ready(function() {
      // Initialize the selectpicker
      $('.selectpicker').selectpicker();
      // Load data from JSON file
      function loadData() {
        $.getJSON('data.json', function(data) {
          var attributes = data.attributes;
          // Populate sizes
          var sizeOptions = '';
          attributes.sizes.forEach(function(size) {
            sizeOptions += '<option value="' + size + '">' + size + '</option>';
          });
          $('#edit_size_1').append(sizeOptions);
          $('.selectpicker').selectpicker('refresh');

          // Populate colors
          var colorOptions = '';
          attributes.colors.forEach(function(color) {
            colorOptions += '<option value="' + color + '">' + color + '</option>';
          });
          $('#edit_color_1').append(colorOptions);
          $('.selectpicker').selectpicker('refresh');
          // Populate colors
          var materialOptions = '';
          attributes.materials.forEach(function(material) {
            materialOptions += '<option value="' + material + '">' + material + '</option>';
          });
          $('#edit_material_1').append(materialOptions);
          $('.selectpicker').selectpicker('refresh');
        });
      }

      // Call the function to load data
      loadData();
    });



    // Function to fetch product details and prepopulate the form
    function getRow(id) {
      $.ajax({
        type: 'POST',
        url: 'products_row.php', // API endpoint
        data: {
          id: id
        }, // Send ID as POST data
        dataType: 'json', // Expect JSON response
        success: function(response) {
          // Populate modal fields with response data
          $('#desc').html(response.description);
          $('.name').html(response.prodname);
          $('.prodid').val(response.prodid);
          $('#edit_name').val(response.prodname);
          $('#catselected').val(response.category_id).html(response.catname);
          $('#edit_price').val(response.price);
          $('#edit_quantity').val(response.qty);
          $('#edit_brand').val(response.brand);
          CKEDITOR.instances["editor2"].setData(response.description);

          // Prepare pre-selected data for attributes
          var preSelectedData = {
            sizes: response.size || [], // Ensure default empty array
            colors: response.color || [],
            materials: response.material || []
          };

          // Call loadEditData with preSelectedData
          loadEditData(preSelectedData);
          getCategoryEdit(response.category_id, response.subcategory_id); // Fetch category options for editing

        },
        error: function() {
          alert('Failed to fetch product data. Please try again.');
        }
      });
    }

    // Function to populate the dropdowns with attributes and pre-select values
    function loadEditData(preSelectedData) {
      $.getJSON('data.json', function(data) {
        var attributes = data.attributes;

        // Populate sizes
        var sizeOptions = '';
        attributes.sizes.forEach(function(size) {
          // Check if the current size is pre-selected
          var isSelected = preSelectedData.sizes.includes(size) ? 'selected' : '';
          sizeOptions += '<option value="' + size + '" ' + isSelected + '>' + size + '</option>';
        });
        $('#edit_size_1').html(sizeOptions); // Replace content
        $('.selectpicker').selectpicker('refresh');

        // Populate colors
        var colorOptions = '';
        attributes.colors.forEach(function(color) {
          var isSelected = preSelectedData.colors.includes(color) ? 'selected' : '';
          colorOptions += '<option value="' + color + '" ' + isSelected + '>' + color + '</option>';
        });
        $('#edit_color_1').html(colorOptions); // Replace content
        $('.selectpicker').selectpicker('refresh');

        // Populate materials
        var materialOptions = '';
        attributes.materials.forEach(function(material) {
          var isSelected = preSelectedData.materials.includes(material) ? 'selected' : '';
          materialOptions += '<option value="' + material + '" ' + isSelected + '>' + material + '</option>';
        });
        $('#edit_material_1').html(materialOptions); // Replace content
        $('.selectpicker').selectpicker('refresh');
      });
    }
  </script>
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> -->
</body>

</html>

