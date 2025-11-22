<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp - Edit Profile</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
    <a href="{{ route('profile') }}" class="back-button">
        <img src="{{ asset('assets/back.png') }}" alt="Back">
    </a>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="header-content">
                <div class="profile-main">
                    <img src="{{ $userProfile->profile_picture ?? asset('assets/default-avatar.png') }}" alt="Profile Picture" class="profile-picture" id="profilePicturePreview">
                    <div class="profile-info">
                        <h1>{{ $user->name ?? 'Guest' }}</h1>
                        <p class="position">
                            @if($user->is_admin)
                                Admin
                            @else
                                User
                            @endif
                        </p>
                        <p class="location">
                            <svg class="location-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            {{ $userProfile->location ?? 'Location not set' }}
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
                
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                <form class="edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- Profile Picture Upload Section -->
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <div class="profile-picture-upload-container">
                            <div class="current-picture">
                                <img src="{{ $userProfile->profile_picture ?? asset('assets/default-avatar.png') }}" alt="Current Profile Picture" id="currentProfilePicture">
                                @if($userProfile && $userProfile->profile_picture)
                                <button type="button" class="remove-picture-btn" id="removeProfilePicture">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                    Remove
                                </button>
                                @endif
                            </div>
                            <div class="upload-controls">
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="file-input">
                                <label for="profile_picture" class="upload-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                    </svg>
                                    Upload New Photo
                                </label>
                                <div class="file-info" id="fileInfo">No file chosen</div>
                                <div class="upload-hint">JPG, PNG or GIF. Max 2MB. Recommended: 200x200px</div>
                            </div>
                        </div>
                        @error('profile_picture')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Editable fields (users) -->
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <!-- Editable fields (user_profiles) -->
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $userProfile->phone ?? '') }}">
                        @error('phone')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $userProfile->date_of_birth ?? '') }}">
                        @error('date_of_birth')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $userProfile->location ?? '') }}">
                        @error('location')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="ideal_height">Ideal Height (cm)</label>
                        <input type="number" id="ideal_height" name="ideal_height" step="0.01" value="{{ old('ideal_height', $userProfile->ideal_height ?? '') }}">
                        @error('ideal_height')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <!-- Non changeable fields -->
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input type="text" id="user_id" value="{{ $user->id }}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_desk">Assigned Desk</label>
                        <input type="text" id="assigned_desk" value="{{ $user->assigned_desk_id ? 'Desk ' . $user->assigned_desk_id : 'Not assigned' }}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_type">Account Type</label>
                        <input type="text" id="account_type" value="{{ $user->is_admin ? 'Administrator' : 'Regular User' }}" disabled>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('profile') }}" class="cancel-btn">
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

    <!-- Include Footer Component -->
    @include('components.footer')
    </div>

    <script>
        // visual thing
        document.querySelector('.edit-form').addEventListener('submit', function(e) {
            const saveBtn = this.querySelector('.save-btn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                </svg>
                Saving...
            `;
        });

        // View file
        const fileInput = document.getElementById('profile_picture');
        const fileInfo = document.getElementById('fileInfo');
        const currentPicture = document.getElementById('currentProfilePicture');
        const profilePicturePreview = document.getElementById('profilePicturePreview');

        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                fileInfo.textContent = this.files[0].name;
                
                // Image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentPicture.src = e.target.result;
                    profilePicturePreview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                fileInfo.textContent = 'No file chosen';
            }
        });

        // Delete photo 
        const removePictureBtn = document.getElementById('removeProfilePicture');
        if (removePictureBtn) {
            removePictureBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove your profile picture?')) {
                    fetch('{{ route("profile.picture.delete") }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reset default
                            const defaultAvatar = '{{ asset("assets/default-avatar.png") }}';
                            currentPicture.src = defaultAvatar;
                            profilePicturePreview.src = defaultAvatar;
                            
                            // hide button
                            removePictureBtn.style.display = 'none';
                            
                            // Reset file input
                            fileInput.value = '';
                            fileInfo.textContent = 'No file chosen';
                            
                            alert('Profile picture removed successfully');
                        } else {
                            alert('Error removing profile picture: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error removing profile picture');
                    });
                }
            });
        }
    </script>
</body>
</html>