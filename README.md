# Du Lich Viet - Tour Booking Website

## Project status

Frontend static website is complete and ready to run locally.

---

## Folder structure

```text
DoAn-TourDuLich-main/
├── index.html                 # Redirect to ./frontend/index.html
├── README.md
├── database/
│   └── database.sql           # Database schema + sample data
└── frontend/
    ├── index.html             # Homepage
    ├── tours.html             # Tour listing page
    ├── about.html
    ├── faq.html
    ├── guide.html
    ├── privacy.html
    ├── terms.html
    ├── css/
    │   └── styles.css
    └── js/
        └── script.js
```

---

## Run project locally

### Option 1: Run from project root (recommended)

```bash
cd DoAn-TourDuLich-main
python3 -m http.server 8000
```

Open:
- `http://localhost:8000` (auto redirect to frontend)

### Option 2: Run directly in frontend folder

```bash
cd DoAn-TourDuLich-main/frontend
python3 -m http.server 8000
```

Open:
- `http://localhost:8000`

---

## Notes

- If you use VS Code Live Server at root, open `index.html` in root (it will redirect correctly).
- If port `8000` is busy, run with another port, for example:

```bash
python3 -m http.server 5501
```
