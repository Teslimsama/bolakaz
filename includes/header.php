    <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row bg-secondary py-2 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center">
                    <a class="text-dark" href="">FAQs</a>
                    <span class="text-muted px-2">|</span>
                    <a class="text-dark" href="">Help</a>
                    <span class="text-muted px-2">|</span>
                    <a class="text-dark" href="">Support</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <div class="d-inline-flex align-items-center">
                    <a class="text-dark px-2" href="">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a class="text-dark px-2" href="">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a class="text-dark px-2" href="">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a class="text-dark px-2" href="">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a class="text-dark pl-2" href="">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="row align-items-center py-3 px-xl-5">
            <div class="col-lg-5 d-none d-lg-block">
                <a href="index" class="text-decoration-none">
                    <h1 class="m-0 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">B</span>Bolakaz.Enterprise</h1>
                </a>
            </div>
            <div class="col-lg-5 col-5 text-left">
                <form action="">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search for products">
                        <div class="input-group-append">
                            <span class="input-group-text bg-transparent text-primary">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-2 col-6 text-right">
                <!-- <a href="logout" class="btn border">
                    <i class="fa-solid fa-right-from-bracket text-primary"></i>
                    <span class="badge">Logout</span>
                </a> -->
                <a href="#" class="btn border dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-shopping-cart text-primary"></i>

                    <span class="label label-success cart_count"></span>
                </a>
                <ul class="dropdown-menu">
                    <li class="header">You have <span class="cart_count"></span> item(s) in cart</li>
                    <li>
                        <ul class="menu" id="cart_menu">
                        </ul>
                    </li>
                    <li class="footer"><a href="cart">Go to Cart</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid mb-5">
        <div class="row border-top px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a class="btn shadow-none d-flex align-items-center justify-content-between bg-primary text-white w-100" data-toggle="collapse" href="#navbar-vertical" style="height: 65px; margin-top: -1px; padding: 0 30px;">
                    <h6 class="m-0">Categories</h6>
                    <i class="fa fa-angle-down text-dark"></i>
                </a>
                <nav class="collapse show navbar navbar-vertical navbar-light align-items-start p-0 border border-top-0 border-bottom-0" id="navbar-vertical">
                    <div class="navbar-nav w-100 overflow-hidden" style="height: 410px">
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link" data-toggle="dropdown">Dresses <i class="fa fa-angle-down float-right mt-1"></i></a>
                            <div class="dropdown-menu position-absolute bg-secondary border-0 rounded-0 w-100 m-0">
                                <a href="" class="dropdown-item">Men's Dresses</a>
                                <a href="" class="dropdown-item">Women's Dresses</a>
                                <a href="" class="dropdown-item">Baby's Dresses</a>
                            </div>
                        </div>
                        <?php

                        $conn = $pdo->open();
                        try {
                            $stmt = $conn->prepare("SELECT * FROM category");
                            $stmt->execute();
                            foreach ($stmt as $row) {
                                echo "
                            <a class='nav-item nav-link' href='shop.php?category=" . $row['cat_slug'] . "'>" . $row['name'] . "</a>
                            ";
                            }
                        } catch (PDOException $e) {
                            echo "There is some problem in connection: " . $e->getMessage();
                        }

                        $pdo->close();

                        ?>

                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0">
                    <a href="index" class="text-decoration-none d-block d-lg-none">
                        <h1 class="m-0 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">B</span>Bolakaz.Enterprises</h1>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index" class="nav-item nav-link active">Home</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu rounded-0 m-0">
                                    <a href="cart" class="dropdown-item">Shopping Cart</a>
                                    <a href="checkout" class="dropdown-item">Checkout</a>
                                </div>
                            </div>
                            <a href="contact" class="nav-item nav-link">Contact</a>
                        </div>
                        <div class="navbar-nav ml-auto py-0">

                            <?php
                            if (isset($_SESSION['user'])) {
                                $image = (!empty($user['photo'])) ? 'images/' . $user['photo'] : 'images/profile.jpg';
                                echo '
                <li class="dropdown user user-menu">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="' . $image . '" class="user-image" alt="User Image">
                    <span class="hidden-xs">' . $user['firstname'] . ' ' . $user['lastname'] . '</span>
                  </a>
                  <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header justify-content-center p-2">
                      <img src="' . $image . '" class="img-circle" alt="User Image">

                      <p>
                        ' . $user['firstname'] . ' ' . $user['lastname'] . '
                        <small>Member since ' . date('M. Y', strtotime($user['created_on'])) . '</small>
                      </p>
                    </li>
                    <li class="user-footer">
                      <div class="pull-left">
                        <a href="profile.php" class="btn btn-primary btn-flat">Profile</a>
                      </div>
                      <div class="pull-right">
                        <a href="logout.php" class="btn btn-primary btn-flat">Sign out</a>
                      </div>
                    </li>
                  </ul>
                </li>
              ';
                            } else {
                                echo "
                            <a href='signin' class='nav-item nav-link'>Login</a>
                            <a href='signup' class='nav-item nav-link'>Register</a>
              ";
                            }
                            ?>
                        </div>
                    </div>
                </nav>

            </div>
        </div>
    </div>
    <style>
        .navbar-nav>.user-menu .user-image {
            float: left;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            margin-right: 10px;
            margin-top: -2px;
        }

        .navbar-nav>.user-menu>.dropdown-menu>li.user-header>img {
            z-index: 5;
            height: 90px;
            width: 90px;
            border: 3px solid;
            border-color: -webkit-linear-gradient(bottom left, #fc2c77 0%, #000 100%);
        }

        img.thumbnail {
            margin: auto 10px auto auto;
            width: 40px;
            height: 40px;
        }

        .thumbnail {

            display: block;
            padding: 4px;
            margin-bottom: 20px;
            line-height: 1.42857143;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            -webkit-transition: border .2s ease-in-out;
            -o-transition: border .2s ease-in-out;
            transition: border .2s ease-in-out;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: -66%;
            z-index: 1000;
            display: none;
            float: left;
            min-width: 160px;
            padding: 5px 0;
            margin: 2px 0 0;
            font-size: 14px;
            text-align: left;
            list-style: none;
            background-color: #f1f1f1;
            -webkit-background-clip: padding-box;
            background-clip: padding-box;
            border: 1px solid #ccc;
            border: 1pxsolidrgba(0, 0, 0, .15);
            border-radius: 4px;
            -webkit-box-shadow: 0 6px 12px rgb(0 0 0 / 18%);
            box-shadow: 0 6px 12pxrgba(0, 0, 0, .175);
        }

        .img-circle {
            border-radius: 50%;
        }

        img {
            vertical-align: middle;
        }

        .navbar-nav>.user-menu>.dropdown-menu>li.user-header {
            height: 175px;
            padding: 10px;
            text-align: center;
        }

        .navbar-nav>.user-menu>.dropdown-menu>li.user-header>p {
            z-index: 5;
            color: #fff;
            color: #a18cd1;
            font-size: 17px;
            margin-top: 10px;
        }

        .navbar-nav>.user-menu>.dropdown-menu>li.user-header>p>small {
            display: block;
            font-size: 12px;
        }

        .navbar-nav>.user-menu>.dropdown-menu>.user-footer {
            /* background-color: #f9f9f9; */
            padding: 10px;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .pull-right {
            float: right;
            margin: 5px;
        }

        .pull-left {
            float: left;
            margin: 5px;
        }

        @media (max-width: 991px) {
            .navbar>.navbar-nav>li>.dropdown-menu {
                position: absolute;
                right: 0%;
                left: auto;
                border: 1px solid #ddd;
                background: #fff;
            }
        }

        .navbar>.navbar-nav>li>.dropdown-menu {
            position: absolute;
            right: 0;
            left: auto;
        }

        .navbar-nav>.user-menu>.dropdown-menu,
        .navbar-nav>.user-menu>.dropdown-menu>.user-body {
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 4px;
        }

        .navbar-nav>.user-menu>.dropdown-menu {
            border-top-right-radius: 0;
            border-top-left-radius: 0;
            padding: 1px 0 0 0;
            border-top-width: 0;
            width: 280px;
        }

        @media (max-width: 767px) {
            .navbar-nav .open .dropdown-menu {
                position: static;
                float: none;
                width: auto;
                margin-top: 0;
                background-color: transparent;
                border: 0;
                -webkit-box-shadow: none;
                box-shadow: none;
            }
        }

        .navbar-nav>li>.dropdown-menu {
            margin-top: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .open>.dropdown-menu {
            display: block;
        }

        .dropdown-menu {
            box-shadow: none;
            border-color: #eee;
        }
    </style>