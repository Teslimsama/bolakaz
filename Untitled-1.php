 <?php

    $query = "SELECT DISTINCT(color) FROM products WHERE status = '0' ORDER BY id DESC";
    $conn = $pdo->open();

    $statement = $conn->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    foreach ($result as $row) {
    ?>
     <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-3">

         <label class="custom-control-label" for="color-1"><input type="checkbox" class="custom-control-input" value="<?php echo $row['color']; ?>" id="color-1"><?php echo $row['color']; ?></label>
         <span class="badge border font-weight-normal">150</span>
     </div>
 <?php
    }

    ?>
 <script>
     load_data();

     function load_data(query = '', page_number = 1, catid = '') {
         var form_data = new FormData();
         const cat = window.location.search;
         const o = cat.split('?')[1];
         const getcat = new URLSearchParams(o);
         for (let pair of getcat.entries()) {
             const element = pair[1];
             form_data.append('category', element);
         }
         form_data.append('query', query);

         form_data.append('page', page_number);


         var ajax_request = new XMLHttpRequest();

         ajax_request.open('POST', 'process_data.php');

         ajax_request.send(form_data);

         ajax_request.onreadystatechange = function() {
             if (ajax_request.readyState == 4 && ajax_request.status == 200) {
                 var response = JSON.parse(ajax_request.responseText);

                 var html = '';

                 var serial_no = 1;

                 if (response.data.length > 0) {
                     for (var count = 0; count < response.data.length; count++) {
                         html += ` 
                            <div  class='col-lg-4 col-md-6 col-sm-12 pb-1'>
                            <form method="get" id="productForm">
                                    <div class="card product-item border-0 mb-4">
                                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                                            <img class="img-fluid w-100" src="./images/` + response.data[count].photo + `" alt="image">
                                        </div>`;

                         html += `
                            <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                                    <h6 class="text-truncate mb-3"><a href="detail.php?product=` + response.data[count].slug + `"> ` + response.data[count].name + `</a></h6>
                                <div class="d-flex justify-content-center"> 
                                <h6 class="text-truncate mb-3">&#36; ` + response.data[count].price + `</h6>
                                    <h6 class="text-muted ml-2"><del>&#36;` + response.data[count].price + `</del></h6>
                                </div>`;
                         html += ` 
                            </div>
                            <div class="card-footer d-flex justify-content-center bg-light border">
                                <a href="detail.php?product=` + response.data[count].slug + `">
                                 <i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                               
                            `;
                         html += `  
                            </div>
                             </div>
                             </form>
                             </div>
                             
                             `;

                         serial_no++;
                     }
                 } else {
                     html += '<h1 colspan="3" class="text-center">No Data Found</h1>';
                 }

                 document.getElementById('post_data').innerHTML = html;

                 document.getElementById('total_data').innerHTML = response.total_data;

                 document.getElementById('pagination_link').innerHTML = response.pagination;

             }

         }
     }
 </script>

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