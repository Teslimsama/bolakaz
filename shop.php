<?php include 'includes/session.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title Page-->
    <title>Bolakaz</title>

    <!-- Favicon -->
    <!-- favicon  -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">


    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

    <?php
    include "includes/header.php"; ?>
    <?php include 'includes/navbar.php'; ?>


    <input type="hidden" value="">
    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Our Shop</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="index">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Shop</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Shop Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5">
            <!-- Shop Sidebar Start -->
            <div class="col-lg-3 col-md-12">
                <!-- Price Start -->
                <div id="price_filter" class="border-bottom mb-4 pb-4">


                </div>
                <!-- Price End -->

                <!-- Color Start -->
                <div id="gender_filter" class="border-bottom mb-4 pb-4">



                </div>
                <!-- Color End -->

                <!-- Size Start -->
                <div class="mb-5">
                    <h5 class="font-weight-semi-bold mb-4">Filter by size</h5>



                </div>
                <!-- Size End -->
            </div>
            <!-- Shop Sidebar End -->


            <!-- Shop Product Start -->
            <div class="col-lg-9 col-md-12">
                <div class="row pb-3">
                    <div class="col-12 pb-1">
                        <div class="d-flex align-items-center justify-content-between mb-4">

                            <div class="input-group">
                                <input type="text" class="form-control" name="search" class="form-control" id="search" onkeyup="load_data(this.value);" placeholder="Search by name">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-transparent text-primary">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="dropdown ml-4">
                                <button class="btn border dropdown-toggle" type="button" id="triggerId" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Sort by
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
                                    <a class="dropdown-item" href="#">Latest</a><span id="total_data"></span>
                                    <a class="dropdown-item" href="#">Popularity</a>
                                    <a class="dropdown-item" href="#">Best Rating</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="product_area">

                    </div>
                    <div id="skeleton_area">

                    </div>





                    <div id="pagination_area" class="col-12 pb-1">

                    </div>
                </div>
            </div>
            <!-- Shop Product End -->
        </div>
    </div>
    <!-- Shop End -->
    <script>
        function $(selector) {
            return document.querySelector(selector);
        }

        load_product(1, '');

        function load_product(page = 1, query = '', category = '') {
            function cat() {
                fetch('shop.php?category=' + element ).then(function(response) {
                    // var form_data = new FormData();
                    const cat = window.location.search;
                    const o = cat.split('?')[1];
                    const getcat = new URLSearchParams(o);
                    for (let pair of getcat.entries()) {
                        const element = pair[1];
                        // form_data.append('category', element);
                    }
                    return response.json();

                })

            }





            $('#product_area').style.display = 'none';

            $('#skeleton_area').style.display = 'block';

            $('#skeleton_area').innerHTML = make_skeleton();

            fetch('process.php?page=' + page + query + '').then(function(response) {

                return response.json();

            }).then(function(responseData) {

                var t_html = '';
                if (responseData.data) {
                    if (responseData.data.length > 0) {
                        t_html += '<p class="h6">' + responseData.total_data + ' Products Found</p>';
                        // t_html += '<div class="row">';
                        for (var i = 0; i < responseData.data.length; i++) {
                            t_html += ` 
                            <div  class='col-lg-4 col-md-6 col-sm-12 pb-1'>
                            <form method="get" id="productForm">
                                    <div class="card product-item border-0 mb-4">
                                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                                            <img class="img-fluid w-100" src="./images/` + response.data[count].photo + `" alt="image">
                                        </div>`;
                            t_html += `
                            <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                                    <h6 class="text-truncate mb-3"><a href="detail.php?product=` + response.data[count].slug + `"> ` + response.data[count].name + `</a></h6>
                                <div class="d-flex justify-content-center"> 
                                <h6 class="text-truncate mb-3">&#36; ` + response.data[count].price + `</h6>
                                    <h6 class="badge border font-weight-normal ml-2"><del>&#36;` + response.data[count].price + `</del></h6>
                                </div>`;
                            t_html += ` 
                            </div>
                            <div class="card-footer d-flex justify-content-center bg-light border">
                                <a href="detail.php?product=` + response.data[count].slug + `">
                                 <i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                               
                            `;
                            t_html += `  
                            </div>
                             </div>
                             </form>
                             </div>
                             
                             `;
                            // t_html += '</div>';
                        }
                        // t_html += '</div>';
                        $('#product_area').innerHTML = t_html;
                    } else {
                        $('#product_area').innerHTML = '<p class="h6">No Product Found</p>';
                    }
                }

                if (responseData.pagination) {
                    $('#pagination_area').innerHTML = responseData.pagination;
                }

                setTimeout(function() {

                    $('#product_area').style.display = 'block';

                    $('#skeleton_area').style.display = 'none';

                }, 3000);

            });
        }

        function make_skeleton() {
            var output = '<div class="row">';
            for (var count = 0; count < 8; count++) {
                output += '<div class="col-md-3 mb-3 p-4">';
                output += '<div class="mb-2 bg-light text-dark" style="height:240px;"></div>';
                output += '<div class="mb-2 bg-light text-dark" style="height:50px;"></div>';
                output += '<div class="mb-2 bg-light text-dark" style="height:25px;"></div>';
                output += '</div>';
            }
            output += '</div>';
            return output;
        }

        load_filter();

        function load_filter() {
            fetch('process.php?action=filter').then(function(response) {

                return response.json();

            }).then(function(responseData) {

                if (responseData.gender) {
                    if (responseData.gender.length > 0) {
                        var html = '<h5 class="font-weight-semi-bold mb-4">Filter by color</h5> <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-3">';
                        for (var i = 0; i < responseData.gender.length; i++) {
                            html += '<label class="custom-control-label">';

                            html += '<input class="custom-control-input  gender_filter" type="radio" name="gender_filter" value="' + responseData.gender[i].name + '">';

                            html += responseData.gender[i].name + ' <span class="badge border font-weight-normal">(' + responseData.gender[i].total + ')</span>';

                            html += '</label>';
                        }

                        html += '</div>';

                        $('#gender_filter').innerHTML = html;

                        var gender_elements = document.getElementsByClassName("gender_filter");

                        for (var i = 0; i < gender_elements.length; i++) {
                            gender_elements[i].onclick = function() {

                                load_product(page = 1, make_query());

                            };
                        }
                    }
                }

                if (responseData.brand) {
                    if (responseData.brand.length > 0) {
                        var html = '<div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-3">';
                        for (var i = 0; i < responseData.brand.length; i++) {
                            html += '<label class="custom-control-label">';

                            html += '<input class="custom-control-input  brand_filter" type="checkbox" value="' + responseData.brand[i].name + '">';

                            html += responseData.brand[i].name + ' <span class="badge border font-weight-normal">(' + responseData.brand[i].total + ')</span>';

                            html += '</label>';
                        }

                        html += '</div>';

                        $('#brand_filter').innerHTML = html;

                        var brand_elements = document.getElementsByClassName("brand_filter");

                        for (var i = 0; i < brand_elements.length; i++) {
                            brand_elements[i].onclick = function() {

                                load_product(page = 1, make_query());

                            };
                        }
                    }
                }

                if (responseData.price) {
                    if (responseData.price.length > 0) {
                        var html = '<h5 class = "font-weight-semi-bold mb-4"> Filter by price </h5> <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-3" > ';

                        for (var i = 0; i < responseData.price.length; i++) {
                            html += '<a href="#" class="list-group-item list-group-item-action price_filter" id="' + responseData.price[i].condition + '"><span>&#8377;</span> ' + responseData.price[i].name + ' <span class="badge border font-weight-normal">(' + responseData.price[i].total + ')</a>';
                        }

                        html += '</div>';

                        $('#price_filter').innerHTML = html;

                        var price_elements = document.getElementsByClassName("price_filter");

                        for (var i = 0; i < price_elements.length; i++) {
                            price_elements[i].onclick = function() {

                                remove_active_class(price_elements);

                                this.classList.add("active");

                                load_product(page = 1, make_query());

                            };
                        }

                    }
                }

            });
        }

        function remove_active_class(element) {
            for (var i = 0; i < element.length; i++) {
                if (element[i].matches('.active')) {
                    element[i].classList.remove("active");
                }
            }
        }

        function make_query() {
            var query = '';

            var gender_elements = document.getElementsByClassName("gender_filter");

            for (var i = 0; i < gender_elements.length; i++) {
                if (gender_elements[i].checked) {
                    query += '&gender_filter=' + gender_elements[i].value + '';
                }
            }

            var price_elements = document.getElementsByClassName("price_filter");

            for (var i = 0; i < price_elements.length; i++) {
                if (price_elements[i].matches('.active')) {
                    query += '&price_filter=' + price_elements[i].getAttribute("id") + '';
                }
            }

            var brand_elements = document.getElementsByClassName("brand_filter");

            var brandlist = '';

            for (var i = 0; i < brand_elements.length; i++) {
                if (brand_elements[i].checked) {
                    brandlist += brand_elements[i].value + ',';
                }
            }

            if (brandlist != '') {
                query += '&brand_filter=' + brandlist + '';
            }

            return query;
        }

        $('#clear_filter').onclick = function() {

            var gender_elements = document.getElementsByClassName("gender_filter");

            for (var i = 0; i < gender_elements.length; i++) {
                gender_elements[i].checked = false;
            }

            var price_elements = document.getElementsByClassName("price_filter");

            remove_active_class(price_elements);

            var brand_elements = document.getElementsByClassName("brand_filter");

            for (var i = 0; i < brand_elements.length; i++) {
                brand_elements[i].checked = false;
            }

            load_product(1, '');

        };
    </script>



    <!-- Footer Start -->

    <?php
    include "includes/footer.php"
    ?>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>