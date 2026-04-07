// ============================================
// SCRIPT.JS - Tour Booking System
// ============================================

// ========== DOM Elements ==========
const searchInput = document.querySelector(".search-input");
const searchField = document.getElementById("search-input");
const sortBy = document.getElementById("sort-by");
const heroSearchInput = document.querySelector(".hero-search-input");
const heroSearchBtn = document.querySelector(".btn-search");
const priceSlider = document.getElementById("price-slider");
const priceValue = document.getElementById("price-value");
const applyFilterBtn = document.getElementById("apply-filter");
const clearFilterBtn = document.getElementById("clear-filter");
const loginBtn = document.querySelector(".btn-login");
const mobileToggle = document.querySelector(".mobile-toggle");
const navbarMenu = document.querySelector(".navbar-menu");
const toursGrid = document.getElementById("tours-grid");
const wishlistItemsContainer = document.getElementById("wishlist-items");
const hotToursGrid = document.getElementById("hot-tours-grid");

// ========== State Management ==========
let currentFilters = {
  destination: [],
  priceRange: 5000000,
  duration: [],
  tourType: [],
  searchQuery: "",
};

let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];
const API_BASE_URL = "http://localhost:4000/api";

// ========== Initialize ==========
document.addEventListener("DOMContentLoaded", () => {
  bootstrapDataFromBackend();
  initializeEventListeners();
  loadWishlistUI();
  setupPriceSlider();
  loadAboutStats();
});

async function bootstrapDataFromBackend() {
  await Promise.all([loadToursFromApi(), loadHotToursFromApi()]);
  bindDynamicCardActions();
  loadWishlistUI();
}

// ========== Event Listeners ==========
function initializeEventListeners() {
  // Mobile menu toggle
  if (mobileToggle) {
    mobileToggle.addEventListener("click", () => {
      navbarMenu.style.display =
        navbarMenu.style.display === "flex" ? "none" : "flex";
    });
  }

  // Header search visibility on scroll
  window.addEventListener("scroll", () => {
    const headerSearch = document.getElementById("header-search");
    if (headerSearch) {
      if (window.scrollY > 400) {
        headerSearch.classList.add("visible");
      } else {
        headerSearch.classList.remove("visible");
      }
    }
  });

  // Price Slider
  if (priceSlider) {
    priceSlider.addEventListener("input", updatePriceDisplay);
  }

  // Filter buttons
  if (applyFilterBtn) {
    applyFilterBtn.addEventListener("click", applyFilters);
  }
  if (clearFilterBtn) {
    clearFilterBtn.addEventListener("click", clearFilters);
  }

  // Sorting (tours page)
  if (sortBy) {
    sortBy.addEventListener("change", handleSort);
  }

  // Search functionality
  if (searchField) {
    searchField.addEventListener("input", handleSearch);
  }

  // Hero search button
  if (heroSearchBtn) {
    heroSearchBtn.addEventListener("click", handleHeroSearch);
  }

  // Login button
  if (loginBtn) {
    loginBtn.addEventListener("click", handleLogin);
  }
}

function bindDynamicCardActions() {
  const wishlistBtns = document.querySelectorAll(".btn-wishlist");
  const bookBtns = document.querySelectorAll(".btn-book");

  wishlistBtns.forEach((btn) => {
    if (!btn.dataset.boundWishlist) {
      btn.addEventListener("click", toggleWishlist);
      btn.dataset.boundWishlist = "1";
    }
  });

  bookBtns.forEach((btn) => {
    if (!btn.dataset.boundBooking) {
      btn.addEventListener("click", handleBooking);
      btn.dataset.boundBooking = "1";
    }
  });
}

function formatPrice(value) {
  return Number(value || 0).toLocaleString("vi-VN") + " đ";
}

function normalizeDurationLabel(duration) {
  if (!duration) return "1 ngày";
  const days = Number(duration.toString().replace(/\D/g, ""));
  if (!days || Number.isNaN(days)) return duration;
  return `${days} ngày`;
}

function durationFilterTag(duration) {
  const days = Number(duration.toString().replace(/\D/g, ""));
  if (days <= 1) return "1-day";
  if (days === 2) return "2-day";
  if (days === 3) return "3-day";
  return "4-day";
}

