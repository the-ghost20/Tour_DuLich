const express = require("express");
const cors = require("cors");
const morgan = require("morgan");
const { readJson, writeJson } = require("./storage");
const { seedToursFromSql } = require("./sqlSeed");

const app = express();
const port = Number(process.env.PORT || 4000);

app.use(cors());
app.use(morgan("dev"));
app.use(express.json());

async function getToursData() {
  // Always refresh from database/database.sql so backend stays aligned with SQL seed.
  return seedToursFromSql();
}

app.get("/api/health", (_req, res) => {
  res.json({
    ok: true,
    service: "tour-du-lich-backend",
    timestamp: new Date().toISOString(),
  });
});

app.get("/api/tours", async (req, res) => {
  const q = (req.query.q || "").toString().trim().toLowerCase();
  const destination = (req.query.destination || "").toString().trim().toLowerCase();
  const maxPrice = Number(req.query.maxPrice || 0);

  let tours = await getToursData();

  if (q) {
    tours = tours.filter((tour) => tour.name.toLowerCase().includes(q));
  }
  if (destination) {
    tours = tours.filter((tour) =>
      tour.destination.toLowerCase().includes(destination),
    );
  }
  if (maxPrice > 0) {
    tours = tours.filter((tour) => Number(tour.price) <= maxPrice);
  }

  res.json({ count: tours.length, items: tours });
});

app.get("/api/tours/:id", async (req, res) => {
  const tours = await getToursData();
  const tour = tours.find((item) => item.id === req.params.id);
  if (!tour) {
    return res.status(404).json({ message: "Tour không tồn tại" });
  }
  return res.json(tour);
});

app.post("/api/auth/login", (req, res) => {
  const { email, password } = req.body || {};
  if (!email || !password) {
    return res.status(400).json({ message: "Thiếu email hoặc mật khẩu" });
  }

  const fakeToken = Buffer.from(`${email}:${Date.now()}`).toString("base64");
  return res.json({
    token: fakeToken,
    user: {
      id: "user-1",
      email,
      fullName: email.split("@")[0],
    },
  });
});

app.get("/api/bookings", async (_req, res) => {
  const bookings = await readJson("bookings.json", []);
  res.json({ count: bookings.length, items: bookings });
});

app.post("/api/bookings", async (req, res) => {
  const { tourId, tourName = "", fullName, phone, email, guests = 1, note = "" } =
    req.body || {};
  if (!tourId || !fullName || !phone) {
    return res
      .status(400)
      .json({ message: "Thiếu dữ liệu bắt buộc: tourId, fullName, phone" });
  }

  const tours = await getToursData();
  const normalizedTourName = tourName.toString().trim().toLowerCase();
  const tour = tours.find((item) => {
    if (item.id === tourId) return true;
    if (!normalizedTourName) return false;
    return item.name.toLowerCase() === normalizedTourName;
  });
  if (!tour) {
    return res.status(404).json({
      message: "Không tìm thấy tour để đặt, vui lòng đồng bộ mã tour ở frontend",
    });
  }

  const bookings = await readJson("bookings.json", []);
  const booking = {
    id: `booking-${Date.now()}`,
    tourId,
    tourName: tour.name,
    fullName,
    phone,
    email: email || "",
    guests: Number(guests) > 0 ? Number(guests) : 1,
    note,
    createdAt: new Date().toISOString(),
    status: "pending",
  };

  bookings.push(booking);
  await writeJson("bookings.json", bookings);

  return res.status(201).json({
    message: "Đặt tour thành công",
    booking,
  });
});

app.use((error, _req, res, _next) => {
  console.error(error);
  res.status(500).json({ message: "Lỗi máy chủ" });
});

app.listen(port, () => {
  console.log(`Backend API is running at http://localhost:${port}`);
});
