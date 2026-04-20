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
  initTourDetailGallery();
  initTourDepartureCalendar();
});

function initTourDetailGallery() {
  const root = document.querySelector("[data-td-gallery]");
  const mainWrap = document.querySelector(".td-gallery-main");
  const mainImg = mainWrap?.querySelector("img");
  const lb = document.getElementById("td-lightbox");
  if (!root || !mainImg || !mainWrap || !lb) return;

  let items = [];
  try {
    const raw =
      root.dataset.galleryItems ||
      root.getAttribute("data-gallery-items") ||
      "[]";
    items = JSON.parse(raw);
  } catch {
    items = [];
  }
  if (!Array.isArray(items) || items.length === 0) return;

  const thumbs = root.querySelectorAll(".td-gallery-thumb");
  const lbImg = lb.querySelector(".td-lightbox-img");
  const lbThumbs = lb.querySelector("[data-td-lb-thumbs]");
  const lbCount = lb.querySelector("[data-td-lb-count]");
  const btnPrev = lb.querySelector(".td-lightbox-prev");
  const btnNext = lb.querySelector(".td-lightbox-next");
  const btnFs = lb.querySelector(".td-lightbox-fs");
  let currentIdx = 0;
  let prevFocus = null;

  function getActiveIndex() {
    let i = 0;
    thumbs.forEach((btn, idx) => {
      if (btn.classList.contains("is-active")) i = idx;
    });
    return i;
  }

  function setPageMainAt(index) {
    const it = items[index];
    if (!it?.full) return;
    mainImg.src = it.full;
    thumbs.forEach((b, idx) => {
      b.classList.toggle("is-active", idx === index);
      b.setAttribute("aria-current", idx === index ? "true" : "false");
    });
  }

  function buildLbThumbs() {
    if (!lbThumbs) return;
    lbThumbs.innerHTML = "";
    items.forEach((it, idx) => {
      const b = document.createElement("button");
      b.type = "button";
      b.className = "td-lb-thumb" + (idx === currentIdx ? " is-active" : "");
      b.setAttribute("aria-label", `Ảnh ${idx + 1}`);
      const im = document.createElement("img");
      im.src = it.thumb || it.full;
      im.alt = "";
      im.loading = "lazy";
      b.appendChild(im);
      b.addEventListener("click", () => goTo(idx));
      lbThumbs.appendChild(b);
    });
    if (lbCount) lbCount.textContent = String(items.length);
  }

  function updateLbThumbActive() {
    if (!lbThumbs) return;
    lbThumbs.querySelectorAll(".td-lb-thumb").forEach((b, idx) => {
      b.classList.toggle("is-active", idx === currentIdx);
    });
    const active = lbThumbs.children[currentIdx];
    active?.scrollIntoView?.({
      behavior: "smooth",
      block: "nearest",
      inline: "center",
    });
  }

  function goTo(index) {
    const n = items.length;
    if (n === 0) return;
    currentIdx = ((index % n) + n) % n;
    const it = items[currentIdx];
    if (lbImg) {
      lbImg.src = it.full;
      lbImg.alt = `Ảnh ${currentIdx + 1}`;
    }
    if (btnFs) btnFs.href = it.full;
    updateLbThumbActive();
    setPageMainAt(currentIdx);
  }

  function openLb(index) {
    currentIdx = Math.max(0, Math.min(index, items.length - 1));
    buildLbThumbs();
    goTo(currentIdx);
    prevFocus = document.activeElement;
    lb.hidden = false;
    lb.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    lb.querySelector(".td-lightbox-close")?.focus();
  }

  function closeLb() {
    lb.hidden = true;
    lb.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    if (prevFocus && typeof prevFocus.focus === "function") prevFocus.focus();
    prevFocus = null;
  }

  thumbs.forEach((btn, idx) => {
    btn.addEventListener("click", () => {
      const full = btn.getAttribute("data-full");
      if (full) mainImg.src = full;
      thumbs.forEach((b, j) => {
        b.classList.toggle("is-active", j === idx);
        b.setAttribute("aria-current", j === idx ? "true" : "false");
      });
    });
  });

  mainWrap.addEventListener("click", () => openLb(getActiveIndex()));
  mainWrap.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      openLb(getActiveIndex());
    }
  });

  if (items.length <= 1) {
    if (btnPrev) btnPrev.style.display = "none";
    if (btnNext) btnNext.style.display = "none";
  } else {
    btnPrev?.addEventListener("click", () => goTo(currentIdx - 1));
    btnNext?.addEventListener("click", () => goTo(currentIdx + 1));
  }

  lb.querySelectorAll("[data-td-lb-close]").forEach((el) => {
    el.addEventListener("click", closeLb);
  });

  document.addEventListener("keydown", (e) => {
    if (lb.hidden) return;
    if (e.key === "Escape") {
      closeLb();
    } else if (e.key === "ArrowLeft") {
      e.preventDefault();
      goTo(currentIdx - 1);
    } else if (e.key === "ArrowRight") {
      e.preventDefault();
      goTo(currentIdx + 1);
    }
  });
}