function getTourImageUrl(index) {
  const images = [
    "https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=300&fit=crop",
    "https://images.unsplash.com/photo-1537225228614-b4fad34a0b60?w=400&h=300&fit=crop",
    "https://images.unsplash.com/photo-1584422604131-a971d26d8f44?w=400&h=300&fit=crop",
    "https://images.unsplash.com/photo-1507146426996-ef05306b995a?w=400&h=300&fit=crop",
    "https://images.unsplash.com/photo-1528127269322-539801943592?w=400&h=300&fit=crop",
    "https://images.unsplash.com/photo-1465311440653-ba9b1d9b0f5b?w=400&h=300&fit=crop",
  ];
  return images[index % images.length];
}

async function loadToursFromApi() {
  if (!toursGrid) return;
  try {
    const response = await fetch(`${API_BASE_URL}/tours`);
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || "Không tải được dữ liệu tour");
    }

    toursGrid.innerHTML = data.items
      .map((tour, index) => {
        const duration = normalizeDurationLabel(tour.duration);
        const rating = Number(tour.rating || 4.5);
        const fullStars = Math.floor(rating);
        const hasHalf = rating - fullStars >= 0.5;
        const stars = Array.from({ length: 5 }, (_, i) => {
          if (i < fullStars) return '<i class="fas fa-star"></i>';
          if (i === fullStars && hasHalf) return '<i class="fas fa-star-half-alt"></i>';
          return '<i class="far fa-star"></i>';
        }).join("");

        return `
          <div class="tour-card"
            data-tour-id="${tour.id}"
            data-destination="${tour.destination || ""}"
            data-duration="${durationFilterTag(duration)}"
            data-type="${tour.type || ""}"
            data-rating="${rating}">
            <div class="tour-card-image">
              <img src="${getTourImageUrl(index)}" alt="${tour.name}" />
              <div class="tour-card-overlay">
                <button class="btn-wishlist" title="Thêm vào yêu thích">
                  <i class="fas fa-heart"></i>
                </button>
              </div>
            </div>
            <div class="tour-card-content">
              <h3>${tour.name}</h3>
              <div class="tour-meta">
                <span class="tour-duration"><i class="fas fa-calendar"></i> ${duration}</span>
                <span class="tour-destination"><i class="fas fa-map-marker-alt"></i> ${tour.destinationName || tour.destination}</span>
              </div>
              <div class="tour-rating">
                <div class="stars">${stars}</div>
                <span class="rating-count">(${Math.max(40, Math.round(rating * 30))} đánh giá)</span>
              </div>
              <div class="tour-card-footer">
                <span class="tour-price">${formatPrice(tour.price)}</span>
                <button class="btn-book">Đặt Ngay</button>
              </div>
            </div>
          </div>
        `;
      })
      .join("");
  } catch (error) {
    showNotification(error.message || "Không kết nối được backend", "error");
  }
}

async function loadHotToursFromApi() {
  if (!hotToursGrid) return;
  try {
    const response = await fetch(`${API_BASE_URL}/tours`);
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || "Không tải được dữ liệu tour");
    }
    const topTours = [...data.items]
      .sort((a, b) => Number(b.rating || 0) - Number(a.rating || 0))
      .slice(0, 6);

    hotToursGrid.innerHTML = topTours
      .map(
        (tour, index) => `
        <div class="hot-tour-card">
          <div class="tour-image">
            <img src="${getTourImageUrl(index)}" alt="${tour.name}" />
            <span class="hot-badge">Hot Tour</span>
          </div>
          <div class="tour-info">
            <h3>${tour.name}</h3>
            <div class="tour-price">
              <span class="price">${formatPrice(tour.price)}</span>
            </div>
            <a href="tours.html" class="btn-detail">Chi tiết</a>
          </div>
        </div>
      `,
      )
      .join("");
  } catch (_error) {
    // Keep current static content if API fails on home page.
  }
}

async function loadAboutStats() {
  const stats = document.querySelectorAll(".stats-section .stat-item h3");
  if (!stats || stats.length < 3) return;

  try {
    const [toursRes, bookingsRes] = await Promise.all([
      fetch(`${API_BASE_URL}/tours`),
      fetch(`${API_BASE_URL}/bookings`),
    ]);
    const toursData = await toursRes.json();
    const bookingsData = await bookingsRes.json();

    if (toursRes.ok) {
      stats[2].textContent = `${toursData.count || 0}+`;
    }
    if (bookingsRes.ok) {
      stats[1].textContent = `${bookingsData.count || 0}+`;
      stats[0].textContent = `${Math.max(100, (bookingsData.count || 0) * 8)}+`;
    }
  } catch (_error) {
    // Keep static stats if backend is unavailable.
  }
}

