<?php
declare(strict_types=1);

/**
 * Lịch trình mẫu cho tour id 1–8 (trùng nội dung sample_data / tour_itinerary_seed.sql).
 * Dùng để tự nạp khi cột itinerary còn trống (không ghi đè nếu admin đã nhập).
 *
 * @return array<int, list<array{title:string,body:string}>>
 */
function tour_itinerary_default_payloads(): array
{
    return [
        1 => [
            [
                'title' => 'Ngày 1 — Hà Nội → Lào Cai, nhận phòng Sapa',
                'body' => "Đón khách theo điểm hẹn tại Hà Nội. Xe giường nằm hoặc limousine về Lào Cai, lên Sapa. Nhận phòng khách sạn, nghỉ ngơi. Chiều tự do dạo thị trấn, chợ tối, thưởng thức đặc sản vùng cao.",
            ],
            [
                'title' => 'Ngày 2 — Fansipan và bản Cát Cát',
                'body' => "Sáng đi cáp treo Fansipan, chiêm ngưỡng biển mây và đỉnh núi. Trưa về nghỉ. Chiều thăm bản Cát Cát, thác nước, giao lưu văn nghệ, tìm hiểu văn hóa đồng bào vùng cao.",
            ],
            [
                'title' => 'Ngày 3 — Thung lũng Mường Hoa — về Hà Nội',
                'body' => "Tham quan thung lũng Mường Hoa tùy điều kiện thời tiết. Trưa trả phòng. Xe đưa về Hà Nội, kết thúc chương trình.",
            ],
        ],
        2 => [
            [
                'title' => 'Ngày 1 — Đại Nội và di tích Huế',
                'body' => "Đón tại Huế, tham quan Đại Nội, điện Thái Hòa, Tử Cấm Thành. Chiều viếng lăng Khải Định hoặc Minh Mạng. Tối tự do thưởng thức cơm hến, chè Huế.",
            ],
            [
                'title' => 'Ngày 2 — Sông Hương và tiễn đoàn',
                'body' => "Sáng du thuyền sông Hương, thăm chùa Thiên Mụ, nghe ca Huế trên thuyền. Trưa trả phòng, tiễn sân bay hoặc ga, kết thúc.",
            ],
        ],
        3 => [
            [
                'title' => 'Ngày 1 — Đến Phú Quốc, nhận phòng',
                'body' => "Đón sân bay Phú Quốc, về resort hoặc khách sạn nhận phòng. Chiều tắm biển Bãi Sao hoặc Bãi Dài, ngắm hoàng hôn.",
            ],
            [
                'title' => 'Ngày 2 — Nam đảo, lặn ngắm san hô',
                'body' => "Cano tham quan Hòn Móng Tay, Hòn Mây Rút, tắm biển, lặn ngắm san hô theo gói dịch vụ. Trưa ăn hải sản tại nhà bè. Chiều về nghỉ ngơi.",
            ],
            [
                'title' => 'Ngày 3 — Vinpearl Safari và chợ đêm',
                'body' => "Sáng tham quan Vinpearl Safari hoặc công viên giải trí tùy chọn. Chiều tự do mua sắm. Tối chợ đêm Dinh Cật, thưởng thức hải sản.",
            ],
            [
                'title' => 'Ngày 4 — Trả phòng, tiễn bay',
                'body' => "Sáng tự do tắm biển hoặc spa. Trưa trả phòng, đưa ra sân bay, kết thúc hành trình.",
            ],
        ],
        4 => [
            [
                'title' => 'Ngày 1 — Hà Nội — Hạ Long, lên du thuyền',
                'body' => "Đón Hà Nội, di chuyển Hạ Long. Lên du thuyền, ăn trưa trên tàu. Chiều tham quan hang Sửng Sốt, chèo kayak. Tối tiệc trên tàu, nghỉ đêm trên vịnh.",
            ],
            [
                'title' => 'Ngày 2 — Vịnh Hạ Long — về Hà Nội',
                'body' => "Sáng hoạt động nhẹ trên tàu, brunch. Thăm hang hoặc làng chài. Trưa trả phòng tàu, về Hà Nội, kết thúc chương trình.",
            ],
        ],
        5 => [
            [
                'title' => 'Ngày 1 — TP.HCM — Singapore',
                'body' => "Tập trung sân bay, bay Singapore. Đón về khách sạn, nhận phòng. Tối tự do Marina Bay, xem nhạc nước hoặc ẩm thực hawker center.",
            ],
            [
                'title' => 'Ngày 2 — Gardens by the Bay và Marina',
                'body' => "Sáng tham quan Gardens by the Bay: Flower Dome, Cloud Forest, Supertree Grove. Chiều Merlion Park, Esplanade. Tối Clarke Quay hoặc Orchard Road.",
            ],
            [
                'title' => 'Ngày 3 — Universal Studios Sentosa',
                'body' => "Cả ngày vui chơi Universal Studios Singapore: phim trường, tàu lượn, show diễn. Tối về khách sạn hoặc dạo Sentosa buổi tối.",
            ],
            [
                'title' => 'Ngày 4 — Mua sắm — về Việt Nam',
                'body' => "Sáng tự do Jewel Changi hoặc Bugis. Trưa trả phòng, ra sân bay Changi, bay về TP.HCM, kết thúc tour.",
            ],
        ],
        6 => [
            [
                'title' => 'Ngày 1 — Đà Lạt: Hồ Xuân Hương và chợ đêm',
                'body' => "Đón sân bay Liên Khương về trung tâm Đà Lạt, nhận phòng. Chiều dạo Hồ Xuân Hương, nhà thờ Con Gà. Tối chợ đêm, thử bánh tráng nướng, sữa đậu nành.",
            ],
            [
                'title' => 'Ngày 2 — Hồ Tuyền Lâm và đồi chè',
                'body' => "Sáng tham quan Thiền viện Trúc Lâm, cáp treo qua hồ Tuyền Lâm. Chiều đồi chè Cầu Đất, check-in view đồi thông. Tối BBQ hoặc lẩu gà lá é.",
            ],
            [
                'title' => 'Ngày 3 — Langbiang và tiễn sân bay',
                'body' => "Sáng xe lên đỉnh Langbiang ngắm toàn cảnh. Trưa trả phòng, mua đặc sản. Chiều tiễn sân bay Liên Khương, kết thúc.",
            ],
        ],
        7 => [
            [
                'title' => 'Ngày 1 — Đà Nẵng — Bà Nà Hills',
                'body' => "Đón Đà Nẵng, lên Bà Nà Hills bằng cáp treo. Tham quan Cầu Vàng, Làng Pháp, vườn hoa. Tối về khách sạn khu biển Mỹ Khê.",
            ],
            [
                'title' => 'Ngày 2 — Hội An cổ kính',
                'body' => "Sáng phố cổ Hội An: chùa Cầu, nhà cổ, làm gốm hoặc lồng đèn. Trưa cao lầu, cơm gà. Chiều thuyền sông Hoài, thả đèn hoa đăng.",
            ],
            [
                'title' => 'Ngày 3 — Tiễn đoàn',
                'body' => "Sáng tự do tắm biển hoặc mua sắm. Trưa trả phòng, tiễn sân bay Đà Nẵng, kết thúc.",
            ],
        ],
        8 => [
            [
                'title' => 'Ngày 1 — Quy Nhơn: Eo Gió và Ghềnh Ráng',
                'body' => "Đón ga hoặc sân bay Phù Cát, nhận phòng. Chiều thăm Eo Gió, Ghềnh Ráng Tiên Sa, khu tưởng niệm Hàn Mặc Tử. Tối hải sản chợ đêm.",
            ],
            [
                'title' => 'Ngày 2 — Kỳ Co và Hòn Khô',
                'body' => "Cano hoặc thuyền ra Kỳ Co, tắm biển, tham quan bãi đá. Trưa ăn tại bãi. Chiều Hòn Khô lặn ngắm san hô tùy thủy triều.",
            ],
            [
                'title' => 'Ngày 3 — Tháp Đôi và tiễn đoàn',
                'body' => "Sáng tham quan Tháp Đôi Chăm, có thể thêm bảo tàng Quang Trung. Trưa trả phòng, mua chả cá, bánh hỏi. Chiều tiễn sân bay hoặc ga, kết thúc.",
            ],
        ],
    ];
}
