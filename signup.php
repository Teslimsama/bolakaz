<?php
require_once ('CreateDb.php');
include 'alert.message.php' ;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title Page-->
    <title>Bolakaz</title>

    <!-- Icons font CSS-->
    <link href="css/vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="css/vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Vendor CSS-->
    <link href="css/vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="css/vendor/datepicker/daterangepicker.css" rel="stylesheet" media="all">
    <script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
    <!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <!-- Main CSS-->
    <link href="css/main.css" rel="stylesheet" media="all">
</head>

<body>
    <div class="page-wrapper bg-gra-02 p-t-130 p-b-100 font-poppins">
        <div class="wrapper wrapper--w680">
            <div class="card card-4">
                <div class="card-body">
                    <h2 class="title">Registration Form</h2>
                    <form action="lib/app/signup.app.php" method="POST">
         
                        <div class="msg"><?php echo ErrorMessage(); echo SuccessMessage();?></div>
                        <div class="row row-space">
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">first name</label>
                                    <input class="input--style-4" type="text" name="firstname"required>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">last name</label>
                                    <input class="input--style-4" type="text" name="lastname"required>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">username</label>
                                    <input class="input--style-4" type="text" name="username"required>
                                </div>
                            </div>
                        </div>
                        <div class="row row-space">
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">password</label>
                                    <input class="input--style-4" type="password" name="password"required>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">Confirm password</label>
                                    <input class="input--style-4" type="password" name="conpassword"required>
                                </div>
                            </div>
                        </div>
                        <div class="row row-space">
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">Birthday</label>
                                    <div class="input-group-icon">
                                        <input class="input--style-4 js-datepicker" type="text" name="dob"required>
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">Gender</label>
                                    <div class="p-t-10">
                                        <label class="radio-container m-r-45">Male
                                            <input type="radio" checked="checked" value="male" name="gender"required>
                                            <span class="checkmark"></span>
                                        </label>
                                        <label class="radio-container">Female
                                            <input type="radio" value="female" name="gender"required>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row row-space">
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">Email</label>
                                    <input class="input--style-4" type="email" name="email"required>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="input-group">
                                    <label class="label">Phone Number</label>
                                    <input class="input--style-4" type="text" name="phone"required>
                                </div>
                            </div>
                        </div>
                                <div class="input-group">
                                    <label class="label">state</label>
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select name="state" required>
                                            <option disabled="disabled" selected="selected">Choose option</option>
                                            <option value="fct">Fct</option>
                                            <option value="yobe">Yobe</option>
                                            <option value="kogi">Kogi</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label class="label">referral</label>
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select name="referral" required>
                                            <option disabled="disabled" selected="selected">Choose option</option>
                                            <option value="A friend">A friend</option>
                                            <option value="facebook">Facebook</option>
                                            <option value="twitter">Twitter</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="ad">From an Ad</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>
                                </div>
                        
                        <div class="p-t-15">
                            <button class="btn btn--radius-2 btn--blue" name="submit" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery JS-->
    <script src="css/vendor/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="css/vendor/select2/select2.min.js"></script>
    <script src="css/vendor/datepicker/moment.min.js"></script>
    <script src="css/vendor/datepicker/daterangepicker.js"></script>
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    <!-- Main JS-->
    <script src="js/global.js"></script>

</body>

</html>
<!-- end document-->