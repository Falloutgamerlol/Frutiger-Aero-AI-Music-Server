# Frutiger-Aero-AI-Music-Server
A self-hosted, fully-local, **voice-controlled AI jukebox** built with PHP, WebSpeech, and a small but powerful LLM—designed to bring your old CD collection back to life in style.   Runs entirely on a single `index.php`, styled in full **Frutiger Aero** glory, and packed with features.
## ✨ Features

- 🎙️ **Voice-Controlled Playback** using the WebSpeech API
- 🤖 **LLM-Powered Command Parsing** (via local small model like a 0.5b LLM)
  - Say things like “list songs,” “play random,” or “play [track name]”
- 💿 **Automatic Album Art** using the iTunes API
- 📂 **Secure File Upload & Download**
  - Upload *any* file type to your server from anywhere
  - Download your ripped CDs on-the-go securely
- 📸 **Photo & Video Viewer** built into the file browser
- 🔐 **Secure Login System**
  - Keeps your private library locked down
- 🖼️ **Frutiger Aero UI**
  - Aero glass style, nostalgic gradients, Windows XP vibes
- 📁 **Simple Directory-Based Music Organization**
  - Music stored in folders under `www`—easy to manage, easy to back up

---

## 📦 Installation

1. Install [WAMP](https://www.wampserver.com/en/) on Windows
2. Drop `index.php` into your `www` directory
3. Organize your music like this:

www/ ├── Music/ │   ├── songname/ │   │   └── Songname.mp3 │   ├── songname/ │   │   └── songname.mp3 │   └── etc...

You must have each song in a separate folder, when the ai builds its json response the json is programmed to look for the directory then play the song within the directory.

4. (Optional) For secure remote access:
   - Install [Cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/)
   - Create a tunnel to your local WAMP server
   - Boom. Instant secure access from anywhere.

---

## 🧠 How It Works

- The LLM (a tiny local 0.5b model) runs locally and interprets voice commands, if you have a more powerful machine I recommend the gemma3n:e2b model, its state of the art at the moment and is incredibly accurate.
- It returns structured JSON (like a song list or playback command)
- `index.php` parses that JSON and handles everything from playback to randomization
- The browser handles album art fetches from iTunes and voice input through WebSpeech
- Fuzzy-matching for song names using Levenshtein distance (handles typos, vague names, or imprecise voice input)

---

## 🔐 Security Notes

- Uploads are protected by a **secure login system**
- Cloudflared ensures no ports are exposed
- The system does **not** require an internet connection except for:
  - Album art fetching (iTunes API)
  - Optional Cloudflared tunnel

---

## 🧪 Tested On

- ✅ **WAMP** (Windows-based Apache stack)
- ✅ Modern browsers (for WebSpeech API)
- ⚠️ *May work on XAMPP, LAMP, or other stacks with minor tweaks*

---

## 🚧 Coming Soon (Maybe)

- Playlist support: On its way soon!
- Mood-based playback (“play something chill”) 
- Download-as-zip for albums
- Config UI
- equalizer on its way in next build!
- media session api, already done but not in this build just yet

---

## 🙌 Why This Exists

I made this to revive my old CD collection and give it a second life—with the flair and nostalgia of Aero-era interfaces, but the power of modern LLMs and voice AI.

It’s fast, private, and honestly... kinda cool.

⚠️Disclaimers, code was ai assisted, if you want it to be fully ran locally you cant have voice recognition without a separate service running, this also applies to the itunes api integrated⚠️
