<!-- WhatsApp Floating Button -->
  <button id="whatsappFloatBtn" style="position:fixed;bottom:30px;right:30px;z-index:9998;background:#067c3c;color:#fff;border:none;border-radius:50%;width:56px;height:56px;box-shadow:0 2px 8px rgba(0,0,0,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:2rem;">
    <i class="fab fa-whatsapp"></i>
  </button>

  <!-- WhatsApp Chat Popup -->
  <div id="whatsappChatPopup" style="position:fixed;bottom:100px;right:30px;z-index:9999;display:none;box-shadow:0 2px 12px rgba(0,0,0,0.18);">
    <div style="background:#067c3c;color:#fff;padding:1rem 1.2rem;border-radius:12px 12px 0 0;display:flex;align-items:center;gap:0.5rem;">
      <i class="fab fa-whatsapp fa-lg"></i>
      <strong>Need Help?</strong>
      <button onclick="closeWhatsappPopup()" style="background:none;border:none;color:#fff;font-size:1.2rem;margin-left:auto;cursor:pointer;">&times;</button>
    </div>
    <div style="background:#fff;border:1px solid #067c3c;border-top:none;padding:1rem;border-radius:0 0 12px 12px;min-width:260px;">
      <div style="margin-bottom:0.7rem;">Hi there! How can we help you today?</div>
      <div style="display:flex;flex-direction:column;gap:0.5rem;">
        <button onclick="sendWhatsapp('I need help with my account')" style="background:#067c3c;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;">Account Help</button>
        <button onclick="sendWhatsapp('I have a payment issue')" style="background:#067c3c;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;">Payment Issue</button>
        <button onclick="sendWhatsapp('I need support with a service')" style="background:#067c3c;color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;">Service Support</button>
      </div>
      <div style="margin-top:1rem;text-align:center;">
        <a href="https://wa.me/2349034095385" id="whatsappDirectLink" target="_blank" style="color:#067c3c;text-decoration:none;font-weight:bold;">
          <i class="fab fa-whatsapp"></i> Chat on WhatsApp
        </a>
      </div>
    </div>
  </div>

  <script>
    // WhatsApp popup logic
      var floatBtn = document.getElementById('whatsappFloatBtn');
      var popup = document.getElementById('whatsappChatPopup');
      // Show popup on first load
      setTimeout(function() {
        popup.style.display = 'block';
        floatBtn.style.display = 'none';
      }, 600);
      // Toggle popup on float button click
      floatBtn.addEventListener('click', function() {
        popup.style.display = 'block';
        floatBtn.style.display = 'none';
      });
    

    function closeWhatsappPopup() {
      document.getElementById('whatsappChatPopup').style.display = 'none';
      document.getElementById('whatsappFloatBtn').style.display = 'flex';
    }

    function sendWhatsapp(message) {
      var phone = "2349034095385"; // Updated WhatsApp number
      var url = "https://wa.me/" + phone + "?text=" + encodeURIComponent(message);
      window.open(url, '_blank');
    }
  </script>