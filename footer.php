<footer class="site-footer">
  <div class="footer-container">
    <h2 class="footer-tagline">AquaWiki — Your Freshwater Fish Encyclopedia</h2>

    <p class="footer-disclaimer">
      © 2025 AquaWiki. This platform is for educational and informational purposes only. 
      All photos and data belong to their respective owners. AquaWiki does not claim ownership 
      of any materials displayed on this site.
    </p>

    <!-- Two forms side by side -->
    <div class="footer-forms">

      <!-- Fish Submission Form -->
   <div class="fish-submission">
  <h3>Submit Fish Information</h3>
  <?php if (isset($_SESSION['user'])): ?>
  <form id="footerFishForm" class="fish-form">
    <div class="form-group">
      <input type="text" name="fish_name" placeholder="Fish Name" required>
      <textarea name="fish_info" placeholder="Information about the fish" required></textarea>
    </div>
    <input type="hidden" name="username" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>">
    <button type="submit">Submit</button>
  </form>
  <p id="fishMsg" style="margin-top:8px; display:none; color:#00c2cb;"></p>
  <?php else: ?>
    <p>Please <a href="login.php">log in</a> to submit fish information.</p>
  <?php endif; ?>
</div>


      <!-- Feedback Form -->
      <div class="feedback-form">
        <h3>Send Feedback</h3>
        <?php if (isset($_SESSION['user'])): ?>
        <form id="footerFeedbackForm" class="fish-form">
          <textarea name="message" placeholder="Your feedback here..." required></textarea>
          <input type="hidden" name="username" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>">
          <button type="submit">Submit Feedback</button>
        </form>
        <p id="feedbackMsg" style="margin-top:8px; display:none; color:#00c2cb;">Feedback submitted!</p>
        <?php else: ?>
          <p>Please <a href="login.php">log in</a> to submit feedback.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Footer Links Section -->
    <div class="footer-links">
      <a href="about.php" class="footer-link">About AquaWiki</a>
      <a href="about_us.php" class="footer-link">About Us</a>
      <a href="credits.php" class="footer-link">Credits & Acknowledgments</a>
    </div>

  </div>
</footer>

<style>
.site-footer {
  background-color: #0d1b2a;
  color: #e0e1dd;
  text-align: center;
  padding: 40px 15px;
  font-family: 'Roboto', sans-serif;
  margin-top: 60px;
}

.footer-container {
  max-width: 1000px;
  margin: 0 auto;
}

.footer-tagline {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 15px;
  color: #ffffff;
}

.footer-disclaimer {
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 25px;
  color: #adb5bd;
}

/* Flex container for the two forms */
.footer-forms {
  display: flex;
  gap: 30px;
  justify-content: center;
  margin-bottom: 25px;
  flex-wrap: wrap; /* wrap on smaller screens */
}

/* Individual form styles */
.fish-submission,
.feedback-form {
  flex: 1 1 100px;
  color: #e0e1dd;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.fish-submission h3,
.feedback-form h3 {
  font-size: 1.1rem;
  margin-bottom: 10px;
  color: #00c2cb;
}

.fish-form {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
  max-width: 500px;
  text-align: center;
}

.fish-form .form-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.fish-form input,
.fish-form textarea {
  padding: 10px;
  border-radius: 6px;
  border: none;
  font-size: 0.95rem;
}

.fish-form textarea {
  min-height: 80px;
  resize: vertical;
}

.fish-form button {
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  background-color: #00c2cb;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  align-self: center;
  width: auto;
}

.fish-form button:hover {
  background-color: #009aa0;
}

/* Footer link section */
.footer-links {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
}

.footer-link {
  display: inline-block;
  text-decoration: underline; /* underline for clarity */
  color: #74c0fc;
  font-weight: 500;
  transition: color 0.3s, transform 0.2s;
}

.footer-link:hover {
  color: #a5d8ff;
  transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 900px) {
  .footer-forms {
    flex-direction: column;
    gap: 20px;
  }

  .fish-form button {
    width: 100%;
  }

  .footer-links {
    flex-direction: column;
    gap: 15px;
  }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  $('#footerFeedbackForm').on('submit', function(e){
    e.preventDefault(); // prevent page reload
    var formData = $(this).serialize();

    $.post('submit_feedback.php', formData, function(response){
      response = response.trim();

      if(response === "success"){
        $('#feedbackMsg').text('Feedback submitted!').css('color','#00c2cb').fadeIn(300).delay(2000).fadeOut(300);
        $('#footerFeedbackForm')[0].reset();
      } else if(response === "empty"){
        alert('Please enter a feedback message.');
      } else if(response === "unauthorized"){
        alert('You must be logged in to submit feedback.');
      } else if(response === "email_error"){
        alert('Feedback saved, but email could not be sent.');
        $('#footerFeedbackForm')[0].reset();
      } else if(response === "db_error"){
        alert('Failed to save feedback. Please try again.');
      } else {
        alert('Unexpected error. Please try again.');
      }

    }).fail(function(){
      alert('Server error. Please try again later.');
    });
  });
});
</script>

<script>
$(function(){
  $('#footerFishForm').on('submit', function(e){
    e.preventDefault(); // prevent page reload
    var formData = $(this).serialize();

    $.post('submit_fish.php', formData, function(response){
      response = response.trim();

      if(response === "success"){
        $('#fishMsg').text('Fish submission sent!').fadeIn(300).delay(2000).fadeOut(300);
        $('#footerFishForm')[0].reset();
      } else if(response === "empty"){
        alert('Please fill in all fields.');
      } else if(response === "unauthorized"){
        alert('You must be logged in to submit fish information.');
      } else if(response === "email_error"){
        $('#fishMsg').text('Submission saved, but email could not be sent.').fadeIn(300).delay(2000).fadeOut(300);
      } else if(response === "db_error"){
        alert('Failed to save submission. Please try again.');
      } else {
        alert('Unexpected error. Please try again.');
      }

    }).fail(function(){
      alert('Server error. Please try again later.');
    });
  });
});
</script>

