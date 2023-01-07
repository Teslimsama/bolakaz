    <!-- Footer Start -->
    <?php include 'includes/scripts.php'; ?>

    <div class="container-fluid bg-secondary text-dark mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <a href="index" class="text-decoration-none">
                    <h1 class="mb-4 font-weight-semi-bold"><span class="text-primary font-weight-bold border border-white px-3 mr-1">B</span>Bolakaz.Enterprise</h1>
                </a>
                <p>Dolore erat dolor sit lorem vero amet. Sed sit lorem magna, ipsum no sit erat lorem et magna ipsum dolore amet erat.</p>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>katampe road, Kubwa, Abuja, Nigeria</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>bolajimotunrayo20@gmail.com</p>
                <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+234 8077747898</p>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row">
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Quick Links</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <a class="text-dark mb-2" href="index"><i class="fa fa-angle-right mr-2"></i>Home</a>
                            <a class="text-dark mb-2" href="shop"><i class="fa fa-angle-right mr-2"></i>Shop</a>
                            <a class="text-dark mb-2" href="cart"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a>
                            <a class="text-dark mb-2" href="checkout"><i class="fa fa-angle-right mr-2"></i>Checkout</a>
                            <a class="text-dark" href="contact"><i class="fa fa-angle-right mr-2"></i>Contact Us</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Newsletter</h5>
                        <form action="" method="$_GET">
                            <div class="form-group">
                                <input type="text" class="form-control border-0 py-4" placeholder="Your Name" required="required" />
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control border-0 py-4" placeholder="Your Email" required="required" />
                            </div>
                            <div>
                                <button class="btn btn-primary btn-block border-0 py-3" type="submit">Subscribe Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row border-top border-light mx-xl-5 py-4">
            <div class="col-md-6 px-xl-0">
                <p class="mb-md-0 text-center text-md-left text-dark">
                    &copy; <script>
                        document.write(new Date().getFullYear())
                    </script> <a class="text-dark font-weight-semi-bold" href="https://teslim.unibooks.com.ng">teslim.unibooks.com.ng</a>. All Rights Reserved. Designed
                    by
                    <a class="text-dark font-weight-semi-bold" href="https://teslim.unibooks.com.ng">Teslimsama</a>
                </p>
            </div>
            <div class="col-md-6 px-xl-0 text-center text-md-right">
                <img class="img-fluid" src="img/payments.png" alt="">
            </div>
        </div>
    </div>
    <div class="wrapper">
        <img src="cookie.png" alt="">
        <div class="content">
            <header>Cookies Consent</header>
            <p>This website use cookies to ensure you get the best experience on our website.<!-- <a href="https://www.cookiepolicygenerator.com/live.php?token=Fvk6GUWOvOCcZMrvHcADOW8VSlKiCODj" class="item">Privacy Policy</a> --></p>
            <div class="buttons">
                <button class="item">I understand</button>
                <a href="https://www.cookiepolicygenerator.com/live.php?token=uBgdxMgsAPvwDPbMPRhemkYvreQN9cvT" class="item">Learn more</a>
                
            </div>
        </div>
    </div>

    <script>
        const cookieBox = document.querySelector(".wrapper"),
            acceptBtn = cookieBox.querySelector("button");

        acceptBtn.onclick = () => {
            //setting cookie for 1 month, after one month it'll be expired automatically
            document.cookie = "CookieBy=CodingNepal; max-age=" + 60 * 60 * 24 * 30;
            if (document.cookie) { //if cookie is set
                cookieBox.classList.add("hide"); //hide cookie box
            } else { //if cookie not set then alert an error
                alert("Cookie can't be set! Please unblock this site from the cookie setting of your browser.");
            }
        }
        let checkCookie = document.cookie.indexOf("CookieBy=CodingNepal"); //checking our cookie
        //if cookie is set then hide the cookie box else show it
        checkCookie != -1 ? cookieBox.classList.add("hide") : cookieBox.classList.remove("hide");
    </script>