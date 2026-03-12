<?php include 'session.php'; ?>
<?php include 'header.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<body class="hold-transition skin-blue sidebar-mini">

    <div class="wrapper"><?php include 'navbar.php'; ?>
        <?php include 'menubar.php'; ?>
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
                <form action="">

                    <div class="form-group">
                        <label for="size">Size</label>
                        <select name="size[]" id="size" class="form-control selectpicker" multiple data-live-search="true">
                            <option value="">--Select any size--</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new-size">Add New Size</label>
                        <div class="input-group">
                            <input type="text" id="new-size" class="form-control" placeholder="Enter new size">
                            <button class="btn btn-primary" id="add-size-btn">Add Size</button>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label for="color">Color</label>
                        <select name="color[]" id="color" class="form-control selectpicker" multiple data-live-search="true">
                            <option value="">--Select any color--</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new-color">Add New Color</label>
                        <div class="input-group">
                            <input type="text" id="new-color" class="form-control" placeholder="Enter new color">
                            <button class="btn btn-primary" id="add-color-btn">Add Color</button>
                        </div>
                    </div>
                </form>

            </section>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'products_modal.php'; ?>
    <?php include 'products_modal2.php'; ?>
    <?php include 'scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <script>
        $.getJSON('data.json')
            .done(function(data) {
                // Populate options=
                console.log(data.sizes)
            })
            .fail(function() {
                alert('Error loading data.json');
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
                    $('#size').append(sizeOptions);
                    $('.selectpicker').selectpicker('refresh');

                    // Populate colors
                    var colorOptions = '';
                    attributes.colors.forEach(function(color) {
                        colorOptions += '<option value="' + color + '">' + color + '</option>';
                    });
                    $('#color').append(colorOptions);
                    $('.selectpicker').selectpicker('refresh');
                });
            }

            // Call the function to load data
            loadData();

            // Handle "Add New Size" button click
            $('#add-size-btn').click(function(e) {
                e.preventDefault();
                var newSize = $('#new-size').val().trim();
                if (newSize) {
                    if ($('#size option').filter(function() {
                            return $(this).text() === newSize;
                        }).length) {
                        alert('This size already exists!');
                    } else {
                        $('#size').append('<option value="' + newSize + '">' + newSize + '</option>');
                        $('.selectpicker').selectpicker('refresh');
                        $('#new-size').val('');
                    }
                } else {
                    alert('Please enter a valid size!');
                }
            });

            // Handle "Add New Color" button click
            $('#add-color-btn').click(function(e) {
                e.preventDefault();
                var newColor = $('#new-color').val().trim();
                if (newColor) {
                    if ($('#color option').filter(function() {
                            return $(this).text() === newColor;
                        }).length) {
                        alert('This color already exists!');
                    } else {
                        $('#color').append('<option value="' + newColor + '">' + newColor + '</option>');
                        $('.selectpicker').selectpicker('refresh');
                        $('#new-color').val('');
                    }
                } else {
                    alert('Please enter a valid color!');
                }
            });
        });
    </script>
</body>