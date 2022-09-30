<?php
require_once('CreateDb.php');

function component($productname, $productprice, $productimg, $productid)
{
    $element = "
    
    <div class=\"col-lg-4 col-md-6 col-sm-12 pb-1\">
                    <form action=\"shop.php\" method=\"post\">
                        <div class=\"card product-item border-0 mb-4\">
                            <div class=\"card-header product-img position-relative overflow-hidden bg-transparent border p-0\">
                                <img class=\"img-fluid w-100\" src=\"$productimg\" alt=\"\">
                            </div>
                            <div class=\"card-body border-left border-right text-center p-0 pt-4 pb-3\">
                                <h6 class=\"text-truncate mb-3\">$productname</h6>
                                <div class=\"d-flex justify-content-center\">
                                    <h6>$1.00</h6><h6 class=\"text-muted ml-2\"><del>$$productprice</del></h6>
                                </div>
                            </div>
                            <div class=\"card-footer d-flex justify-content-between bg-light border\">
                                <a href=\"\" class=\"btn btn-sm text-dark p-0\"><i class=\"fas fa-eye text-primary mr-1\"></i>View Detail</a>
                                <button type=\"submit\"class=\"btn btn-sm text-dark p-0\" name=\"add\">Add to Cart <i class=\"fas fa-shopping-cart text-primary mr-1\"></i></button>
                             <input type='hidden' name='product_id' value='$productid'>

                             </div>
                             </div>
                             </form>
                             </div>
                             
                             
                             ";

    echo $element;
}

function cartElement($productimg, $productname, $productprice, $productid)
{
    $element = "
    <tr>
        <form action=\"cart.php?action=remove&id=$productid\" method=\"post\" class=\"cart-items\">
                            <td class=\"align-middle\"><img src=\"$productimg\" alt=\"\" style=\"width: 50px;\">$productname</td>
                            <td class=\"align-middle\">$$productprice</td>
                            <td class=\"align-middle\">
                                <div class=\"input-group quantity mx-auto\" style=\"width: 100px;\">
                                    <div class=\"input-group-btn\">
                                        <button class=\"btn btn-sm btn-primary btn-minus\" >
                                        <i class=\"fa fa-minus\"></i>
                                        </button>
                                    </div>
                                    <input type=\"text\" class=\"form-control form-control-sm bg-secondary text-center\" value=\"1\">
                                    <div class=\"input-group-btn\">
                                        <button class=\"btn btn-sm btn-primary btn-plus\">
                                            <i class=\"fa fa-plus\"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <input type='hidden' name='product_id' value='$productid'>
                            <td class=\"align-middle\">$productprice</td>
                            <td class=\"align-middle\"><button type=\"submit\" name=\"remove\" class=\"btn btn-sm btn-primary\"><i class=\"fa fa-times\"></i></button></td>
                        </tr>
        </form>
                        ";
    echo  $element;
}

function trendElement($productname, $productprice, $productimg,  $productid)
{
    $element = "
    <div class=\"col-lg-3 col-md-6 col-sm-12 pb-1\">
        <div class=\"card product-item border-0 mb-4\">
    <form action=\"index.php\" method=\"post\">
            <div class=\"card-header product-img position-relative overflow-hidden bg-transparent border p-0\">
                <img class=\"img-fluid w-100\" src=\"$productimg\" alt=\"\">
            </div>
            <div class=\"card-body border-left border-right text-center p-0 pt-4 pb-3\">
                <h6 class=\"text-truncate mb-3\">$productname</h6>
                <div class=\"d-flex justify-content-center\">
                    <h6>$123.00</h6><h6 class=\"text-muted ml-2\"><del>$$productprice</del></h6>
                </div>
            </div>
            <input type='hidden' name='product_id' value='$productid'>
            <div class=\"card-footer d-flex justify-content-between bg-light border\">
                <a href=\"\" class=\"btn btn-sm text-dark p-0\"><i class=\"fas fa-eye text-primary mr-1\"></i>View Detail</a>
                 <button type=\"submit\"class=\"btn btn-sm text-dark p-0\" name=\"add\"><i class=\"fas fa-shopping-cart text-primary mr-1\"></i>Add to Cart </button>
            </div>
        </div>
    </div>
        </form>
                        ";
    echo  $element;
}