// ========== Price Slider Setup ==========
function setupPriceSlider() {
  if (!priceSlider) return;
  updatePriceDisplay();
}

function updatePriceDisplay() {
  const value = parseInt(priceSlider.value);
  currentFilters.priceRange = value;

  if (priceValue) {
    priceValue.textContent = value.toLocaleString("vi-VN") + " đ";
  }
}

// ========== Filter Functions ==========
function applyFilters() {
  // Get selected destinations
  const destinationCheckboxes = document.querySelectorAll(
    'input[name="destination"]:checked',
  );
  currentFilters.destination = Array.from(destinationCheckboxes).map(
    (cb) => cb.value,
  );

  // Get selected durations
  const durationCheckboxes = document.querySelectorAll(
    'input[name="duration"]:checked',
  );
  currentFilters.duration = Array.from(durationCheckboxes).map(
    (cb) => cb.value,
  );

  // Get selected tour types
  const tourTypeCheckboxes = document.querySelectorAll(
    'input[name="tour-type"]:checked',
  );
  currentFilters.tourType = Array.from(tourTypeCheckboxes).map(
    (cb) => cb.value,
  );

  filterTours();
  showNotification("Bộ lọc đã được áp dụng!", "success");
}

function clearFilters() {
  // Reset all checkboxes
  document.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
    cb.checked = false;
  });

  // Reset price slider
  if (priceSlider) {
    priceSlider.value = 5000000;
    updatePriceDisplay();
  }

  // Reset search
  if (searchField) {
    searchField.value = "";
  }

  // Reset filters object
  currentFilters = {
    destination: [],
    priceRange: 5000000,
    duration: [],
    tourType: [],
    searchQuery: "",
  };

  filterTours();
  showNotification("Bộ lọc đã được xóa!", "info");
}

function filterTours() {
  if (!toursGrid) return;

  const tours = document.querySelectorAll(".tour-card");
  let visibleCount = 0;

  tours.forEach((tour) => {
    // Get tour attributes
    const tourDestination = tour.getAttribute("data-destination") || "";
    const tourPrice = parseInt(
      tour.querySelector(".tour-price").textContent.replace(/\D/g, ""),
    );
    const tourDuration = tour.getAttribute("data-duration") || "";
    const tourType = tour.getAttribute("data-type") || "";
    const tourName = tour.querySelector("h3").textContent.toLowerCase();
    const searchQuery = currentFilters.searchQuery.toLowerCase();

    // Check filters
    const destinationMatch =
      currentFilters.destination.length === 0 ||
      currentFilters.destination.some((d) => tourDestination.includes(d));

    const priceMatch = tourPrice <= currentFilters.priceRange;

    const durationMatch =
      currentFilters.duration.length === 0 ||
      currentFilters.duration.some((d) => tourDuration.includes(d));

    const typeMatch =
      currentFilters.tourType.length === 0 ||
      currentFilters.tourType.some((t) => tourType.includes(t));

    const searchMatch = searchQuery === "" || tourName.includes(searchQuery);

    // Show/hide tour
    const shouldShow =
      destinationMatch &&
      priceMatch &&
      durationMatch &&
      typeMatch &&
      searchMatch;
    tour.style.display = shouldShow ? "block" : "none";

    if (shouldShow) {
      visibleCount++;
    }
  });

  // Show empty state if needed
  if (visibleCount === 0 && toursGrid) {
    if (!document.querySelector(".empty-state")) {
      const emptyState = document.createElement("div");
      emptyState.className = "empty-state";
      emptyState.innerHTML = `
        <i class="fas fa-inbox"></i>
        <h3>Không tìm thấy tour phù hợp</h3>
        <p>Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
      `;
      toursGrid.appendChild(emptyState);
    }
  } else if (document.querySelector(".empty-state")) {
    document.querySelector(".empty-state").remove();
  }
}

// ========== Search & Sort Functions ==========
function handleSearch(e) {
  currentFilters.searchQuery = e.target.value;
  filterTours();
}

