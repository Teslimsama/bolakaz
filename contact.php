<?php include 'session.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Contact"; include "head.php"; ?>
</head>

<body>
    <!-- Topbar Start -->

    <?php
    include "header.php"
    ?>
    <?php include 'navbar.php'; ?>

    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Contact Us</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="index">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Contact</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Contact Start -->
    <div class="container-fluid pt-5">
        <div class="text-center mb-4">
            <h2 class="section-title px-5"><span class="px-2">Contact For Any Queries</span></h2>
        </div>
        <div class="row px-xl-5">
            <div class="col-lg-7 mb-5">
                <div class="contact-form">
                    <div id="success"></div>
                    <form name="sentMessage" id="contactForm" novalidate="novalidate">
                        <div class="control-group">
                            <input type="text" class="form-control" id="name" placeholder="Your Name" required="required" data-validation-required-message="Please enter your name" />
                            <p class="help-block text-danger"></p>
                        </div>
                        <div class="control-group">
                            <input type="email" class="form-control" id="email" placeholder="Your Email" required="required" data-validation-required-message="Please enter your email" />
                            <p class="help-block text-danger"></p>
                        </div>
                        <div class="control-group">
                            <input type="text" class="form-control" id="subject" placeholder="Subject" required="required" data-validation-required-message="Please enter a subject" />
                            <p class="help-block text-danger"></p>
                        </div>
                        <div class="control-group">
                            <textarea class="form-control" rows="6" id="message" placeholder="Message" required="required" data-validation-required-message="Please enter your message"></textarea>
                            <p class="help-block text-danger"></p>
                        </div>
                        <div>
                            <button class="btn btn-primary py-2 px-4" type="submit" id="sendMessageButton">Send
                                Message</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5 mb-5">
                <h5 class="font-weight-semi-bold mb-3">Get In Touch</h5>
                <p>If you have any questions or concerns, please don't hesitate to reach out to us. You can contact us by phone or by email. We're here to help!</p>
                <div class="d-flex flex-column mb-3">
                    <h5 class="font-weight-semi-bold mb-3">Store </h5>
                    <p class="mb-2"><i class="fa fa-map-marker text-primary mr-3"></i><a class="text-dark" href="https://maps.app.goo.gl/wH8jTwV21exQd4HMA" target="_blank"> Bolakaz Enterprise , Dogo Daji Street, Beside Bentex Guest House
                            , Katampe, Kubwa Village, Abuja, Nigeria.</a></p>
                    <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i> <a class="text-dark" href="mailto:info@unibooks.com">info@unibooks.com</a></p>
                    <p class="mb-0"><i class="fa fa-phone text-primary mr-3"></i><a class="text-dark" href="tel:+234 8077747898">+234 8077747898</a></p>
                </div>
                <!-- <div class="d-flex flex-column">
                    <h5 class="font-weight-semi-bold mb-3">Store 2</h5>
                    <p class="mb-2"><i class="fa fa-map-marker text-primary mr-3"></i>Katampe road, Kubwa, Abuja, Nigeria</p>
                    <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>bolajimotunrayo20@gmail.com</p>
                    <p class="mb-0"><i class="fa fa-phone text-primary mr-3"></i>+234 8077747898</p>
                </div> -->
            </div>
        </div>
    </div>
    <!-- Contact End -->


    <!-- Footer Start -->
    <?php include "footer.php"; ?>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

    <!-- JavaScript Libraries -->
    <!-- Contact Javascript File -->
    <script src="mail/contact.js"></script>





    <!-- JavaScript Bundle with Popper -->
    <!-- Template Javascript -->
</body>

</html>

