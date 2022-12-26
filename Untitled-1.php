 <!-- <i class="fas fa-star star-light submit_star" id="submit_star_1" data-rating="1"></i>
 <i class="fas fa-star star-light submit_star" id="submit_star_2" data-rating="2"></i>
 <i class="fas fa-star star-light submit_star" id="submit_star_3" data-rating="3"></i>
 <i class="fas fa-star star-light submit_star" id="submit_star_4" data-rating="4"></i>
 <i class="fas fa-star star-light submit_star" id="submit_star_5" data-rating="5"></i> -->


 <script>
     $(document).ready(function() {

         filter_data();

         function filter_data() {
             $('.filter_data').html('<div id="loading" style="" ></div>');
             var action = 'fetch_data';
             var minimum_price = $('#hidden_minimum_price').val();
             var maximum_price = $('#hidden_maximum_price').val();
             var brand = get_filter('brand');
             var ram = get_filter('ram');
             var storage = get_filter('storage');
             $.ajax({
                 url: "fetch_data.php",
                 method: "POST",
                 data: {
                     action: action,
                     minimum_price: minimum_price,
                     maximum_price: maximum_price,
                     brand: brand,
                     ram: ram,
                     storage: storage
                 },
                 success: function(data) {
                     $('.filter_data').html(data);
                 }
             });
         }

         function get_filter(class_name) {
             var filter = [];
             $('.' + class_name + ':checked').each(function() {
                 filter.push($(this).val());
             });
             return filter;
         }

         $('.common_selector').click(function() {
             filter_data();
         });

         $('#price_range').slider({
             range: true,
             min: 1000,
             max: 65000,
             values: [1000, 65000],
             step: 500,
             stop: function(event, ui) {
                 $('#price_show').html(ui.values[0] + ' - ' + ui.values[1]);
                 $('#hidden_minimum_price').val(ui.values[0]);
                 $('#hidden_maximum_price').val(ui.values[1]);
                 filter_data();
             }
         });

     });
 </script>
 <script>
     function $(selector) {
         return document.querySelector(selector);
     }

     load_product(1, '');

     function load_product(page = 1, query = '', category = '') {
         function cat() {
             fetch('shop.php?category=' + element).then(function(response) {
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
 <?php
    $n = 1;
    foreach ($categories as $key => $category) {
        if (isset($_POST['category'])) {
            if (in_array($product->cleanString($category['category_id']), $_POST['category'])) {
                $categoryCheck = 'checked="checked"';
            } else {
                $categoryCheck = "";
            }
        }
    ?>
     <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
         <input type="checkbox" class="custom-control-input sort_rang category" value="<?php echo $product->cleanString($category['category_id']); ?>" <?php echo @$categoryCheck; ?> name="category[]" id="<?php echo 'cat-' . $n ?>">
         <label class="custom-control-label" for="<?php echo 'cat-' . $n ?>"><?php echo $product->cleanString($category['category_name']); ?></label>
         <span class="badge border font-weight-normal">150</span>
     </div>
 <?php $n++;
    }
    ?>