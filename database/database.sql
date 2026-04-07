IF DB_ID(N'QuanLy_Tour') IS NULL
BEGIN
    CREATE DATABASE [QuanLy_Tour];
END
GO

-- ============================================
-- 6. FRONTEND/BACKEND DATA SYNC (LATEST)
-- Đồng bộ dữ liệu hiển thị hiện tại từ ứng dụng về database.sql
-- ============================================

-- Bổ sung điểm đến nếu thiếu (theo dữ liệu app hiện tại)
INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Hạ Long', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Quảng Ninh', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Hạ Long');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Phú Quốc', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Kiên Giang', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Phú Quốc');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Sapa', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Lào Cai', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Sapa');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Hội An', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Quảng Nam', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Hội An');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Mù Cang Chải', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Yên Bái', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Mù Cang Chải');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Nha Trang', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Khánh Hòa', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Nha Trang');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Đà Lạt', N'Điểm đến đồng bộ từ frontend/backend', N'Việt Nam', N'Lâm Đồng', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Đà Lạt');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Bangkok', N'Điểm đến đồng bộ từ frontend/backend', N'Thái Lan', N'Bangkok', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Bangkok');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Angkor Wat', N'Điểm đến đồng bộ từ frontend/backend', N'Campuchia', N'Siem Reap', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Angkor Wat');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Bali', N'Điểm đến đồng bộ từ frontend/backend', N'Indonesia', N'Bali', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Bali');

DECLARE @TourSync TABLE (
    TourCode NVARCHAR(50) NOT NULL,
    TourName NVARCHAR(255) NOT NULL,
    DestinationName NVARCHAR(150) NOT NULL,
    DurationDays INT NOT NULL,
    PricePerAdult DECIMAL(12, 2) NOT NULL,
    TourType NVARCHAR(50) NOT NULL,
    IsInternational BIT NOT NULL
);

INSERT INTO @TourSync (TourCode, TourName, DestinationName, DurationDays, PricePerAdult, TourType, IsInternational)
VALUES
    (N'HL001', N'Hạ Long Bay 2 Ngày 1 Đêm', N'Hạ Long', 2, 1500000, N'Beach', 0),
    (N'HL002', N'Hạ Long Bay 3 Ngày 2 Đêm - Deluxe', N'Hạ Long', 3, 2500000, N'Beach', 0),
    (N'PQ001', N'Phú Quốc Paradise 4 Ngày 3 Đêm', N'Phú Quốc', 4, 3500000, N'Beach', 0),
    (N'SP001', N'Sapa Trekking & Mountain Adventure 3 Ngày', N'Sapa', 3, 2000000, N'Mountain', 0),
    (N'HA001', N'Hội An - Mỹ Sơn 2 Ngày 1 Đêm', N'Hội An', 2, 1200000, N'Cultural', 0),
    (N'MCC001', N'Mù Cang Chải - Ruộng Bậc Thang Vàng 2 Ngày', N'Mù Cang Chải', 2, 1500000, N'Mountain', 0),
    (N'NT001', N'Nha Trang Biển Xanh 3 Ngày 2 Đêm', N'Nha Trang', 3, 2200000, N'Beach', 0),
    (N'DL001', N'Đà Lạt - Thành Phố Ngàn Hoa 2 Ngày 1 Đêm', N'Đà Lạt', 2, 1800000, N'Mountain', 0),
    (N'BKK001', N'Bangkok - Thái Lan 3 Ngày 2 Đêm', N'Bangkok', 3, 2800000, N'Cultural', 1),
    (N'ANGKOR001', N'Campuchia - Angkor Wat 3 Ngày 2 Đêm', N'Angkor Wat', 3, 3200000, N'Cultural', 1),
    (N'BALI001', N'Bali - Indonesia 4 Ngày 3 Đêm', N'Bali', 4, 4200000, N'Beach', 1);

;WITH TourSource AS (
    SELECT
        ts.TourCode,
        ts.TourName,
        d.DestinationId,
        ts.DurationDays,
        ts.PricePerAdult,
        ts.TourType,
        ts.IsInternational
    FROM @TourSync ts
    INNER JOIN dbo.Destinations d ON d.Name = ts.DestinationName
)
MERGE dbo.Tours AS target
USING TourSource AS source
ON target.TourCode = source.TourCode
WHEN MATCHED THEN
    UPDATE SET
        target.TourName = source.TourName,
        target.DestinationId = source.DestinationId,
        target.Duration = source.DurationDays,
        target.PricePerAdult = source.PricePerAdult,
        target.TourType = source.TourType,
        target.IsInternational = source.IsInternational,
        target.Status = 'Available',
        target.UpdatedAt = GETDATE()
WHEN NOT MATCHED BY TARGET THEN
    INSERT (
        TourCode, TourName, Description, ItineraryDetails, DestinationId, Duration,
        DepartureCity, Capacity, AvailableSeats, PricePerAdult, PricePerChild, PricePerSenior,
        StartDate, EndDate, Highlights, Included, NotIncluded, CancellationPolicy,
        IsInternational, Status, TourType, DifficultyLevel, CreatedByUserId
    )
    VALUES (
        source.TourCode,
        source.TourName,
        N'Dữ liệu đồng bộ tự động từ frontend/backend.',
        N'Lịch trình chi tiết sẽ được cập nhật thêm.',
        source.DestinationId,
        source.DurationDays,
        N'TP Hồ Chí Minh',
        40,
        30,
        source.PricePerAdult,
        CAST(source.PricePerAdult * 0.65 AS DECIMAL(12,2)),
        CAST(source.PricePerAdult * 0.85 AS DECIMAL(12,2)),
        '2026-07-01 00:00:00',
        '2026-07-05 00:00:00',
        N'Đồng bộ dữ liệu từ ứng dụng',
        N'Bao gồm dịch vụ cơ bản',
        N'Không bao gồm chi tiêu cá nhân',
        N'Hủy trước 7 ngày hoàn 100%',
        source.IsInternational,
        'Available',
        source.TourType,
        'Easy',
        1
    );

GO

-- ============================================
-- 7. BOOKING DATA SYNC (LATEST)
-- Đồng bộ booking demo từ backend/data/bookings.json về DB
-- ============================================

DECLARE @BookingSync TABLE (
    BookingCode NVARCHAR(50) NOT NULL,
    TourCode NVARCHAR(50) NOT NULL,
    FullName NVARCHAR(150) NOT NULL,
    Email NVARCHAR(150) NOT NULL,
    PhoneNumber NVARCHAR(20) NULL,
    Guests INT NOT NULL,
    BookingStatus NVARCHAR(50) NOT NULL,
    BookingDate DATETIME NOT NULL
);

-- Dữ liệu hiện tại từ backend/data/bookings.json
-- tourId "tour-1" map sang TourCode "HL001" theo backend/data/tours.json
INSERT INTO @BookingSync (
    BookingCode, TourCode, FullName, Email, PhoneNumber, Guests, BookingStatus, BookingDate
)
VALUES (
    N'booking-1775531912284', N'HL001', N'Nguyen Van A', N'a@example.com',
    N'0901234567', 1, N'Pending', '2026-04-07T03:18:32.284'
);

-- Đảm bảo user khách hàng tồn tại trước khi tạo booking
MERGE dbo.Users AS target
USING (
    SELECT DISTINCT
        bs.FullName,
        bs.Email,
        bs.PhoneNumber
    FROM @BookingSync bs
) AS source
ON target.Email = source.Email
WHEN MATCHED THEN
    UPDATE SET
        target.FullName = source.FullName,
        target.PhoneNumber = source.PhoneNumber,
        target.UpdatedAt = GETDATE()
WHEN NOT MATCHED BY TARGET THEN
    INSERT (
        FullName, Email, PhoneNumber, Password, City, Country, RoleId, IsActive, IsEmailVerified
    )
    VALUES (
        source.FullName,
        source.Email,
        source.PhoneNumber,
        '$2b$10$abcdef1234567890abcdef',
        N'TP Hồ Chí Minh',
        N'Việt Nam',
        3,
        1,
        1
    );

;WITH BookingSource AS (
    SELECT
        bs.BookingCode,
        u.UserId,
        t.TourId,
        bs.Guests AS NumberOfAdults,
        bs.Guests AS TotalParticipants,
        t.PricePerAdult AS PricePerPerson,
        CAST(t.PricePerAdult * bs.Guests AS DECIMAL(12,2)) AS SubTotal,
        CAST(0 AS DECIMAL(12,2)) AS DiscountAmount,
        CAST(t.PricePerAdult * bs.Guests AS DECIMAL(12,2)) AS TotalAmount,
        bs.BookingStatus AS Status,
        N'Unpaid' AS PaymentStatus,
        bs.BookingDate
    FROM @BookingSync bs
    INNER JOIN dbo.Users u ON u.Email = bs.Email
    INNER JOIN dbo.Tours t ON t.TourCode = bs.TourCode
)
MERGE dbo.Bookings AS target
USING BookingSource AS source
ON target.BookingCode = source.BookingCode
WHEN MATCHED THEN
    UPDATE SET
        target.UserId = source.UserId,
        target.TourId = source.TourId,
        target.NumberOfAdults = source.NumberOfAdults,
        target.NumberOfChildren = 0,
        target.NumberOfSeniors = 0,
        target.TotalParticipants = source.TotalParticipants,
        target.PricePerPerson = source.PricePerPerson,
        target.SubTotal = source.SubTotal,
        target.DiscountAmount = source.DiscountAmount,
        target.CouponApplied = NULL,
        target.TaxAmount = 0,
        target.TotalAmount = source.TotalAmount,
        target.Status = source.Status,
        target.PaymentStatus = source.PaymentStatus,
        target.BookingDate = source.BookingDate,
        target.UpdatedAt = GETDATE()
WHEN NOT MATCHED BY TARGET THEN
    INSERT (
        BookingCode, UserId, TourId, NumberOfAdults, NumberOfChildren, NumberOfSeniors,
        TotalParticipants, PricePerPerson, SubTotal, DiscountAmount, CouponApplied,
        TaxAmount, TotalAmount, Status, PaymentStatus, BookingDate
    )
    VALUES (
        source.BookingCode, source.UserId, source.TourId, source.NumberOfAdults, 0, 0,
        source.TotalParticipants, source.PricePerPerson, source.SubTotal, source.DiscountAmount, NULL,
        0, source.TotalAmount, source.Status, source.PaymentStatus, source.BookingDate
    );

GO

USE [QuanLy_Tour];
GO


DROP TABLE IF EXISTS dbo.PaymentDetails;
DROP TABLE IF EXISTS dbo.RefundRequests;
DROP TABLE IF EXISTS dbo.CouponUsage;
DROP TABLE IF EXISTS dbo.ReviewRatings;
DROP TABLE IF EXISTS dbo.BlogComments;
DROP TABLE IF EXISTS dbo.BlogPosts;
DROP TABLE IF EXISTS dbo.Wishlists;
DROP TABLE IF EXISTS dbo.Contacts;
DROP TABLE IF EXISTS dbo.BookingDetails;
DROP TABLE IF EXISTS dbo.Bookings;
DROP TABLE IF EXISTS dbo.Coupons;
DROP TABLE IF EXISTS dbo.Tours;
DROP TABLE IF EXISTS dbo.Destinations;
DROP TABLE IF EXISTS dbo.RolePermissions;
DROP TABLE IF EXISTS dbo.UserPermissions;
DROP TABLE IF EXISTS dbo.Users;
DROP TABLE IF EXISTS dbo.Permissions;
DROP TABLE IF EXISTS dbo.Roles;
DROP TABLE IF EXISTS dbo.SystemSettings;

