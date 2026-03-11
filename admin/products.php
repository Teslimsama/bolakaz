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
              <div class="box-header with-border admin-list-toolbar">
                <div class="admin-list-toolbar-main">
                  <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm" id="addproduct"><i class="fa fa-plus"></i> Add Product</a>
                </div>
                <div class="admin-list-toolbar-filters">
                  <form class="form-inline" onsubmit="return false;">
                    <div class="form-group">
                      <label for="select_category">Category</label>
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
                    <button type="button" id="clear_product_filters" class="btn btn-default btn-sm">Reset</button>
                  </form>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Name</th>
                    <th>Photo</th>
                    <th>Price</th>
                    <th>Views Today</th>
                    <th>Status</th>
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
                        $isArchived = ((int)($row['product_status'] ?? 1) === 0);
                        $statusBadge = $isArchived
                          ? "<span class='label label-default'>Archived</span>"
                          : "<span class='label label-success'>Active</span>";
                        $archiveBtn = $isArchived
                          ? "<button class='btn btn-default btn-sm btn-flat' type='button' disabled><i class='fa fa-archive'></i> Archived</button>"
                          : "<button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-archive'></i> Archive</button>";
                        echo "
                          <tr class='" . ($isArchived ? "product-row-archived" : "") . "'>
                            <td>" . e($row['name']) . "</td>
                            <td>
                                <!-- Image Thumbnail -->
                                <img src='" . e($image) . " ' height='30px' width='30px' alt='Product Image' class='img-thumbnail' onerror=\"this.onerror=null;this.src='../images/storefront-placeholder.svg';\">
                            
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
                            <td>" . app_money($row['price']) . "</td>
                            <td>" . $counter . "</td>
                            <td>" . $statusBadge . "</td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              " . $archiveBtn . "
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
                <div id="productsMobileCards" class="products-mobile-cards"></div>
                <div id="productsMobilePagination" class="products-mobile-pagination"></div>
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
      $(document).on('click', '.image-delete-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
          deleteImage(id);
        }
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

      $('#clear_product_filters').on('click', function() {
        window.location = 'products.php';
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

      buildMobileProductCards();
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#example1')) {
        $('#example1').on('draw.dt order.dt search.dt', function() {
          buildMobileProductCards(1);
        });
      }
      $(window).on('resize', buildMobileProductCards);
      $(document).on('click', '#productsMobilePagination .page-link', function(e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10);
        if (!page || page < 1) {
          return;
        }
        buildMobileProductCards(page);
      });
      setupAddWizard();
      setupEditWizard();

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
          if (!response || !response.success) {
            $('#category').html("<option value='' selected>- Select -</option>");
            return;
          }
          $('#category').html("<option value='' selected>- Select -</option>" + (response.options || ''));
        }
      });
    }

    function getCategoryEdit(selectedCategoryId, selectedSubcategoryId, selectedCategoryName, selectedSubcategoryName) {
      function escapeHtml(value) {
        return $('<div>').text(value || '').html();
      }

      $.ajax({
        type: 'POST',
        url: 'category_fetch.php',
        data: {
          selected_id: selectedCategoryId || ''
        },
        dataType: 'json',
        success: function(response) {
          if (!response || !response.success) {
            $('#edit_category').html("<option value=''>- Select -</option>");
            return;
          }
          var options = "<option value=''>- Select -</option>" + (response.options || '');
          $('#edit_category').html(options);
          if (selectedCategoryId) {
            $('#edit_category').val(String(selectedCategoryId));
            if ($('#edit_category').val() !== String(selectedCategoryId)) {
              var fallbackLabel = selectedCategoryName || ('Category #' + selectedCategoryId);
              $('#edit_category').append("<option value='" + selectedCategoryId + "' selected>" + escapeHtml(fallbackLabel) + "</option>");
            }
          }

          // Fetch subcategories for selected category, then pre-select current one if available.
          var childCatId = selectedSubcategoryId || '';
          if (selectedCategoryId) {
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
                  if (childCatId && subCategoryOptions.indexOf("value='" + childCatId + "'") === -1) {
                    var subFallback = selectedSubcategoryName || ('Subcategory #' + childCatId);
                    subCategoryOptions += "<option value='" + childCatId + "' selected>" + escapeHtml(subFallback) + "</option>";
                  }
                  $('#edit_child_cat_id').html(subCategoryOptions);
                  $('#edit_child_cat_div').removeClass('d-none');
                } else if (childCatId) {
                  var selectedSubLabel = selectedSubcategoryName || ('Subcategory #' + childCatId);
                  $('#edit_child_cat_id').html("<option value=''>--Select Subcategory--</option><option value='" + childCatId + "' selected>" + escapeHtml(selectedSubLabel) + "</option>");
                  $('#edit_child_cat_div').removeClass('d-none');
                } else {
                  $('#edit_child_cat_id').html("<option value=''>--Select Subcategory--</option>");
                  $('#edit_child_cat_div').addClass('d-none');
                }
              },
              error: function() {
                alert('Failed to fetch subcategories. Please try again.');
              }
            });
          } else {
            $('#edit_child_cat_id').html("<option value=''>--Select Subcategory--</option>");
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
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (!response || !response.success) {
            $('#image_show').html('<div class="alert alert-danger">Unable to load gallery editor.</div>');
            return;
          }
          $('#image_show').html(response.html || '');
        },
        error: function() {
          $('#image_show').html('<div class="alert alert-danger">Unable to load gallery editor.</div>');
        }
      });
    }

    function setImageModalStatus(type, message) {
      var $status = $('#imageModalStatus');
      if (!$status.length) {
        return;
      }
      $status.removeClass('alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger');
      $status.text(message || '').show();
    }

    function lockGalleryButtons(lock) {
      $('#galleryUploadBtn').prop('disabled', lock);
      $('.image-delete-btn').prop('disabled', lock);
    }

    $(document).on('submit', '#galleryUploadForm', function(e) {
      e.preventDefault();
      var form = this;
      var formData = new FormData(form);
      lockGalleryButtons(true);
      setImageModalStatus('success', 'Uploading images...');

      $.ajax({
        url: $(form).attr('action') || 'image_actions.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(resp) {
          if (resp && resp.success) {
            setImageModalStatus('success', resp.message || 'Upload successful.');
            var productId = $('.prodid').first().val();
            if (productId) {
              getImages(productId);
            }
          } else {
            setImageModalStatus('error', (resp && resp.message) ? resp.message : 'Upload failed.');
          }
        },
        error: function(xhr) {
          var msg = 'Upload failed.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          }
          setImageModalStatus('error', msg);
        },
        complete: function() {
          lockGalleryButtons(false);
        }
      });
    });

    function deleteImage(id) {
      var result = confirm("Are you sure to delete?");
      if (result) {
        lockGalleryButtons(true);
        $.ajax({
          url: "image_actions.php",
          type: 'POST',
          dataType: 'json',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          data: {
            action_type: "img_delete",
            id: id,
            _csrf: $('meta[name="csrf-token"]').attr('content') || ''
          },
          success: function(resp) {
            if (resp && resp.success) {
              $('#imgb_' + id).remove();
              setImageModalStatus('success', resp.message || 'The image has been removed.');
            } else {
              setImageModalStatus('error', (resp && resp.message) ? resp.message : 'Unable to remove image.');
            }
          },
          error: function(xhr) {
            var msg = 'Unable to remove image.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              msg = xhr.responseJSON.message;
            }
            setImageModalStatus('error', msg);
          },
          complete: function() {
            lockGalleryButtons(false);
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

      function buildOptions(items, placeholder) {
        var html = '<option value="">' + placeholder + '</option>';
        (items || []).forEach(function(item) {
          html += '<option value="' + item + '">' + item + '</option>';
        });
        return html;
      }

      // Load normalized option values into both add/edit forms without duplicates
      function loadData() {
        $.getJSON('data.json', function(data) {
          var attributes = data.attributes || {};
          $('#size_1').html(buildOptions(attributes.sizes, '--Select any size--'));
          $('#edit_size_1').html(buildOptions(attributes.sizes, '--Select any size--'));

          $('#color_1').html(buildOptions(attributes.colors, '--Select any color--'));
          $('#edit_color_1').html(buildOptions(attributes.colors, '--Select any color--'));

          $('#material_1').html(buildOptions(attributes.materials, '--Select any material--'));
          $('#edit_material_1').html(buildOptions(attributes.materials, '--Select any material--'));

          $('.selectpicker').selectpicker('refresh');
        });
      }

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
          $('#edit_product_status').val(String(response.product_status || 1));
          $('#edit_spec_fit').val((response.additional_info && response.additional_info.fit) ? response.additional_info.fit : '');
          $('#edit_spec_composition').val((response.additional_info && response.additional_info.composition) ? response.additional_info.composition : '');
          $('#edit_spec_care_instructions').val((response.additional_info && response.additional_info.care_instructions) ? response.additional_info.care_instructions : '');
          $('#edit_spec_dimensions').val((response.additional_info && response.additional_info.dimensions) ? response.additional_info.dimensions : '');
          $('#edit_spec_shipping_class').val((response.additional_info && response.additional_info.shipping_class) ? response.additional_info.shipping_class : '');
          $('#edit_spec_origin').val((response.additional_info && response.additional_info.origin) ? response.additional_info.origin : '');
          $('#edit_custom_size_values').val('');
          $('#edit_custom_color_values').val('');
          $('#edit_custom_material_values').val('');
          CKEDITOR.instances["editor2"].setData(response.description);

          // Prepare pre-selected data for attributes
          var preSelectedData = {
            sizes: response.size || [], // Ensure default empty array
            colors: response.color || [],
            materials: response.material || []
          };

          // Call loadEditData with preSelectedData
          loadEditData(preSelectedData);
          getCategoryEdit(
            response.edit_category_id || response.category_id,
            response.edit_subcategory_id || response.subcategory_id,
            response.edit_category_name || response.catname,
            response.edit_subcategory_name || response.subcatname
          ); // Fetch category options for editing

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

    function buildMobileProductCards(page) {
      var $cards = $('#productsMobileCards');
      var $pager = $('#productsMobilePagination');
      if (!$cards.length) {
        return;
      }
      $cards.empty();
      $pager.empty();

      if (window.innerWidth > 768) {
        $cards.hide();
        $pager.hide();
        return;
      }

      var rows = [];
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#example1')) {
        rows = $('#example1').DataTable().rows({
          search: 'applied',
          order: 'applied'
        }).nodes().toArray();
      } else {
        rows = $('#example1 tbody tr').toArray();
      }
      var perPage = 8;
      var totalRows = rows.length;
      var totalPages = Math.max(1, Math.ceil(totalRows / perPage));
      var currentPage = parseInt(page || $cards.data('currentPage') || 1, 10);
      if (currentPage > totalPages) {
        currentPage = totalPages;
      }
      if (currentPage < 1) {
        currentPage = 1;
      }
      $cards.data('currentPage', currentPage);

      var start = (currentPage - 1) * perPage;
      var end = Math.min(start + perPage, totalRows);

      $(rows.slice(start, end)).each(function() {
        var $tr = $(this);
        if ($tr.hasClass('child')) {
          return;
        }
        var name = $.trim($tr.find('td').eq(0).text());
        var imgSrc = $tr.find('td').eq(1).find('img').attr('src') || '../images/noimage.jpg';
        var price = $.trim($tr.find('td').eq(2).text());
        var status = $.trim($tr.find('td').eq(4).text());
        var qty = $.trim($tr.find('td').eq(10).text());
        var editId = $tr.find('.edit').data('id');
        var deleteId = $tr.find('.delete').data('id');
        var archived = $tr.hasClass('product-row-archived');

        var card = '' +
          '<div class="mobile-product-card ' + (archived ? 'mobile-product-card-archived' : '') + '">' +
          '<div class="mobile-product-main">' +
          '<img src="' + imgSrc + '" alt="' + $('<div>').text(name).html() + '" class="mobile-product-thumb">' +
          '<div class="mobile-product-meta">' +
          '<h5>' + $('<div>').text(name).html() + '</h5>' +
          '<p><strong>' + $('<div>').text(price).html() + '</strong></p>' +
          '<p>Status: ' + $('<div>').text(status).html() + '</p>' +
          '<p>Qty: ' + $('<div>').text(qty).html() + '</p>' +
          '</div>' +
          '</div>' +
          '<div class="mobile-product-actions">' +
          '<button class="btn btn-success btn-sm edit" data-id="' + editId + '"><i class="fa fa-edit"></i> Edit</button> ' +
          (archived
            ? '<button class="btn btn-default btn-sm" type="button" disabled><i class="fa fa-archive"></i> Archived</button>'
            : '<button class="btn btn-danger btn-sm delete" data-id="' + deleteId + '"><i class="fa fa-archive"></i> Archive</button>') +
          '</div>' +
          '</div>';
        $cards.append(card);
      });

      if (totalRows > perPage) {
        var paginationHtml = '<ul class="pagination justify-content-center mb-3">';
        paginationHtml += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">';
        paginationHtml += '<a href="#" class="page-link" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';

        for (var i = 1; i <= totalPages; i++) {
          paginationHtml += '<li class="page-item ' + (i === currentPage ? 'active' : '') + '">';
          paginationHtml += '<a href="#" class="page-link" data-page="' + i + '">' + i + '</a></li>';
        }

        paginationHtml += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">';
        paginationHtml += '<a href="#" class="page-link" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';
        paginationHtml += '</ul>';
        $pager.html(paginationHtml).show();
      } else {
        $pager.hide();
      }

      $cards.show();
    }

    function setupAddWizard() {
      var $modal = $('#addnew');
      var $form = $modal.find('form');
      var $body = $modal.find('.modal-body');
      var $groups = $body.find('.form-group');
      if (!$groups.length || $form.data('wizardReady')) {
        return;
      }

      var steps = [[], [], []];
      $groups.each(function(index) {
        if ([0, 1, 4, 6].indexOf(index) !== -1) {
          steps[0].push(this);
        } else if ([2, 5, 7, 8, 9, 10].indexOf(index) !== -1) {
          steps[1].push(this);
        } else {
          steps[2].push(this);
        }
      });

      var $descHeading = $body.find('p > b').filter(function() {
        return $.trim($(this).text()).toLowerCase() === 'description';
      }).closest('p');
      if ($descHeading.length) {
        steps[2].push($descHeading.get(0));
      }

      var $nav = $('<div class="wizard-nav"><span class="wizard-dot active">1</span><span class="wizard-dot">2</span><span class="wizard-dot">3</span></div>');
      $body.prepend($nav);
      var $controls = $('<div class="wizard-controls"><button type="button" class="btn btn-default btn-flat wizard-back" disabled>Back</button><button type="button" class="btn btn-primary btn-flat wizard-next">Next</button></div>');
      $body.append($controls);

      var currentStep = 0;
      function render() {
        $groups.hide();
        if ($descHeading.length) {
          $descHeading.hide();
        }
        $(steps[currentStep]).show();
        $nav.find('.wizard-dot').removeClass('active').eq(currentStep).addClass('active');
        $controls.find('.wizard-back').prop('disabled', currentStep === 0);
        var isLast = currentStep === 2;
        $controls.find('.wizard-next').toggle(!isLast);
        $form.closest('.modal-content').find('.modal-footer button[name="add"]').toggle(isLast);
      }

      $controls.on('click', '.wizard-next', function() {
        if (currentStep < 2) {
          currentStep++;
          render();
        }
      });
      $controls.on('click', '.wizard-back', function() {
        if (currentStep > 0) {
          currentStep--;
          render();
        }
      });
      $modal.on('shown.bs.modal', function() {
        currentStep = 0;
        render();
      });
      $form.data('wizardReady', true);
    }

    function setupEditWizard() {
      var $modal = $('#edit');
      var $form = $modal.find('form');
      var $body = $modal.find('.modal-body');
      var $groups = $body.find('.form-group');
      if (!$groups.length || $form.data('wizardReady')) {
        return;
      }

      var stepA = [];
      var stepB = [];
      $groups.each(function(index) {
        if (index <= 4) {
          stepA.push(this);
        } else {
          stepB.push(this);
        }
      });

      var $descHeading = $body.find('p > b').filter(function() {
        return $.trim($(this).text()).toLowerCase() === 'description';
      }).closest('p');
      if ($descHeading.length) {
        stepB.push($descHeading.get(0));
      }

      var $nav = $('<div class="wizard-nav"><span class="wizard-dot active">1</span><span class="wizard-dot">2</span></div>');
      $body.prepend($nav);
      var $controls = $('<div class="wizard-controls"><button type="button" class="btn btn-default btn-flat wizard-back" disabled>Back</button><button type="button" class="btn btn-primary btn-flat wizard-next">Next</button></div>');
      $body.append($controls);

      var currentStep = 0;
      function render() {
        $groups.hide();
        if ($descHeading.length) {
          $descHeading.hide();
        }
        if (currentStep === 0) {
          $(stepA).show();
        } else {
          $(stepB).show();
        }
        $nav.find('.wizard-dot').removeClass('active').eq(currentStep).addClass('active');
        $controls.find('.wizard-back').prop('disabled', currentStep === 0);
        var isLast = currentStep === 1;
        $controls.find('.wizard-next').toggle(!isLast);
        $form.closest('.modal-content').find('.modal-footer button[name="edit"]').toggle(isLast);
      }

      $controls.on('click', '.wizard-next', function() {
        if (currentStep < 1) {
          currentStep++;
          render();
        }
      });
      $controls.on('click', '.wizard-back', function() {
        if (currentStep > 0) {
          currentStep--;
          render();
        }
      });

      $modal.on('shown.bs.modal', function() {
        currentStep = 0;
        render();
      });
      $form.data('wizardReady', true);
    }
  </script>
  <style>
    .products-mobile-cards { display: none; }
    .products-mobile-pagination { display: none; }
    @media (max-width: 768px) {
      #example1_wrapper { display: none; }
      .products-mobile-cards { display: block; }
      .products-mobile-pagination {
        display: flex;
        justify-content: center;
        width: 100%;
      }
      .products-mobile-pagination .pagination {
        margin-left: auto;
        margin-right: auto;
      }
      .mobile-product-card {
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fff;
      }
      .mobile-product-main { display: flex; gap: 10px; }
      .mobile-product-thumb {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        object-fit: cover;
      }
      .mobile-product-meta h5 {
        margin: 0 0 4px 0;
        font-size: 14px;
      }
      .mobile-product-meta p {
        margin: 0 0 2px 0;
        font-size: 13px;
      }
      .mobile-product-actions {
        margin-top: 8px;
        display: flex;
        gap: 8px;
      }
    .mobile-product-actions .btn {
        flex: 1;
      }
      .mobile-product-card-archived {
        opacity: 0.75;
        border-style: dashed;
      }
    }
    .product-row-archived {
      opacity: 0.72;
      background: #fafafa;
    }
    .wizard-nav {
      display: flex;
      gap: 8px;
      margin-bottom: 12px;
    }
    .wizard-dot {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 1px solid #d2d6de;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #666;
      font-weight: 700;
    }
    .wizard-dot.active {
      background: #3c8dbc;
      border-color: #3c8dbc;
      color: #fff;
    }
    .wizard-controls {
      margin-top: 12px;
      display: flex;
      justify-content: space-between;
      gap: 8px;
    }
    .image-gallery-grid {
      margin-top: 12px;
    }
    .image-card {
      border: 1px solid #e6e6e6;
      border-radius: 8px;
      padding: 8px;
      margin-bottom: 12px;
      background: #fff;
    }
    .image-card-main {
      border-style: dashed;
    }
    .image-card-img {
      width: 100%;
      max-width: 220px;
      height: 140px;
      object-fit: cover;
      border-radius: 6px;
      display: block;
      margin: 0 auto;
    }
    .image-card-meta {
      margin-top: 8px;
      text-align: center;
    }
  </style>
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> -->
</body>

</html>

