# Local Docling extraction service

PDF and DOCX extraction is performed by a separate [`docling-serve`](https://github.com/docling-project/docling-serve) process. The Laravel application submits a private document to the service from a queued job and stores the normalized result as a private JSON artifact.

The service is not installed by the application and is not required for the automated test suite.

## Local setup (Windows PowerShell)

```powershell
py -3.12 -m venv python\docling\.venv
python\docling\.venv\Scripts\python -m pip install --upgrade pip
python\docling\.venv\Scripts\pip install "docling-serve==1.37.0"
python\docling\.venv\Scripts\docling-serve run --host 127.0.0.1 --port 5001
```

Point the application at the service in `.env`:

```dotenv
DOCLING_SERVE_URL=http://127.0.0.1:5001
DOCLING_SERVE_API_KEY=
DOCLING_OCR_ENABLED=false
DOCLING_MAX_PAGES=200
```

## Operational notes

- Keep the service bound to localhost or a private network. Do not expose it publicly without authentication.
- Configure the service's own memory, page, and request limits in addition to `DOCLING_MAX_PAGES`. The Laravel limit is a post-extraction guard, not a substitute for a resource limit inside the extraction process.
- OCR is disabled by default. Enable it only after the Docling OCR engine and required language data have been installed and verified in the deployment environment.
- The application re-validates the downloaded file before extraction and treats the document as untrusted data.
- Live Cloudinary uploads are intentionally not required for tests. The test suite uses fakes.
