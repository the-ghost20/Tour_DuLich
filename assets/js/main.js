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
/**
 * Tour & thống kê lấy từ PHP/MySQL (render sẵn). API Node cổng 4000 không tồn tại trong stack MAMP →
 * bật true chỉ khi bạn chạy thêm backend Node và muốn ghi đè danh sách tour bằng API.
 */
const USE_LEGACY_NODE_TOUR_API = false;
const API_BASE_URL = "http://localhost:4000/api";

function escapeHtml(text) {
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

// ========== Initialize ==========
document.addEventListener("DOMContentLoaded", () => {
  bootstrapDataFromBackend();
  initializeEventListeners();
  loadWishlistUI();
  setupPriceSlider();
  loadAboutStats();
  setupLoginModal();
  _bkSetupModalEvents();
  initToursUrlSearch();
  initWishlistPage();
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
}

function setupLoginModal() {
  const trigger = document.querySelector('[data-login-trigger="1"]');
  const modal = document.getElementById("login-modal");
  if (!trigger || !modal) return;

  const closeBtn = document.getElementById("login-modal-close");
  const emailInput = modal.querySelector("#login-modal-email");
  const tabTriggers = modal.querySelectorAll("[data-auth-tab-trigger]");
  const tabPanels = modal.querySelectorAll("[data-auth-tab]");
  const modalTitle = modal.querySelector("#login-modal-title");
  const modalSubtitle = modal.querySelector("#login-modal-subtitle");
  const messageBox = modal.querySelector("#auth-modal-message");
  const forgotForm = modal.querySelector("#auth-forgot-form");

  const loginTitle = "Đăng nhập";
  const loginSubtitle =
    "Chào mừng bạn quay trở lại!";
  const forgotTitle = "Đặt lại mật khẩu";
  const forgotSubtitle =
    "Nhập email hoặc số điện thoại đã đăng ký để đặt mật khẩu mới.";

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^[0-9]{8,14}$/;

  function showAuthMessage(text, type = "error") {
    if (!messageBox) return;
    messageBox.textContent = text;
    messageBox.classList.remove("is-error", "is-success");
    messageBox.classList.add(type === "success" ? "is-success" : "is-error");
  }

  function clearAuthMessage() {
    if (!messageBox) return;
    messageBox.textContent = "";
    messageBox.classList.remove("is-error", "is-success");
  }

  function switchAuthTab(tabName) {
    tabPanels.forEach((panel) => {
      const shouldShow = panel.dataset.authTab === tabName;
      panel.classList.toggle("auth-panel-hidden", !shouldShow);
      panel.hidden = !shouldShow;
    });

    if (modalTitle && modalSubtitle) {
      if (tabName === "forgot") {
        modalTitle.textContent = forgotTitle;
        modalSubtitle.textContent = forgotSubtitle;
      } else {
        modalTitle.textContent = loginTitle;
        modalSubtitle.textContent = loginSubtitle;
      }
    }

    clearAuthMessage();

    if (tabName === "forgot") {
      const forgotIdentity = modal.querySelector("#forgot-identity");
      setTimeout(() => forgotIdentity?.focus(), 50);
    } else {
      setTimeout(() => emailInput?.focus(), 50);
    }
  }

  function openModal(event) {
    if (event) {
      event.preventDefault();
    }
    modal.classList.add("is-open");
    document.body.classList.add("login-modal-open");
    switchAuthTab("login");
  }

  function closeModal() {
    modal.classList.remove("is-open");
    document.body.classList.remove("login-modal-open");
    clearAuthMessage();
    if (forgotForm) {
      forgotForm.reset();
    }
  }

  trigger.addEventListener("click", openModal);

  if (closeBtn) {
    closeBtn.addEventListener("click", (event) => {
      event.preventDefault();
      closeModal();
    });
  }

  tabTriggers.forEach((el) => {
    el.addEventListener("click", (event) => {
      if (el.tagName === "A") {
        event.preventDefault();
      }
      const targetTab = el.dataset.authTabTrigger;
      if (targetTab) {
        switchAuthTab(targetTab);
      }
    });
  });

  if (forgotForm) {
    forgotForm.addEventListener("submit", (event) => {
      event.preventDefault();

      const identityInput = forgotForm.querySelector("#forgot-identity");
      const countryCodeInput = forgotForm.querySelector("#forgot-country-code");
      const newPasswordInput = forgotForm.querySelector("#forgot-new-password");
      const confirmPasswordInput = forgotForm.querySelector(
        "#forgot-confirm-password",
      );

      const identity = (identityInput?.value || "").trim();
      const countryCode = (countryCodeInput?.value || "+84").trim();
      const newPassword = newPasswordInput?.value || "";
      const confirmPassword = confirmPasswordInput?.value || "";

      if (!identity) {
        showAuthMessage("Vui lòng nhập email hoặc số điện thoại.");
        return;
      }

      const identityIsEmail = emailRegex.test(identity);
      const normalizedPhone = identity.replace(/\s|-/g, "");
      const identityIsPhone = phoneRegex.test(normalizedPhone);

      if (!identityIsEmail && !identityIsPhone) {
        showAuthMessage(
          "Định dạng chưa hợp lệ. Hãy nhập email hoặc số điện thoại 8-14 chữ số.",
        );
        return;
      }

      if (identityIsPhone && !countryCode.startsWith("+")) {
        showAuthMessage("Mã quốc gia phải bắt đầu bằng dấu + (ví dụ: +84).");
        return;
      }

      if (newPassword.length < 8) {
        showAuthMessage("Mật khẩu mới phải có ít nhất 8 ký tự.");
        return;
      }

      if (newPassword !== confirmPassword) {
        showAuthMessage("Xác nhận mật khẩu chưa khớp.");
        return;
      }

      const destination = identityIsEmail
        ? identity
        : `${countryCode}${normalizedPhone}`;

      showAuthMessage(
        `Yêu cầu đổi mật khẩu đã được ghi nhận cho ${destination}.`,
        "success",
      );
      forgotForm.reset();
    });
  }

  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && modal.classList.contains("is-open")) {
      closeModal();
    }
  });
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
  if (!USE_LEGACY_NODE_TOUR_API || !toursGrid) return;
  if (toursGrid.dataset.toursStatic === "1") return;
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
  if (!USE_LEGACY_NODE_TOUR_API || !hotToursGrid) return;
  if (hotToursGrid.dataset.hotToursStatic === "1") return;
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
            <a href="tours.php" class="btn-detail">Chi tiết</a>
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
  if (!USE_LEGACY_NODE_TOUR_API) return;
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
    window.location.href = `tours.php?search=${encodeURIComponent(
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
function initToursUrlSearch() {
  if (!searchField) return;
  const params = new URLSearchParams(window.location.search);
  const q = params.get("search");
  if (q) {
    searchField.value = q;
    currentFilters.searchQuery = q;
    filterTours();
  }
}

function initWishlistPage() {
  const root = document.getElementById("wishlist-page-root");
  const box = document.getElementById("wishlist-page-list");
  if (!root || !box) return;

  function render() {
    const list = JSON.parse(localStorage.getItem("wishlist") || "[]");
    if (list.length === 0) {
      box.innerHTML =
        '<p class="wishlist-empty">Bạn chưa lưu tour nào. <a href="tours.php">Khám phá danh sách tour</a></p>';
      return;
    }
    box.innerHTML = list
      .map(
        (item) => `
      <div class="wishlist-page-item" data-wl-id="${escapeHtml(String(item.id))}">
        <div class="wishlist-page-item__main">
          <a href="tour_detail.php?id=${encodeURIComponent(String(item.id))}">${escapeHtml(item.name)}</a>
        </div>
        <button type="button" class="wishlist-remove-btn" data-wl-remove="${escapeHtml(String(item.id))}" aria-label="Xóa khỏi yêu thích">
          <i class="fas fa-times"></i>
        </button>
      </div>`,
      )
      .join("");

    box.querySelectorAll("[data-wl-remove]").forEach((b) => {
      b.addEventListener("click", () => {
        const id = b.getAttribute("data-wl-remove");
        wishlist = wishlist.filter((x) => String(x.id) !== String(id));
        localStorage.setItem("wishlist", JSON.stringify(wishlist));
        loadWishlistUI();
        render();
        showNotification("Đã xóa khỏi danh sách yêu thích", "info");
      });
    });
  }

  render();
}

function toggleWishlist(e) {
  e.preventDefault();
  const btn = e.currentTarget;
  const tourCard = btn.closest(".tour-card");
  const tourIdRaw =
    btn.dataset.tourId ||
    tourCard?.getAttribute("data-tour-id") ||
    tourCard?.querySelector("h3")?.textContent;
  const tourId = tourIdRaw != null ? String(tourIdRaw) : "";
  const tourName =
    btn.dataset.tourName ||
    tourCard?.querySelector("h3")?.textContent.trim() ||
    "Tour";

  const index = wishlist.findIndex((item) => String(item.id) === String(tourId));

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
    const tourIdRaw =
      btn.dataset.tourId ||
      tourCard?.getAttribute("data-tour-id") ||
      tourCard?.querySelector("h3")?.textContent;
    const tourId = tourIdRaw != null ? String(tourIdRaw) : "";
    btn.classList.toggle(
      "added",
      wishlist.some((item) => String(item.id) === String(tourId)),
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
          <a href="tour_detail.php?id=${encodeURIComponent(String(item.id))}">${escapeHtml(item.name)}</a>
        </div>
      `,
        )
        .join("");
    }
  }
}

