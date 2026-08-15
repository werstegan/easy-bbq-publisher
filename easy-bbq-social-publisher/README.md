# Easy BBQ Social Publisher

A standalone WordPress plugin to generate 9:16 promotional menu flyers and video snippets for Instagram Reels and TikTok.

## Features
- Generates 1080x1920 visuals using HTML5 Canvas.
- AI-generated captions via Google Gemini.
- High-quality food visual generation via Google Imagen 3 API.
- Converts Canvas output and audio loops into short MP4/WebM videos via MediaRecorder.
- Dispatches payload (images, video, caption) via Webhook.

## Directory Structure
- `easy-bbq-social-publisher.php` : Main plugin file.
- `includes/`: PHP classes for settings, REST API, Gemini/Imagen 3 integration, and media handling.
- `assets/css/`: Admin styles.
- `assets/js/`: Logic for Canvas rendering and admin UI (including media recorder).
- `assets/images/` : Image assets.
- `assets/audio/` : Audio loops for the generated video clips.
