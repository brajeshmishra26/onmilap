<!DOCTYPE html>
<html lang="en">
    <head>
<?php
include('assets/php/head.php');
?>
    </head>
    <body>
        <!-- Dark/Light Toggle -->
		<div class="dark-version">
            <label id="switch" class="switch">
                <input type="checkbox" onchange="toggleTheme()" id="slider">
                <span class="slider round"></span>
            </label>
        </div>

        <!-- Start Preloader Area -->
        <div class="preloader-area">
            <div class="spinner">
                <div class="inner">
                    <div class="disc"></div>
                    <div class="disc"></div>
                    <div class="disc"></div>
                </div>
            </div>
        </div>
        <!-- End Preloader Area -->

        <!-- Start Navbar Area -->
        <?php
include('assets/php/nav.php');
?>
        <!-- End Navbar Area -->

        <!-- Start Page Title Area -->
        <div class="page-title-area">
            <div class="container">
                <div class="page-title-content">
                    <h2>About Us</h2>
                    <p>The onMilap Story</p>
                </div>
            </div>
        </div>
        <!-- End Page Title Area -->

        <!-- Start About Area -->
        <section class="about-area ptb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="about-content">
                            <span class="sub-title">Our Mission</span>
                            <h2>Connecting People, Empowering Communication</h2>
                            <p>onMilap was founded with a vision to make real-time video communication accessible, secure, and engaging for everyone. Our platform bridges distances, enabling seamless face-to-face interactions for businesses, educators, creators, and communities worldwide.</p>
                            <p>We believe in the power of human connection. onMilap empowers users with HD video, interactive features, and robust privacy controls—whether you're hosting a global webinar, collaborating with a remote team, or catching up with loved ones.</p>
                            <p>Driven by innovation and user feedback, onMilap continues to evolve, delivering a reliable, intuitive, and feature-rich experience that brings people together, no matter where they are.</p>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="about-image">
                            <img src="assets/img/about-img.jpg" alt="onMilap video chat platform">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End About Area -->

        <!-- Start Partner Area -->
        <section class="partner-area pt-70 pb-70 bg-f8fbfa">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3 col-md-12">
                        <div class="partner-title">
                            <h3>Trusted by Innovators</h3>
                        </div>
                    </div>

                    <div class="col-lg-9 col-md-12">
                        <div class="partner-slides owl-carousel owl-theme">
                            <div class="single-partner-item">
                                <a href="#">
                                    <img src="assets/img/partner-image/1.png" alt="partner">
                                </a>
                            </div>
                            <div class="single-partner-item">
                                <a href="#">
                                    <img src="assets/img/partner-image/2.png" alt="partner">
                                </a>
                            </div>
                            <div class="single-partner-item">
                                <a href="#">
                                    <img src="assets/img/partner-image/3.png" alt="partner">
                                </a>
                            </div>
                            <div class="single-partner-item">
                                <a href="#">
                                    <img src="assets/img/partner-image/4.png" alt="partner">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Partner Area -->

        <!-- Start Video Presentation Area -->
        <section class="video-presentation-area ptb-100">
            <div class="container">
                <div class="section-title">
                    <h2>Discover onMilap in Action</h2>
                </div>

                <div class="video-box">
                    <img src="assets/img/video-bg.jpg" class="main-image" alt="onMilap video demo">
                    <a href="https://www.youtube.com/watch?v=RgEr1Unb1N4" class="video-btn popup-youtube"><i class="bx bx-play"></i></a>
                    <div class="shape1"><img src="assets/img/shape/1.png" alt="shape"></div>
                    <div class="shape2"><img src="assets/img/shape/2.png" alt="shape"></div>
                    <div class="shape3"><img src="assets/img/shape/3.png" alt="shape"></div>
                    <div class="shape4"><img src="assets/img/shape/4.png" alt="shape"></div>
                    <div class="shape5"><img src="assets/img/shape/5.png" alt="shape"></div>
                    <div class="shape6"><img src="assets/img/shape/6.png" alt="shape"></div>
                </div>

                <div class="funfacts-inner">
                    <div class="row">
                        <div class="col-lg-3 col-6 col-sm-3 col-md-3">
                            <div class="single-funfacts">
                                <h3><span class="odometer" data-count="500">00</span><span class="sign-icon">k</span></h3>
                                <p>Meetings Hosted</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 col-sm-3 col-md-3">
                            <div class="single-funfacts">
                                <h3><span class="odometer" data-count="120">00</span><span class="sign-icon">k</span></h3>
                                <p>Active Users</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 col-sm-3 col-md-3">
                            <div class="single-funfacts">
                                <h3><span class="odometer" data-count="99">00</span><span class="sign-icon">%</span></h3>
                                <p>Uptime</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 col-sm-3 col-md-3">
                            <div class="single-funfacts">
                                <h3><span class="odometer" data-count="50">00</span><span class="sign-icon">+</span></h3>
                                <p>Countries Served</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-cta-box">
                    <h3>Have questions about onMilap?</h3>
                    <p>We're here to help you connect better.</p>
                    <a href="contact.php" class="default-btn"><i class="bx bxs-edit-alt"></i>Contact Us<span></span></a>
                </div>
            </div>
            <div class="shape-map1"><img src="assets/img/map1.png" alt="map"></div>
            <div class="shape7"><img src="assets/img/shape/7.png" alt="shape"></div>
            <div class="shape8"><img src="assets/img/shape/8.png" alt="shape"></div>
            <div class="shape9"><img src="assets/img/shape/9.png" alt="shape"></div>
        </section>
        <!-- End Video Presentation Area -->

        <!-- Start Team Area --
        <section class="team-area pb-70">
            <div class="container">
                <div class="section-title">
                    <h2>Meet the onMilap Team</h2>
                </div>
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-team-box">
                            <div class="image">
                                <img src="assets/img/team-image/1.jpg" alt="team">
                                <ul class="social">
                                    <li><a href="#" target="_blank"><i class="bx bxl-facebook"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-twitter"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-linkedin"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-instagram"></i></a></li>
                                </ul>
                            </div>
                            <div class="content">
                                <h3>Priya Sharma</h3>
                                <span>Founder & CEO</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-team-box">
                            <div class="image">
                                <img src="assets/img/team-image/2.jpg" alt="team">
                                <ul class="social">
                                    <li><a href="#" target="_blank"><i class="bx bxl-facebook"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-twitter"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-linkedin"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-instagram"></i></a></li>
                                </ul>
                            </div>
                            <div class="content">
                                <h3>Rahul Mehta</h3>
                                <span>Lead Engineer</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-team-box">
                            <div class="image">
                                <img src="assets/img/team-image/3.jpg" alt="team">
                                <ul class="social">
                                    <li><a href="#" target="_blank"><i class="bx bxl-facebook"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-twitter"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-linkedin"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-instagram"></i></a></li>
                                </ul>
                            </div>
                            <div class="content">
                                <h3>Anjali Verma</h3>
                                <span>Product Designer</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-team-box">
                            <div class="image">
                                <img src="assets/img/team-image/4.jpg" alt="team">
                                <ul class="social">
                                    <li><a href="#" target="_blank"><i class="bx bxl-facebook"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-twitter"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-linkedin"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="bx bxl-instagram"></i></a></li>
                                </ul>
                            </div>
                            <div class="content">
                                <h3>Vikram Singh</h3>
                                <span>Customer Success</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        End Team Area -->

        <!-- Start Feedback Area -->
        <section class="feedback-area pt-100 pb-70">
            <div class="container">
                <div class="section-title">
                    <h2>What Our Clients Say About <span>onMilap</span></h2>
                </div>
                <div class="feedback-slides owl-carousel owl-theme">
                    <div class="single-feedback-item">
                        <img src="assets/img/woman1.png" alt="client">
                        <div class="feedback-desc">
                            <p>onMilap has transformed the way we connect with our clients. The HD video quality and interactive features make every meeting productive and engaging!</p>
                            <div class="rating">
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                            </div>
                            <div class="client-info">
                                <h3>Sarah Taylor</h3>
                                <span>Business Consultant</span>
                            </div>
                        </div>
                    </div>
                    <div class="single-feedback-item">
                        <img src="assets/img/woman2.png" alt="client">
                        <div class="feedback-desc">
                            <p>We use onMilap for our virtual events and webinars. The platform is reliable, easy to use, and our audience loves the interactive Q&A features!</p>
                            <div class="rating">
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                            </div>
                            <div class="client-info">
                                <h3>Olivar Lucy</h3>
                                <span>Event Organizer</span>
                            </div>
                        </div>
                    </div>
                    <div class="single-feedback-item">
                        <img src="assets/img/man1.png" alt="client">
                        <div class="feedback-desc">
                            <p>onMilap made remote work and team collaboration so much easier for us. The screen sharing and chat features are top-notch!</p>
                            <div class="rating">
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                            </div>
                            <div class="client-info">
                                <h3>Steven Smith</h3>
                                <span>Team Lead</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Feedback Area -->

        <!-- Start Free Trial Area -->
        <section class="free-trial-area ptb-100 bg-f4f5fe">
            <div class="container">
                <div class="free-trial-content">
                    <h2>Experience Seamless Video Communication</h2>
                    <p>Join onMilap today and discover a smarter way to connect, collaborate, and grow your community or business.</p>
                    <a href="contact.php" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                </div>
            </div>
            <div class="shape10"><img src="assets/img/shape/10.png" alt="shape"></div>
            <div class="shape11"><img src="assets/img/shape/7.png" alt="shape"></div>
            <div class="shape12"><img src="assets/img/shape/11.png" alt="shape"></div>
            <div class="shape13"><img src="assets/img/shape/12.png" alt="shape"></div>
        </section>
        <!-- End Free Trial Area -->

        <!-- Start Footer Area -->
       <?php
