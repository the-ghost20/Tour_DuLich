# Du Lich Viet - Tour Booking Website

## Project status

Frontend + backend API are available and can run locally.

---

## Folder structure

```text
DoAn-TourDuLich-main/
├── index.html                 # Redirect to ./frontend/index.html
├── README.md
├── database/
│   └── database.sql           # Database schema + sample data
├── backend/
│   ├── src/server.js          # Express API server
│   ├── data/tours.json        # Sample tours data
│   └── data/bookings.json     # Booking records
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

### 1) Run backend API

```bash
cd Tour_DuLich/backend
npm install
npm run dev
```

Backend URL:
- `http://localhost:4000`
- Health check: `http://localhost:4000/api/health`

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

- Frontend booking/login in `frontend/js/script.js` now calls backend API:
  - `POST /api/auth/login`
  - `POST /api/bookings`
  - `GET /api/tours`
- Tours API is auto-seeded from `database/database.sql` (tables `Destinations` + `Tours` sample data).