// ========== Booking Modal System ==========

let _bkTourId = null;
let _bkTourPrice = 0;
let _bkCouponApplied = false;
let _bkQuoteTimer = null;

function _bkTodayIso() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function _bkCouponCodeForQuote() {
  if (!_bkCouponApplied) return "";
  return (document.getElementById("bk-coupon")?.value || "").trim();
}

function _bkRenderQuoteFromJson(json) {
  const base = Number(json.base_subtotal);
  const hpct = Number(json.holiday_percent) || 0;
  const hamt = Number(json.holiday_amount) || 0;
  const disc = Number(json.discount) || 0;
  const total = Number(json.total) || 0;

  const subEl = document.getElementById("bk-subtotal-display");
  if (subEl && !Number.isNaN(base)) {
    subEl.textContent = base.toLocaleString("vi-VN") + " đ";
  }

  const hRow = document.getElementById("bk-holiday-row");
  const hEl = document.getElementById("bk-holiday-display");
  const hLbl = document.getElementById("bk-holiday-label");
  if (hRow && hEl) {
    if (hpct > 0) {
      hRow.style.display = "flex";
      if (hLbl) {
        hLbl.textContent =
          json.holiday_label || "Phụ thu lễ (+" + hpct + "%)";
      }
      hEl.textContent = "+ " + hamt.toLocaleString("vi-VN") + " đ";
    } else {
      hRow.style.display = "none";
    }
  }

  const discRow = document.getElementById("bk-discount-row");
  const discEl = document.getElementById("bk-discount-display");
  const totEl = document.getElementById("bk-total-display");

  if (disc > 0 && discRow && discEl) {
    discRow.style.display = "flex";
    discEl.textContent = "− " + disc.toLocaleString("vi-VN") + " đ";
  } else if (discRow) {
    discRow.style.display = "none";
  }

  if (totEl && !Number.isNaN(total)) {
    totEl.textContent = total.toLocaleString("vi-VN") + " đ";
  }
}

