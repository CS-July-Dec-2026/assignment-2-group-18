# 📷 PixelNest — Insecure File Upload Demo (CWE-434)

**Vulnerability:** CWE-434 — Unrestricted Upload of File with Dangerous Type
**Video walkthrough:** [Watch here](https://drive.google.com/file/d/1zw_3SBJ-r4CJe7mXNu53GdmBXYFelMO2/view?usp=sharing)

PixelNest is a small "upload a profile photo" app built to demonstrate CWE-434 —
a vulnerable version that can be exploited for remote code execution, and a fixed
version that blocks the same attack.

---

## What's in the repo

- **`public/`** — the vulnerable app
- **`secure-version/`** — the same app, fixed

## The vulnerability

The vulnerable `upload.php` has three flaws:

1. **Weak extension check** — it only checks if `.jpg`/`.png` appears *anywhere* in the filename. A file named `shell.jpg.php` passes.
2. **Trusts the Content-Type header** — this is sent by the client and easily spoofed.
3. **Saves the file under its original name** in a public folder that executes PHP.

Together, this lets an attacker upload a disguised PHP file and run it directly on
the server — full remote code execution from an image upload form.

## The fix

`secure-version/upload.php` fixes this by:

- Verifying the file's real content with `getimagesize()`, not the filename or header
- Saving uploads under a random generated name, never the original
- Re-encoding the image to strip any hidden payload
- Disabling script execution in the uploads folder via `.htaccess`

## Running it

Requires PHP 8.x.

```bash
# Terminal 1 — vulnerable version
cd public
php -S 127.0.0.1:8000

# Terminal 2 — secure version
cd secure-version
php -S 127.0.0.1:8001
```

Open `http://127.0.0.1:8000` and `http://127.0.0.1:8001` in your browser.

## Reproducing the exploit

1. Create a file named `shell.jpg.php` containing a minimal PHP script that runs a
   command passed in a `cmd` query parameter (see the video for the exact code —
   left out here since some antivirus tools flag web-shell snippets even in plain text).
2. Upload it with curl, spoofing the Content-Type:
   ```bash
   curl -F "file=@shell.jpg.php;type=image/jpeg" http://127.0.0.1:8000/upload.php
   ```
3. Trigger it directly:
   ```bash
   curl "http://127.0.0.1:8000/uploads/shell.jpg.php?cmd=id"
   ```
   *(Windows: use `whoami` instead of `id`, and run `Remove-Item alias:curl` first
   so `curl` isn't PowerShell's built-in alias.)*

Repeat against port `8001` — the upload will be rejected, and the file will never
be saved.

> Note: none of this needs access to this repo or filesystem — a real attacker only
> needs the public upload URL. The same steps work against any real domain.

## Repo structure

```
insecure-upload-demo/
├── README.md
├── public/                 vulnerable app
│   ├── index.php
│   ├── upload.php           flaws marked [FLAW #1..3]
│   ├── gallery.php
│   └── uploads/
└── secure-version/         fixed app
    ├── index.php
    ├── upload.php           fixes marked ERROR: / FIX:
    ├── gallery.php
    └── uploads/
        └── .htaccess         disables script execution
```

#Team Members
  Name	             Roll No.
Amelia Rubey	      IIT2024206
Princi Kannaujiya	   IIT2024186
Banshika Aggarwal 	IIT2024184
Surbhi Kumari	      IIT2024141
Fatima Hussain	      IIT2024188

---
