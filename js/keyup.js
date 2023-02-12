        $(document).ready(function() {
            $("#search-input").on("keyup", function() {
                var searchTerm = $(this).val();
                if (searchTerm.length > 2) {
                    $.ajax({
                        url: "keyup.php",
                        type: "post",
                        data: {
                            search: searchTerm
                        },
                        success: function(response) {
                            var products = JSON.parse(response);
                            var html = "";
                            for (var i = 0; i < products.length; i++) {
                                html += '<div class="col-lg-4 col-md-6 col-sm-12 pb-1">';
        html += '<div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">';
            html +=' <img class="img-fluid w-100" src="images/' + products[i].photo+ '" alt="">';
            html +='</div>';
        html +=' <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">';
            html +='<h6 class="text-truncate mb-3">' + products[i].name + '</h6>';
            html +='<div class="d-flex justify-content-center"><h6>' + products[i].price +'</h6><h6 class="text-muted ml-2"><del>' +products[i].price +'</del></h6></div>';
            html +="</div> ";
        html +=' <div class="card product-item border-0 mb-4">';
            html += '<div class="card-footer d-flex justify-content-between bg-light border">';
            html += '<a href="detail.php?product=' + products[i].slug +'" class="btn btn-sm text-dark p-0" > <i class="fas fa-eye text-primary mr-1"></i>View Detail</a ></div ></div > ';

        html += "</div>";
                            }
                            $(".filter_data").html(html);
                        }
                    });
                } else {
                    $(".filter_data").html("");
                }
            });
        });
    
                        
                    