function _bkClientFallbackSummary(adults, children) {
  const subtotal = _bkTourPrice * (adults + children * 0.5);
  const subEl = document.getElementById("bk-subtotal-display");
  if (subEl) subEl.textContent = subtotal.toLocaleString("vi-VN") + " đ";
  const hRow = document.getElementById("bk-holiday-row");
  if (hRow) hRow.style.display = "none";
  const discRow = document.getElementById("bk-discount-row");
  if (discRow) discRow.style.display = "none";
  const totEl = document.getElementById("bk-total-display");
  if (totEl) totEl.textContent = subtotal.toLocaleString("vi-VN") + " đ";
}

function _bkScheduleQuoteRefresh() {
  if (_bkQuoteTimer) clearTimeout(_bkQuoteTimer);
  _bkQuoteTimer = setTimeout(() => {
    _bkQuoteTimer = null;
    void _bkFetchQuote();
  }, 280);
}

async function _bkFetchQuote() {
  const tourId = _bkTourId;
  if (!tourId) return;

  const adults = parseInt(document.getElementById("bk-adults")?.value) || 1;
  const children = parseInt(document.getElementById("bk-children")?.value) || 0;
  const departure = (document.getElementById("bk-departure")?.value || "").trim();
  const code = _bkCouponCodeForQuote();

  if (!departure) {
    _bkClientFallbackSummary(adults, children);
    return;
  }

  const fd = new FormData();
  fd.append("tour_id", String(tourId));
  fd.append("adults", String(adults));
  fd.append("children", String(children));
  fd.append("departure_date", departure);
  fd.append("coupon_code", code);

  try {
    const res = await fetch("booking_quote.php", { method: "POST", body: fd });
    const json = await res.json();

    if (json.base_subtotal != null) {
      _bkRenderQuoteFromJson(json);
    }

    if (json.success) {
      if (code) _bkCouponApplied = true;
    } else {
      _bkCouponApplied = false;
    }
  } catch (_e) {
    _bkClientFallbackSummary(adults, children);
    _bkCouponApplied = false;
  }
}

