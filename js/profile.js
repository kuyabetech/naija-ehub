// Add these functions to profile.js
async function loadRecentActivity() {
    try {
        const response = await fetch('user_profile.php?action=get_activity');
        const data = await response.json();
        
        if (data.success && data.activities) {
            const activityList = document.getElementById('activityList');
            activityList.innerHTML = '';
            
            data.activities.forEach(activity => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                item.innerHTML = `
                    <div class="activity-icon">
                        <i class="fas fa-${getActivityIcon(activity.activity_type)}"></i>
                    </div>
                    <div class="activity-details">
                        <p>${formatActivityText(activity)}</p>
                        <small>${new Date(activity.activity_date).toLocaleString()}</small>
                    </div>
                `;
                activityList.appendChild(item);
            });
        }
    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

function getActivityIcon(type) {
    const icons = {
        'login': 'sign-in-alt',
        'password_change': 'key',
        'profile_update': 'user-edit',
        'preferences_update': 'cog',
        'avatar_change': 'image'
    };
    return icons[type] || 'info-circle';
}

function formatActivityText(activity) {
    const texts = {
        'login': 'You logged in from ${activity.ip_address}',
        'password_change': 'You changed your password',
        'profile_update': 'You updated your profile information',
        'preferences_update': 'You updated your preferences',
        'avatar_change': 'You changed your profile picture'
    };
    return texts[activity.activity_type] || 'Activity recorded';
}

// Add to DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Load activity if activity tab exists
    if (document.getElementById('activityTab')) {
        loadRecentActivity();
    }
    
    // Add session management
    if (document.getElementById('sessionsTab')) {
        loadActiveSessions();
    }
});
async function loadActiveSessions() {
    try {
        const response = await fetch('user_profile.php?action=get_sessions');
        const data = await response.json();
        
        if (data.success && data.sessions) {
            const sessionsList = document.getElementById('sessionsList');
            sessionsList.innerHTML = '';
            
            data.sessions.forEach(session => {
                const sessionItem = document.createElement('div');
                sessionItem.className = 'session-item';
                sessionItem.innerHTML = `
                    <div class="session-info">
                        <div class="session-meta">
                            <span class="session-device">${parseUserAgent(session.user_agent)}</span>
                            <span class="session-ip">${session.ip_address}</span>
                        </div>
                        <div class="session-time">
                            <span>Last active: ${new Date(session.last_activity).toLocaleString()}</span>
                        </div>
                    </div>
                    <div class="session-actions">
                        ${session.current ? 
                            '<span class="badge current">Current Session</span>' : 
                            '<button class="btn btn-sm btn-danger end-session" data-id="${session.session_id}">End Session</button>'
                        }
                    </div>
                `;
                sessionsList.appendChild(sessionItem);
            });
            
            // Add event listeners to end session buttons
            document.querySelectorAll('.end-session').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (confirm('Are you sure you want to end this session?')) {
                        const sessionId = btn.dataset.id;
                        const formData = new URLSearchParams();
                        formData.append('action', 'terminate_session');
                        formData.append('sessionId', sessionId);
                        
                        const response = await fetch('user_profile.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            showAlert('Session terminated successfully', 'success');
                            loadActiveSessions();
                        } else {
                            showAlert(result.message || 'Failed to terminate session', 'error');
                        }
                    }
                });
            });
        }
    } catch (error) {
        showAlert('Failed to load sessions', 'error');
    }
}

function parseUserAgent(userAgent) {
    if (!userAgent) return 'Unknown Device';
    
    if (/mobile/i.test(userAgent)) {
        return 'Mobile Device';
    } else if (/tablet/i.test(userAgent)) {
        return 'Tablet';
    } else if (/windows/i.test(userAgent)) {
        return 'Windows PC';
    } else if (/macintosh|mac os x/i.test(userAgent)) {
        return 'Mac';
    } else if (/linux/i.test(userAgent)) {
        return 'Linux PC';
    }
    
    return 'Desktop Device';
}

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.profile-tab-content').forEach(tab => tab.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.tab + 'Tab').classList.add('active');
  });
});

// Personal Info Form Submission (AJAX)
document.getElementById('personalInfoForm').addEventListener('submit', function (e) {
  e.preventDefault();
  fetch('services/update_profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'personal',
      fullName: document.getElementById('fullName').value,
      email: document.getElementById('email').value,
      phone: document.getElementById('phone').value,
      dob: document.getElementById('dob').value,
      address: document.getElementById('address').value
    })
  })
    .then(res => res.json())
    .then(data => {
      showConfirmation(data.success ? 'Success' : 'Error', data.message || data.error);
    });
});

// Security (Password) Form Submission (AJAX)
document.getElementById('securityForm').addEventListener('submit', function (e) {
  e.preventDefault();
  fetch('services/update_profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'password',
      currentPassword: document.getElementById('currentPassword').value,
      newPassword: document.getElementById('newPassword').value,
      confirmPassword: document.getElementById('confirmPassword').value
    })
  })
    .then(res => res.json())
    .then(data => {
      showConfirmation(data.success ? 'Success' : 'Error', data.message || data.error);
    });
});

// Password Strength Meter
document.getElementById('newPassword').addEventListener('input', function () {
  const val = this.value;
  const bar = document.querySelector('.strength-bar');
  const text = document.getElementById('strengthText');
  let strength = 0;
  if (val.length >= 8) strength++;
  if (/\d/.test(val)) strength++;
  if (/[A-Z]/.test(val)) strength++;
  if (/[^A-Za-z0-9]/.test(val)) strength++;
  bar.style.width = (strength * 25) + '%';
  if (strength <= 1) {
    bar.style.background = '#e74c3c';
    text.textContent = 'Weak';
  } else if (strength === 2) {
    bar.style.background = '#f1c40f';
    text.textContent = 'Fair';
  } else if (strength === 3) {
    bar.style.background = '#2980b9';
    text.textContent = 'Good';
  } else {
    bar.style.background = '#27ae60';
    text.textContent = 'Strong';
  }
});

// Avatar Upload Preview
document.getElementById('avatarUpload').addEventListener('change', function (e) {
  const file = e.target.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = function (evt) {
      document.querySelector('#avatarPreview img').src = evt.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// Remove Photo
document.getElementById('removePhoto').addEventListener('click', function () {
  document.querySelector('#avatarPreview img').src = '../assets/img/avatar-placeholder.png';
  document.getElementById('avatarUpload').value = '';
});

// Confirmation Modal Logic
function showConfirmation(title, message) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalMessage').textContent = message;
  document.getElementById('confirmationModal').style.display = 'flex';
}
document.querySelector('.close-modal').onclick =
  document.getElementById('cancelConfirm').onclick = function () {
    document.getElementById('confirmationModal').style.display = 'none';
  };
document.getElementById('confirmAction').onclick = function () {
  document.getElementById('confirmationModal').style.display = 'none';
};

// Preferences Form (optional: implement AJAX if you want to save preferences server-side)
document.getElementById('preferencesForm').addEventListener('submit', function (e) {
  e.preventDefault();
  showConfirmation('Preferences Saved', 'Your preferences have been updated.');
});