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
const wishlistBtns = document.querySelectorAll(".btn-wishlist");
const bookBtns = document.querySelectorAll(".btn-book");
const mobileToggle = document.querySelector(".mobile-toggle");
const navbarMenu = document.querySelector(".navbar-menu");
const toursGrid = document.getElementById("tours-grid");
const wishlistItemsContainer = document.getElementById("wishlist-items");

// ========== State Management ==========
let currentFilters = {
  destination: [],
  priceRange: 5000000,
  duration: [],
  tourType: [],
  searchQuery: "",
};

let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

// ========== Initialize ==========
document.addEventListener("DOMContentLoaded", () => {
  initializeEventListeners();
  loadWishlistUI();
  setupPriceSlider();
});

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

  // Wishlist buttons
  wishlistBtns.forEach((btn) => {
    btn.addEventListener("click", toggleWishlist);
  });

  // Book buttons
  bookBtns.forEach((btn) => {
    btn.addEventListener("click", handleBooking);
  });

  // Login button
  if (loginBtn) {
    loginBtn.addEventListener("click", handleLogin);
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
  const tourPrice = tourCard.querySelector(".tour-price").textContent;

  // Check if logged in
  const isLoggedIn = localStorage.getItem("userToken");
  if (!isLoggedIn) {
    showNotification("Vui lòng đăng nhập để đặt tour!", "warning");
    handleLogin();
    return;
  }

  // Redirect to booking page
  console.log(`Đặt tour: ${tourName} - ${tourPrice}`);
  showNotification(`Bạn sắp đặt tour: ${tourName}`, "success");
  // window.location.href = `/booking?tour=${encodeURIComponent(tourName)}`;
}

// ========== Login Function ==========
function handleLogin(e) {
  if (e) e.preventDefault();
  showNotification("Chuyển hướng đến trang đăng nhập...", "info");
  // window.location.href = '/login';
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