function openBookingModal(tourId, tourName, tourPrice) {
  _bkTourId = tourId;
  _bkTourPrice = Number(tourPrice) || 0;
  _bkCouponApplied = false;

  const modal = document.getElementById("booking-modal");
  if (!modal) return;

  // Reset state
  document.getElementById("bk-tour-id").value = tourId;
  document.getElementById("bk-tour-name-display").textContent = tourName;
  document.getElementById("bk-price-per").textContent =
    _bkTourPrice.toLocaleString("vi-VN") + " đ";
  document.getElementById("bk-adults").value = 1;
  document.getElementById("bk-children").value = 0;
  const dep = document.getElementById("bk-departure");
  if (dep) {
    const t = _bkTodayIso();
    dep.min = t;
    dep.value = t;
  }
  const couponInp = document.getElementById("bk-coupon");
  if (couponInp) couponInp.value = "";
  document.getElementById("bk-form").style.display = "";
  document.getElementById("bk-success").style.display = "none";

  const msgEl = document.getElementById("bk-msg");
  msgEl.style.display = "none";
  msgEl.textContent = "";

  const submitBtn = document.getElementById("bk-submit-btn");
  submitBtn.disabled = false;
  submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Xác nhận đặt tour';

  _bkScheduleQuoteRefresh();
  modal.classList.add("is-open");
  document.body.style.overflow = "hidden";
}

function closeBookingModal() {
  const modal = document.getElementById("booking-modal");
  if (modal) modal.classList.remove("is-open");
  document.body.style.overflow = "";
}

function openLoginRequiredModal() {
  const modal = document.getElementById("login-required-modal");
  if (modal) {
    modal.classList.add("is-open");
    document.body.style.overflow = "hidden";
  }
}

function closeLoginRequiredModal() {
  const modal = document.getElementById("login-required-modal");
  if (modal) modal.classList.remove("is-open");
  document.body.style.overflow = "";
}

function _bkUpdateTotal() {
  _bkCouponApplied = false;
  _bkScheduleQuoteRefresh();
}

