
<!DOCTYPE html>
<html>
<head>
    <title>HorsePlaying Hotel</title>
    <link rel="icon" type="image/x-icon" href="horse.jpg">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 10px auto;
        }
        h1 {
            color: #333;
        }
        p {
            font-size: 18px;
            color: #555;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .carousel {
            width: 100%;
            max-width: 650px;
            height: 300px;
            margin: 20px auto;
            overflow: hidden;
            position: relative;
            border-radius: 8px;
        }
        .carousel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        .carousel img.active {
            display: block;
        }
        .review-carousel .review {
            display: none;
            font-size: 18px;
            font-style: italic;
            color: #333;
        }
        .review-carousel .review.active {
            display: block;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let images = document.querySelectorAll(".carousel img");
            let reviews = document.querySelectorAll(".review-carousel .review");
            let imgIndex = 0;
            let reviewIndex = 0;
            
            function showNextImage() {
                images[imgIndex].classList.remove("active");
                imgIndex = (imgIndex + 1) % images.length;
                images[imgIndex].classList.add("active");
            }
            function showNextReview() {
                reviews[reviewIndex].classList.remove("active");
                reviewIndex = (reviewIndex + 1) % reviews.length;
                reviews[reviewIndex].classList.add("active");
            }
            
            images[0].classList.add("active");
            reviews[0].classList.add("active");
            setInterval(showNextImage, 3000);
            setInterval(showNextReview, 4000);
        });
    </script>
</head>
<body>
<?php
    include 'header.php'; 
?>
  <div class="carousel">
            <img src="image1.jpg" alt="Hotel Image 1">
            <img src="image2.webp" alt="Hotel Image 2">
            <img src="image3.jfif" alt="Hotel Image 3">
        </div>
    <div class="container">
        <h1>Welcome to Our Hotel</h1>
        
        <p>Experience luxury and comfort with our top-rated hotel services. Choose from a variety of room options that suit your needs.</p>
        <p>Book your stay with us today and enjoy the best hospitality experience.</p>
        <a href="HotelReservation.php" class="btn">Make a Reservation</a>
    </div>
    <div class="review-carousel container">
        <h2>What Our Guests Say</h2>
        <p class="review active">"Fantastic experience! The rooms were clean, and the service was excellent." - Sarah M.</p>
        <p class="review">"Best hotel I’ve ever stayed at. The staff was incredibly helpful." - John D.</p>
        <p class="review">"A true luxury getaway! Highly recommended for anyone visiting the area." - Emily R.</p>
    </div>
</body>
</html>
