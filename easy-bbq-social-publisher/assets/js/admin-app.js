document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ebsp-menu-form');
    const btnCaption = document.getElementById('ebsp-btn-caption');
    const btnGenerate = document.getElementById('ebsp-btn-generate');
    const btnRegenerate = document.getElementById('ebsp-btn-regenerate');
    const btnPublish = document.getElementById('ebsp-btn-publish');
    const modal = document.getElementById('ebsp-modal');
    const captionArea = document.getElementById('ebsp-caption');
    const statusArea = document.getElementById('ebsp-status');

    let renderer = new window.EBSPCanvasRenderer('ebsp-canvas');
    let recordedChunks = [];
    let mediaRecorder = null;

    const imageMappings = { ...ebspSettings.defaultImages, ...ebspSettings.customImages };
    const currentImages = {
        starter: '',
        main1: '',
        main2: ''
    };

    const getFormData = () => {
        return {
            day: document.getElementById('ebsp-day').value,
            starter_title: document.getElementById('ebsp-starter-title').value,
            main1_title: document.getElementById('ebsp-main1-title').value,
            main2_title: document.getElementById('ebsp-main2-title').value,
            drink: document.getElementById('ebsp-drink').value,
            price: document.getElementById('ebsp-price').value,
            audio: document.getElementById('ebsp-audio').value,
            images: currentImages
        };
    };

    const updateImageManager = (field, value) => {
        const managerDiv = document.getElementById(`ebsp-${field}-image-manager`);
        if (!managerDiv) return;

        managerDiv.innerHTML = ''; // Clear previous

        if (!value) return;

        if (imageMappings[value]) {
            currentImages[field] = imageMappings[value];
            managerDiv.innerHTML = `<span class="ebsp-image-mapped">✅ Image mapped automatically.</span>`;
        } else {
            currentImages[field] = '';
            const btnGen = document.createElement('button');
            btnGen.type = 'button';
            btnGen.className = 'button button-secondary';
            btnGen.innerText = '🪄 Générer la photo par IA';
            btnGen.onclick = async () => {
                btnGen.disabled = true;
                btnGen.innerText = 'Generating...';
                try {
                    const response = await fetch(ebspSettings.restUrl + 'generate-dish-image', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': ebspSettings.nonce
                        },
                        body: JSON.stringify({ dish_name: value })
                    });
                    const res = await response.json();
                    if (response.ok && res.image_url) {
                        imageMappings[value] = res.image_url;
                        currentImages[field] = res.image_url;
                        managerDiv.innerHTML = `<span class="ebsp-image-mapped">✅ IA Image mapped.</span>`;
                    } else {
                        managerDiv.innerHTML = `<span style="color:red">Error: ${res.error || 'Unknown error'}</span>`;
                    }
                } catch(e) {
                    managerDiv.innerHTML = `<span style="color:red">Error: ${e.message}</span>`;
                }
            };
            managerDiv.appendChild(btnGen);
        }
    };

    document.querySelectorAll('.ebsp-dish-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const field = e.target.id.replace('ebsp-', '').replace('-title', '');
            updateImageManager(field, e.target.value.trim());
        });
    });

    const generateCaption = async () => {
        statusArea.innerText = 'Generating caption...';
        btnCaption.disabled = true;

        try {
            const data = getFormData();
            const response = await fetch(ebspSettings.restUrl + 'generate-caption', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ebspSettings.nonce
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.caption) {
                captionArea.value = result.caption;
                statusArea.innerText = 'Caption generated successfully!';
            } else {
                statusArea.innerText = 'Error generating caption: ' + (result.error || 'Unknown error');
            }
        } catch (e) {
            statusArea.innerText = 'Error generating caption: ' + e.message;
        } finally {
            btnCaption.disabled = false;
        }
    };

    const renderCanvas = async () => {
        btnGenerate.disabled = true;
        statusArea.innerText = 'Rendering canvas...';

        const data = getFormData();

        await renderer.render(data);
        modal.classList.remove('hidden');
        statusArea.innerText = 'Visual generated. Review and publish.';
        btnGenerate.disabled = false;
    };

    const startRecording = () => {
        return new Promise(async (resolve) => {
            const canvas = document.getElementById('ebsp-canvas');
            const videoStream = canvas.captureStream(30);

            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const destination = audioContext.createMediaStreamDestination();

            const data = getFormData();
            const audioUrl = ebspSettings.pluginUrl + 'assets/audio/' + data.audio + '-loop.mp3';

            try {
                const response = await fetch(audioUrl);
                const arrayBuffer = await response.arrayBuffer();
                const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

                const source = audioContext.createBufferSource();
                source.buffer = audioBuffer;
                source.loop = true;
                source.connect(destination);
                source.start();

                const audioTrack = destination.stream.getAudioTracks()[0];
                if (audioTrack) {
                    videoStream.addTrack(audioTrack);
                }
            } catch (e) {
                console.error("Audio loading failed", e);
            }

            try {
                mediaRecorder = new MediaRecorder(videoStream, { mimeType: 'video/webm; codecs=vp9' });
            } catch (e) {
                mediaRecorder = new MediaRecorder(videoStream);
            }

            recordedChunks = [];
            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = () => {
                resolve(new Blob(recordedChunks, { type: 'video/webm' }));
            };

            mediaRecorder.start();

            // Record for 3 seconds to create a short loop
            setTimeout(() => {
                mediaRecorder.stop();
            }, 3000);
        });
    };

    const publishMenu = async () => {
        statusArea.innerText = 'Preparing media...';
        btnPublish.disabled = true;

        try {
            // 1. Get Canvas Blob
            const canvas = document.getElementById('ebsp-canvas');
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));

            // 2. Record Canvas + Audio to video blob
            statusArea.innerText = 'Recording video snippet...';
            const videoBlob = await startRecording();

            // 3. Upload to WP
            statusArea.innerText = 'Uploading to server and dispatching webhook...';

            const formData = new FormData();

            formData.append('image', imageBlob, 'menu-' + Date.now() + '.png');
            formData.append('video', videoBlob, 'menu-' + Date.now() + '.webm');

            const data = getFormData();
            formData.append('day', data.day);
            formData.append('starter_title', data.starter_title);
            formData.append('main1_title', data.main1_title);
            formData.append('main2_title', data.main2_title);
            formData.append('drink', data.drink);
            formData.append('caption', captionArea.value);

            const response = await fetch(ebspSettings.restUrl + 'publish', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': ebspSettings.nonce
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                statusArea.innerText = 'Published successfully!';
                console.log(result.payload);
            } else {
                statusArea.innerText = 'Publish error: ' + (result.error || 'Unknown error');
            }
        } catch (e) {
            statusArea.innerText = 'Publish exception: ' + e.message;
        } finally {
            btnPublish.disabled = false;
        }
    };

    btnCaption.addEventListener('click', generateCaption);
    btnGenerate.addEventListener('click', renderCanvas);
    btnRegenerate.addEventListener('click', renderCanvas);
    btnPublish.addEventListener('click', publishMenu);
});