function _bkSetupModalEvents() {
  // Counter buttons
  document.querySelectorAll(".bk-cnt-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.target;
      const delta = parseInt(btn.dataset.delta);
      const input = document.getElementById(targetId);
      if (!input) return;
      const min = parseInt(input.min) || 0;
      const max = parseInt(input.max) || 99;
      const newVal = Math.min(max, Math.max(min, parseInt(input.value) + delta));
      input.value = newVal;
      _bkUpdateTotal();
    });
  });

  // Close booking modal
  const closeBtn = document.getElementById("bk-close-btn");
  const cancelBtn = document.getElementById("bk-cancel-btn");
  const successClose = document.getElementById("bk-success-close");

  if (closeBtn) closeBtn.addEventListener("click", closeBookingModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeBookingModal);
  if (successClose) successClose.addEventListener("click", closeBookingModal);

  // Close on backdrop click
  const bookingModal = document.getElementById("booking-modal");
  if (bookingModal) {
    bookingModal.addEventListener("click", (e) => {
      if (e.target === bookingModal) closeBookingModal();
    });
  }

  // Close login required modal
  const lrClose = document.getElementById("lr-close-btn");
  if (lrClose) lrClose.addEventListener("click", closeLoginRequiredModal);

  const loginRequiredModal = document.getElementById("login-required-modal");
  if (loginRequiredModal) {
    loginRequiredModal.addEventListener("click", (e) => {
      if (e.target === loginRequiredModal) closeLoginRequiredModal();
    });
  }

  // Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeBookingModal();
      closeLoginRequiredModal();
    }
  });

  // Submit form
  const form = document.getElementById("bk-form");
  const applyCouponBtn = document.getElementById("bk-apply-coupon");
  if (applyCouponBtn) {
    applyCouponBtn.addEventListener("click", async () => {
      const msgEl = document.getElementById("bk-msg");
      const adults = parseInt(document.getElementById("bk-adults").value) || 1;
      const children = parseInt(document.getElementById("bk-children").value) || 0;
      const departure = (document.getElementById("bk-departure")?.value || "").trim();
      const code = (document.getElementById("bk-coupon")?.value || "").trim();
      msgEl.style.display = "none";
      if (!code) {
        _bkCouponApplied = false;
        _bkScheduleQuoteRefresh();
        return;
      }
      if (!departure) {
        msgEl.textContent = "Vui lòng chọn ngày khởi hành trước khi áp dụng mã.";
        msgEl.className = "bk-msg is-error";
        msgEl.style.display = "";
        return;
      }
      applyCouponBtn.disabled = true;
      try {
        const fd = new FormData();
        fd.append("tour_id", String(_bkTourId));
        fd.append("adults", String(adults));
        fd.append("children", String(children));
        fd.append("departure_date", departure);
        fd.append("coupon_code", code);
        const res = await fetch("booking_quote.php", { method: "POST", body: fd });
        const json = await res.json();
        if (!json.success) {
          _bkCouponApplied = false;
          if (json.base_subtotal != null) {
            _bkRenderQuoteFromJson(json);
          } else {
            _bkScheduleQuoteRefresh();
          }
          msgEl.textContent = json.message || "Không áp dụng được mã.";
          msgEl.className = "bk-msg is-error";
          msgEl.style.display = "";
          return;
        }
        _bkCouponApplied = true;
        _bkRenderQuoteFromJson(json);
        const disc = Number(json.discount) || 0;
        msgEl.textContent =
          disc > 0
            ? "Đã áp dụng mã — giá đã cập nhật bên dưới."
            : "Mã hợp lệ nhưng không có giảm thêm cho đơn này.";
        msgEl.className = "bk-msg is-success";
        msgEl.style.display = "";
      } catch (_e) {
        msgEl.textContent = "Không kiểm tra được mã. Thử lại.";
        msgEl.className = "bk-msg is-error";
        msgEl.style.display = "";
      } finally {
        applyCouponBtn.disabled = false;
      }
    });
  }

  const depInp = document.getElementById("bk-departure");
  if (depInp) {
    depInp.addEventListener("change", () => _bkScheduleQuoteRefresh());
  }

  const couponInput = document.getElementById("bk-coupon");
  if (couponInput) {
    couponInput.addEventListener("input", () => {
      _bkCouponApplied = false;
      _bkScheduleQuoteRefresh();
      const msgEl = document.getElementById("bk-msg");
      if (msgEl && msgEl.classList.contains("is-success")) {
        msgEl.style.display = "none";
        msgEl.textContent = "";
      }
    });
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById("bk-submit-btn");
      const msgEl = document.getElementById("bk-msg");
      const adults = parseInt(document.getElementById("bk-adults").value) || 1;
      const children = parseInt(document.getElementById("bk-children").value) || 0;
      const departure = (document.getElementById("bk-departure")?.value || "").trim();
      const couponCode = (document.getElementById("bk-coupon")?.value || "").trim();

      if (!departure) {
        msgEl.textContent = "Vui lòng chọn ngày khởi hành.";
        msgEl.className = "bk-msg is-error";
        msgEl.style.display = "";
        return;
      }

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
      msgEl.style.display = "none";

      try {
        const formData = new FormData();
        formData.append("tour_id", _bkTourId);
        formData.append("adults", adults);
        formData.append("children", children);
        formData.append("departure_date", departure);
        formData.append("coupon_code", couponCode);

        const res = await fetch("booking.php", {
          method: "POST",
          body: formData,
        });
        const json = await res.json();

        if (json.success) {
          // Show success state
          document.getElementById("bk-form").style.display = "none";
          document.getElementById("bk-success-msg").textContent = json.message;
          document.getElementById("bk-success").style.display = "";
        } else {
          msgEl.textContent = json.message || "Đặt tour thất bại. Vui lòng thử lại.";
          msgEl.className = "bk-msg is-error";
          msgEl.style.display = "";
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Xác nhận đặt tour';
        }
      } catch (_err) {
        msgEl.textContent = "Lỗi kết nối. Vui lòng thử lại.";
        msgEl.className = "bk-msg is-error";
        msgEl.style.display = "";
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Xác nhận đặt tour';
      }
    });
  }
}