-- ============================================
-- 2. CREATE TABLES
-- ============================================

CREATE TABLE dbo.SystemSettings (
    SettingId INT PRIMARY KEY IDENTITY(1,1),
    SettingKey NVARCHAR(100) NOT NULL UNIQUE,
    SettingValue NVARCHAR(MAX),
    Description NVARCHAR(255),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    UpdatedBy INT
);

-- ========== ROLES ==========
CREATE TABLE dbo.Roles (
    RoleId INT PRIMARY KEY IDENTITY(1,1),
    RoleName NVARCHAR(50) NOT NULL UNIQUE, -- 'Admin', 'Staff', 'Customer'
    Description NVARCHAR(255),
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ========== PERMISSIONS ==========
CREATE TABLE dbo.Permissions (
    PermissionId INT PRIMARY KEY IDENTITY(1,1),
    PermissionCode NVARCHAR(100) NOT NULL UNIQUE, -- 'VIEW_DASHBOARD', 'CREATE_TOUR', etc
    PermissionName NVARCHAR(150),
    Description NVARCHAR(255),
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ========== ROLE - PERMISSION MAPPING ==========
CREATE TABLE dbo.RolePermissions (
    RolePermissionId INT PRIMARY KEY IDENTITY(1,1),
    RoleId INT NOT NULL,
    PermissionId INT NOT NULL,
    CreatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (RoleId) REFERENCES dbo.Roles(RoleId),
    FOREIGN KEY (PermissionId) REFERENCES dbo.Permissions(PermissionId),
    UNIQUE(RoleId, PermissionId)
);

-- ========== USER - PERMISSION OVERRIDE (RBAC chi tiết theo từng nhân viên) ==========
CREATE TABLE dbo.UserPermissions (
    UserPermissionId INT PRIMARY KEY IDENTITY(1,1),
    UserId INT NOT NULL,
    PermissionId INT NOT NULL,
    IsGranted BIT NOT NULL, -- 1: cấp thêm quyền, 0: từ chối quyền theo user cụ thể
    CreatedByUserId INT,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (PermissionId) REFERENCES dbo.Permissions(PermissionId),
    UNIQUE(UserId, PermissionId)
);

-- ========== USERS (Updated with more fields) ==========
CREATE TABLE dbo.Users (
    UserId INT PRIMARY KEY IDENTITY(1,1),
    FullName NVARCHAR(150) NOT NULL,
    Email NVARCHAR(150) NOT NULL UNIQUE,
    PhoneNumber NVARCHAR(20),
    Password NVARCHAR(255) NOT NULL, -- Hashed with Bcrypt
    DateOfBirth DATE,
    Gender NVARCHAR(10), -- 'Male', 'Female', 'Other'
    Address NVARCHAR(255),
    City NVARCHAR(100),
    Province NVARCHAR(100),
    Country NVARCHAR(100),
    PostalCode NVARCHAR(20),
    Avatar NVARCHAR(500), -- URL to avatar image
    RoleId INT NOT NULL DEFAULT 3, -- Default: Customer (RoleId 3)
    IsActive BIT NOT NULL DEFAULT 1,
    IsEmailVerified BIT DEFAULT 0,
    IsPhoneVerified BIT DEFAULT 0,
    LastLoginAt DATETIME,
    GoogleId NVARCHAR(255), -- For Google OAuth
    FacebookId NVARCHAR(255), -- For Facebook OAuth
    PasswordResetToken NVARCHAR(500),
    PasswordResetExpiry DATETIME,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (RoleId) REFERENCES dbo.Roles(RoleId)
);

ALTER TABLE dbo.UserPermissions
ADD CONSTRAINT FK_UserPermissions_UserId
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId);

ALTER TABLE dbo.UserPermissions
ADD CONSTRAINT FK_UserPermissions_CreatedByUserId
    FOREIGN KEY (CreatedByUserId) REFERENCES dbo.Users(UserId);

-- ========== DESTINATIONS ==========
CREATE TABLE dbo.Destinations (
    DestinationId INT PRIMARY KEY IDENTITY(1,1),
    Name NVARCHAR(150) NOT NULL,
    Description NVARCHAR(MAX),
    Country NVARCHAR(100),
    Province NVARCHAR(100),
    ImageUrl NVARCHAR(500),
    IsInternational BIT DEFAULT 0, -- 0: Trong nước, 1: Quốc tế
    IsActive BIT DEFAULT 1,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);

-- ========== TOURS ==========
CREATE TABLE dbo.Tours (
    TourId INT PRIMARY KEY IDENTITY(1,1),
    TourCode NVARCHAR(50) NOT NULL UNIQUE, -- Mã tour duy nhất
    TourName NVARCHAR(255) NOT NULL,
    Description NVARCHAR(MAX),
    ItineraryDetails NVARCHAR(MAX), -- Chi tiết lịch trình từng ngày
    DestinationId INT NOT NULL,
    Duration INT NOT NULL, -- Số ngày
    DepartureCity NVARCHAR(100), -- Thành phố khởi hành
    Capacity INT NOT NULL, -- Sức chứa tối đa
    AvailableSeats INT NOT NULL, -- Số chỗ còn lại
    PricePerAdult DECIMAL(12, 2) NOT NULL,
    PricePerChild DECIMAL(12, 2), -- Giá trẻ em
    PricePerSenior DECIMAL(12, 2), -- Giá người cao tuổi
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL,
    Highlights NVARCHAR(MAX), -- Những điểm nổi bật
    Included NVARCHAR(MAX), -- Những gì bao gồm (ăn, ở, vé vào, hướng dẫn)
    NotIncluded NVARCHAR(MAX), -- Những gì không bao gồm
    CancellationPolicy NVARCHAR(MAX), -- Chính sách hủy
    ImageUrl NVARCHAR(500), -- Hình ảnh đại diện
    IsInternational BIT DEFAULT 0,
    Status NVARCHAR(50) DEFAULT 'Available', -- 'Available', 'Full', 'Cancelled', 'Expired'
    TourType NVARCHAR(50), -- 'Beach', 'Mountain', 'Cultural', 'Adventure', etc
    DifficultyLevel NVARCHAR(20), -- 'Easy', 'Moderate', 'Hard'
    CreatedByUserId INT, -- Admin/Staff tạo tour
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (DestinationId) REFERENCES dbo.Destinations(DestinationId),
    FOREIGN KEY (CreatedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== COUPONS/VOUCHERS ==========
CREATE TABLE dbo.Coupons (
    CouponId INT PRIMARY KEY IDENTITY(1,1),
    CouponCode NVARCHAR(50) NOT NULL UNIQUE,
    Description NVARCHAR(255),
    DiscountType NVARCHAR(20) NOT NULL, -- 'Percentage' (%) hoặc 'Fixed' (VNĐ)
    DiscountValue DECIMAL(10, 2) NOT NULL,
    MaxDiscountAmount DECIMAL(12, 2), -- Nếu % thì giới hạn tiền giảm tối đa
    MinPurchaseAmount DECIMAL(12, 2), -- Giá tối thiểu để dùng coupon
    UsageLimit INT, -- Tổng số lần dùng (-1 = vô hạn)
    UsagePerCustomer INT DEFAULT 1, -- Số lần dùng per khách (-1 = vô hạn)
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL,
    IsActive BIT DEFAULT 1,
    ApplicableTourIds NVARCHAR(MAX), -- Danh sách TourId (cách nhau bởi dấu phẩy, NULL = áp dụng tất cả)
    CreatedByUserId INT,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (CreatedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== BOOKINGS ==========
CREATE TABLE dbo.Bookings (
    BookingId INT PRIMARY KEY IDENTITY(1,1),
    BookingCode NVARCHAR(50) NOT NULL UNIQUE,
    UserId INT NOT NULL,
    TourId INT NOT NULL,
    NumberOfAdults INT NOT NULL DEFAULT 1,
    NumberOfChildren INT DEFAULT 0,
    NumberOfSeniors INT DEFAULT 0,
    TotalParticipants INT NOT NULL,
    PricePerPerson DECIMAL(12, 2), -- Giá cuối cùng sau giảm giá
    SubTotal DECIMAL(12, 2), -- Tổng tiền trước giảm giá
    DiscountAmount DECIMAL(12, 2) DEFAULT 0,
    CouponApplied NVARCHAR(50), -- Mã coupon được áp dụng
    TaxAmount DECIMAL(12, 2) DEFAULT 0,
    TotalAmount DECIMAL(12, 2) NOT NULL,
    Status NVARCHAR(50) DEFAULT 'Pending', -- 'Pending', 'Confirmed', 'Cancelled', 'Completed'
    PaymentStatus NVARCHAR(50) DEFAULT 'Unpaid', -- 'Unpaid', 'PartialPaid', 'Paid'
    BookingDate DATETIME DEFAULT GETDATE(),
    ConfirmedDate DATETIME,
    CancelledDate DATETIME,
    CancellationReason NVARCHAR(500),
    Notes NVARCHAR(MAX),
    ApprovedByUserId INT, -- Admin/Staff phê duyệt
    ApprovedDate DATETIME,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (TourId) REFERENCES dbo.Tours(TourId),
    FOREIGN KEY (ApprovedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== BOOKING DETAILS (Danh sách người tham gia) ==========
CREATE TABLE dbo.BookingDetails (
    BookingDetailId INT PRIMARY KEY IDENTITY(1,1),
    BookingId INT NOT NULL,
    ParticipantName NVARCHAR(150) NOT NULL,
    ParticipantEmail NVARCHAR(150),
    ParticipantPhone NVARCHAR(20),
    ParticipantAge INT,
    ParticipantType NVARCHAR(20), -- 'Adult', 'Child', 'Senior'
    IdentityNumber NVARCHAR(50), -- CMND/Hộ chiếu
    SpecialRequests NVARCHAR(255), -- Yêu cầu đặc biệt (ăn kiêng, accessibility, etc)
    CreatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (BookingId) REFERENCES dbo.Bookings(BookingId) ON DELETE CASCADE
);

-- ========== PAYMENT DETAILS ==========
CREATE TABLE dbo.PaymentDetails (
    PaymentId INT PRIMARY KEY IDENTITY(1,1),
    BookingId INT NOT NULL,
    Amount DECIMAL(12, 2) NOT NULL,
    PaymentMethod NVARCHAR(50) NOT NULL, -- 'VNPay', 'CreditCard', 'BankTransfer', 'Cash'
    PaymentStatus NVARCHAR(50) DEFAULT 'Pending', -- 'Pending', 'Completed', 'Failed', 'Refunded'
    TransactionCode NVARCHAR(100) UNIQUE,
    TransactionRefCode NVARCHAR(100), -- Mã ref từ cổng thanh toán
    PaymentGateway NVARCHAR(50), -- 'VNPay', 'Stripe', 'PayPal', etc
    PaymentDate DATETIME,
    RefundAmount DECIMAL(12, 2) DEFAULT 0,
    RefundDate DATETIME,
    RefundReason NVARCHAR(500),
    RefundTransactionCode NVARCHAR(100),
    IPAddress NVARCHAR(50),
    UserAgent NVARCHAR(255),
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (BookingId) REFERENCES dbo.Bookings(BookingId) ON DELETE CASCADE
);

-- ========== REFUND REQUESTS (Theo dõi yêu cầu hủy tour/hoàn tiền) ==========
CREATE TABLE dbo.RefundRequests (
    RefundRequestId INT PRIMARY KEY IDENTITY(1,1),
    BookingId INT NOT NULL,
    RequestedByUserId INT NOT NULL, -- Khách hàng yêu cầu
    RequestedAt DATETIME DEFAULT GETDATE(),
    CancelReason NVARCHAR(500) NOT NULL,
    CancelPolicySnapshot NVARCHAR(MAX), -- Snapshot policy tại thời điểm yêu cầu
    RequestStatus NVARCHAR(50) NOT NULL DEFAULT 'Pending', 
    -- 'Pending', 'Approved', 'Rejected', 'RefundInProgress', 'Refunded', 'Closed'
    ReviewedByUserId INT, -- Staff/Admin xử lý
    ReviewedAt DATETIME,
    ReviewNote NVARCHAR(500),
    RefundAmountExpected DECIMAL(12,2),
    RefundAmountActual DECIMAL(12,2),
    RefundMethod NVARCHAR(50), -- Gateway/BankTransfer/Wallet...
    RefundTransactionCode NVARCHAR(100),
    RefundProcessedAt DATETIME,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (BookingId) REFERENCES dbo.Bookings(BookingId),
    FOREIGN KEY (RequestedByUserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (ReviewedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== COUPON USAGE TRACKING ==========
CREATE TABLE dbo.CouponUsage (
    UsageId INT PRIMARY KEY IDENTITY(1,1),
    CouponId INT NOT NULL,
    UserId INT NOT NULL,
    BookingId INT,
    UsedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (CouponId) REFERENCES dbo.Coupons(CouponId),
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (BookingId) REFERENCES dbo.Bookings(BookingId)
);

-- ========== WISHLISTS (Danh sách yêu thích) ==========
CREATE TABLE dbo.Wishlists (
    WishlistId INT PRIMARY KEY IDENTITY(1,1),
    UserId INT NOT NULL,
    TourId INT NOT NULL,
    Notes NVARCHAR(500),
    AddedAt DATETIME DEFAULT GETDATE(),
    UNIQUE(UserId, TourId),
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId) ON DELETE CASCADE,
    FOREIGN KEY (TourId) REFERENCES dbo.Tours(TourId) ON DELETE CASCADE
);

-- ========== BLOG POSTS ==========
CREATE TABLE dbo.BlogPosts (
    PostId INT PRIMARY KEY IDENTITY(1,1),
    Title NVARCHAR(255) NOT NULL,
    Slug NVARCHAR(255) NOT NULL UNIQUE, -- URL-friendly slug
    Content NVARCHAR(MAX) NOT NULL,
    Excerpt NVARCHAR(500), -- Mô tả ngắn
    AuthorUserId INT NOT NULL, -- Admin/Staff tác giả
    FeaturedImage NVARCHAR(500),
    Category NVARCHAR(100), -- 'Travel Tips', 'Destination Guide', 'Travel Story', etc
    Tags NVARCHAR(500), -- Cách nhau bởi dấu phẩy
    ViewCount INT DEFAULT 0,
    IsPublished BIT DEFAULT 1,
    PublishedAt DATETIME,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (AuthorUserId) REFERENCES dbo.Users(UserId)
);

-- ========== BLOG COMMENTS (Bình luận bài blog) ==========
CREATE TABLE dbo.BlogComments (
    CommentId INT PRIMARY KEY IDENTITY(1,1),
    PostId INT NOT NULL,
    UserId INT,
    CommentorName NVARCHAR(150), -- Nếu user không đăng nhập
    CommentorEmail NVARCHAR(150),
    Content NVARCHAR(MAX) NOT NULL,
    IsApproved BIT DEFAULT 0, -- Chờ duyệt
    ApprovedByUserId INT, -- Admin/Staff duyệt
    ApprovedAt DATETIME,
    IsHidden BIT DEFAULT 0, -- Ẩn spam/inappropriate comments
    RejectionReason NVARCHAR(255),
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (PostId) REFERENCES dbo.BlogPosts(PostId) ON DELETE CASCADE,
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (ApprovedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== REVIEW RATINGS (Đánh giá tour) ==========
CREATE TABLE dbo.ReviewRatings (
    ReviewId INT PRIMARY KEY IDENTITY(1,1),
    BookingId INT NOT NULL,
    TourId INT NOT NULL,
    UserId INT NOT NULL,
    Rating INT NOT NULL, -- 1-5 stars
    ReviewTitle NVARCHAR(255),
    ReviewContent NVARCHAR(MAX),
    Pros NVARCHAR(MAX), -- Điểm tốt
    Cons NVARCHAR(MAX), -- Điểm yếu
    IsRecommended BIT DEFAULT 1, -- Có recommend không
    HelpfulCount INT DEFAULT 0, -- Số người thấy helpful
    IsApproved BIT DEFAULT 0, -- Chờ duyệt
    ApprovedByUserId INT,
    ApprovedAt DATETIME,
    IsHidden BIT DEFAULT 0,
    RejectionReason NVARCHAR(255),
    VerifiedPurchase BIT DEFAULT 1, -- Khách hàng đã mua tour này
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (BookingId) REFERENCES dbo.Bookings(BookingId),
    FOREIGN KEY (TourId) REFERENCES dbo.Tours(TourId),
    FOREIGN KEY (UserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (ApprovedByUserId) REFERENCES dbo.Users(UserId)
);

-- ========== CONTACTS (Liên hệ/Support) ==========
CREATE TABLE dbo.Contacts (
    ContactId INT PRIMARY KEY IDENTITY(1,1),
    Name NVARCHAR(150) NOT NULL,
    Email NVARCHAR(150) NOT NULL,
    PhoneNumber NVARCHAR(20),
    Subject NVARCHAR(255) NOT NULL,
    Message NVARCHAR(MAX) NOT NULL,
    ContactType NVARCHAR(50), -- 'Inquiry', 'Complaint', 'Support', 'Feedback'
    Status NVARCHAR(50) DEFAULT 'New', -- 'New', 'In Progress', 'Resolved', 'Closed'
    Priority NVARCHAR(20), -- 'Low', 'Medium', 'High'
    AssignedToUserId INT, -- Staff xử lý
    Response NVARCHAR(MAX),
    RespondedAt DATETIME,
    RespondedByUserId INT,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (AssignedToUserId) REFERENCES dbo.Users(UserId),
    FOREIGN KEY (RespondedByUserId) REFERENCES dbo.Users(UserId)
);

-- ============================================
-- 4. INSERT SYSTEM DATA
-- ============================================

-- Roles
INSERT INTO dbo.Roles (RoleName, Description) VALUES
    ('Admin', N'Quản trị viên - Toàn quyền hệ thống'),
    ('Staff', N'Nhân viên - Xử lý đơn hàng, nội dung, support'),
    ('Customer', N'Khách hàng - Đặt tour, xem review');

-- Permissions
INSERT INTO dbo.Permissions (PermissionCode, PermissionName, Description) VALUES
    ('VIEW_DASHBOARD', N'Xem Dashboard', N'Truy cập bảng điều khiển thống kê'),
    ('MANAGE_TOURS', N'Quản lý Tour', N'Thêm, sửa, xóa tour'),
    ('MANAGE_BOOKINGS', N'Quản lý Đặt tour', N'Xem, duyệt, hủy đơn đặt'),
    ('MANAGE_PAYMENTS', N'Quản lý Thanh toán', N'Xem chi tiết thanh toán, hoàn tiền'),
    ('MANAGE_USERS', N'Quản lý User', N'Xem, khóa/mở user'),
    ('MANAGE_STAFF', N'Quản lý Nhân viên', N'Tạo tài khoản staff, phân quyền'),
    ('MANAGE_COUPONS', N'Quản lý Coupon', N'Tạo, sửa mã giảm giá'),
    ('VIEW_REPORTS', N'Xem Báo cáo', N'Truy cập báo cáo doanh thu'),
    ('MANAGE_BLOG', N'Quản lý Blog', N'Viết, duyệt bài viết'),
    ('MANAGE_REVIEWS', N'Duyệt Review', N'Duyệt/ẩn đánh giá khách hàng'),
    ('CUSTOMER_SUPPORT', N'Hỗ trợ khách hàng', N'Trả lời liên hệ, live chat'),
    ('MANAGE_SYSTEM', N'Cài đặt hệ thống', N'Quản lý thông tin website, chính sách'),
    ('EXPORT_DATA', N'Xuất dữ liệu', N'Xuất báo cáo Excel, PDF');

-- Role - Permission Mapping (Admin có tất cả)
INSERT INTO dbo.RolePermissions (RoleId, PermissionId)
SELECT 1, PermissionId FROM dbo.Permissions;

-- Staff có quyền nhất định
INSERT INTO dbo.RolePermissions (RoleId, PermissionId)
SELECT 2, PermissionId FROM dbo.Permissions
WHERE PermissionCode IN ('MANAGE_BOOKINGS', 'MANAGE_PAYMENTS', 'MANAGE_BLOG', 
                          'MANAGE_REVIEWS', 'CUSTOMER_SUPPORT', 'VIEW_REPORTS', 'EXPORT_DATA');

-- Customer không có quyền gì đặc biệt (default = 0)

-- System Settings
INSERT INTO dbo.SystemSettings (SettingKey, SettingValue, Description) VALUES
    ('WEBSITE_NAME', N'Tour Du Lịch Việt Nam', N'Tên website'),
    ('WEBSITE_LOGO', '/images/logo.png', 'URL logo'),
    ('WEBSITE_HOTLINE', '1900-8888', N'Số hotline'),
    ('WEBSITE_EMAIL', 'support@tourdullichvn.com', N'Email hỗ trợ'),
    ('COMPANY_ADDRESS', N'123 Nguyễn Huệ, Quận 1, TP HCM', N'Địa chỉ công ty'),
    ('COMPANY_PHONE', '028-1234-5678', N'SĐT công ty'),
    ('WEBSITE_BANNER', '/images/banner.jpg', N'Banner chính'),
    ('TAX_RATE', '10', N'Tỷ suất thuế (%)'),
    ('REFUND_POLICY_DAYS', '7', N'Số ngày cho phép hủy tour'),
    ('MIN_BOOKING_DAYS', '3', N'Số ngày tối thiểu trước khi tour khởi hành để đặt'),
    ('PAYMENT_TIMEOUT_MINUTES', '30', N'Thời gian để thanh toán (phút)'),
    ('VNP_TMNN', 'SANDBOX', 'VNPay Sandbox/Production'),
    ('MAX_PARTICIPANTS_PER_BOOKING', '10', N'Số người tối đa per đơn đặt');

-- ============================================
-- 5. INSERT SAMPLE DATA
-- ============================================

-- Users
INSERT INTO dbo.Users (FullName, Email, PhoneNumber, Password, DateOfBirth, Gender, 
                       Address, City, Country, RoleId, IsActive, IsEmailVerified)
VALUES
    (N'Tân Hiệp', 'tanhiep.admin@example.com', '0987654321', 
     '$2b$10$abcdef1234567890abcdef', '1990-05-15', 'Male', 
     N'123 Nguyễn Huệ', N'TP Hồ Chí Minh', N'Việt Nam', 1, 1, 1),
    
    (N'Nguyễn Thị Lan', 'lan.staff@example.com', '0912345678',
     '$2b$10$abcdef1234567890abcdef', '1995-08-20', 'Female',
     N'456 Lê Lợi', N'Hà Nội', N'Việt Nam', 2, 1, 1),
    
    (N'Trần Văn Sơn', 'son.staff@example.com', '0923456789',
     '$2b$10$abcdef1234567890abcdef', '1992-03-10', 'Male',
     N'789 Trần Hưng Đạo', N'Đà Nẵng', N'Việt Nam', 2, 1, 1),
    
    (N'Nguyễn Văn A', 'nguyenvana@example.com', '0901234567',
     '$2b$10$abcdef1234567890abcdef', '1988-11-25', 'Male',
     N'321 Võ Văn Kiệt', N'TP HCM', N'Việt Nam', 3, 1, 1),
    
    (N'Phạm Thị B', 'phamthib@example.com', '0934567890',
     '$2b$10$abcdef1234567890abcdef', '1996-07-30', 'Female',
     N'654 Pasteur', N'Sài Gòn', N'Việt Nam', 3, 1, 1),
    
    (N'Lê Văn C', 'levanc@example.com', '0945678901',
     '$2b$10$abcdef1234567890abcdef', '1999-02-14', 'Male',
     N'987 Ngô Gia Tự', N'Hải Phòng', N'Việt Nam', 3, 1, 1),
    
    (N'Hoàng Thị D', 'hoangthid@example.com', '0956789012',
     '$2b$10$abcdef1234567890abcdef', '1994-09-05', 'Female',
     N'111 Điện Biên Phủ', N'Cần Thơ', N'Việt Nam', 3, 1, 1),
    
    (N'Dương Văn E', 'duongvane@example.com', '0967890123',
     '$2b$10$abcdef1234567890abcdef', '2000-01-20', 'Male',
     N'222 Chu Văn An', N'Hà Nam', N'Việt Nam', 3, 1, 1);

-- User permission override ví dụ (cần chạy sau khi có dữ liệu dbo.Users để FK không lỗi)
-- Staff UserId=2 chỉ duyệt đơn, không quản lý blog
INSERT INTO dbo.UserPermissions (UserId, PermissionId, IsGranted, CreatedByUserId)
SELECT 2, PermissionId, CASE WHEN PermissionCode = 'MANAGE_BLOG' THEN 0 ELSE 1 END, 1
FROM dbo.Permissions
WHERE PermissionCode IN ('MANAGE_BOOKINGS', 'MANAGE_PAYMENTS', 'MANAGE_BLOG');

-- Staff UserId=3 chỉ viết blog/support, không duyệt thanh toán
INSERT INTO dbo.UserPermissions (UserId, PermissionId, IsGranted, CreatedByUserId)
SELECT 3, PermissionId, CASE WHEN PermissionCode = 'MANAGE_PAYMENTS' THEN 0 ELSE 1 END, 1
FROM dbo.Permissions
WHERE PermissionCode IN ('MANAGE_BLOG', 'CUSTOMER_SUPPORT', 'MANAGE_PAYMENTS');

-- Destinations (Trong nước & Quốc tế)
INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
VALUES
    (N'Hạ Long', N'Vịnh Hạ Long nổi tiếng với hòn đảo đá vôi xanh thẫm, di sản thế giới UNESCO', N'Việt Nam', N'Quảng Ninh', 0, 1),
    (N'Phú Quốc', N'Đảo ngọc phía nam với bãi biển trắng, lặn biển và khí hậu tuyệt vời', N'Việt Nam', N'Kiên Giang', 0, 1),
    (N'Sapa', N'Thị trấn núi tuyệt đẹp với khí hậu mát mẻ quanh năm và trek đẹp mê hồn', N'Việt Nam', N'Lào Cai', 0, 1),
    (N'Hội An', N'Phố cổ xinh đẹp với kiến trúc độc đáo, di sản thế giới', N'Việt Nam', N'Quảng Nam', 0, 1),
    (N'Mù Cang Chải', N'Vùng núi cao đẹp mê hoặc với ruộng bậc thang vàng óng ả', N'Việt Nam', N'Yên Bái', 0, 1),
    (N'Nha Trang', N'Thành phố biển du lịch nổi tiếng với nước xanh và bãi cát trắng', N'Việt Nam', N'Khánh Hòa', 0, 1),
    (N'Đà Lạt', N'Thành phố ngàn hoa với khí hậu mát mẻ, lâu đài cũ và thác nước đẹp', N'Việt Nam', N'Lâm Đồng', 0, 1),
    (N'Cần Thơ', N'Thành phố sông nước xinh đẹp, chợi nổi Cái Răng, vườn trái cây', N'Việt Nam', N'Cần Thơ', 0, 1),
    (N'Bangkok', N'Thủ đô Thái Lan với các đền chùa lộng lẫy, chợ đêm sôi động', N'Thái Lan', N'Bangkok', 1, 1),
    (N'Angkor Wat', N'Ngôi đền Khmer cổ nhất thế giới, nằm tại Campuchia', N'Campuchia', N'Siem Reap', 1, 1),
    (N'Bali', N'Pulau Bali nổi tiếng với bãi biển, đền thờ, và cuộc sống yên bình', N'Indonesia', N'Bali', 1, 1),
    (N'Đảo Langkawi', N'Đảo du lịch Malaysia nổi tiếng với khung cảnh tự nhiên độc đáo', N'Malaysia', N'Kedah', 1, 1);

-- Tours (Các tour chi tiết)
INSERT INTO dbo.Tours (TourCode, TourName, Description, ItineraryDetails, DestinationId, Duration, 
                       DepartureCity, Capacity, AvailableSeats, PricePerAdult, PricePerChild, PricePerSenior,
                       StartDate, EndDate, Highlights, Included, NotIncluded, CancellationPolicy, 
                       IsInternational, Status, TourType, DifficultyLevel, CreatedByUserId)
VALUES
    ('HL001', N'Hạ Long Bay 2 Ngày 1 Đêm', N'Tour tham quan vịnh Hạ Long du thuyền hạng sang với vé cáp treo Bà Nà',
     N'Ngày 1: TP HCM -> Hạ Long (3h) -> Du thuyền -> Hang Sửng Sốt -> Đảo Titop
     Ngày 2: Hang Dầu Gó -> Cảnh Bích -> Câu Cá -> Trở về Hạ Long -> TP HCM',
     1, 2, N'TP Hồ Chí Minh', 50, 38, 1500000, 900000, 1200000,
     '2026-04-10 00:00:00', '2026-04-12 00:00:00',
     N'Du thuyền hạng sang, Hang động tự nhiên, Đảo Titop, Bình minh trên vịnh',
     N'Vé du thuyền, Ăn sáng chiều, Hướng dẫn viên, Bảo hiểm',
     N'Vé máy bay, Ăn trưa, Tiền hành lý',
     N'Hủy 7 ngày trước được hoàn 100%, từ 4-7 ngày hoàn 50%',
     0, 'Available', 'Beach', 'Easy', 1),

    ('HL002', N'Hạ Long Bay 3 Ngày 2 Đêm - Deluxe', N'Tour tham quan đầy đủ vịnh Hạ Long với các hang động nổi tiếng',
     N'Ngày 1: Hà Nội -> Hạ Long -> Du thuyền -> Hang Sửng Sốt -> Đảo Titop -> Ăn tối trên thuyền
     Ngày 2: Bình minh trên vịnh -> Hang Dầu Gó -> Cảnh Bích -> Đảo Tuần Châu -> Ăn tối
     Ngày 3: Câu Cá -> Trở về Hạ Long -> Hà Nội',
     1, 3, N'Hà Nội', 40, 30, 2500000, 1500000, 2000000,
     '2026-04-15 00:00:00', '2026-04-18 00:00:00',
     N'Du thuyền 5 sao, 3 hang động, Ăn tối trên thuyền, Spa trên biển',
     N'Du thuyền deluxe, Phòng AC, 5 bữa ăn, Hướng dẫn, Bảo hiểm',
     N'Vé máy bay, Nước uống, Thêm dịch vụ',
     N'Hủy 10 ngày trước được hoàn 100%, từ 5-10 ngày hoàn 75%',
     0, 'Available', 'Beach', 'Easy', 1),

    ('PQ001', N'Phú Quốc Paradise 4 Ngày 3 Đêm', N'Tour 4 ngày 3 đêm khám phá Phú Quốc - Đảo Ngọc với các hoạt động biển',
     N'Ngày 1: TP HCM -> Phú Quốc (1h bay) -> Bãi Sao -> Tắm biển -> Chợ đêm
     Ngày 2: Tham quan vườn tiêu -> Lặn san hô -> Khám phá đảo Thơm -> Ăn hải sản
     Ngày 3: Bãi Khem -> Suối Nước Ngọt -> Zip line -> Ăn tối tại nhà hàng cao cấp
     Ngày 4: Tự do mua sắm -> Trở về TP HCM',
     2, 4, N'TP Hồ Chí Minh', 35, 20, 3500000, 2000000, 2800000,
     '2026-04-20 00:00:00', '2026-04-24 00:00:00',
     N'Bãi Sao, Lặn san hô, Khám phá đảo, Zip line, Ăn hải sản tươi',
     N'Vé máy bay khứ hồi, Resort 4 sao, 7 bữa ăn, Lặn san hô, Bảo hiểm',
     N'Chi phí cá nhân, Ăn sáng ngoài',
     N'Hủy 14 ngày trước hoàn 100%, từ 7-14 ngày hoàn 80%',
     0, 'Available', 'Beach', 'Easy', 1),

    ('SP001', N'Sapa Trekking & Mountain Adventure 3 Ngày', N'Tour leo núi và trekking tại Sapa với lắp trại cắm',
     N'Ngày 1: Hà Nội -> Sapa (5h) -> Cáp treo Fansipan -> Thị trấn Sapa
     Ngày 2: Trek Sapa -> Thác nước -> Làng dân tộc H''Mông -> Cắm trại
     Ngày 3: Bình minh núi -> Thác tơ Hà -> Trở về Hà Nội',
     3, 3, N'Hà Nội', 25, 15, 2000000, 1200000, 1600000,
     '2026-04-25 00:00:00', '2026-04-28 00:00:00',
     N'Fansipan 3143m, Trek tuyệt đẹp, Cắm trại, Gặp dân tộc H''Mông, Thác nước',
     N'Xe riêng, Nhà nghỉ 3 sao, Cắm trại, 6 bữa ăn, Hướng dẫn, Bảo hiểm',
     N'Đồ cắm trại (có thể thuê), Nước uống thêm',
     N'Hủy 5 ngày trước hoàn 100%, từ 3-5 ngày hoàn 70%',
     0, 'Available', 'Mountain', 'Moderate', 1),

    ('HA001', N'Hội An - Mỹ Sơn 2 Ngày 1 Đêm', N'Tour tham quan phố cổ Hội An và di tích Mỹ Sơn',
     N'Ngày 1: Đà Nẵng -> Hội An -> Phố cổ Hội An -> Chợ Hội An -> Nhà cũ 200 tuổi
     Ngày 2: Mỹ Sơ (Di tích Chăm) -> Thị trấn Hội An ban đêm -> Trở về Đà Nẵng',
     4, 2, N'Đà Nẵng', 45, 28, 1200000, 700000, 1000000,
     '2026-05-01 00:00:00', '2026-05-03 00:00:00',
     N'Phố cổ, Mỹ Sơn, Đèn lồng truyền thống, Nước cổ tích',
     N'Xe, Khách sạn 3 sao, 3 bữa ăn, Vé cổng, Hướng dẫn, Bảo hiểm',
     N'Ăn sáng ngoài',
     N'Hủy 7 ngày trước hoàn 100%, từ 4-7 ngày hoàn 60%',
     0, 'Available', 'Cultural', 'Easy', 1),

    ('MCC001', N'Mù Cang Chải - Ruộng Bậc Thang Vàng 2 Ngày', N'Tour chiêm ngưỡng ruộng bậc thang tuyệt đẹp nhất Việt Nam',
     N'Ngày 1: Hà Nội -> Yên Bái -> Mù Cang Chải -> La Pán Tẩn -> Khách sạn
     Ngày 2: Bình minh ruộng bậc thang -> Khám phá làng dân tộc -> Khiêu Phố -> Hà Nội',
     5, 2, N'Hà Nội', 30, 25, 1500000, 900000, 1200000,
     '2026-05-05 00:00:00', '2026-05-07 00:00:00',
     N'Ruộng bậc thang vàng óng, La Pán Tẩn, Bình minh, Dân tộc H''Mông',
     N'Xe riêng, Nhà nghỉ 3 sao, 4 bữa ăn, Hướng dẫn, Bảo hiểm',
     N'Chi phí cá nhân',
     N'Hủy 7 ngày trước hoàn 100%, từ 3-7 ngày hoàn 70%',
     0, 'Available', 'Mountain', 'Moderate', 1),

    ('NT001', N'Nha Trang Biển Xanh 3 Ngày 2 Đêm', N'Tour thơi mái ở Nha Trang với các hoạt động biển và lặn cá',
     N'Ngày 1: TP HCM -> Nha Trang (6h) -> Bãi Tắm -> Chợ đêm Nha Trang
     Ngày 2: Lặn biển -> Khám phá san hô -> Thác Ba Hồ -> Bãi Dốc Lết
     Ngày 3: Tự do mua sắm -> Trở về TP HCM',
     6, 3, N'TP Hồ Chí Minh', 50, 40, 2200000, 1300000, 1800000,
     '2026-05-10 00:00:00', '2026-05-13 00:00:00',
     N'Lặn biển, San hô, Thác Ba Hồ, Ăn hải sản tươi, Đảo Mukđahan',
     N'Xe, Resort 4 sao, 5 bữa ăn, Lặn biển, Hướng dẫn, Bảo hiểm',
     N'Các chi phí cá nhân',
     N'Hủy 7 ngày trước hoàn 100%, từ 4-7 ngày hoàn 70%',
     0, 'Available', 'Beach', 'Easy', 1),

    ('DL001', N'Đà Lạt - Thành Phố Ngàn Hoa 2 Ngày 1 Đêm', N'Tour khám phá Đà Lạt với các thác nước, lâu đài cũ',
     N'Ngày 1: TP HCM -> Đà Lạt (4h) -> Lâu Đài Cổ -> Hợp Lưu -> Chợ Đêm
     Ngày 2: Thác Liên Khương -> Vườn Hoa Dạ Thảo -> Đại Lâm -> Trở về TP HCM',
     7, 2, N'TP Hồ Chí Minh', 40, 35, 1800000, 1000000, 1500000,
     '2026-05-15 00:00:00', '2026-05-17 00:00:00',
     N'Lâu đài cũ, Thác nước, Thị trấn cổ, Khí hậu mát mẻ, Hợp Lưu',
     N'Xe, Khách sạn 3 sao, 3 bữa ăn, Vé cổng, Bảo hiểm',
     N'Ăn sáng ngoài',
     N'Hủy 7 ngày trước hoàn 100%, từ 4-7 ngày hoàn 70%',
     0, 'Available', 'Mountain', 'Easy', 1),

    ('BKK001', N'Bangkok - Thái Lan 3 Ngày 2 Đêm', N'Tour khám phá thủ đô Thái Lan với các đền chùa nổi tiếng',
     N'Ngày 1: TP HCM -> Bangkok (1h) -> Chùa Wat Phra Kaew -> Cung Điện Hoàng Gia
     Ngày 2: Chợi Damnoen Saduak -> Chùa Arun -> Chợ Đêm Chatuchak
     Ngày 3: Tự do mua sắm -> Trở về TP HCM',
     9, 3, N'TP Hồ Chí Minh', 40, 32, 2800000, 1500000, 2200000,
     '2026-05-20 00:00:00', '2026-05-23 00:00:00',
     N'Đền chùa lộng lẫy, Chợi nổi, Chợ đêm sôi động, Ẩm thực Thái',
     N'Vé máy bay, Hotel 4 sao, 5 bữa ăn, Hướng dẫn, Visa, Bảo hiểm',
     N'Chi phí mua sắm',
     N'Hủy 14 ngày trước hoàn 100%, từ 7-14 ngày hoàn 80%',
     1, 'Available', 'Cultural', 'Easy', 1),

    ('ANGKOR001', N'Campuchia - Angkor Wat 3 Ngày 2 Đêm', N'Tour khám phá đền Angkor Wat - kỳ quan thế giới',
     N'Ngày 1: TP HCM -> Siem Reap (1h) -> Angkor Wat bình minh -> Tham quan đền
     Ngày 2: Angkor Thom -> Bayon -> Thớm Tá -> Baoung -> Chợ đêm',
     10, 3, N'TP Hồ Chí Minh', 35, 25, 3200000, 1800000, 2600000,
     '2026-05-25 00:00:00', '2026-05-28 00:00:00',
     N'Angkor Wat, Angkor Thom, Bayon, Di sản thế giới, Bình minh đẹp',
     N'Vé máy bay, Hotel 3 sao, 5 bữa ăn, Vé Angkor 3 ngày, Hướng dẫn, Bảo hiểm',
     N'Chi phí cá nhân, Thêm dịch vụ',
     N'Hủy 14 ngày trước hoàn 100%, từ 7-14 ngày hoàn 75%',
     1, 'Available', 'Cultural', 'Easy', 1),

    ('BALI001', N'Bali - Indonesia 4 Ngày 3 Đêm', N'Tour khám phá Bali với các bãi biển, đền thờ, và cuộc sống yên bình',
     N'Ngày 1: TP HCM -> Bali (2h) -> Bãi biển Kuta -> Tắm biển -> Ăn tối trên bãi
     Ngày 2: Đền Tanah Lot -> Sawah terass -> Ubud -> Chợ đêm
     Ngày 3: Núi Batur trek -> Thác Tegenungan -> Bãi biển
     Ngày 4: Tự do -> Trở về TP HCM',
     11, 4, N'TP Hồ Chí Minh', 40, 30, 4200000, 2200000, 3400000,
     '2026-06-01 00:00:00', '2026-06-05 00:00:00',
     N'Bãi biển, Đền Tanah Lot, Trek Batur, Ubud, SPA truyền thống',
     N'Vé máy bay, Resort 4 sao, 8 bữa ăn, SPA, Hướng dẫn, Bảo hiểm, Visa',
     N'Chi phí cá nhân',
     N'Hủy 21 ngày trước hoàn 100%, từ 7-21 ngày hoàn 85%',
     1, 'Available', 'Beach', 'Easy', 1);

-- Bổ sung điểm đến nội địa phổ biến cho nhóm tour mới
INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Sài Gòn', N'Trung tâm kinh tế, văn hóa với nhiều địa danh lịch sử nổi bật', N'Việt Nam', N'TP Hồ Chí Minh', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Sài Gòn');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Đà Nẵng', N'Thành phố biển hiện đại, cửa ngõ kết nối Hội An và Bà Nà Hills', N'Việt Nam', N'Đà Nẵng', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Đà Nẵng');

INSERT INTO dbo.Destinations (Name, Description, Country, Province, IsInternational, IsActive)
SELECT N'Miền Tây', N'Vùng sông nước Cửu Long với chợ nổi, miệt vườn và văn hóa bản địa', N'Việt Nam', N'Đồng bằng Sông Cửu Long', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM dbo.Destinations WHERE Name = N'Miền Tây');

-- Bổ sung 5 tour mới (dài, chi tiết) để test hiển thị dữ liệu
INSERT INTO dbo.Tours (
    TourCode, TourName, Description, ItineraryDetails, DestinationId, Duration,
    DepartureCity, Capacity, AvailableSeats, PricePerAdult, PricePerChild, PricePerSenior,
    StartDate, EndDate, Highlights, Included, NotIncluded, CancellationPolicy,
    IsInternational, Status, TourType, DifficultyLevel, CreatedByUserId
)
SELECT
    N'SG003',
    N'Sài Gòn City Tour: Dinh Độc Lập - Nhà Thờ Đức Bà - Bưu Điện Thành Phố - Chợ Bến Thành',
    N'Tour nội đô khám phá các biểu tượng lịch sử và văn hóa nổi tiếng tại trung tâm Sài Gòn.',
    N'Ngày 1: Đón khách tại trung tâm -> Dinh Độc Lập -> Nhà Thờ Đức Bà -> Bưu Điện Thành Phố -> Chợ Bến Thành -> Kết thúc.',
    d.DestinationId, 1, N'TP Hồ Chí Minh', 45, 33, 1290000, 790000, 1090000,
    '2026-06-08 08:00:00', '2026-06-08 20:00:00',
    N'Di tích lịch sử trung tâm, ẩm thực địa phương, hướng dẫn viên chuyên tuyến',
    N'Xe đưa đón nội thành, vé tham quan, hướng dẫn viên, nước suối',
    N'Chi tiêu cá nhân, đồ uống phát sinh',
    N'Hủy trước 3 ngày hoàn 100%, từ 1-3 ngày hoàn 50%',
    0, 'Available', 'Cultural', 'Easy', 1
FROM dbo.Destinations d
WHERE d.Name = N'Sài Gòn'
  AND NOT EXISTS (SELECT 1 FROM dbo.Tours t WHERE t.TourCode = N'SG003')
UNION ALL
SELECT
    N'HL003',
    N'Khám Phá Hạ Long Trọn Gói: Vịnh Hạ Long - Hang Sửng Sốt - Đảo Ti Tốp - Du Thuyền Ngắm Hoàng Hôn',
    N'Tour Hạ Long trọn gói với du thuyền nghỉ đêm và lịch trình điểm nhấn đặc trưng.',
    N'Ngày 1: Hà Nội -> Hạ Long -> Du thuyền -> Hang Sửng Sốt -> Đảo Ti Tốp -> Ngắm hoàng hôn. Ngày 2: Tham quan vịnh -> Trở về.',
    d.DestinationId, 2, N'Hà Nội', 40, 29, 3590000, 2190000, 3090000,
    '2026-06-12 07:00:00', '2026-06-13 19:00:00',
    N'Du thuyền chất lượng cao, trải nghiệm hoàng hôn trên vịnh, lịch trình cân bằng',
    N'Xe khứ hồi, du thuyền 1 đêm, 3 bữa chính, vé tham quan',
    N'Đồ uống ngoài chương trình, chi phí cá nhân',
    N'Hủy trước 7 ngày hoàn 100%, từ 4-7 ngày hoàn 60%',
    0, 'Available', 'Beach', 'Easy', 1
FROM dbo.Destinations d
WHERE d.Name = N'Hạ Long'
  AND NOT EXISTS (SELECT 1 FROM dbo.Tours t WHERE t.TourCode = N'HL003')
UNION ALL
SELECT
    N'MT002',
    N'Hành Trình Miền Tây Sông Nước: Mỹ Tho - Bến Tre - Cồn Phụng - Trải Nghiệm Chợ Nổi Cái Răng',
    N'Tour sông nước miền Tây kết hợp miệt vườn, chợ nổi và trải nghiệm văn hóa bản địa.',
    N'Ngày 1: TP HCM -> Mỹ Tho -> Bến Tre -> Cồn Phụng. Ngày 2: Cần Thơ -> Chợ nổi Cái Răng -> Trở về.',
    d.DestinationId, 2, N'TP Hồ Chí Minh', 42, 30, 2490000, 1490000, 2090000,
    '2026-06-16 06:30:00', '2026-06-17 20:00:00',
    N'Chợ nổi, xuồng ba lá, đờn ca tài tử, đặc sản miền Tây',
    N'Xe đưa đón, khách sạn 3 sao, vé tham quan, 4 bữa ăn',
    N'Chi phí cá nhân, đồ uống riêng',
    N'Hủy trước 5 ngày hoàn 100%, từ 2-5 ngày hoàn 50%',
    0, 'Available', 'Cultural', 'Easy', 1
FROM dbo.Destinations d
WHERE d.Name = N'Miền Tây'
  AND NOT EXISTS (SELECT 1 FROM dbo.Tours t WHERE t.TourCode = N'MT002')
UNION ALL
SELECT
    N'DN002',
    N'Tour Nghỉ Dưỡng Đà Nẵng: Bà Nà Hills - Cầu Vàng - Ngũ Hành Sơn - Hội An Phố Cổ Về Đêm',
    N'Tour kết hợp nghỉ dưỡng và tham quan biểu tượng miền Trung trong 3 ngày 2 đêm.',
    N'Ngày 1: Đà Nẵng -> Ngũ Hành Sơn -> Hội An. Ngày 2: Bà Nà Hills -> Cầu Vàng. Ngày 3: Tự do mua sắm -> Kết thúc.',
    d.DestinationId, 3, N'TP Hồ Chí Minh', 36, 27, 4290000, 2690000, 3690000,
    '2026-06-20 08:00:00', '2026-06-22 20:00:00',
    N'Bà Nà Hills, Cầu Vàng, phố cổ Hội An, dịch vụ nghỉ dưỡng',
    N'Vé máy bay khứ hồi, khách sạn 4 sao, xe đưa đón, vé cáp treo',
    N'Ăn uống tự do ngoài chương trình, mua sắm cá nhân',
    N'Hủy trước 10 ngày hoàn 100%, từ 5-10 ngày hoàn 70%',
    0, 'Available', 'Beach', 'Easy', 1
FROM dbo.Destinations d
WHERE d.Name = N'Đà Nẵng'
  AND NOT EXISTS (SELECT 1 FROM dbo.Tours t WHERE t.TourCode = N'DN002')
UNION ALL
SELECT
    N'DL002',
    N'Khám Phá Đà Lạt Mộng Mơ: Langbiang - Thung Lũng Tình Yêu - Chợ Đêm Đà Lạt - Vườn Hoa Thành Phố',
    N'Tour Đà Lạt thư giãn, phù hợp gia đình và nhóm bạn yêu thích khí hậu cao nguyên.',
    N'Ngày 1: TP HCM -> Đà Lạt -> Langbiang -> Chợ đêm. Ngày 2: Thung lũng Tình Yêu -> Vườn hoa -> Trở về.',
    d.DestinationId, 2, N'TP Hồ Chí Minh', 38, 28, 3190000, 1990000, 2790000,
    '2026-06-25 07:30:00', '2026-06-26 21:00:00',
    N'Langbiang, khí hậu mát mẻ, check-in vườn hoa, ẩm thực Đà Lạt',
    N'Xe giường nằm, khách sạn 3 sao, vé tham quan, hướng dẫn viên',
    N'Chi phí cá nhân, đồ uống ngoài chương trình',
    N'Hủy trước 5 ngày hoàn 100%, từ 2-5 ngày hoàn 60%',
    0, 'Available', 'Mountain', 'Easy', 1
FROM dbo.Destinations d
WHERE d.Name = N'Đà Lạt'
  AND NOT EXISTS (SELECT 1 FROM dbo.Tours t WHERE t.TourCode = N'DL002');

-- Coupons
INSERT INTO dbo.Coupons (CouponCode, Description, DiscountType, DiscountValue, MaxDiscountAmount, 
                         MinPurchaseAmount, UsageLimit, UsagePerCustomer, StartDate, EndDate, IsActive, CreatedByUserId)
VALUES
    ('SUMMER2026', N'Khuyến mãi hè 2026 - Giảm 20%', 'Percentage', 20, 2000000, 5000000, 100, 1,
     '2026-04-01 00:00:00', '2026-06-30 23:59:59', 1, 1),
    
    ('NEWYEAR2026', N'Khuyến mãi năm mới - Giảm 500.000đ', 'Fixed', 500000, NULL, 3000000, 50, 2,
     '2026-04-01 00:00:00', '2026-05-31 23:59:59', 1, 1),
    
    ('VIP1000', N'VIP - Giảm 1.000.000đ', 'Fixed', 1000000, NULL, 10000000, 20, 1,
     '2026-04-01 00:00:00', '2026-12-31 23:59:59', 1, 1),
    
    ('BEACH15', N'Tour biển - Giảm 15%', 'Percentage', 15, 1500000, 5000000, 100, 2,
     '2026-04-01 00:00:00', '2026-08-31 23:59:59', 1, 1),
    
    ('REFER100', N'Giới thiệu bạn - Giảm 100.000đ', 'Fixed', 100000, NULL, 2000000, -1, 5,
     '2026-04-01 00:00:00', '2026-12-31 23:59:59', 1, 1);

-- Bookings
INSERT INTO dbo.Bookings (BookingCode, UserId, TourId, NumberOfAdults, NumberOfChildren, 
                         TotalParticipants, PricePerPerson, SubTotal, DiscountAmount, CouponApplied,
                         TotalAmount, Status, PaymentStatus, ConfirmedDate, ApprovedByUserId, ApprovedDate)
VALUES
    ('BK001', 4, 1, 2, 0, 2, 1500000, 3000000, 0, NULL, 3000000, 'Confirmed', 'Paid',
     '2026-03-31 10:30:00', 1, '2026-03-31 10:45:00'),
    
    ('BK002', 5, 2, 3, 1, 4, 2250000, 9000000, 900000, 'SUMMER2026', 8100000, 'Confirmed', 'Paid',
     '2026-03-30 14:15:00', 1, '2026-03-30 14:30:00'),
    
    ('BK003', 6, 3, 4, 2, 6, 3200000, 19200000, 1920000, 'SUMMER2026', 17280000, 'Pending', 'Unpaid',
     NULL, NULL, NULL),
    
    ('BK004', 7, 4, 2, 0, 2, 2000000, 4000000, 500000, 'NEWYEAR2026', 3500000, 'Confirmed', 'Paid',
     '2026-03-29 09:00:00', 2, '2026-03-29 09:15:00'),
    
    ('BK005', 8, 5, 3, 1, 4, 1100000, 4400000, 0, NULL, 4400000, 'Pending', 'PartialPaid',
     NULL, NULL, NULL);

-- Booking Details
INSERT INTO dbo.BookingDetails (BookingId, ParticipantName, ParticipantEmail, ParticipantPhone, 
                               ParticipantAge, ParticipantType)
VALUES
    (1, N'Nguyễn Văn A', 'nguyenvana@example.com', '0901234567', 30, 'Adult'),
    (1, N'Nguyễn Thị A1', 'nguyenthia1@example.com', '0901234568', 28, 'Adult'),
    (2, N'Phạm Thị B', 'phamthib@example.com', '0934567890', 25, 'Adult'),
    (2, N'Phạm Văn B', 'phamvanb@example.com', '0934567891', 27, 'Adult'),
    (2, N'Phạm Thị B2', 'phamthib2@example.com', '0934567892', 60, 'Senior'),
    (2, N'Phạm Văn B1', 'phamvanb1@example.com', '0934567893', 5, 'Child'),
    (3, N'Lê Văn C', 'levanc@example.com', '0945678901', 35, 'Adult'),
    (3, N'Lê Thị C', 'lethic@example.com', '0945678902', 33, 'Adult'),
    (3, N'Lê Văn C1', 'levanc1@example.com', '0945678903', 8, 'Child'),
    (3, N'Lê Thị C2', 'lethic2@example.com', '0945678904', 6, 'Child'),
    (3, N'Lê Văn C2', 'levanc2@example.com', '0945678905', 65, 'Senior'),
    (3, N'Lê Thị C3', 'lethic3@example.com', '0945678906', 3, 'Child');

-- Payment Details
INSERT INTO dbo.PaymentDetails (BookingId, Amount, PaymentMethod, PaymentStatus, TransactionCode, PaymentDate)
VALUES
    (1, 3000000, 'VNPay', 'Completed', 'TXN20260331001', '2026-03-31 10:35:00'),
    (2, 8100000, 'VNPay', 'Completed', 'TXN20260330001', '2026-03-30 14:20:00'),
    (3, 17280000, 'VNPay', 'Pending', 'TXN20260329001', NULL),
    (4, 3500000, 'CreditCard', 'Completed', 'TXN20260329002', '2026-03-29 09:10:00'),
    (5, 2200000, 'BankTransfer', 'PartialPaid', 'TXN20260328001', '2026-03-28 15:00:00');

-- Refund Requests
INSERT INTO dbo.RefundRequests (
    BookingId, RequestedByUserId, RequestedAt, CancelReason, CancelPolicySnapshot, RequestStatus,
    ReviewedByUserId, ReviewedAt, ReviewNote, RefundAmountExpected, RefundAmountActual,
    RefundMethod, RefundTransactionCode, RefundProcessedAt
)
VALUES
    (4, 7, '2026-03-30 08:00:00', 
     N'Lịch cá nhân thay đổi, không thể tham gia tour.',
     N'Hủy trước 7 ngày hoàn 100%, từ 4-7 ngày hoàn 60%',
     'Refunded', 2, '2026-03-30 09:00:00', N'Đủ điều kiện hoàn tiền theo chính sách',
     3500000, 3500000, 'CreditCard', 'RFD20260330001', '2026-03-30 10:00:00'),
    (5, 8, '2026-03-31 08:30:00',
     N'Cần dời lịch công tác đột xuất.',
     N'Hủy 7 ngày trước hoàn 100%, từ 4-7 ngày hoàn 70%',
     'Pending', NULL, NULL, NULL,
     2200000, NULL, NULL, NULL, NULL);

-- Wishlists
INSERT INTO dbo.Wishlists (UserId, TourId, Notes, AddedAt)
VALUES
    (4, 2, N'Muốn đi tour này với gia đình trong hè 2026', '2026-03-25 10:00:00'),
    (5, 9, N'Khám phá Bangkok sau', '2026-03-26 14:30:00'),
    (6, 10, N'Du lịch Angkor Wat - ước mơ lâu đời', '2026-03-27 09:15:00'),
    (7, 11, N'Bali - kỳ nghỉ dưỡng tuyệt vời', '2026-03-28 11:45:00'),
    (8, 5, N'Tour Mù Cang Chải mùa lúa chín', '2026-03-29 08:20:00');

-- Blog Posts
INSERT INTO dbo.BlogPosts (Title, Slug, Content, Excerpt, AuthorUserId, FeaturedImage, 
                          Category, Tags, IsPublished, PublishedAt)
VALUES
    (N'10 Điều Bạn Không Nên Bỏ Lỡ Khi Du Lịch Hạ Long',
     '10-dieu-khong-nen-bo-lo-khi-du-lich-ha-long',
     N'Hạ Long Bay là một trong những điểm du lịch nổi tiếng nhất Việt Nam. Dưới đây là 10 điều không nên bỏ lỡ khi tới Hạ Long...
     1. Đi du thuyền qua vịnh
     2. Tham quan hang động Sửng Sốt
     3. Đảo Titop
     4. Cảnh bình minh
     5. Ăn hải sản tươi sống
     6. Câu cá
     7. Lặn biển
     8. Thăm chợi nổi
     9. Thăm làng cư dân Cát Bà
     10. Chèo thuyền kayak',
     N'Khám phá 10 điểm đặc sắc không thể bỏ lỡ tại vịnh Hạ Long độc đáo',
     2, '/images/ha-long.jpg', 'Destination Guide', N'Hạ Long, Vietnam, Travel Tips', 1, '2026-03-20 10:00:00'),

    (N'Kinh Nghiệm Du Lịch Phú Quốc Từ A Đến Z',
     'kinh-nghiem-du-lich-phu-quoc-tu-a-den-z',
     N'Phú Quốc - Đảo Ngọc của Việt Nam là điểm đến hoàn hảo cho kỳ nghỉ hè. Bài viết này cung cấp tất cả thông tin bạn cần...
     Thời gian tốt nhất để đi: Tháng 10-11
     Cách di chuyển: Bay hoặc tàu
     Nơi ở: Resort 4-5 sao
     Hoạt động: Lặn biển, tắm nắng, ẩm thực
     Chi phí: 15-20 triệu đồng/người',
     N'Hướng dẫn chi tiết về du lịch Phú Quốc - Đảo Ngọc của Việt Nam',
     2, '/images/phu-quoc.jpg', 'Travel Story', N'Phú Quốc, Beach, Travel Guide', 1, '2026-03-18 14:30:00'),

    (N'Sapa - Thiên Đường Trekking Của Dãy Hoang Sơ Hoàng Liên',
     'sapa-thien-duong-trekking',
     N'Sapa nằm trên cao nguyên Tây Bắc với độ cao 1500m, khí hậu mát mẻ quanh năm. Du lịch trekking tại Sapa là trải nghiệm độc nhất...
     - Fansipan - nóc nhà Đông Dương 3143m
     - Trek qua rừng sâu
     - Gặp dân tộc H''Mông
     - Cắm trại dưới sao
     - Ăn đặc sản rừng',
     N'Khám phá thiên đường trekking tại Sapa cùng những dân tộc bản địa',
     3, '/images/sapa.jpg', 'Adventure', 'Sapa, Trekking, Mountain', 1, '2026-03-15 09:00:00');

-- Blog Comments
INSERT INTO dbo.BlogComments (PostId, UserId, CommentorName, CommentorEmail, Content, IsApproved, ApprovedByUserId)
VALUES
    (1, 4, N'Nguyễn Văn A', 'nguyenvana@example.com', 
     N'Bài viết rất hữu ích! Tôi đã đi Hạ Long và thực sự những điều này đều đúng. Cảnh bình minh nhất là tuyệt vời!',
     1, 2),
    
    (1, 5, N'Phạm Thị B', 'phamthib@example.com',
     N'Cảm ơn bạn đã chia sẻ! Mình định đi Hạ Long tháng 5 này, bài viết này giúp mình rất nhiều.',
     1, 2),
    
    (2, NULL, N'Lê Văn C', 'levanc@example.com',
     N'Phú Quốc thực sự là thiên đường! Tôi vừa về từ đó tuần trước.',
     0, NULL);

-- Review Ratings
INSERT INTO dbo.ReviewRatings (BookingId, TourId, UserId, Rating, ReviewTitle, ReviewContent, 
                              Pros, Cons, IsRecommended, IsApproved, VerifiedPurchase)
VALUES
    (1, 1, 4, 5, N'Trải nghiệm du thuyền tuyệt vời!',
     N'Tour Hạ Long 2 ngày là một trong những trải nghiệm tuyệt nhất. Khách sạn sạch sẽ, hướng dẫn viên rất tư tế.',
     N'Du thuyền sạch đẹp, Hướng dẫn viên chuyên nghiệp, Khung cảnh tuyệt đẹp, Ăn uống tốt',
     N'Hơi ồn ào vào buổi tối',
     1, 1, 1),
    
    (2, 2, 5, 4, N'Đáng tiền nhưng hơi mỏi',
     N'Tour Hạ Long 3 ngày khá tốt nhưng hơi mệt vì quãng đường dài. Hướng dẫn viên vui vẻ.',
     N'Phòng khách sạn thoải mái, Hang động đẹp, Ăn ngon',
     N'Quãng đường dài, Một số hoạt động hơi nhàm chán',
     1, 1, 1);

-- Contacts
INSERT INTO dbo.Contacts (Name, Email, PhoneNumber, Subject, Message, ContactType, Status, Priority)
VALUES
    (N'Trần Văn D', 'tranvand@example.com', '0967890123',
     N'Hỏi về khách sạn trong tour',
     N'Xin hỏi tour Hạ Long 3 ngày có khách sạn nào ở gần thành phố không? Tôi muốn có thêm thời gian khám phá.',
     'Inquiry', 'New', 'Medium'),
    
    (N'Hoàng Thị E', 'hoangthie@example.com', '0978901234',
     N'Khiếu nại về dịch vụ',
     N'Tour mà tôi tham gia tuần trước không đúng như trong mô tả. Hướng dẫn viên không chuyên nghiệp.',
     'Complaint', 'New', 'High'),
    
    (N'Dương Văn F', 'duongvanf@example.com', '0989012345',
     N'Đề xuất tuyến tour mới',
     N'Tôi muốn tham gia tour Hội An kéo dài 4-5 ngày với lịch trình tham quan chi tiết hơn.',
     'Feedback', 'New', 'Low');

GO

-- ============================================
-- 5. CREATE STORED PROCEDURES
-- ============================================

-- Procedure kiểm tra sức chứa và đặt tour
CREATE OR ALTER PROCEDURE dbo.sp_CheckAndBookTour
    @TourId INT,
    @NumberOfParticipants INT,
    @BookingId INT OUTPUT
AS
BEGIN
    BEGIN TRANSACTION
    BEGIN TRY
        DECLARE @AvailableSeats INT;
        
        SELECT @AvailableSeats = AvailableSeats 
        FROM dbo.Tours WITH (UPDLOCK)
        WHERE TourId = @TourId;
        
        IF @AvailableSeats IS NULL
        BEGIN
            SET @BookingId = -1; -- Tour không tồn tại
            ROLLBACK TRANSACTION;
            RETURN;
        END
        
        IF @AvailableSeats >= @NumberOfParticipants
        BEGIN
            UPDATE dbo.Tours 
            SET AvailableSeats = AvailableSeats - @NumberOfParticipants,
                Status = CASE 
                    WHEN AvailableSeats - @NumberOfParticipants = 0 THEN 'Full' 
                    ELSE 'Available' 
                END
            WHERE TourId = @TourId;
            
            SET @BookingId = 1; -- Success
        END
        ELSE
        BEGIN
            SET @BookingId = -2; -- Không đủ chỗ
        END
        
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        SET @BookingId = -3; -- Error
    END CATCH
END;
GO

-- Procedure hủy đơn và hoàn tiền
CREATE OR ALTER PROCEDURE dbo.sp_CancelBookingAndRefund
    @BookingId INT,
    @RefundReason NVARCHAR(500),
    @Result INT OUTPUT
AS
BEGIN
    BEGIN TRANSACTION
    BEGIN TRY
        DECLARE @TourId INT;
        DECLARE @NumberOfParticipants INT;
        DECLARE @PaymentId INT;
        DECLARE @BookingStatus NVARCHAR(50);
        
        -- Kiểm tra trạng thái đơn
        SELECT @TourId = TourId, @NumberOfParticipants = TotalParticipants, @BookingStatus = Status
        FROM dbo.Bookings 
        WHERE BookingId = @BookingId;
        
        IF @BookingStatus = 'Cancelled'
        BEGIN
            SET @Result = -1; -- Đã hủy trước đó
            ROLLBACK TRANSACTION;
            RETURN;
        END
        
        -- Cập nhật trạng thái đơn
        UPDATE dbo.Bookings 
        SET Status = 'Cancelled', 
            CancelledDate = GETDATE(),
            CancellationReason = @RefundReason
        WHERE BookingId = @BookingId;
        
        -- Trả lại chỗ trống
        UPDATE dbo.Tours 
        SET AvailableSeats = AvailableSeats + @NumberOfParticipants,
            Status = 'Available'
        WHERE TourId = @TourId;
        
        -- Cập nhật hoàn tiền
        SELECT @PaymentId = PaymentId FROM dbo.PaymentDetails WHERE BookingId = @BookingId;
        
        IF @PaymentId IS NOT NULL
        BEGIN
            UPDATE dbo.PaymentDetails 
            SET PaymentStatus = 'Refunded',
                RefundAmount = Amount,
                RefundDate = GETDATE(),
                RefundReason = @RefundReason
            WHERE PaymentId = @PaymentId;
        END
        
        SET @Result = 1; -- Success
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        SET @Result = -2; -- Error
    END CATCH
END;
GO

-- Procedure xác nhận đơn
CREATE OR ALTER PROCEDURE dbo.sp_ConfirmBooking
    @BookingId INT,
    @ApprovedByUserId INT,
    @Result INT OUTPUT
AS
BEGIN
    BEGIN TRY
        UPDATE dbo.Bookings 
        SET Status = 'Confirmed',
            ConfirmedDate = GETDATE(),
            ApprovedByUserId = @ApprovedByUserId,
            ApprovedDate = GETDATE()
        WHERE BookingId = @BookingId AND Status = 'Pending';
        
        IF @@ROWCOUNT > 0
            SET @Result = 1; -- Success
        ELSE
            SET @Result = 0; -- Không tìm thấy hoặc đã xác nhận
    END TRY
    BEGIN CATCH
        SET @Result = -1; -- Error
    END CATCH
END;
GO

-- Procedure tính chiết khấu coupon
CREATE OR ALTER PROCEDURE dbo.sp_ApplyCoupon
    @CouponCode NVARCHAR(50),
    @UserId INT,
    @TourId INT,
    @SubTotal DECIMAL(12,2),
    @DiscountAmount DECIMAL(12,2) OUTPUT,
    @FinalTotal DECIMAL(12,2) OUTPUT,
    @Result INT OUTPUT
AS
BEGIN
    BEGIN TRY
        DECLARE @CouponId INT;
        DECLARE @DiscountType NVARCHAR(20);
        DECLARE @DiscountValue DECIMAL(10,2);
        DECLARE @MaxDiscount DECIMAL(12,2);
        DECLARE @MinPurchase DECIMAL(12,2);
        DECLARE @UsageCount INT;
        DECLARE @UsageLimit INT;
        DECLARE @UsagePerCustomer INT;
        DECLARE @IsActive BIT;
        DECLARE @CurrentDate DATETIME = GETDATE();
        
        -- Kiểm tra coupon
        SELECT @CouponId = CouponId, @DiscountType = DiscountType, @DiscountValue = DiscountValue,
               @MaxDiscount = MaxDiscountAmount, @MinPurchase = MinPurchaseAmount,
               @UsageLimit = UsageLimit, @UsagePerCustomer = UsagePerCustomer, @IsActive = IsActive
        FROM dbo.Coupons
        WHERE CouponCode = @CouponCode 
          AND IsActive = 1
          AND @CurrentDate BETWEEN StartDate AND EndDate;
        
        IF @CouponId IS NULL
        BEGIN
            SET @Result = -1; -- Coupon không tồn tại hoặc hết hạn
            SET @DiscountAmount = 0;
            SET @FinalTotal = @SubTotal;
            RETURN;
        END
        
        -- Kiểm tra đơn tối thiểu
        IF @SubTotal < ISNULL(@MinPurchase, 0)
        BEGIN
            SET @Result = -2; -- Giá trị đơn hàng dưới yêu cầu
            SET @DiscountAmount = 0;
            SET @FinalTotal = @SubTotal;
            RETURN;
        END
        
        -- Kiểm tra số lần sử dụng
        SELECT @UsageCount = COUNT(*) FROM dbo.CouponUsage WHERE CouponId = @CouponId;
        
        IF @UsageLimit > 0 AND @UsageCount >= @UsageLimit
        BEGIN
            SET @Result = -3; -- Đã hết lượt sử dụng
            SET @DiscountAmount = 0;
            SET @FinalTotal = @SubTotal;
            RETURN;
        END
        
        -- Kiểm tra sử dụng per customer
        IF @UsagePerCustomer > 0
        BEGIN
            SELECT @UsageCount = COUNT(*) FROM dbo.CouponUsage 
            WHERE CouponId = @CouponId AND UserId = @UserId;
            
            IF @UsageCount >= @UsagePerCustomer
            BEGIN
                SET @Result = -4; -- Đã dùng hết coupon này
                SET @DiscountAmount = 0;
                SET @FinalTotal = @SubTotal;
                RETURN;
            END
        END
        
        -- Tính chiết khấu
        IF @DiscountType = 'Percentage'
        BEGIN
            SET @DiscountAmount = (@SubTotal * @DiscountValue) / 100;
            IF @MaxDiscount IS NOT NULL AND @DiscountAmount > @MaxDiscount
                SET @DiscountAmount = @MaxDiscount;
        END
        ELSE IF @DiscountType = 'Fixed'
        BEGIN
            SET @DiscountAmount = @DiscountValue;
        END
        
        SET @FinalTotal = @SubTotal - @DiscountAmount;
        SET @Result = 1; -- Success
    END TRY
    BEGIN CATCH
        SET @Result = -5; -- Error
        SET @DiscountAmount = 0;
        SET @FinalTotal = @SubTotal;
    END CATCH
END;
GO

-- Procedure thêm blog comment với duyệt
CREATE OR ALTER PROCEDURE dbo.sp_AddBlogComment
    @PostId INT,
    @UserId INT,
    @CommentorName NVARCHAR(150),
    @CommentorEmail NVARCHAR(150),
    @Content NVARCHAR(MAX),
    @CommentId INT OUTPUT
AS
BEGIN
    BEGIN TRY
        INSERT INTO dbo.BlogComments (PostId, UserId, CommentorName, CommentorEmail, Content, IsApproved)
        VALUES (@PostId, @UserId, @CommentorName, @CommentorEmail, @Content, 0);
        
        SET @CommentId = SCOPE_IDENTITY();
    END TRY
    BEGIN CATCH
        SET @CommentId = -1;
    END CATCH
END;
GO

-- Procedure thêm review
CREATE OR ALTER PROCEDURE dbo.sp_AddTourReview
    @BookingId INT,
    @TourId INT,
    @UserId INT,
    @Rating INT,
    @ReviewTitle NVARCHAR(255),
    @ReviewContent NVARCHAR(MAX),
    @Pros NVARCHAR(MAX),
    @Cons NVARCHAR(MAX),
    @IsRecommended BIT,
    @ReviewId INT OUTPUT
AS
BEGIN
    BEGIN TRY
        -- Kiểm tra tour đã hoàn thành hay thanh toán
        IF NOT EXISTS (SELECT 1 FROM dbo.Bookings WHERE BookingId = @BookingId AND UserId = @UserId AND PaymentStatus = 'Paid')
        BEGIN
            SET @ReviewId = -1; -- Chưa hoàn tất đơn hàng
            RETURN;
        END
        
        INSERT INTO dbo.ReviewRatings (BookingId, TourId, UserId, Rating, ReviewTitle, ReviewContent, 
                                       Pros, Cons, IsRecommended, VerifiedPurchase)
        VALUES (@BookingId, @TourId, @UserId, @Rating, @ReviewTitle, @ReviewContent, 
                @Pros, @Cons, @IsRecommended, 1);
        
        SET @ReviewId = SCOPE_IDENTITY();
    END TRY
    BEGIN CATCH
        SET @ReviewId = -2;
    END CATCH
END;
GO

-- Procedure gửi yêu cầu hủy tour/hoàn tiền
CREATE OR ALTER PROCEDURE dbo.sp_SubmitRefundRequest
    @BookingId INT,
    @RequestedByUserId INT,
    @CancelReason NVARCHAR(500),
    @RefundRequestId INT OUTPUT
AS
BEGIN
    BEGIN TRY
        DECLARE @Exists INT;
        DECLARE @CancellationPolicy NVARCHAR(MAX);
        DECLARE @ExpectedRefund DECIMAL(12,2);

        SELECT @Exists = COUNT(*)
        FROM dbo.Bookings
        WHERE BookingId = @BookingId AND UserId = @RequestedByUserId;

        IF @Exists = 0
        BEGIN
            SET @RefundRequestId = -1; -- Booking không thuộc user
            RETURN;
        END

        IF EXISTS (
            SELECT 1 FROM dbo.RefundRequests
            WHERE BookingId = @BookingId
              AND RequestStatus IN ('Pending', 'Approved', 'RefundInProgress')
        )
        BEGIN
            SET @RefundRequestId = -2; -- Đã có yêu cầu đang xử lý
            RETURN;
        END

        SELECT @CancellationPolicy = t.CancellationPolicy,
               @ExpectedRefund = b.TotalAmount
        FROM dbo.Bookings b
        INNER JOIN dbo.Tours t ON b.TourId = t.TourId
        WHERE b.BookingId = @BookingId;

        INSERT INTO dbo.RefundRequests (
            BookingId, RequestedByUserId, CancelReason, CancelPolicySnapshot,
            RequestStatus, RefundAmountExpected
        )
        VALUES (
            @BookingId, @RequestedByUserId, @CancelReason, @CancellationPolicy,
            'Pending', @ExpectedRefund
        );

        SET @RefundRequestId = SCOPE_IDENTITY();
    END TRY
    BEGIN CATCH
        SET @RefundRequestId = -3; -- Error
    END CATCH
END;
GO

-- Procedure staff/admin xử lý yêu cầu hủy/hoàn tiền
CREATE OR ALTER PROCEDURE dbo.sp_ProcessRefundRequest
    @RefundRequestId INT,
    @ReviewedByUserId INT,
    @Action NVARCHAR(20), -- 'Approve', 'Reject', 'MarkRefundInProgress', 'MarkRefunded', 'Close'
    @ReviewNote NVARCHAR(500) = NULL,
    @RefundAmountActual DECIMAL(12,2) = NULL,
    @RefundMethod NVARCHAR(50) = NULL,
    @RefundTransactionCode NVARCHAR(100) = NULL,
    @Result INT OUTPUT
AS
BEGIN
    BEGIN TRANSACTION
    BEGIN TRY
        DECLARE @BookingId INT;
        DECLARE @CurrentStatus NVARCHAR(50);

        SELECT @BookingId = BookingId, @CurrentStatus = RequestStatus
        FROM dbo.RefundRequests
        WHERE RefundRequestId = @RefundRequestId;

        IF @BookingId IS NULL
        BEGIN
            SET @Result = -1; -- Không tìm thấy request
            ROLLBACK TRANSACTION;
            RETURN;
        END

        IF @Action = 'Approve'
        BEGIN
            UPDATE dbo.RefundRequests
            SET RequestStatus = 'Approved',
                ReviewedByUserId = @ReviewedByUserId,
                ReviewedAt = GETDATE(),
                ReviewNote = @ReviewNote,
                UpdatedAt = GETDATE()
            WHERE RefundRequestId = @RefundRequestId;
        END
        ELSE IF @Action = 'Reject'
        BEGIN
            UPDATE dbo.RefundRequests
            SET RequestStatus = 'Rejected',
                ReviewedByUserId = @ReviewedByUserId,
                ReviewedAt = GETDATE(),
                ReviewNote = @ReviewNote,
                UpdatedAt = GETDATE()
            WHERE RefundRequestId = @RefundRequestId;
        END
        ELSE IF @Action = 'MarkRefundInProgress'
        BEGIN
            UPDATE dbo.RefundRequests
            SET RequestStatus = 'RefundInProgress',
                ReviewedByUserId = @ReviewedByUserId,
                ReviewedAt = GETDATE(),
                ReviewNote = @ReviewNote,
                UpdatedAt = GETDATE()
            WHERE RefundRequestId = @RefundRequestId;
        END
        ELSE IF @Action = 'MarkRefunded'
        BEGIN
            UPDATE dbo.RefundRequests
            SET RequestStatus = 'Refunded',
                ReviewedByUserId = @ReviewedByUserId,
                ReviewedAt = GETDATE(),
                ReviewNote = @ReviewNote,
                RefundAmountActual = ISNULL(@RefundAmountActual, RefundAmountExpected),
                RefundMethod = @RefundMethod,
                RefundTransactionCode = @RefundTransactionCode,
                RefundProcessedAt = GETDATE(),
                UpdatedAt = GETDATE()
            WHERE RefundRequestId = @RefundRequestId;

            UPDATE dbo.Bookings
            SET Status = 'Cancelled',
                PaymentStatus = CASE WHEN PaymentStatus = 'Paid' THEN 'Refunded' ELSE PaymentStatus END,
                CancelledDate = GETDATE(),
                CancellationReason = ISNULL(@ReviewNote, 'Cancelled with refund request workflow'),
                UpdatedAt = GETDATE()
            WHERE BookingId = @BookingId;
        END
        ELSE IF @Action = 'Close'
        BEGIN
            UPDATE dbo.RefundRequests
            SET RequestStatus = 'Closed',
                ReviewedByUserId = @ReviewedByUserId,
                ReviewedAt = GETDATE(),
                ReviewNote = @ReviewNote,
                UpdatedAt = GETDATE()
            WHERE RefundRequestId = @RefundRequestId;
        END
        ELSE
        BEGIN
            SET @Result = -2; -- Action không hợp lệ
            ROLLBACK TRANSACTION;
            RETURN;
        END

        SET @Result = 1;
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        SET @Result = -3; -- Error
    END CATCH
END;
GO

-- Procedure lấy dashboard thống kê
CREATE OR ALTER PROCEDURE dbo.sp_GetDashboardStats
    @StartDate DATETIME,
    @EndDate DATETIME
AS
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM dbo.Bookings WHERE BookingDate BETWEEN @StartDate AND @EndDate) AS TotalBookings,
        (SELECT SUM(TotalAmount) FROM dbo.Bookings WHERE BookingDate BETWEEN @StartDate AND @EndDate) AS TotalRevenue,
        (SELECT COUNT(DISTINCT UserId) FROM dbo.Bookings WHERE BookingDate BETWEEN @StartDate AND @EndDate) AS NewCustomers,
        (SELECT COUNT(*) FROM dbo.Bookings WHERE Status = 'Pending' AND BookingDate BETWEEN @StartDate AND @EndDate) AS PendingBookings,
        (SELECT COUNT(DISTINCT TourId) FROM dbo.Tours WHERE Status = 'Available') AS AvailableTours,
        (SELECT AVG(Rating) FROM dbo.ReviewRatings WHERE CreatedAt BETWEEN @StartDate AND @EndDate AND IsApproved = 1) AS AvgRating;
END;
GO

