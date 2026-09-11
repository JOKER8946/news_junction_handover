<?php
include './inc/php/db_config.php';
include './inc/php/validate.logged.php';
include './inc/php/function.php';

// Fetch active complaint email contacts
$departmentContacts = [];
$contactSql = "SELECT id, name, email, department FROM complaint_email_contacts WHERE is_active = 1 ORDER BY department ASC, name ASC";
$contactResult = $creamdb->query($contactSql);
if ($contactResult) {
    $departmentContacts = $contactResult->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - News Junction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="../grfx/img/logo.ico">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.zohostatic.com/books/zfwidgets/assets/js/zf-widget.js"></script>
    <script src="inc/js/common.js"></script>
    <link rel="stylesheet" href="inc/css/social.css">

    <style>
        .complaint-form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .complaint-card {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: none;
        }

        .complaint-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }

        .complaint-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .complaint-header p {
            color: var(--text-primary);
            margin-top: 8px;
            margin-bottom: 0;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-label.required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-control,
        .form-control[readonly] {
            background-color: var(--bg-card);
            width: 100%;
            padding: 2px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 30px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .file-input-label:hover {
            border-color: #667eea;
            background: #f5f8ff;
        }

        .file-input-label.has-file {
            border-color: #27ae60;
            background: #f0fdf4;
        }

        .file-input-label i {
            font-size: 24px;
            color: #667eea;
        }

        .file-input-label.has-file i {
            color: #27ae60;
        }

        .file-input-text {
            text-align: center;
        }

        .file-input-text strong {
            color: #333;
            display: block;
            margin-bottom: 4px;
        }

        .file-input-text span {
            color: #999;
            font-size: 12px;
        }

        #complaintFileInput {
            display: none;
        }

        .file-preview {
            margin-top: 12px;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 8px;
            display: none;
        }

        .file-preview.show {
            display: block;
        }

        .file-preview-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #333;
        }

        .file-remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }

        .file-remove-btn:hover {
            background: #c0392b;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-action {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.show {
            display: block;
        }

        .btn-audio-to-text {
            width: 50px;
            height: 50px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .btn-audio-to-text:hover {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-audio-to-text.recording {
            background: #e74c3c;
            border-color: #e74c3c;
            color: white;
            animation: pulse 1.5s infinite;
        }

        .mic-container {
            display: flex;
            gap: 10px;
        }

        .mic-lang {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 5px;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
            }
        }

        @media (max-width: 768px) {

            .complaint-card {
                padding: 20px;
            }

            .complaint-header h2 {
                font-size: 20px;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 550px) {

            .mic-lang {
                flex-direction: row;
            }

            .mic-container {
                flex-direction: column;
            }
        }
    </style>

</head>

<body>
    <div class="containers">
        <?php include 'inc/php/social_navbar.php'; ?>
        <?php include 'inc/php/social_sidebar.php'; ?>

        <div class="search-main-content main-content">
            <div class="complaint-form-container">
                <div class="complaint-card">
                    <div class="complaint-header">
                        <h2>
                            <i class="fas fa-exclamation-circle"></i>
                            Submit a Complaint
                        </h2>
                        <p>Tell us about your issue and we'll help you reach your complaint to relavent stake holders.</p>
                    </div>

                    <div id="alertMessage" class="alert"></div>
                    <div id="loadingSpinner" class="loading-spinner">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #667eea;"></i>
                        <p style="margin-top: 10px; color: #999;">Submitting your complaint...</p>
                    </div>

                    <form id="complaintForm">
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control" id="pincode" name="pincode"
                                placeholder="Your pincode" readonly
                                value="<?php echo htmlspecialchars($gUserPincode ?? ''); ?>">
                            <small style="color: #999;">Your registered pincode is automatically associated with this complaint</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Subject</label>
                            <input type="text" class="form-control" id="title" name="title"
                                placeholder="Brief subject of your complaint" required
                                maxlength="255">
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Description</label>
                            <div class="mic-container">
                                <div style="flex: 1;">
                                    <textarea class="form-control" id="description" name="description"
                                        placeholder="Please provide detailed information about your issue..."
                                        required style="width: 100%;"></textarea>

                                </div>
                                <div class="mic-lang">
                                    <button type="button" id="audioToTextBtn" class="btn-audio-to-text" title="Click to speak - Voice will be converted to text">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                    <div style="display: flex; gap: 8px; margin-top: 8px; align-items: center;">
                                        <select id="languageSelect" class="form-control" style="flex: 1; padding: 8px; font-size: 12px;">
                                            <option value="en-IN">English (India)</option>
                                            <option value="kn-IN">ಕನ್ನಡ (Kannada)</option>
                                            <option value="hi-IN">हिन्दी (Hindi)</option>
                                            <option value="te-IN">తెలుగు (Telugu)</option>
                                            <option value="ta-IN">தமிழ் (Tamil)</option>
                                            <option value="ml-IN">മലയാളം (Malayalam)</option>
                                            <option value="gu-IN">ગુજરાતી (Gujarati)</option>
                                            <option value="mr-IN">मराठी (Marathi)</option>
                                            <option value="bn-IN">বাংলা (Bengali)</option>
                                            <option value="pa-IN">ਪੰਜਾਬੀ (Punjabi)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <small id="audioStatus" style="color: #999; display: none; margin-top: 5px;"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Select Department</label>
                            <select class="form-control" id="departmentSelect" name="departmentSelect" required>
                                <option value="">-- Select Department to Report --</option>
                                <?php foreach ($departmentContacts as $contact): ?>
                                    <option value="<?php echo htmlspecialchars($contact['id']); ?>" data-email="<?php echo htmlspecialchars($contact['email']); ?>" data-name="<?php echo htmlspecialchars($contact['name']); ?>">
                                        <?php echo htmlspecialchars($contact['department'] ?? 'Other'); ?> - <?php echo htmlspecialchars($contact['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Hidden fields to store selected department info -->
                        <input type="hidden" id="departmentName" name="departmentName">
                        <input type="hidden" id="departmentEmail" name="departmentEmail">

                        <div class="form-group">
                            <label class="form-label">Attach Media (Optional)</label>
                            <div class="file-input-wrapper">
                                <label for="complaintFileInput" class="file-input-label">
                                    <div class="file-input-text">
                                        <strong>Click to upload or drag and drop</strong>
                                        <span>Images (PNG, JPG)</span>
                                    </div>
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </label>
                                <!-- <input type="file" id="complaintFileInput" name="media" accept="image/*,video/*,.pdf"> -->
                                <input type="file" id="complaintFileInput" name="media" accept="image/*">
                            </div>
                            <div id="filePreview" class="file-preview"></div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-action btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Submit Complaint
                            </button>
                            <a href="my_complaints.php" class="btn-action btn-cancel">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php include 'inc/php/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = document.getElementById('complaintForm');
        const fileInput = document.getElementById('complaintFileInput');
        const fileLabel = document.querySelector('.file-input-label');
        const filePreview = document.getElementById('filePreview');
        const alertMessage = document.getElementById('alertMessage');
        const loadingSpinner = document.getElementById('loadingSpinner');
        let selectedFile = null;

        // Handle department selection
        document.getElementById('departmentSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('departmentName').value = selectedOption.getAttribute('data-name');
                document.getElementById('departmentEmail').value = selectedOption.getAttribute('data-email');
            } else {
                document.getElementById('departmentName').value = '';
                document.getElementById('departmentEmail').value = '';
            }
        });

        fileLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = '#667eea';
            fileLabel.style.background = '#f5f8ff';
        });

        fileLabel.addEventListener('dragleave', () => {
            fileLabel.style.borderColor = '#ddd';
            fileLabel.style.background = '#fafafa';
        });

        fileLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = '#ddd';
            fileLabel.style.background = '#fafafa';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            const maxSize = 50 * 1024 * 1024; // 50MB for videos
            const validTypes = ['image/png', 'image/jpeg', 'image/gif', 'application/pdf', 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

            if (!validTypes.includes(file.type)) {
                showAlert('Invalid file type. Please upload images (PNG, JPG, GIF), videos (MP4, WebM, OGG), or PDF.', 'error');
                fileInput.value = '';
                return;
            }

            if (file.size > maxSize) {
                showAlert('File size exceeds 50MB limit.', 'error');
                fileInput.value = '';
                return;
            }

            selectedFile = file;
            fileLabel.classList.add('has-file');
            filePreview.classList.add('show');
            filePreview.innerHTML = `
                <div class="file-preview-item">
                    <span><i class="fas fa-check-circle" style="color: #27ae60; margin-right: 8px;"></i>${file.name}</span>
                    <button type="button" class="file-remove-btn" onclick="removeFile()">Remove</button>
                </div>
            `;
        }

        function removeFile() {
            fileInput.value = '';
            selectedFile = null;
            fileLabel.classList.remove('has-file');
            filePreview.classList.remove('show');
            filePreview.innerHTML = '';
        }

        function showAlert(message, type = 'error') {
            alertMessage.className = `alert ${type === 'error' ? 'alert-error' : 'alert-success'} show`;
            alertMessage.innerHTML = message;
            if (type === 'success') {
                setTimeout(() => alertMessage.classList.remove('show'), 3000);
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const departmentSelect = document.getElementById('departmentSelect').value.trim();
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const departmentName = document.getElementById('departmentName').value.trim();
            const departmentEmail = document.getElementById('departmentEmail').value.trim();

            if (!departmentSelect || !title || !description || !departmentName || !departmentEmail) {
                showAlert('Please select a department and fill in all required fields.', 'error');
                return;
            }

            loadingSpinner.classList.add('show');

            const formData = new FormData();
            formData.append('action', 'submitComplaint');
            formData.append('title', title);
            formData.append('description', description);
            formData.append('departmentName', departmentName);
            formData.append('departmentEmail', departmentEmail);
            if (selectedFile) {
                formData.append('media', selectedFile);
            }

            try {
                const response = await fetch('inc/php/complaint_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                loadingSpinner.classList.remove('show');

                if (data.status === 'success') {
                    showAlert('✓ Complaint submitted successfully! Ticket ID: ' + data.ticket_id, 'success');
                    form.reset();
                    removeFile();
                    setTimeout(() => {
                        window.location.href = 'my_complaints.php';
                    }, 2000);
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to submit complaint'), 'error');
                }
            } catch (error) {
                loadingSpinner.classList.remove('show');
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        // Speech-to-Text functionality
        const audioBtn = document.getElementById('audioToTextBtn');
        const audioStatus = document.getElementById('audioStatus');
        const descriptionField = document.getElementById('description');
        const languageSelect = document.getElementById('languageSelect');
        let isListening = false;
        let recognition;
        let silenceTimeout;
        let initialText = ''; // Store text before recording started
        const SILENCE_THRESHOLD = 3000; // 3 seconds of silence to auto-stop

        // Initialize Web Speech API
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = languageSelect.value; // Set initial language

            // Update language when changed
            languageSelect.addEventListener('change', () => {
                if (!isListening) {
                    recognition.lang = languageSelect.value;
                    const langName = languageSelect.options[languageSelect.selectedIndex].text;
                    audioStatus.textContent = `Language changed to ${langName}`;
                    audioStatus.style.display = 'block';
                    audioStatus.style.color = '#667eea';
                    setTimeout(() => {
                        audioStatus.style.display = 'none';
                    }, 2000);
                }
            });

            let interimTranscript = '';
            let finalTranscript = '';

            recognition.onstart = () => {
                isListening = true;
                audioBtn.classList.add('recording');
                audioBtn.innerHTML = '<i class="fas fa-pause"></i>'; // Change to pause icon
                audioStatus.style.display = 'block';
                const langName = languageSelect.options[languageSelect.selectedIndex].text;
                audioStatus.textContent = `🎤 Listening in ${langName}... (Click to stop)`;
                audioStatus.style.color = '#e74c3c';
                initialText = descriptionField.value.trim(); // Save initial text before recording
                interimTranscript = '';
                finalTranscript = '';
                clearTimeout(silenceTimeout); // Reset timeout on start
            };

            recognition.onresult = (event) => {
                // Clear the silence timeout whenever we get speech
                clearTimeout(silenceTimeout);

                interimTranscript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += transcript + ' ';
                    } else {
                        interimTranscript += transcript;
                    }
                }

                // Show real-time text in textarea - combine initial text with new transcripts only
                let displayText = initialText;
                if (finalTranscript || interimTranscript) {
                    displayText += (initialText ? ' ' : '') + finalTranscript + interimTranscript;
                }
                descriptionField.value = displayText.trim();

                // Show interim results in status
                if (interimTranscript) {
                    audioStatus.textContent = '🎤 Listening... "' + interimTranscript + '"';
                } else {
                    audioStatus.textContent = '🎤 Listening in ' + languageSelect.options[languageSelect.selectedIndex].text + '... (Click to stop)';
                }

                // Set timeout to auto-stop after silence
                silenceTimeout = setTimeout(() => {
                    recognition.stop();
                }, SILENCE_THRESHOLD);
            };

            recognition.onerror = (event) => {
                clearTimeout(silenceTimeout);
                audioStatus.textContent = '❌ Error: ' + event.error;
                audioStatus.style.color = '#e74c3c';
                console.error('Speech recognition error', event.error);
            };

            recognition.onend = () => {
                isListening = false;
                audioBtn.classList.remove('recording');
                audioBtn.innerHTML = '<i class="fas fa-microphone"></i>'; // Change back to mic icon
                clearTimeout(silenceTimeout);

                if (finalTranscript.trim()) {
                    audioStatus.textContent = '✓ Text added to description';
                    audioStatus.style.color = '#27ae60';
                } else {
                    audioStatus.textContent = '⚠️ No speech detected. Please try again.';
                    audioStatus.style.color = '#f39c12';
                }

                setTimeout(() => {
                    audioStatus.style.display = 'none';
                }, 3000);
            };

            audioBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (isListening) {
                    recognition.stop();
                } else {
                    try {
                        recognition.start();
                    } catch (error) {
                        console.log('Recognition already started');
                    }
                }
            });
        } else {
            // Fallback message if Web Speech API is not supported
            audioBtn.addEventListener('click', (e) => {
                e.preventDefault();
                alert('Speech Recognition is not supported in your browser.\nPlease try using Chrome, Edge, or Safari.');
            });
            audioBtn.style.opacity = '0.5';
            audioBtn.style.cursor = 'not-allowed';
        }
    </script>

</body>

</html>