function initTourDepartureCalendar() {
  const root = document.getElementById("td-departure-root");
  if (!root) return;
  let departures = [];
  try {
    departures = JSON.parse(root.getAttribute("data-td-departures") || "[]");
  } catch {
    departures = [];
  }
  if (!Array.isArray(departures) || departures.length === 0) return;

  const byDate = new Map();
  const monthKeys = new Set();
  for (const row of departures) {
    const d = row && row.date ? String(row.date) : "";
    if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) continue;
    byDate.set(d, {
      price: Number(row.price) || 0,
      promo: Boolean(row.promo),
    });
    monthKeys.add(d.slice(0, 7));
  }
  const monthsSorted = Array.from(monthKeys).sort();

  const monthsEl = root.querySelector("[data-td-cal-months]");
  const titleEl = root.querySelector("[data-td-cal-title]");
  const gridEl = root.querySelector("[data-td-cal-grid]");
  const btnPrev = root.querySelector("[data-td-cal-prev]");
  const btnNext = root.querySelector("[data-td-cal-next]");
  const pickedInput = document.getElementById("td-picked-departure");
  if (!monthsEl || !titleEl || !gridEl) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const todayIso = _bkTodayIso();

  let viewYm = monthsSorted[0] || todayIso.slice(0, 7);
  let selectedIso = "";

  function parseYm(ym) {
    const [y, m] = ym.split("-").map((x) => parseInt(x, 10));
    return { y, m };
  }

  function fmtMonthTitle(ym) {
    const { y, m } = parseYm(ym);
    return `THÁNG ${m}/${y}`;
  }

  function fmtPriceK(price) {
    const n = Math.round(Number(price) || 0);
    if (n <= 0) return "";
    const k = Math.round(n / 1000);
    return k.toLocaleString("vi-VN") + "K";
  }

  function renderMonthSidebar() {
    monthsEl.innerHTML = "";
    monthsSorted.forEach((ym) => {
      const b = document.createElement("button");
      b.type = "button";
      b.className = "td-cal-month-btn" + (ym === viewYm ? " is-active" : "");
      const { y, m } = parseYm(ym);
      b.textContent = `${m}/${y}`;
      b.addEventListener("click", () => {
        viewYm = ym;
        renderMonthSidebar();
        renderGrid();
      });
      monthsEl.appendChild(b);
    });
  }

  function renderGrid() {
    titleEl.textContent = fmtMonthTitle(viewYm);
    const { y, m } = parseYm(viewYm);
    const first = new Date(y, m - 1, 1);
    const startPad = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(y, m, 0).getDate();
    const prevMonthDays = new Date(y, m - 1, 0).getDate();

    gridEl.innerHTML = "";
    const totalCells = Math.ceil((startPad + daysInMonth) / 7) * 7;

    for (let i = 0; i < totalCells; i++) {
      const dayNum = i - startPad + 1;
      const cell = document.createElement("div");
      cell.className = "td-cal-cell";

      if (dayNum < 1) {
        cell.classList.add("td-cal-cell--muted", "td-cal-cell--pad");
        const n = prevMonthDays + dayNum;
        cell.innerHTML = `<span class="td-cal-daynum">${n}</span>`;
      } else if (dayNum > daysInMonth) {
        cell.classList.add("td-cal-cell--muted", "td-cal-cell--pad");
        cell.innerHTML = `<span class="td-cal-daynum">${dayNum - daysInMonth}</span>`;
      } else {
        const iso = `${y}-${String(m).padStart(2, "0")}-${String(dayNum).padStart(2, "0")}`;
        const dt = new Date(y, m - 1, dayNum);
        dt.setHours(0, 0, 0, 0);
        const isPast = dt < today;
        const info = byDate.get(iso);

        cell.innerHTML = `<span class="td-cal-daynum">${dayNum}</span>`;
        if (isPast) {
          cell.classList.add("td-cal-cell--past");
        }
        if (info && !isPast) {
          cell.classList.add("td-cal-cell--tour");
          const gift =
            info.promo ?
              '<i class="fas fa-gift td-cal-gift" aria-hidden="true"></i>'
            : "";
          const pk = fmtPriceK(info.price);
          cell.innerHTML += `${gift}<span class="td-cal-price">${pk}</span>`;
          cell.setAttribute("role", "button");
          cell.tabIndex = 0;
          cell.setAttribute("aria-label", `Chọn ngày khởi hành ${iso}`);
          if (selectedIso === iso) {
            cell.classList.add("td-cal-cell--picked");
          }
          const pick = () => {
            selectedIso = iso;
            if (pickedInput) pickedInput.value = iso;
            renderGrid();
          };
          cell.addEventListener("click", pick);
          cell.addEventListener("keydown", (ev) => {
            if (ev.key === "Enter" || ev.key === " ") {
              ev.preventDefault();
              pick();
            }
          });
        } else if (info && isPast) {
          cell.classList.add("td-cal-cell--past");
        }
      }
      gridEl.appendChild(cell);
    }
  }

  function monthIndex() {
    let i = monthsSorted.indexOf(viewYm);
    if (i < 0) i = 0;
    return i;
  }

  btnPrev?.addEventListener("click", () => {
    const i = monthIndex();
    if (i > 0) {
      viewYm = monthsSorted[i - 1];
      renderMonthSidebar();
      renderGrid();
    }
  });
  btnNext?.addEventListener("click", () => {
    const i = monthIndex();
    if (i < monthsSorted.length - 1) {
      viewYm = monthsSorted[i + 1];
      renderMonthSidebar();
      renderGrid();
    }
  });

  const future = departures.map((r) => r.date).filter((d) => d >= todayIso);
  if (future.length) {
    selectedIso = future.sort()[0];
    if (pickedInput) pickedInput.value = selectedIso;
    viewYm = selectedIso.slice(0, 7);
  }

  renderMonthSidebar();
  renderGrid();
}

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

  // Price Slider — cập nhật giá + lọc ngay (không cần chỉ bấm "Áp dụng")
  if (priceSlider) {
    priceSlider.addEventListener("input", () => {
      updatePriceDisplay();
      readSidebarFiltersIntoState();
      filterTours();
    });
  }

  document
    .querySelectorAll('.sidebar .filter-section input[type="checkbox"]')
    .forEach((cb) => {
      cb.addEventListener("change", () => {
        readSidebarFiltersIntoState();
        filterTours();
      });
    });

  // Filter buttons
  if (applyFilterBtn) {
    applyFilterBtn.addEventListener("click", () => applyFilters(true));
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

        const destLabel = tour.destinationName || tour.destination || "";
        return `
          <article class="tour-card"
            data-tour-id="${tour.id}"
            data-destination="${tour.destination || ""}"
            data-duration="${durationFilterTag(duration)}"
            data-type="${tour.type || ""}"
            data-rating="${rating}">
            <div class="tour-card-image">
              <img src="${getTourImageUrl(index)}" alt="${tour.name}" loading="lazy" />
              <div class="tour-card-image-shine" aria-hidden="true"></div>
              <div class="tour-card-badges">
                <span class="tour-chip tour-chip--duration"><i class="fas fa-clock" aria-hidden="true"></i> ${duration}</span>
              </div>
              <div class="tour-card-overlay">
                <a href="tour_detail.php?id=${tour.id}" class="tour-card-quick-view">Xem chi tiết</a>
              </div>
              <button type="button" class="btn-wishlist btn-wishlist--card" title="Thêm vào yêu thích" aria-label="Thêm vào yêu thích" aria-pressed="false">
                <i class="far fa-heart" aria-hidden="true"></i>
              </button>
            </div>
            <div class="tour-card-content">
              <h3><a href="tour_detail.php?id=${tour.id}" class="tour-title-link">${tour.name}</a></h3>
              <div class="tour-meta tour-meta--inline">
                <span class="tour-destination"><i class="fas fa-location-dot" aria-hidden="true"></i> ${destLabel}</span>
              </div>
              <div class="tour-rating">
                <div class="stars" aria-hidden="true">${stars}</div>
                <span class="rating-count">${rating} · ${Math.max(40, Math.round(rating * 30))} đánh giá</span>
              </div>
              <div class="tour-card-footer">
                <div class="tour-price-block">
                  <span class="tour-price-label">Giá từ</span>
                  <span class="tour-price">${formatPrice(tour.price)}</span>
                </div>
                <div class="tour-card-footer-btns">
                  <a href="tour_detail.php?id=${tour.id}" class="btn-tour-detail">Chi tiết</a>
                  <button type="button" class="btn-book"
                    data-tour-id="${tour.id}"
                    data-tour-name="${String(tour.name).replace(/"/g, "&quot;")}"
                    data-tour-price="${Number(tour.price) || 0}">Đặt tour</button>
                </div>
              </div>
            </div>
          </article>
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
  currentFilters.priceRange = parseInt(priceSlider.value, 10) || 0;
}

function readSidebarFiltersIntoState() {
  currentFilters.destination = Array.from(
    document.querySelectorAll('input[name="destination"]:checked'),
  ).map((cb) => cb.value);
  currentFilters.duration = Array.from(
    document.querySelectorAll('input[name="duration"]:checked'),
  ).map((cb) => cb.value);
  currentFilters.tourType = Array.from(
    document.querySelectorAll('input[name="tour-type"]:checked'),
  ).map((cb) => cb.value);
  if (priceSlider) {
    currentFilters.priceRange = parseInt(priceSlider.value, 10) || 0;
  }
}

function updatePriceDisplay() {
  const value = parseInt(priceSlider.value);
  currentFilters.priceRange = value;

  if (priceValue) {
    priceValue.textContent = value.toLocaleString("vi-VN") + " đ";
  }
}

// ========== Filter Functions ==========
function applyFilters(showToast = false) {
  readSidebarFiltersIntoState();
  filterTours();
  if (showToast) {
    showNotification("Bộ lọc đã được áp dụng!", "success");
  }
}

function clearFilters() {
  document
    .querySelectorAll('.sidebar .filter-section input[type="checkbox"]')
    .forEach((cb) => {
      cb.checked = false;
    });

  if (priceSlider) {
    const maxVal =
      parseInt(priceSlider.getAttribute("max") || "", 10) ||
      parseInt(priceSlider.dataset.priceMax || "", 10) ||
      5000000;
    priceSlider.value = String(maxVal);
    updatePriceDisplay();
  }

  if (searchField) {
    searchField.value = "";
  }

  currentFilters = {
    destination: [],
    priceRange: priceSlider
      ? parseInt(priceSlider.value, 10) || 0
      : 5000000,
    duration: [],
    tourType: [],
    searchQuery: "",
  };

  filterTours();
  showNotification("Bộ lọc đã được xóa!", "info");
}

function filterTours() {
  if (!toursGrid) return;

  const tours = toursGrid.querySelectorAll(".tour-card");
  if (tours.length === 0) return;

  toursGrid.querySelectorAll(".tours-filter-empty").forEach((el) => el.remove());

  let visibleCount = 0;
  const searchQuery = currentFilters.searchQuery.trim().toLowerCase();

  tours.forEach((tour) => {
    const tourDestination = (tour.getAttribute("data-destination") || "").toLowerCase();
    const regionKeys = (tour.getAttribute("data-filter-regions") || "").toLowerCase();
    const haystack = `${regionKeys} ${tourDestination}`;
    const tourPrice = parseInt(tour.getAttribute("data-price") || "0", 10);
    const tourDuration = tour.getAttribute("data-duration") || "";
    const typeTags = (tour.getAttribute("data-tour-tags") || "")
      .split(/\s+/)
      .filter(Boolean);
    const searchBlob =
      (tour.getAttribute("data-search-text") || "").toLowerCase() ||
      (tour.querySelector("h3")?.textContent || "").toLowerCase();

    const destinationMatch =
      currentFilters.destination.length === 0 ||
      currentFilters.destination.some((key) => {
        const k = key.toLowerCase();
        return haystack.includes(k);
      });

    const priceMatch = tourPrice <= currentFilters.priceRange;

    const durationMatch =
      currentFilters.duration.length === 0 ||
      currentFilters.duration.some((d) => d === tourDuration);

    const typeMatch =
      currentFilters.tourType.length === 0 ||
      currentFilters.tourType.some((t) => typeTags.includes(t));

    const searchMatch =
      searchQuery === "" || searchBlob.includes(searchQuery);

    const shouldShow =
      destinationMatch &&
      priceMatch &&
      durationMatch &&
      typeMatch &&
      searchMatch;
    tour.style.display = shouldShow ? "" : "none";

    if (shouldShow) {
      visibleCount++;
    }
  });

  if (visibleCount === 0) {
    const emptyState = document.createElement("div");
    emptyState.className = "empty-state tours-filter-empty";
    emptyState.innerHTML = `
        <i class="fas fa-inbox"></i>
        <h3>Không tìm thấy tour phù hợp</h3>
        <p>Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
      `;
    toursGrid.appendChild(emptyState);
  }
}

// ========== Search & Sort Functions ==========
function handleSearch(e) {
  currentFilters.searchQuery = e.target.value;
  readSidebarFiltersIntoState();
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

  const tours = Array.from(toursGrid.querySelectorAll(".tour-card"));
  const emptyMsg = toursGrid.querySelector(".tours-filter-empty");

  const priceOf = (card) =>
    parseInt(card.getAttribute("data-price") || "0", 10);

  switch (sortValue) {
    case "price-low":
      tours.sort((a, b) => priceOf(a) - priceOf(b));
      break;
    case "price-high":
      tours.sort((a, b) => priceOf(b) - priceOf(a));
      break;
    case "rating":
      tours.sort(
        (a, b) =>
          parseFloat(b.getAttribute("data-rating") || "0") -
          parseFloat(a.getAttribute("data-rating") || "0"),
      );
      break;
    case "newest":
      tours.sort(
        (a, b) =>
          parseInt(b.getAttribute("data-tour-id") || "0", 10) -
          parseInt(a.getAttribute("data-tour-id") || "0", 10),
      );
      break;
    default:
      tours.sort(
        (a, b) =>
          parseInt(b.getAttribute("data-tour-id") || "0", 10) -
          parseInt(a.getAttribute("data-tour-id") || "0", 10),
      );
      break;
  }

  tours.forEach((tour) => toursGrid.appendChild(tour));
  if (emptyMsg) {
    toursGrid.appendChild(emptyMsg);
  }

  filterTours();
}

// ========== Wishlist Functions ==========
function initToursUrlSearch() {
  if (!searchField) return;
  const params = new URLSearchParams(window.location.search);
  const q = params.get("search");
  if (q) {
    searchField.value = q;
    currentFilters.searchQuery = q;
    readSidebarFiltersIntoState();
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
    const isAdded = wishlist.some((item) => String(item.id) === String(tourId));
    btn.classList.toggle("added", isAdded);
    btn.setAttribute("aria-pressed", isAdded ? "true" : "false");
    const icon = btn.querySelector("i");
    if (icon) {
      icon.className = isAdded ? "fas fa-heart" : "far fa-heart";
    }
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
    const picked = document.getElementById("td-picked-departure");
    const pv = picked && picked.value ? String(picked.value).trim() : "";
    dep.value = pv && pv >= t ? pv : t;
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
