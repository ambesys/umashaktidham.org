<div class="container">
    <h1><i class="fas fa-envelope"></i> Contact Us</h1>
    <p>Email: <a href="mailto:umashaktidham@gmail.com">umashaktidham@gmail.com</a></p>
    <p>Phone: (704) 350-5040</p>
    <h2>Send a message</h2>
    <form action="/contact_submit.php" method="POST">
        <label for="name">Name</label>
        <input id="name" name="name" required>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required>
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required></textarea>
        <button class="btn" type="submit">Send</button>
    </form>
</div>