function handleHeroSearch(e) {
  e.preventDefault();
  const searchValue = heroSearchInput?.value || "";
  if (searchValue.trim()) {
    // Redirect to tours page with search query
    window.location.href = `tours.html?search=${encodeURIComponent(
      searchValue,
    )}`;
  } else {
    showNotification("Vui lòng nhập điểm đến bạn muốn đi!", "warning");
  }
}

function handleSort(e) {
  const sortValue = e.target.value;
  if (!toursGrid) return;

  const tours = Array.from(document.querySelectorAll(".tour-card"));

  switch (sortValue) {
    case "price-low":
      tours.sort(
        (a, b) =>
          parseInt(
            a.querySelector(".tour-price").textContent.replace(/\D/g, ""),
          ) -
          parseInt(
            b.querySelector(".tour-price").textContent.replace(/\D/g, ""),
          ),
      );
      break;
    case "price-high":
      tours.sort(
        (a, b) =>
          parseInt(
            b.querySelector(".tour-price").textContent.replace(/\D/g, ""),
          ) -
          parseInt(
            a.querySelector(".tour-price").textContent.replace(/\D/g, ""),
          ),
      );
      break;
    case "rating":
      tours.sort(
        (a, b) =>
          parseFloat(b.getAttribute("data-rating") || "0") -
          parseFloat(a.getAttribute("data-rating") || "0"),
      );
      break;
    case "newest":
      tours.reverse();
      break;
    default:
      // Keep default order
      break;
  }

  toursGrid.innerHTML = "";
  tours.forEach((tour) => {
    toursGrid.appendChild(tour);
  });
}

// ========== Wishlist Functions ==========
function toggleWishlist(e) {
  e.preventDefault();
  const btn = e.currentTarget;
  const tourCard = btn.closest(".tour-card");
  const tourId =
    tourCard.getAttribute("data-tour-id") ||
    tourCard.querySelector("h3").textContent;
  const tourName = tourCard.querySelector("h3").textContent;

  const index = wishlist.findIndex((item) => item.id === tourId);

  if (index > -1) {
    // Remove from wishlist
    wishlist.splice(index, 1);
    btn.classList.remove("added");
    showNotification(`${tourName} đã được xóa khỏi yêu thích`, "info");
  } else {
    // Add to wishlist
    wishlist.push({ id: tourId, name: tourName, addedAt: new Date() });
    btn.classList.add("added");
    showNotification(`${tourName} đã được thêm vào yêu thích`, "success");
  }

  localStorage.setItem("wishlist", JSON.stringify(wishlist));
  loadWishlistUI();
}

function loadWishlistUI() {
  const wishlistBtns = document.querySelectorAll(".btn-wishlist");
  // Update buttons
  wishlistBtns.forEach((btn) => {
    const tourCard = btn.closest(".tour-card");
    const tourId =
      tourCard.getAttribute("data-tour-id") ||
      tourCard.querySelector("h3").textContent;
    btn.classList.toggle(
      "added",
      wishlist.some((item) => item.id === tourId),
    );
  });

  // Update sidebar
  if (wishlistItemsContainer) {
    if (wishlist.length === 0) {
      wishlistItemsContainer.innerHTML =
        '<p class="empty-wishlist">Chưa có tour yêu thích</p>';
    } else {
      wishlistItemsContainer.innerHTML = wishlist
        .map(
          (item) => `
        <div class="wishlist-item">
          <p>${item.name}</p>
        </div>
      `,
        )
        .join("");
    }
  }
}

