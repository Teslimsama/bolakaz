<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form>
        <input type="text" id="search-input">
    </form>
    <div id="results"></div>

    <!--  -->
    <script src="js/jquery-1.10.2.min.js"></script>

    <script>
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
                                html += "<div class='product'>";
                                html += "<h3>" + products[i].name + "</h3>";
                                html += "<p>" + products[i].description + "</p>";
                                html += "<p>$" + products[i].price + "</p>";
                                html += "</div>";
                            }
                            $("#results").html(html);
                        }
                    });
                } else {
                    $("#results").html("");
                }
            });
        });
    </script>
    
</body>

</html>