      <div class="container-fluid">
          <div class="row border-top px-xl-5">
              <div class="col-lg-3 d-none d-lg-block">
                  <a class="btn shadow-none d-flex align-items-center justify-content-between bg-primary text-white w-100" data-toggle="collapse" href="#navbar-vertical" style="height: 65px; margin-top: -1px; padding: 0 30px;">
                      <h6 class="m-0">Categories</h6>
                      <i class="fa fa-angle-down text-dark"></i>
                  </a>
                  <nav class="position-absolute navbar navbar-vertical navbar-light align-items-start p-0 border border-top-0 border-bottom-0 bg-light collapse show" style="width: 21.51%; " id="navbar-vertical">
                      <div class="navbar-nav w-100 overflow-hidden" style="height: 410px">

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
                          <h1 class="m-0 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">B</span>Bolakaz.Ent</h1>
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
                              <a href="profile" class="nav-item nav-link">Profile</a>
                          </div>
                          <div class="navbar-nav ml-auto py-0">

                              <?php
                                if (isset($_SESSION['user'])) {
                                    $image = (!empty($user['photo'])) ? 'images/' . $user['photo'] : 'images/profile.jpg';
                                    echo "
                                    <a href='logout' class='nav-item nav-link'>Logout</a>
                                    ";
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