include('assets/php/footer.php');
?>
        <!-- End Footer Area -->

        <div class="go-top"><i class='bx bx-chevron-up'></i></div>
        <!-- jQuery Min JS -->
        <script src="assets/js/jquery.min.js"></script>
        <!-- Bootstrap Min JS -->
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <!-- Magnific Popup Min JS -->
        <script src="assets/js/jquery.magnific-popup.min.js"></script>
        <!-- Appear Min JS -->
        <script src="assets/js/jquery.appear.min.js"></script>
        <!-- Odometer Min JS -->
        <script src="assets/js/odometer.min.js"></script>
        <!-- Owl Carousel Min JS -->
        <script src="assets/js/owl.carousel.min.js"></script>
        <!-- MeanMenu JS -->
        <script src="assets/js/jquery.meanmenu.js"></script>
        <!-- WOW Min JS -->
        <script src="assets/js/wow.min.js"></script>
        <!-- Message Conversation JS -->
        <script src="assets/js/conversation.js"></script>
        <!-- AjaxChimp Min JS -->
        <script src="assets/js/jquery.ajaxchimp.min.js"></script>
        <!-- Form Validator Min JS -->
        <script src="assets/js/form-validator.min.js"></script>
        <!-- Contact Form Min JS -->
        <script src="assets/js/contact-form-script.js"></script>
        <!-- Particles Min JS -->
        <script src="assets/js/particles.min.js"></script>
        <script src="assets/js/custom-particles.js"></script>
        <!-- Main JS -->
        <script src="assets/js/main.js"></script>
    </body>
</html>