// ========== Booking Functions ==========
function handleBooking(e) {
  e.preventDefault();
  const btn = e.currentTarget;
  const tourCard = btn.closest(".tour-card");
  const tourName = tourCard.querySelector("h3").textContent;
  const tourId =
    tourCard.getAttribute("data-tour-id") || `tour-${tourName.toLowerCase()}`;

  // Check if logged in
  const isLoggedIn = localStorage.getItem("userToken");
  if (!isLoggedIn) {
    showNotification("Vui lòng đăng nhập để đặt tour!", "warning");
    handleLogin();
    return;
  }

  const fullName =
    localStorage.getItem("userFullName") ||
    prompt("Nhập họ tên để hoàn tất đặt tour:");
  const phone = prompt("Nhập số điện thoại liên hệ:");

  if (!fullName || !phone) {
    showNotification("Bạn cần nhập đủ họ tên và số điện thoại", "warning");
    return;
  }

  fetch(`${API_BASE_URL}/bookings`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${isLoggedIn}`,
    },
    body: JSON.stringify({
      tourId,
      tourName,
      fullName,
      phone,
      email: localStorage.getItem("userEmail") || "",
      guests: 1,
    }),
  })
    .then(async (response) => {
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || "Không thể đặt tour");
      }
      showNotification(`Đặt tour thành công: ${tourName}`, "success");
    })
    .catch((error) => {
      showNotification(error.message || "Lỗi kết nối backend", "error");
    });
}

// ========== Login Function ==========
function handleLogin(e) {
  if (e) e.preventDefault();
  const email = prompt("Email đăng nhập:");
  const password = prompt("Mật khẩu:");

  if (!email || !password) {
    showNotification("Bạn đã hủy đăng nhập", "info");
    return;
  }

  fetch(`${API_BASE_URL}/auth/login`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
  })
    .then(async (response) => {
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || "Đăng nhập thất bại");
      }

      localStorage.setItem("userToken", data.token);
      localStorage.setItem("userEmail", data.user.email);
      localStorage.setItem("userFullName", data.user.fullName || "");
      showNotification("Đăng nhập thành công!", "success");
    })
    .catch((error) => {
      showNotification(error.message || "Không thể kết nối backend", "error");
    });
}

// ========== Notification System ==========
function showNotification(message, type = "info") {
  const notificationId = `notification-${Date.now()}`;
  const notification = document.createElement("div");
  notification.id = notificationId;
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
    <i class="fas fa-${getNotificationIcon(type)}"></i>
    <span>${message}</span>
    <button class="notification-close">&times;</button>
  `;

  // Add styles if not already added
  addNotificationStyles();

  document.body.appendChild(notification);

  // Trigger animation
  setTimeout(() => notification.classList.add("show"), 10);

  // Close button
  notification
    .querySelector(".notification-close")
    .addEventListener("click", () => {
      notification.classList.remove("show");
      setTimeout(() => notification.remove(), 300);
    });

  // Auto dismiss
  setTimeout(() => {
    if (document.body.contains(notification)) {
      notification.classList.remove("show");
      setTimeout(() => notification.remove(), 300);
    }
  }, 5000);
}

function getNotificationIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  };
  return icons[type] || "info-circle";
}

function addNotificationStyles() {
  if (document.getElementById("notification-styles")) return;

  const style = document.createElement("style");
  style.id = "notification-styles";
  style.textContent = `
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: white;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      display: flex;
      align-items: center;
      gap: 1rem;
      z-index: 1000;
      max-width: 400px;
      opacity: 0;
      transform: translateX(400px);
      transition: all 0.3s ease;
      font-weight: 500;
      border-left: 4px solid #00bcd4;
    }

    .notification.show {
      opacity: 1;
      transform: translateX(0);
    }

    .notification-success {
      border-left-color: #4caf50;
    }

    .notification-success i {
      color: #4caf50;
    }

    .notification-error {
      border-left-color: #f44336;
    }

    .notification-error i {
      color: #f44336;
    }

    .notification-warning {
      border-left-color: #ff9800;
    }

    .notification-warning i {
      color: #ff9800;
    }

    .notification-info {
      border-left-color: #2196f3;
    }

    .notification-info i {
      color: #2196f3;
    }

    .notification-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #999;
      padding: 0;
      margin-left: 1rem;
    }

    .notification-close:hover {
      color: #333;
    }

    @media (max-width: 480px) {
      .notification {
        left: 10px;
        right: 10px;
        max-width: none;
      }
    }
  `;
  document.head.appendChild(style);
}

// ========== Empty State ==========
function showEmptyState() {
  if (!toursGrid || document.querySelector(".empty-state")) return;
  const emptyState = document.createElement("div");
  emptyState.className = "empty-state";
  emptyState.innerHTML = `
    <i class="fas fa-inbox"></i>
    <h3>Không tìm thấy tour phù hợp</h3>
    <p>Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
  `;
  toursGrid.appendChild(emptyState);
}

// ========== Initialization Complete ==========
console.log("Tour Booking System Initialized");
