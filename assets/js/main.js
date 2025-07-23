document.addEventListener('DOMContentLoaded', () => {
    const videoFile = document.getElementById('videoFile');
    const fileNameEl = document.getElementById('fileName');
    const uploadButton = document.getElementById('uploadButton');
    const progressBar = document.getElementById('progressBar');
    const statusEl = document.getElementById('status');
    const videoListEl = document.getElementById('videoList');

    let selectedFile = null;

    videoFile.addEventListener('change', () => {
        selectedFile = videoFile.files[0];
        if (selectedFile) {
            fileNameEl.textContent = selectedFile.name;
            uploadButton.disabled = false;
        }
    });

    uploadButton.addEventListener('click', () => {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('video', selectedFile);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'uploads.php', true);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                statusEl.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                statusEl.style.color = 'var(--text-light)';
            }
        });

        xhr.onload = () => {
            progressBar.style.width = '0%';
            uploadButton.disabled = true;
            fileNameEl.textContent = 'Click to choose a file...';
            videoFile.value = '';

            try {
                const response = JSON.parse(xhr.responseText);
                statusEl.textContent = response.message;
                statusEl.style.color = (xhr.status === 200) ? 'var(--success-color)' : 'red';
                
                if (xhr.status === 200) {
                    const newVideo = createVideoItem(response.data);
                    videoListEl.prepend(newVideo);
                }
            } catch (error) {
                statusEl.textContent = 'An unexpected error occurred. Check console for details.';
                statusEl.style.color = 'red';
                console.error("Parse Error:", error, "Server Response:", xhr.responseText);
            }
        };

        xhr.onerror = () => {
            statusEl.textContent = 'Upload failed. Check your network connection.';
            statusEl.style.color = 'red';
        };

        xhr.send(formData);
    });

    async function loadVideos() {
        try {
            const response = await fetch('list-videos.php');
            const videos = await response.json();
            videoListEl.innerHTML = '';
            if (videos.length === 0) {
                videoListEl.innerHTML = '<p>No files uploaded yet.</p>';
            } else {
                videos.forEach(video => {
                    const videoItem = createVideoItem(video);
                    videoListEl.appendChild(videoItem);
                });
            }
        } catch (error) {
            videoListEl.innerHTML = '<p>Could not load file library.</p>';
        }
    }

    function createVideoItem(video) {
        const item = document.createElement('a');
        item.className = 'video-item';
        item.href = `watch.html?v=${encodeURIComponent(video.file_path)}&title=${encodeURIComponent(video.original_name)}`;
        
        const date = new Date(video.uploaded_at).toLocaleString();

        item.innerHTML = `
            <div class="video-item-icon"></div>
            <div class="video-item-details">
                <div class="video-item-title">${video.original_name}</div>
                <div class="video-item-date">Uploaded: ${date}</div>
            </div>
        `;
        return item;
    }

    loadVideos();
});
