<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/contact-us.css') }}">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>
    <x-landing-header />
    
    <main class="container contact-container">
        <h1>Contact Us</h1>
        
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <div class="contact-method">
                    <h3>📧 Email</h3>
                    <p>support@deskup.com</p>
                </div>
                <div class="contact-method">
                    <h3>📞 Phone</h3>
                    <p>+45 12345678</p>
                </div>
                <div class="contact-method">
                    <h3>📍 Address</h3>
                    <p>Alsion<br>Sønderborg,<br>Denmark</p>
                </div>
                <div class="contact-method">
                    <h3>🕒 Business Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM <br>Saturday: 10:00 AM - 2:00 PM </p>
                </div>
            </div>

            <div class="contact-form-container">
                <h2>Send us a Message</h2>
                <form class="contact-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                     <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </main>

    <x-landing-footer />

    <script>
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>