// ========== Booking Functions ==========
function handleBooking(e) {
  e.preventDefault();
  const btn = e.currentTarget;
  const tourCard = btn.closest(".tour-card");

  // tour_detail.php: card đặt tour không có <h3> — ưu tiên data-* trên nút
  const tourName =
    (btn.dataset.tourName && String(btn.dataset.tourName).trim()) ||
    tourCard?.querySelector("h3 a")?.textContent?.trim() ||
    tourCard?.querySelector("h3")?.textContent?.trim() ||
    "Tour";

  const tourId =
    btn.dataset.tourId ||
    tourCard?.dataset.tourId ||
    tourCard?.getAttribute("data-tour-id") ||
    "";

  const priceFromDom =
    tourCard?.querySelector(".tour-price")?.textContent?.replace(/\D/g, "") ||
    tourCard?.querySelector(".tour-detail-price")?.textContent?.replace(/\D/g, "") ||
    "0";
  const tourPrice =
    btn.dataset.tourPrice !== undefined && String(btn.dataset.tourPrice).trim() !== ""
      ? Number.parseFloat(String(btn.dataset.tourPrice))
      : parseFloat(priceFromDom) || 0;

  // Kiểm tra trạng thái đăng nhập từ PHP session (truyền qua biến JS toàn cục)
  const isLoggedIn =
    typeof window.__PHP_IS_LOGGED_IN__ !== "undefined"
      ? window.__PHP_IS_LOGGED_IN__
      : false;

  if (!isLoggedIn) {
    openLoginRequiredModal();
    return;
  }

  openBookingModal(tourId, tourName, tourPrice);
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
