    <!-- ========== BOOKING MODAL ========== -->
    <div id="booking-modal" class="bk-backdrop" role="dialog" aria-modal="true" aria-labelledby="bk-modal-title" style="display:none;">
      <div class="bk-box">
        <button class="bk-close" id="bk-close-btn" aria-label="Đóng"><i class="fas fa-times"></i></button>

        <div class="bk-header">
          <div class="bk-icon"><i class="fas fa-map-marked-alt"></i></div>
          <div>
            <h2 id="bk-modal-title" class="bk-title">Đặt Tour</h2>
            <p class="bk-subtitle" id="bk-tour-name-display">Chọn thông tin đặt tour của bạn</p>
          </div>
        </div>

        <div class="bk-price-row">
          <span class="bk-price-label">Giá / người:</span>
          <span class="bk-price-val" id="bk-price-per">—</span>
        </div>

        <form id="bk-form" novalidate>
          <input type="hidden" id="bk-tour-id" name="tour_id" value="" />

          <div class="bk-row">
            <div class="bk-field">
              <label for="bk-adults"><i class="fas fa-user"></i> Người lớn</label>
              <div class="bk-counter">
                <button type="button" class="bk-cnt-btn" data-target="bk-adults" data-delta="-1">−</button>
                <input type="number" id="bk-adults" name="adults" value="1" min="1" max="20" readonly />
                <button type="button" class="bk-cnt-btn" data-target="bk-adults" data-delta="1">+</button>
              </div>
            </div>
            <div class="bk-field">
              <label for="bk-children"><i class="fas fa-child"></i> Trẻ em <small>(50%)</small></label>
              <div class="bk-counter">
                <button type="button" class="bk-cnt-btn" data-target="bk-children" data-delta="-1">−</button>
                <input type="number" id="bk-children" name="children" value="0" min="0" max="20" readonly />
                <button type="button" class="bk-cnt-btn" data-target="bk-children" data-delta="1">+</button>
              </div>
            </div>
          </div>

          <div class="bk-total">
            <span>Tổng tiền:</span>
            <strong id="bk-total-display">—</strong>
          </div>

          <div id="bk-msg" class="bk-msg" style="display:none;"></div>

          <div class="bk-actions">
            <button type="button" class="bk-btn-cancel" id="bk-cancel-btn">Huỷ</button>
            <button type="submit" class="bk-btn-submit" id="bk-submit-btn">
              <i class="fas fa-check-circle"></i> Xác nhận đặt tour
            </button>
          </div>
        </form>

        <div id="bk-success" style="display:none;" class="bk-success-box">
          <div class="bk-success-icon"><i class="fas fa-check-circle"></i></div>
          <h3>Đặt tour thành công!</h3>
          <p id="bk-success-msg"></p>
          <div class="bk-actions" style="justify-content:center;gap:12px;">
            <a href="my_bookings.php" class="bk-btn-submit" style="text-decoration:none;text-align:center;"><i class="fas fa-receipt"></i> Xem đơn của tôi</a>
            <button type="button" class="bk-btn-cancel" id="bk-success-close">Tiếp tục xem tour</button>
          </div>
        </div>
      </div>
    </div>

    <div id="login-required-modal" class="bk-backdrop" role="dialog" style="display:none;">
      <div class="bk-box" style="max-width:420px;text-align:center;">
        <button class="bk-close" id="lr-close-btn" aria-label="Đóng"><i class="fas fa-times"></i></button>
        <div class="bk-icon" style="margin:0 auto 16px;"><i class="fas fa-lock"></i></div>
        <h2 style="margin:0 0 8px;font-size:1.5rem;color:#0f2552;">Cần đăng nhập</h2>
        <p style="color:#6b7fa0;margin:0 0 24px;">Vui lòng đăng nhập để đặt tour và theo dõi các chuyến đi của bạn.</p>
        <div class="bk-actions" style="justify-content:center;gap:12px;">
          <a href="login.php" class="bk-btn-submit" style="text-decoration:none;"><i class="fas fa-sign-in-alt"></i> Đăng nhập ngay</a>
          <a href="register.php" class="bk-btn-cancel" style="text-decoration:none;">Đăng ký</a>
        </div>
      </div>
    </div>

    <style>
    .bk-backdrop {
      position: fixed; inset: 0;
      background: rgba(8, 20, 55, 0.6);
      backdrop-filter: blur(8px);
      z-index: 9999;
      display: flex !important;
      align-items: center;
      justify-content: center;
      padding: 16px;
      animation: bkFadeIn 0.2s ease;
    }
    @keyframes bkFadeIn { from { opacity: 0; } to { opacity: 1; } }
    .bk-box {
      background: #fff;
      border-radius: 24px;
      padding: 32px 30px 28px;
      width: 100%; max-width: 520px;
      box-shadow: 0 32px 80px rgba(8, 20, 80, 0.28);
      position: relative;
      animation: bkSlideUp 0.25s ease;
    }
    @keyframes bkSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .bk-close {
      position: absolute; top: 14px; right: 16px;
      background: #f0f4fa; border: none; border-radius: 50%;
      width: 34px; height: 34px; font-size: 0.9rem;
      color: #5a6e90; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background 0.2s, color 0.2s;
    }
    .bk-close:hover { background: #dce6f8; color: #1a3a70; }
    .bk-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
    .bk-icon {
      width: 50px; height: 50px; border-radius: 14px; flex-shrink: 0;
      background: linear-gradient(135deg, #1a73e8, #00bcd4);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.3rem;
      box-shadow: 0 8px 20px rgba(33,150,243,0.3);
    }
    .bk-title { margin: 0 0 3px; font-size: 1.4rem; font-weight: 800; color: #0f2552; }
    .bk-subtitle { margin: 0; font-size: 0.88rem; color: #6b7fa0; }
    .bk-price-row {
      background: linear-gradient(135deg, #eef5ff, #f4f8ff);
      border-radius: 12px; padding: 10px 16px;
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 18px; border: 1px solid #dce8f8;
    }
    .bk-price-label { color: #7088b5; font-size: 0.88rem; font-weight: 500; }
    .bk-price-val { color: #1a73e8; font-weight: 800; font-size: 1.05rem; }
    .bk-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .bk-field label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.87rem; color: #2a3d60; }
    .bk-field label small { color: #8aa0c8; font-weight: 400; }
    .bk-counter {
      display: flex; align-items: center; gap: 0;
      border: 2px solid #d6e4f8; border-radius: 12px; overflow: hidden;
    }
    .bk-cnt-btn {
      width: 38px; height: 40px; border: none; background: #eef5ff;
      color: #1a73e8; font-size: 1.1rem; font-weight: 700;
      cursor: pointer; transition: background 0.15s;
      display: flex; align-items: center; justify-content: center;
    }
    .bk-cnt-btn:hover { background: #dce8f8; }
    .bk-counter input {
      flex: 1; text-align: center; border: none; outline: none;
      font-size: 1rem; font-weight: 700; color: #0f2552;
      background: #fff; width: 0;
    }
    .bk-total {
      display: flex; justify-content: space-between; align-items: center;
      background: #f0f9ff; border-radius: 12px;
      padding: 12px 16px; margin-bottom: 16px;
      border: 1.5px solid #b3d9ff;
    }
    .bk-total span { color: #4a6080; font-weight: 500; }
    .bk-total strong { color: #0d47a1; font-size: 1.2rem; font-weight: 800; }
    .bk-msg {
      padding: 10px 14px; border-radius: 10px;
      font-size: 0.9rem; font-weight: 600; margin-bottom: 14px;
    }
    .bk-msg.is-error { background: #fff0f0; color: #ae1a1a; border: 1px solid #fcd5d5; }
    .bk-msg.is-success { background: #ecfdf5; color: #0d6e3a; border: 1px solid #b2f0d3; }
    .bk-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .bk-btn-submit {
      padding: 12px 22px; border: none; border-radius: 12px;
      background: linear-gradient(135deg, #1a73e8, #00bcd4);
      color: #fff; font-weight: 700; font-size: 0.93rem;
      cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
      box-shadow: 0 6px 18px rgba(33,150,243,0.3);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .bk-btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(33,150,243,0.38); }
    .bk-btn-submit:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
    .bk-btn-cancel {
      padding: 12px 18px; border: 1.5px solid #d0dbf0; border-radius: 12px;
      background: #fff; color: #4a6080; font-weight: 600; font-size: 0.93rem;
      cursor: pointer; transition: background 0.2s;
    }
    .bk-btn-cancel:hover { background: #f0f4fa; }
    .bk-success-box { text-align: center; padding: 10px 0 0; }
    .bk-success-icon { font-size: 3.5rem; color: #22c55e; margin-bottom: 12px; }
    .bk-success-box h3 { font-size: 1.5rem; font-weight: 800; color: #0f2552; margin: 0 0 8px; }
    .bk-success-box p { color: #6b7fa0; margin: 0 0 22px; }
    @media (max-width: 520px) {
      .bk-box { padding: 24px 18px 20px; }
      .bk-row { grid-template-columns: 1fr; }
      .bk-actions { flex-direction: column; }
    }
    </style>
