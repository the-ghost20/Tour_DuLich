<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h4>Du Lịch Việt</h4>
        <p>
          Chúng tôi cam kết mang đến những chuyến đi ý nghĩa và kỳ niệm khó
          quên cho mọi gia đình Việt.
        </p>
        <div class="contact-info" id="contact">
          <p>
            <i class="fas fa-map-marker-alt"></i> 12 Nguyễn Văn Bảo, Gò Vấp,
            TP HCM
          </p>
          <p><i class="fas fa-phone"></i> (+84) 778-118 008</p>
          <p><i class="fas fa-envelope"></i> thairan2706@gmail.com</p>
        </div>
      </div>
      <div class="footer-col">
        <h4>DANH MỤC</h4>
        <ul>
          <li><a href="index.php">Trang chủ</a></li>
          <li><a href="about.php">Giới thiệu</a></li>
          <li><a href="tours.php">Tour trong nước</a></li>
          <li><a href="tours.php">Tour quốc tế</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>HỖ TRỢ</h4>
        <ul>
          <li><a href="terms.php">Điều khoản sử dụng</a></li>
          <li><a href="privacy.php">Chính sách bảo mật</a></li>
          <li><a href="guide.php">Hướng dẫn đặt tour</a></li>
          <li><a href="faq.php">FAQ</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>KẾT NỐI VỚI CHÚNG TÔI</h4>
        <p>Đăng ký để nhận tin khuyến mãi mới nhất!</p>
        <div class="social-links">
          <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
          <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
          <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Du lịch Việt.</p>
    </div>
  </div>
</footer>
<div
  class="login-modal-backdrop"
  id="login-modal"
  aria-hidden="true"
>
  <div
    class="login-modal-dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="login-modal-title"
  >
    <div class="auth-container auth-container--modal">
      <button
        type="button"
        class="login-modal-close"
        id="login-modal-close"
        aria-label="Đóng"
      >
        <i class="fas fa-times"></i>
      </button>
      <div class="auth-header-center">
        <div class="auth-header-icon">
          <i class="fas fa-user-circle"></i>
        </div>
        <h1 class="auth-title" id="login-modal-title">Đăng nhập</h1>
        <p class="auth-subtitle" id="login-modal-subtitle">Chào mừng bạn quay trở lại!</p>
      </div>

      <div class="auth-message" id="auth-modal-message" aria-live="polite"></div>

      <form method="post" action="login.php" id="auth-login-form" data-auth-tab="login">
        <div class="auth-field">
          <label for="login-modal-email"><i class="fas fa-envelope" style="color: #00acc1; margin-right: 4px;"></i> Email</label>
          <input id="login-modal-email" name="email" type="email" placeholder="Nhập email của bạn" required />
        </div>
        <div class="auth-field">
          <label for="login-modal-password"><i class="fas fa-lock" style="color: #00acc1; margin-right: 4px;"></i> Mật khẩu</label>
          <input id="login-modal-password" name="password" type="password" minlength="8" placeholder="Nhập mật khẩu" required />
        </div>
        
        <div class="auth-forgot-row">
          <a href="#" class="auth-link auth-forgot-link" id="auth-forgot-link" data-auth-tab-trigger="forgot">Quên mật khẩu?</a>
        </div>

        <div class="auth-actions auth-actions--stack">
          <button class="auth-btn auth-btn--block" type="submit">Đăng nhập</button>
          
          <div class="auth-footer-links">
            <p class="auth-footer-line">
              Chưa có tài khoản?
              <a class="auth-link" href="register.php">Đăng ký ngay</a>
            </p>
          </div>
          
        </div>
      </form>

      <form id="auth-forgot-form" data-auth-tab="forgot" class="auth-panel-hidden" hidden novalidate>
        <div class="auth-field">
          <label for="forgot-identity">Email hoặc số điện thoại</label>
          <input
            id="forgot-identity"
            name="identity"
            type="text"
            placeholder="Nhập email hoặc số điện thoại"
            required
          />
          <small class="auth-hint">Bạn có thể nhập email hoặc số điện thoại đã đăng ký.</small>
        </div>

        <div class="auth-field">
          <label for="forgot-country-code">Mã quốc gia (nếu nhập số điện thoại)</label>
          <select id="forgot-country-code" name="country_code">
            <option value="+84" selected>Việt Nam (+84)</option>
            <option value="+1">Mỹ (+1)</option>
            <option value="+81">Nhật Bản (+81)</option>
            <option value="+82">Hàn Quốc (+82)</option>
            <option value="+86">Trung Quốc (+86)</option>
          </select>
        </div>

        <div class="auth-field">
          <label for="forgot-new-password">Mật khẩu mới</label>
          <input
            id="forgot-new-password"
            name="new_password"
            type="password"
            minlength="8"
            required
          />
        </div>

        <div class="auth-field">
          <label for="forgot-confirm-password">Xác nhận mật khẩu mới</label>
          <input
            id="forgot-confirm-password"
            name="confirm_password"
            type="password"
            minlength="8"
            placeholder="Nhập lại mật khẩu mới"
            required
          />
        </div>

        <div class="auth-actions auth-actions--stack">
          <button class="auth-btn auth-btn--block" type="submit">Đổi mật khẩu</button>
          <p class="auth-footer-line">
            <a href="#" class="auth-link" data-auth-tab-trigger="login">Quay lại đăng nhập</a>
          </p>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="js/script.js?v=4"></script>

