# Audio Assets Directory

This folder is designated for audio files (sound effects, intro jingles, and background tracks) used in the **Barangay Tabon Information System**.

## Preloader Audio Integration

You can place your preloader sound effect or background track here:
- Recommended file: `Audio/intro.mp3` (or `.wav` / `.ogg`)
- The preloader in `preloader.php` is configured to look for `Audio/intro.mp3` by default.

### Browser Autoplay Policy Note
Modern browsers require user interaction before unmuted audio can play automatically. The preloader script gracefully attempts playback and safely catches any browser restrictions without blocking the visual animation.
