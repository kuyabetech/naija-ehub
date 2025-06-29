<!-- Spinner Overlay Include -->
<div id="globalSpinner" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:99999;background:rgba(255,255,255,0.65);align-items:center;justify-content:center;">
  <div style="display:flex;flex-direction:column;align-items:center;">
    <div class="spinner" style="border:6px solid #e0e0e0;border-top:6px solid #067c3c;border-radius:50%;width:48px;height:48px;animation:spin 1s linear infinite;"></div>
    <div style="margin-top:1rem;color:#067c3c;font-weight:600;">Loading...</div>
  </div>
</div>
<style>
  @keyframes spin {
    0% { transform: rotate(0deg);}
    100% { transform: rotate(360deg);}
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var spinner = document.getElementById('globalSpinner');
    spinner.style.display = 'flex';
    window.addEventListener('load', function() {
      spinner.style.display = 'none';
    });
  });
  if (window.jQuery) {
    $(document).ajaxStart(function(){ $('#globalSpinner').show(); });
    $(document).ajaxStop(function(){ $('#globalSpinner').hide(); });
  }
</script>
