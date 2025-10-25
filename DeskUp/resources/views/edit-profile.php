<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp - Edit Profile</title>
    <link rel="stylesheet" href="css/profile-style.css">
</head>
<body>
    <a href="#" class="back-button" onclick="history.back()">
        <img src="{{ asset ('assets/back.png') }}" alt="Back">
    </a>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="header-content">
                <div class="profile-main">
                    <img src="{{ asset ('assets/default-avatar.png') }}" alt="Profile Picture" class="profile-picture">
                    <div class="profile-info">
                        <h1>Alex Johnson</h1>
                        <p class="position">Senior UX Designer</p>
                        <p class="location">
                            <svg class="location-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            Sønderborg Office, 3rd Floor
                        </p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.93 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </button>
                    <button class="settings-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="profile-content full-width">
            <!-- Edit info -->
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Edit Personal Information</h2>
                </div>
                <form class="edit-form">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" value="Alex">
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" value="Johnson">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="alex.johnson@company.com">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="+45 12345678">
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="Senior UX Designer">
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="Product Design">
                    </div>
                    <div class="form-group">
                        <label for="birthDate">Date of Birth</label>
                        <input type="date" id="birthDate" name="birthDate" value="1985-06-15">
                    </div>
                    <div class="form-group">
                        <label for="joinDate">Join Date</label>
                        <input type="date" id="joinDate" name="joinDate" value="2020-03-15" disabled>
                    </div>
                    <div class="form-group">
                        <label for="employeeId">Employee ID</label>
                        <input type="text" id="employeeId" name="employeeId" value="EMP-0452" disabled>
                    </div>
                    <div class="form-group full-width">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3">Nørregade 45, 6400 Sønderborg, Denmark</textarea>
                    </div>
                    <div class="form-group">
                        <label for="officeLocation">Office Location</label>
                        <select id="officeLocation" name="officeLocation">
                            <option value="sonderborg-3" selected>Sønderborg Office, 3rd Floor</option>
                            <option value="sonderborg-2">Sønderborg Office, 2nd Floor</option>
                            <option value="copenhagen">Copenhagen Office</option>
                            <option value="aarhus">Aarhus Office</option>
                            <option value="odense">Odense Office</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="workSchedule">Work Schedule</label>
                        <select id="workSchedule" name="workSchedule">
                            <option value="full-time" selected>Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="flexible">Flexible</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="#" class="cancel-btn" onclick="history.back()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                            Cancel
                        </a>
                        <button type="submit" class="save-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Future database implementation
        document.querySelector('.edit-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Save changes (implement logic here)
            alert('Changes saved successfully!');
            
            // Redirection (dependent on server response)
            window.location.href = 'profile'; 
        });
    </script>
</body>
</html>