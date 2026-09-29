<?php

namespace App\Services;

use App\Models\Perfume;
use App\Models\Product;

class FragranceProfileService
{
    /**
     * Lấy hồ sơ mùi hương độc bản cho từng sản phẩm nước hoa.
     */
    public static function getProfile($perfume): array
    {
        $name = $perfume->name ?? '';
        $brand = $perfume->brand ?? '';
        $gender = $perfume->gender ?? 'unisex';
        $concentration = $perfume->concentration ?? 'EDP';
        $id = $perfume->id ?? 1;

        $key = mb_strtolower($name . ' ' . $brand);

        // 1. Kiểm tra các dòng nước hoa biểu tượng cụ thể
        $customProfile = self::matchSpecificFragrance($key, $gender, $concentration);
        if ($customProfile !== null) {
            return $customProfile;
        }

        // 2. Thuật toán sinh tầng hương độc bản dựa trên Giới tính, Nồng độ và ID của chai
        return self::generateHarmonicProfile($id, $name, $brand, $gender, $concentration);
    }

    /**
     * Đối chiếu với thư viện các dòng nước hoa kinh điển
     */
    private static function matchSpecificFragrance(string $key, string $gender, string $concentration): ?array
    {
        // Dior Sauvage
        if (str_contains($key, 'sauvage')) {
            return [
                'family' => 'Hương Cam Chanh Thơm Nồng & Gỗ Hổ Phách (Aromatic Fougere & Amber)',
                'family_badge' => 'Thảo Mộc & Gỗ Cay',
                'badge_color' => '#1b4332',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'citrus',
                    'notes' => 'Cam Bergamot vùng Calabria tươi mát, Tiêu đen Tứ Xuyên cay sắc nét, Quả tiêu hồng dịu mát.',
                    'desc' => 'Mở màn bừng sáng, tràn đầy năng lượng tươi mát như làn gió đại ngàn khoáng đạt.',
                    'tags' => ['Cam Bergamot Calabria', 'Tiêu Tứ Xuyên', 'Hồng tiêu']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'leaf',
                    'notes' => 'Hoa oải hương Pháp (Lavender), Tiêu hồng, Cỏ hương bài (Vetiver), Phong lữ và Cây xô thơm.',
                    'desc' => 'Trái tim của chai nước hoa, tạo nên dấu ấn nam tính, phong trần và cực kỳ lôi cuốn.',
                    'tags' => ['Hoa oải hương Pháp', 'Cây phong lữ', 'Cỏ hương bài']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 12 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Nhựa Ambroxan quý giá, Gỗ tuyết tùng Virginia, Hương Labdanum ấm áp và Hổ phách.',
                    'desc' => 'Lưu lại trên da và quần áo sâu lắng, quyền lực, vững chãi và lưu hương bền bỉ cả ngày.',
                    'tags' => ['Nhựa Ambroxan', 'Gỗ tuyết tùng', 'Labdanum']
                ],
                'longevity' => ['text' => 'Rất lâu: 8 - 12 giờ', 'percent' => 88],
                'sillage' => ['text' => 'Xa: Trong bán kính 2 mét', 'percent' => 82],
                'season' => ['text' => 'Bốn mùa, Ngày & Đêm, Tiệc tùng & Hẹn hò', 'percent' => 95],
                'style' => ['text' => 'Nam tính, Phóng khoáng, Lôi cuốn, Đẳng cấp', 'percent' => 92],
                'highlight_notes' => ['Cam Bergamot Calabria', 'Tiêu Tứ Xuyên', 'Hoa Oải Hương', 'Ambroxan', 'Gỗ Tuyết Tùng'],
            ];
        }

        // Miss Dior Blooming Bouquet
        if (str_contains($key, 'blooming bouquet') || (str_contains($key, 'miss dior') && str_contains($key, 'blooming'))) {
            return [
                'family' => 'Hương Hoa Cỏ Mùa Xuân Tươi Mát (Fresh Floral)',
                'family_badge' => 'Hoa Cỏ Dịu Nhẹ',
                'badge_color' => '#c94d68',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'citrus',
                    'notes' => 'Quả quýt hồng Sicili mọng nước, Cam Bergamot thanh khiết và Quả mơ chín.',
                    'desc' => 'Mở đầu nhẹ nhàng, rạng rỡ và tươi tắn như khu vườn hoa sớm mai đọng sương.',
                    'tags' => ['Quýt hồng Sicili', 'Cam Bergamot', 'Quả mơ']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Hoa mẫu đơn Damascus kiêu sa, Tinh chất hoa hồng Damask, Quả đào ngọt lành.',
                    'desc' => 'Vẻ đẹp nữ tính kiều diễm, ôm ấp làn da bằng sự thanh tao thuần khiết.',
                    'tags' => ['Hoa mẫu đơn Damascus', 'Hoa hồng Damask', 'Quả đào']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 8 Giờ',
                    'icon' => 'sparkles',
                    'notes' => 'Xạ hương trắng tinh khôi (White Musk), Gỗ cẩm lai mềm mại và Vani nhẹ.',
                    'desc' => 'Dư âm vương vấn như làn sương mỏng, trong veo, trang nhã và đầy xao xuyến.',
                    'tags' => ['Xạ hương trắng', 'Gỗ cẩm lai', 'Vani']
                ],
                'longevity' => ['text' => 'Vừa phải: 6 - 8 giờ', 'percent' => 70],
                'sillage' => ['text' => 'Thoang thoảng: Trong vòng một cánh tay', 'percent' => 65],
                'season' => ['text' => 'Mùa Xuân / Hạ, Ban ngày, Hẹn hò lãng mạn', 'percent' => 90],
                'style' => ['text' => 'Nữ tính, Ngọt ngào, Dịu dàng, Thanh lịch', 'percent' => 94],
                'highlight_notes' => ['Hoa Mẫu Đơn', 'Hoa Hồng Damask', 'Xạ Hương Trắng', 'Quýt Hồng', 'Đào'],
            ];
        }

        // Chanel Chance Eau Tendre
        if (str_contains($key, 'eau tendre') || str_contains($key, 'chance')) {
            return [
                'family' => 'Hương Hoa Cỏ & Trái Cây Quý Phái (Floral Fruity Tender)',
                'family_badge' => 'Hoa Cỏ & Trái Cây',
                'badge_color' => '#d4738c',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả bưởi hồng tươi mát rạng rỡ, Quả mộc qua (Quince) thanh lịch độc đáo.',
                    'desc' => 'Khởi đầu xanh mát, ngọt lành và tràn ngập sinh khí tích cực.',
                    'tags' => ['Quả bưởi hồng', 'Quả mộc qua', 'Cam chanh tươi']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Hoa nhài Sambac trắng muốt, Tinh chất hoa hồng tháng 5 Pháp, Hoa dạ lan hương.',
                    'desc' => 'Bản hòa ca hoa trắng quý tộc, lan tỏa nét duyên dáng thanh tao không thể chối từ.',
                    'tags' => ['Hoa nhài Sambac', 'Hoa hồng Pháp', 'Dạ lan hương']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 10 Giờ',
                    'icon' => 'bulb',
                    'notes' => 'Xạ hương trắng cao cấp, Hổ phách vàng ấm mịn màng, Gỗ tuyết tùng Virginia.',
                    'desc' => 'Đọng lại lớp nền sang trọng, lưu luyến sâu lắng và ấm áp trên cơ thể.',
                    'tags' => ['Xạ hương trắng', 'Hổ phách', 'Gỗ tuyết tùng']
                ],
                'longevity' => ['text' => 'Lâu: 8 - 10 giờ', 'percent' => 82],
                'sillage' => ['text' => 'Tinh tế: Khoảng 1 - 1.5 mét', 'percent' => 75],
                'season' => ['text' => 'Quanh năm, Ban ngày, Công sở, Tiệc trà nhẹ', 'percent' => 92],
                'style' => ['text' => 'Quý phái, Tinh khôi, Thanh lịch, Duyên dáng', 'percent' => 95],
                'highlight_notes' => ['Bưởi Hồng', 'Hoa Nhài Sambac', 'Xạ Hương Trắng', 'Mộc Qua', 'Hổ Phách'],
            ];
        }

        // Parfums de Marly Delina Exclusif
        if (str_contains($key, 'delina')) {
            return [
                'family' => 'Hương Hoa Cỏ Hoàng Gia & Trầm Hương Quý Tộc (Oriental Floral Royalty)',
                'family_badge' => 'Hoa Hồng & Trầm Hương',
                'badge_color' => '#9333ea',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả vải Litchi mọng nước, Quả lê tươi giòn, Cam Bergamot hoàng tộc.',
                    'desc' => 'Bùng nổ vị trái cây thượng hạng, kiêu sa và khiến người đối diện ngỡ ngàng.',
                    'tags' => ['Quả vải Litchi', 'Quả lê tươi', 'Cam Bergamot']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Hoa hồng Thổ Nhĩ Kỳ quý giá, Gỗ trầm hương (Oud) khói nhẹ, Trầm hương Incense bí ẩn.',
                    'desc' => 'Trái tim kiêu hãnh của nàng công chúa châu Âu, quyến rũ ma mị đầy quyền lực.',
                    'tags' => ['Hoa hồng Thổ Nhĩ Kỳ', 'Gỗ trầm hương Oud', 'Trầm hương Incense']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 14 Giờ',
                    'icon' => 'crown',
                    'notes' => 'Vanilla Madagascar béo mịn, Hổ phách hoàng gia (Amber), Gỗ đàn hương ấm nồng.',
                    'desc' => 'Lớp nền xa xỉ bám tỏa mãnh liệt, tạo nên thần thái quý tộc không thể nhầm lẫn.',
                    'tags' => ['Vanilla Madagascar', 'Hổ phách hoàng gia', 'Gỗ đàn hương']
                ],
                'longevity' => ['text' => 'Cực lâu: Trên 12 giờ', 'percent' => 96],
                'sillage' => ['text' => 'Rất xa: Bán kính 2 mét', 'percent' => 90],
                'season' => ['text' => 'Thu / Đông, Buổi tối, Dạ tiệc sang trọng bậc nhất', 'percent' => 96],
                'style' => ['text' => 'Xa hoa, Quyền lực, Quyến rũ tột cùng, Độc bản', 'percent' => 98],
                'highlight_notes' => ['Quả Vải Litchi', 'Hoa Hồng Thổ Nhĩ Kỳ', 'Trầm Hương Oud', 'Vanilla', 'Hổ Phách'],
            ];
        }

        // Tom Ford Rose Prick
        if (str_contains($key, 'rose prick')) {
            return [
                'family' => 'Hương Hoa Hồng Gai Góc & Gia Vị Cay Nồng (Chypre Floral Spicy)',
                'family_badge' => 'Hoa Hồng Gai Góc',
                'badge_color' => '#be185d',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'flame',
                    'notes' => 'Tiêu đen Tứ Xuyên cay tê, Củ nghệ vàng thơm ấm nồng, Cam chanh Calabria.',
                    'desc' => 'Cảm giác gai góc, cay nhẹ bùng nổ kích thích mọi giác quan ngay phút ban đầu.',
                    'tags' => ['Tiêu Tứ Xuyên', 'Củ nghệ vàng', 'Cam chanh']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Bộ ba hoa hồng danh giá: Hoa hồng tháng Năm (May Rose), Hoa hồng Bungari, Hoa hồng Thổ Nhĩ Kỳ.',
                    'desc' => 'Bó hồng gai quyến rũ chết người, vừa lãng mạn vừa sắc sảo ma mị.',
                    'tags' => ['Hoa hồng May Rose', 'Hoa hồng Bungari', 'Hoa hồng Thổ Nhĩ Kỳ']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 12 Giờ',
                    'icon' => 'candy',
                    'notes' => 'Cây hoắc hương hoang dã Indonesia, Đậu Tonka nướng béo ngậy, Hổ phách quý.',
                    'desc' => 'Lưu lại dải hương bí ẩn, nồng nàn sang chảnh và đọng lại dấu ấn khó phai.',
                    'tags' => ['Hoắc hương Indonesia', 'Đậu Tonka', 'Hổ phách']
                ],
                'longevity' => ['text' => 'Rất lâu: 8 - 12 giờ', 'percent' => 86],
                'sillage' => ['text' => 'Tốt: Trong bán kính 1.5 mét', 'percent' => 80],
                'season' => ['text' => 'Thu / Đông, Ban đêm, Hẹn hò lãng mạn bí mật', 'percent' => 93],
                'style' => ['text' => 'Độc đáo, Cá tính, Sang chảnh, Ma mị', 'percent' => 94],
                'highlight_notes' => ['Hoa Hồng Bungari', 'Tiêu Tứ Xuyên', 'Hoa Hồng May', 'Hoắc Hương', 'Đậu Tonka'],
            ];
        }

        // Narciso Rodriguez Musc Noir Rose
        if (str_contains($key, 'musc noir') || str_contains($key, 'narciso')) {
            return [
                'family' => 'Hương Xạ Hương Đêm & Hổ Phách Hoa Cỏ (Amber Floral Musky)',
                'family_badge' => 'Xạ Hương Quyến Rũ',
                'badge_color' => '#831843',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả mận chín mọng nước (Plum), Cam Bergamot tươi mát, Hạt tiêu hồng.',
                    'desc' => 'Ngọt ngào êm ái, gợi mở sự tò mò với nốt mận chín mê đắm lòng người.',
                    'tags' => ['Mận chín mọng', 'Cam Bergamot', 'Tiêu hồng']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Trái tim xạ hương đặc trưng Narciso Musc, Hoa huệ trắng nồng nàn, Cánh hoa hồng đêm.',
                    'desc' => 'Đỉnh cao gợi cảm xác thịt, cuốn hút như hơi thở ấm áp sát bên tai.',
                    'tags' => ['Xạ hương Narciso', 'Hoa huệ trắng', 'Hoa hồng đêm']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 10 Giờ',
                    'icon' => 'moon',
                    'notes' => 'Vanilla Madagascar ngọt ngào, Da lộn Suede cao cấp, Cây hoắc hương ấm.',
                    'desc' => 'Hương thơm mịn như nhung bám chặt trên da, lưu giữ nét quyến rũ bí ẩn khó cưỡng.',
                    'tags' => ['Vanilla Madagascar', 'Da lộn Suede', 'Hoắc hương']
                ],
                'longevity' => ['text' => 'Lâu: 8 - 10 giờ', 'percent' => 84],
                'sillage' => ['text' => 'Gần gũi: Thoang thoảng gợi cảm (1 mét)', 'percent' => 72],
                'season' => ['text' => 'Mùa Thu / Đông / Mát mẻ, Ban đêm, Hẹn hò thân mật', 'percent' => 95],
                'style' => ['text' => 'Gợi cảm tột bậc, Bí ẩn, Nữ tính, Tinh tế', 'percent' => 96],
                'highlight_notes' => ['Mận Chín', 'Xạ Hương Narciso', 'Hoa Huệ Trắng', 'Vanilla', 'Da Lộn'],
            ];
        }

        // Stronger With You Intensely (Emporio Armani)
        if (str_contains($key, 'stronger with you')) {
            return [
                'family' => 'Hương Hổ Phách Ấm & Kẹo Bơ Ngọt Ngào (Amber Fougere Warm Spicy)',
                'family_badge' => 'Hổ Phách & Kẹo Bơ',
                'badge_color' => '#b45309',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'leaf',
                    'notes' => 'Hồng tiêu cay nồng, Quả bách xù xanh biếc, Lá hoa violet thanh tao.',
                    'desc' => 'Ấm áp, sắc nét và nồng nhiệt ngay từ giây đầu tiên tiếp xúc.',
                    'tags' => ['Hồng tiêu cay', 'Quả bách xù', 'Lá hoa violet']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'candy',
                    'notes' => 'Kẹo bơ cứng Toffee ngọt ngào, Quế ấm áp nồng nàn, Hoa oải hương và Xô thơm.',
                    'desc' => 'Mùi hương ngọt béo ấm sực như một cái ôm siết chặt giữa ngày đông buốt giá.',
                    'tags' => ['Kẹo bơ Toffee', 'Quế ấm nồng', 'Hoa oải hương']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 14 Giờ',
                    'icon' => 'flame',
                    'notes' => 'Vani Madagascar, Đậu Tonka nướng béo ngậy, Hổ phách vàng và Da lộn lôi cuốn.',
                    'desc' => 'Vương vấn nồng nàn cực lâu, tỏa hương xa và ghi dấu ấn nam thần ấm áp.',
                    'tags' => ['Vani Madagascar', 'Đậu Tonka', 'Hổ phách vàng']
                ],
                'longevity' => ['text' => 'Rất lâu: 10 - 14 giờ', 'percent' => 92],
                'sillage' => ['text' => 'Rất xa: Bán kính 2 mét', 'percent' => 88],
                'season' => ['text' => 'Mùa Đông / Thu lạnh, Dạ tiệc tối, Hẹn hò nồng say', 'percent' => 96],
                'style' => ['text' => 'Nồng nàn, Ấm áp, Quyến rũ, Đốn tim phái đẹp', 'percent' => 95],
                'highlight_notes' => ['Kẹo Bơ Toffee', 'Quế Ấm', 'Đậu Tonka', 'Vani', 'Hồng Tiêu'],
            ];
        }

        // In Love With You (Emporio Armani)
        if (str_contains($key, 'in love with you')) {
            return [
                'family' => 'Hương Trái Cây Mọng & Mật Ngọt Tình Yêu (Fruity Floral Gourmand)',
                'family_badge' => 'Anh Đào & Hoa Nhài',
                'badge_color' => '#db2777',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả anh đào đen (Black Cherry) mọng mật, Quả mâm xôi chín đỏ, Quả lý chua đen.',
                    'desc' => 'Vị ngọt mọng nước chua ngọt đan xen, tựa nụ hôn đầu ngây ngất và rực rỡ.',
                    'tags' => ['Anh đào đen', 'Quả mâm xôi', 'Lý chua đen']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'flower',
                    'notes' => 'Hoa nhài Sambac kiêu sa, Hoa hồng Damask, Cây ngải tây thơm nhẹ.',
                    'desc' => 'Giai điệu hoa cỏ dịu ngọt, thể hiện tình yêu cuồng nhiệt và lãng mạn.',
                    'tags' => ['Hoa nhài Sambac', 'Hoa hồng Damask', 'Ngải tây']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 10 Giờ',
                    'icon' => 'candy',
                    'notes' => 'Hoắc hương tinh tế, Hương Vani béo ngậy, Hổ phách mịn màng.',
                    'desc' => 'Đọng lại cảm giác ngọt ngào ấm áp, vương vấn bền bỉ trên tóc và làn da.',
                    'tags' => ['Hoắc hương', 'Vani ngọt ngào', 'Hổ phách']
                ],
                'longevity' => ['text' => 'Lâu: 7 - 9 giờ', 'percent' => 80],
                'sillage' => ['text' => 'Vừa phải: Khoảng 1 - 1.5 mét', 'percent' => 74],
                'season' => ['text' => 'Mùa Thu / Đông, Hẹn hò lãng mạn, Tiệc nhẹ', 'percent' => 90],
                'style' => ['text' => 'Lãng mạn, Ngọt ngào, Say đắm, Trẻ trung', 'percent' => 92],
                'highlight_notes' => ['Anh Đào Đen', 'Mâm Xôi', 'Hoa Nhài Sambac', 'Vani', 'Hoắc Hương'],
            ];
        }

        // Aqua Allegoria Pera Granita (Guerlain)
        if (str_contains($key, 'pera granita') || (str_contains($key, 'aqua allegoria') && str_contains($key, 'guerlain'))) {
            return [
                'family' => 'Hương Món Đá Bào Lê Tuyết & Cam Chanh Mát Lạnh (Fruity Citrus Aromatic)',
                'family_badge' => 'Lê Tuyết & Bưởi Chùm',
                'badge_color' => '#059669',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả bưởi chùm tươi mọng, Chanh vàng Ý giòn tan, Cam Bergamot tươi mát.',
                    'desc' => 'Giải nhiệt mùa hè tức thì như ly đá bào trái cây mát lạnh sảng khoái.',
                    'tags' => ['Quả bưởi chùm', 'Chanh vàng Ý', 'Cam Bergamot']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'candy',
                    'notes' => 'Quả lê tuyết Granita thanh khiết, Hoa mộc tê (Osmanthus), Hoa cam tao nhã.',
                    'desc' => 'Hương lê giòn tan hòa quyện cùng hoa mộc thơm ngát, tự nhiên và trong trẻo.',
                    'tags' => ['Quả lê tuyết', 'Hoa mộc tê Osmanthus', 'Hoa cam']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 7 Giờ',
                    'icon' => 'leaf',
                    'notes' => 'Rêu sồi thanh sạch, Xạ hương trắng tinh khôi, Gỗ tuyết tùng dịu nhẹ.',
                    'desc' => 'Lắng đọng êm dịu, sạch sẽ và tạo cảm giác thư giãn thảnh thơi cả ngày dài.',
                    'tags' => ['Rêu sồi', 'Xạ hương trắng', 'Gỗ tuyết tùng']
                ],
                'longevity' => ['text' => 'Tươi mát: 5 - 7 giờ', 'percent' => 65],
                'sillage' => ['text' => 'Thoang thoảng: Trong vòng 1 mét', 'percent' => 65],
                'season' => ['text' => 'Mùa Hè / Xuân oi bức, Ban ngày, Du lịch, Dạo phố', 'percent' => 96],
                'style' => ['text' => 'Tươi mát, Sảng khoái, Tự nhiên, Tràn đầy năng lượng', 'percent' => 95],
                'highlight_notes' => ['Lê Tuyết Granita', 'Bưởi Chùm', 'Hoa Mộc Tê', 'Chanh Ý', 'Xạ Hương Trắng'],
            ];
        }

        // Royal Oud (Maison Privée)
        if (str_contains($key, 'royal oud') || str_contains($key, 'oud 1770')) {
            return [
                'family' => 'Hương Gỗ Trầm Hương Hoàng Đế & Gia Vị Quý (Woody Oriental Oud)',
                'family_badge' => 'Trầm Hương Hoàng Gia',
                'badge_color' => '#78350f',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'tree',
                    'notes' => 'Chanh vàng Amalfi, Tiêu hồng cay nồng nàn, Cam hương Bergamot quý tộc.',
                    'desc' => 'Trầm ấm và uy nghi ngay từ những nốt đầu, gợi mở cung điện hoàng gia cổ kính.',
                    'tags' => ['Chanh Amalfi', 'Tiêu hồng cay', 'Cam Bergamot']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Gỗ tuyết tùng vùng Atlas, Cây bạch chỉ Angelica, Nhựa thơm Galbanum.',
                    'desc' => 'Tầng gỗ sâu thẳm, mạnh mẽ và tạo nên bản lĩnh của bậc vương giả thống trị.',
                    'tags' => ['Gỗ tuyết tùng Atlas', 'Bạch chỉ Angelica', 'Nhựa Galbanum']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 16 Giờ',
                    'icon' => 'crown',
                    'notes' => 'Trầm hương Ấn Độ (Oud) quý hiếm, Gỗ đàn hương Mysore, Xạ hương hoàng gia.',
                    'desc' => 'Lưu hương bất tử với thời gian, mùi hương của quyền lực, tiền tài và địa vị tối thượng.',
                    'tags' => ['Trầm hương Ấn Độ Oud', 'Gỗ đàn hương Mysore', 'Xạ hương']
                ],
                'longevity' => ['text' => 'Cực đỉnh: 12 - 16 giờ', 'percent' => 95],
                'sillage' => ['text' => 'Xa và quyền uy: Bán kính 2 mét', 'percent' => 88],
                'season' => ['text' => 'Thu / Đông, Sự kiện thượng lưu, Gặp gỡ đối tác lớn', 'percent' => 95],
                'style' => ['text' => 'Vương giả, Uy nghiêm, Trầm tĩnh, Quyền lực', 'percent' => 98],
                'highlight_notes' => ['Trầm Hương Oud', 'Gỗ Tuyết Tùng Atlas', 'Gỗ Đàn Hương', 'Tiêu Hồng', 'Bạch Chỉ'],
            ];
        }

        // Cristiano Ronaldo CR7
        if (str_contains($key, 'cristiano ronaldo') || str_contains($key, 'cr7')) {
            return [
                'family' => 'Hương Thảo Mộc Thể Thao & Gỗ Cay Nam Tính (Aromatic Fougere Sport)',
                'family_badge' => 'Thể Thao & Mạnh Mẽ',
                'badge_color' => '#dc2626',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'bolt',
                    'notes' => 'Cam Bergamot, Bạch đậu khấu, Hoa oải hương xanh mát, Cây ngải cứu thảo mộc.',
                    'desc' => 'Bùng nổ sảng khoái tức thì, truyền cảm hứng tự tin và tinh thần chiến binh bứt phá.',
                    'tags' => ['Cam Bergamot', 'Bạch đậu khấu', 'Hoa oải hương']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'bolt',
                    'notes' => 'Vỏ quế nồng ấm, Cây diên vĩ thơm mịn, Gỗ tuyết tùng, Hương thuốc lá nhẹ.',
                    'desc' => 'Trái tim mạnh mẽ, rắn rỏi và cuốn hút mọi ánh nhìn trên từng bước di chuyển.',
                    'tags' => ['Vỏ quế ấm', 'Cây diên vĩ', 'Gỗ tuyết tùng']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 8 Giờ',
                    'icon' => 'trophy',
                    'notes' => 'Vanilla ngọt dịu, Xạ hương thể thao sảng khoái, Gỗ đàn hương ấm, Hổ phách.',
                    'desc' => 'Lưu giữ vẻ điển trai, phong trần và đầy sinh lực suốt cả ngày vận động.',
                    'tags' => ['Vanilla', 'Xạ hương thể thao', 'Gỗ đàn hương']
                ],
                'longevity' => ['text' => 'Tốt: 6 - 8 giờ', 'percent' => 75],
                'sillage' => ['text' => 'Năng động: Khoảng 1.5 mét', 'percent' => 78],
                'season' => ['text' => 'Bốn mùa, Ban ngày, Thể thao, Đi làm & Đi chơi', 'percent' => 90],
                'style' => ['text' => 'Khỏe khoắn, Tự tin, Tràn đầy nhiệt huyết, Năng động', 'percent' => 92],
                'highlight_notes' => ['Bạch Đậu Khấu', 'Hoa Oải Hương', 'Vỏ Quế', 'Gỗ Tuyết Tùng', 'Xạ Hương'],
            ];
        }

        // Bleu de Chanel
        if (str_contains($key, 'bleu')) {
            return [
                'family' => 'Hương Gỗ Thơm Sang Trọng & Bạc Hà Tươi Mát (Woody Aromatic Luxury)',
                'family_badge' => 'Biển Sâu & Gỗ Quyền Lực',
                'badge_color' => '#1e3a8a',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'citrus',
                    'notes' => 'Quả chanh vàng, Bạc hà lạnh băng, Quả bưởi chùm, Hồng tiêu cay nhẹ.',
                    'desc' => 'Tươi mát đỉnh cao, mang hơi thở tự do phóng khoáng của bầu trời và biển cả.',
                    'tags' => ['Bạc hà lạnh', 'Bưởi chùm', 'Chanh vàng', 'Hồng tiêu']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Gừng nồng ấm, Nhục đậu khấu, Hoa nhài thanh tao, Phân tử hương Iso E Super.',
                    'desc' => 'Nét cuốn hút trầm ổn, bí ẩn và lịch thiệp của người đàn ông thành đạt.',
                    'tags' => ['Gừng cay ấm', 'Nhục đậu khấu', 'Hoa nhài']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 12 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Hương trầm (Incense) khói thiêng, Cỏ hương bài Vetiver, Gỗ tuyết tùng, Gỗ đàn hương.',
                    'desc' => 'Dư âm xa xỉ đọng lại dài lâu, khẳng định vị thế đỉnh cao của quý ông Chanel.',
                    'tags' => ['Hương trầm Incense', 'Gỗ tuyết tùng', 'Cỏ hương bài']
                ],
                'longevity' => ['text' => 'Rất lâu: 8 - 12 giờ', 'percent' => 88],
                'sillage' => ['text' => 'Xa: Trong bán kính 2 mét', 'percent' => 82],
                'season' => ['text' => 'Bốn mùa, Ngày & Đêm, Mọi hoàn cảnh cao cấp', 'percent' => 96],
                'style' => ['text' => 'Lịch lãm, Bản lĩnh, Bí ẩn, Đẳng cấp quý ông', 'percent' => 96],
                'highlight_notes' => ['Bạc Hà', 'Bưởi Chùm', 'Hương Trầm', 'Gỗ Đàn Hương', 'Gừng Cay'],
            ];
        }

        // Versace Eros
        if (str_contains($key, 'eros')) {
            return [
                'family' => 'Hương Phương Đông Nồng Nhiệt & Bạc Hà Táo Xanh (Aromatic Oriental Spicy)',
                'family_badge' => 'Vị Thần Tình Yêu',
                'badge_color' => '#0891b2',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Lá bạc hà tươi lạnh, Táo xanh giòn ngọt, Vỏ chanh vàng nước Ý.',
                    'desc' => 'Bùng nổ sảng khoái và kích thích mãnh liệt như tiếng gọi tình yêu của thần Eros.',
                    'tags' => ['Lá bạc hà', 'Táo xanh giòn', 'Chanh vàng Ý']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'leaf',
                    'notes' => 'Đậu Tonka Venezuela, Hoa phong lữ thảo mộc, Phân tử Ambroxan quyến rũ.',
                    'desc' => 'Hơi thở nồng nàn say đắm, tạo nên sức hút nam tính táo bạo thiêu đốt ánh nhìn.',
                    'tags' => ['Đậu Tonka', 'Ambroxan', 'Hoa phong lữ']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 12 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Vani Madagascar ngọt béo, Cỏ Vetiver, Rêu sồi, Gỗ tuyết tùng Virginia & Atlas.',
                    'desc' => 'Lớp hương ngọt ngào quyền năng bám tỏa cực khủng, làm chủ mọi bữa tiệc đêm.',
                    'tags' => ['Vani Madagascar', 'Gỗ tuyết tùng', 'Rêu sồi']
                ],
                'longevity' => ['text' => 'Rất lâu: 8 - 12 giờ', 'percent' => 88],
                'sillage' => ['text' => 'Rất xa: Bán kính 2 mét', 'percent' => 88],
                'season' => ['text' => 'Mùa Thu / Đông, Dạ tiệc đêm, Bar / Club, Hẹn hò', 'percent' => 96],
                'style' => ['text' => 'Táo bạo, Nồng nhiệt, Sát thủ tình trường, Cuốn hút', 'percent' => 95],
                'highlight_notes' => ['Bạc Hà Lạnh', 'Táo Xanh', 'Đậu Tonka', 'Vani Madagascar', 'Ambroxan'],
            ];
        }

        // Creed Aventus
        if (str_contains($key, 'aventus')) {
            return [
                'family' => 'Hương Dứa Khói Hoàng Đế & Rêu Sồi Vương Giả (Fruity Chypre Creed)',
                'family_badge' => 'Dứa Khói Vương Giả',
                'badge_color' => '#18181b',
                'top' => [
                    'title' => 'Hương Đầu (Top Notes)',
                    'time' => 'Tầng 1 · 0 - 15 Phút',
                    'icon' => 'fruit',
                    'notes' => 'Quả dứa (thơm) tươi mọng nước, Táo xanh, Quả lý chua đen, Cam Bergamot.',
                    'desc' => 'Dứa tươi mọng nước vương giả, mở màn rực rỡ tượng trưng cho chiến thắng vinh quang.',
                    'tags' => ['Quả dứa tươi', 'Táo xanh', 'Lý chua đen', 'Bergamot']
                ],
                'heart' => [
                    'title' => 'Hương Giữa (Heart Notes)',
                    'time' => 'Tầng 2 · 15 Phút - 4 Giờ',
                    'icon' => 'tree',
                    'notes' => 'Gỗ bạch dương hun khói (Birch), Cây hoắc hương, Hoa hồng Ma-rốc, Hoa nhài.',
                    'desc' => 'Dải khói bạch dương huyền thoại, tạo nên phong thái vĩ nhân lãnh tụ bất khả chiến bại.',
                    'tags' => ['Gỗ bạch dương hun khói', 'Hoắc hương', 'Hoa hồng Ma-rốc']
                ],
                'base' => [
                    'title' => 'Hương Cuối (Base Notes)',
                    'time' => 'Tầng 3 · 4 - 14 Giờ',
                    'icon' => 'crown',
                    'notes' => 'Long diên hương (Ambergris) đại dương quý giá, Xạ hương, Rêu sồi, Vani nhẹ.',
                    'desc' => 'Đọng lại cốt cách tối thượng, bền bỉ đến kinh ngạc trên áo và da thịt.',
                    'tags' => ['Long diên hương Ambergris', 'Xạ hương', 'Rêu sồi']
                ],
                'longevity' => ['text' => 'Đỉnh cao: 10 - 14 giờ', 'percent' => 94],
                'sillage' => ['text' => 'Xa: Bán kính 2 mét', 'percent' => 86],
                'season' => ['text' => 'Bốn mùa, Ngày & Đêm, Gặp đối tác, Sự kiện danh giá', 'percent' => 98],
                'style' => ['text' => 'Vương giả, Thành đạt, Mạnh mẽ, Quyền lực đỉnh cao', 'percent' => 98],
                'highlight_notes' => ['Quả Dứa Tươi', 'Gỗ Bạch Dương Khói', 'Long Diên Hương', 'Xạ Hương', 'Rêu Sồi'],
            ];
        }

        return null;
    }

    /**
     * Thuật toán sinh tầng hương độc bản hài hòa cho bất kỳ chai nước hoa nào khác
     */
    private static function generateHarmonicProfile(int $id, string $name, string $brand, string $gender, string $concentration): array
    {
        // Thư viện các nốt hương tuyển chọn theo giới tính
        $topLibraries = [
            'nam' => [
                ['icon' => 'citrus', 'notes' => 'Cam Bergamot Calabria, Quả bưởi chùm tươi mát và Tiêu đen cay nhẹ.', 'desc' => 'Cảm giác tươi mát, bừng sáng và sảng khoái tức thì.', 'tags' => ['Cam Bergamot', 'Bưởi chùm', 'Tiêu đen']],
                ['icon' => 'leaf', 'notes' => 'Lá bạc hà băng tuyết, Quả táo xanh giòn ngọt và Chanh vàng Ý.', 'desc' => 'Cơn lốc tươi lạnh tràn trề năng lượng và phóng khoáng.', 'tags' => ['Bạc hà tuyết', 'Táo xanh', 'Chanh vàng Ý']],
                ['icon' => 'citrus', 'notes' => 'Cam đỏ Sicili, Bạch đậu khấu thơm nồng và Hồng tiêu cay ngọt.', 'desc' => 'Mở màn nồng nhiệt, ấm áp và khơi dậy sự tò mò mạnh mẽ.', 'tags' => ['Cam đỏ Sicili', 'Bạch đậu khấu', 'Hồng tiêu']],
                ['icon' => 'fruit', 'notes' => 'Quả dứa chín mọng nước, Táo xanh giòn tan và Cam Bergamot thanh.', 'desc' => 'Tươi mát quý tộc, tự tin và tràn đầy sinh khí chiến thắng.', 'tags' => ['Quả dứa mọng', 'Táo xanh', 'Cam Bergamot']],
            ],
            'nu' => [
                ['icon' => 'flower', 'notes' => 'Quả quýt hồng thơm ngọt, Hoa cam Neroli và Quả mơ chín mọng.', 'desc' => 'Thanh khiết, rạng rỡ như ánh nắng sớm mai rót mật vào vườn hoa.', 'tags' => ['Quýt hồng', 'Hoa cam Neroli', 'Quả mơ chín']],
                ['icon' => 'fruit', 'notes' => 'Quả mâm xôi chín đỏ, Quả dâu rừng tươi và Cam Bergamot thanh tao.', 'desc' => 'Vị ngọt mọng nước, trẻ trung, tinh nghịch và đáng yêu.', 'tags' => ['Mâm xôi đỏ', 'Dâu rừng', 'Cam Bergamot']],
                ['icon' => 'fruit', 'notes' => 'Quả lê tuyết giòn tan, Hoa mộc tê ngọt dịu và Chanh vàng thanh nhã.', 'desc' => 'Thanh tao, trong trẻo và gợi cảm giác thư thái tuyệt đối.', 'tags' => ['Lê tuyết', 'Hoa mộc tê', 'Chanh vàng']],
                ['icon' => 'fruit', 'notes' => 'Quả anh đào đen (Black Cherry), Hạt tiêu hồng và Cam đỏ tươi tắn.', 'desc' => 'Gợi cảm, bí ẩn và cuốn hút ngay từ giây phút đầu tiên.', 'tags' => ['Anh đào đen', 'Tiêu hồng', 'Cam đỏ']],
            ],
            'unisex' => [
                ['icon' => 'leaf', 'notes' => 'Lá trà xanh thanh lọc, Cam Bergamot tươi và Quả bạch đậu khấu.', 'desc' => 'Tinh khôi, thanh tịnh và mang lại cảm giác an yên sâu lắng.', 'tags' => ['Trà xanh', 'Cam Bergamot', 'Bạch đậu khấu']],
                ['icon' => 'citrus', 'notes' => 'Chanh bưởi vùng Địa Trung Hải, Tiêu hồng và Thảo mộc dịu mát.', 'desc' => 'Mát lạnh, sảng khoái và tràn đầy năng lượng tự do.', 'tags' => ['Chanh Địa Trung Hải', 'Tiêu hồng', 'Thảo mộc']],
                ['icon' => 'tree', 'notes' => 'Vỏ thông thơm, Cây xô thơm và Cam chanh thanh nhã.', 'desc' => 'Khoáng đạt như làn gió rừng sớm, độc đáo và phong cách.', 'tags' => ['Vỏ thông thơm', 'Xô thơm', 'Cam chanh']],
            ]
        ];

        $heartLibraries = [
            'nam' => [
                ['icon' => 'leaf', 'notes' => 'Hoa oải hương Pháp (Lavender), Cây phong lữ và Cỏ Vetiver thơm râm mát.', 'desc' => 'Khí chất lịch lãm, phong trần và đầy bản lĩnh quý ông.', 'tags' => ['Hoa oải hương', 'Phong lữ', 'Cỏ Vetiver']],
                ['icon' => 'flame', 'notes' => 'Vỏ quế ấm nồng, Nhục đậu khấu cay nhẹ và Hoa nhài trắng tinh tế.', 'desc' => 'Ấm áp, nồng đượm và mang sức hút nam tính mê hoặc.', 'tags' => ['Vỏ quế ấm', 'Nhục đậu khấu', 'Hoa nhài']],
                ['icon' => 'tree', 'notes' => 'Gỗ tuyết tùng Virginia, Cây xô thơm và Hương khói nhẹ huyền bí.', 'desc' => 'Vững chãi, trầm ổn và tạo niềm tin cậy tuyệt đối.', 'tags' => ['Gỗ tuyết tùng', 'Cây xô thơm', 'Khói huyền bí']],
            ],
            'nu' => [
                ['icon' => 'flower', 'notes' => 'Hoa mẫu đơn Damascus, Hoa hồng Pháp thơm ngát và Hoa nhài Sambac.', 'desc' => 'Trái tim kiêu diễm, tôn vinh nét đẹp nữ tính đài các và sang trọng.', 'tags' => ['Hoa mẫu đơn', 'Hoa hồng Pháp', 'Hoa nhài Sambac']],
                ['icon' => 'flower', 'notes' => 'Xạ hương xạ đặc trưng, Hoa huệ trắng tuberose nồng nàn và Hoa diên vĩ.', 'desc' => 'Quyến rũ mê hồn, ma mị và để lại ấn tượng khó phai mờ.', 'tags' => ['Xạ hương trắng', 'Hoa huệ trắng', 'Hoa diên vĩ']],
                ['icon' => 'flower', 'notes' => 'Cánh hoa hồng Bungari ướp sương, Quả đào ngọt và Hoa dạ lan hương.', 'desc' => 'Lãng mạn, ngọt ngào và thơ mộng như truyện cổ tích.', 'tags' => ['Hoa hồng Bungari', 'Quả đào', 'Dạ lan hương']],
            ],
            'unisex' => [
                ['icon' => 'tree', 'notes' => 'Gỗ đàn hương Mysore kem mịn, Hoa diên vĩ trắng và Cây hoắc hương.', 'desc' => 'Hòa quyện tinh tế giữa nét ấm áp của gỗ và sự mềm mại của hoa.', 'tags' => ['Gỗ đàn hương', 'Hoa diên vĩ', 'Hoắc hương']],
                ['icon' => 'sparkles', 'notes' => 'Nhựa thơm Frankincense, Hương hoa cam và Hoa mộc lan tinh tế.', 'desc' => 'Sang trọng kín đáo, mang phong thái nghệ thuật đương đại.', 'tags' => ['Nhựa Frankincense', 'Hoa cam', 'Hoa mộc lan']],
            ]
        ];

        $baseLibraries = [
            'nam' => [
                ['icon' => 'tree', 'notes' => 'Nhựa Ambroxan quý giá, Gỗ tuyết tùng, Hổ phách vàng và Da thuộc.', 'desc' => 'Lưu giữ hương thơm quyền lực, bền bỉ và cuốn hút suốt cả ngày.', 'tags' => ['Nhựa Ambroxan', 'Gỗ tuyết tùng', 'Hổ phách']],
                ['icon' => 'tree', 'notes' => 'Gỗ đàn hương ấm cúng, Rêu sồi thanh tao và Vani Madagascar nhẹ.', 'desc' => 'Đọng lại dư vị trầm ấm, tinh tế và gợi nhớ sâu sắc.', 'tags' => ['Gỗ đàn hương', 'Rêu sồi', 'Vani Madagascar']],
                ['icon' => 'candy', 'notes' => 'Đậu Tonka nướng béo bùi, Hoắc hương hoang dã và Xạ hương thể thao.', 'desc' => 'Ấm áp, nam tính và tạo dấu ấn mạnh mẽ khó phai.', 'tags' => ['Đậu Tonka', 'Hoắc hương', 'Xạ hương']],
            ],
            'nu' => [
                ['icon' => 'sparkles', 'notes' => 'Xạ hương trắng mịn màng, Vanilla Madagascar ngọt béo và Gỗ tuyết tùng.', 'desc' => 'Mềm mịn như dải lụa ôm lấy làn da, gợi cảm và thanh lịch.', 'tags' => ['Xạ hương trắng', 'Vanilla Madagascar', 'Gỗ tuyết tùng']],
                ['icon' => 'bulb', 'notes' => 'Hổ phách vàng ấm cúng, Gỗ đàn hương kem béo và Đậu Tonka.', 'desc' => 'Lưu hương sâu lắng, ấm áp và toát lên vẻ quý phái quyền quý.', 'tags' => ['Hổ phách vàng', 'Gỗ đàn hương', 'Đậu Tonka']],
                ['icon' => 'moon', 'notes' => 'Kẹo hạt dẻ nướng Praline, Hoắc hương tinh tế và Xạ hương mềm.', 'desc' => 'Dư vị ngọt ngào lưu luyến, thơm dai dẳng khó quên.', 'tags' => ['Kẹo Praline', 'Hoắc hương', 'Xạ hương mềm']],
            ],
            'unisex' => [
                ['icon' => 'tree', 'notes' => 'Gỗ đàn hương cổ thụ, Rêu sồi, Long diên hương và Hổ phách trắng.', 'desc' => 'Dấu ấn độc bản tĩnh lặng, thanh lịch và đẳng cấp bất hủ.', 'tags' => ['Gỗ đàn hương', 'Long diên hương', 'Hổ phách trắng']],
                ['icon' => 'leaf', 'notes' => 'Cỏ hương bài Haiti, Gỗ tuyết tùng và Xạ hương bông sạch sẽ.', 'desc' => 'Trong lành, mộc mạc mà sang trọng tinh khôi.', 'tags' => ['Cỏ hương bài', 'Gỗ tuyết tùng', 'Xạ hương bông']],
            ]
        ];

        // Lựa chọn chỉ mục ổn định theo id
        $genderKey = in_array($gender, ['nam', 'nu', 'unisex']) ? $gender : 'unisex';
        $topIndex = $id % count($topLibraries[$genderKey]);
        $heartIndex = ($id + 1) % count($heartLibraries[$genderKey]);
        $baseIndex = ($id + 2) % count($baseLibraries[$genderKey]);

        $top = $topLibraries[$genderKey][$topIndex];
        $heart = $heartLibraries[$genderKey][$heartIndex];
        $base = $baseLibraries[$genderKey][$baseIndex];

        // Xác định độ lưu hương theo Nồng độ (EDT, EDP, Parfum, Extrait)
        $concLower = mb_strtolower($concentration);
        if (str_contains($concLower, 'extrait') || str_contains($concLower, 'parfum extrait')) {
            $longevity = ['text' => 'Cực đỉnh: 12 - 16 giờ', 'percent' => 96];
            $sillage = ['text' => 'Rất xa: Bán kính 2 mét', 'percent' => 88];
        } elseif (str_contains($concLower, 'parfum') && !str_contains($concLower, 'eau de parfum')) {
            $longevity = ['text' => 'Rất lâu: 10 - 14 giờ', 'percent' => 92];
            $sillage = ['text' => 'Xa: Bán kính 1.5 - 2 mét', 'percent' => 84];
        } elseif (str_contains($concLower, 'edt') || str_contains($concLower, 'toilette')) {
            $longevity = ['text' => 'Tươi mát: 6 - 8 giờ', 'percent' => 74];
            $sillage = ['text' => 'Vừa phải: Trong vòng một cánh tay', 'percent' => 70];
        } else { // Mặc định EDP
            $longevity = ['text' => 'Lâu bền: 8 - 10 giờ', 'percent' => 85];
            $sillage = ['text' => 'Tỏa tốt: Khoảng 1.5 mét', 'percent' => 78];
        }

        $seasonTexts = [
            'nam' => 'Bốn mùa, Ngày & Đêm, Gặp gỡ, Tiệc tùng & Sự kiện',
            'nu' => 'Mùa Xuân / Thu / Đông, Hẹn hò lãng mạn, Tiệc tùng, Dạo phố',
            'unisex' => 'Quanh năm, Môi trường công sở cao cấp, Dạ tiệc sang trọng'
        ];

        $styleTexts = [
            'nam' => 'Lịch lãm, Nam tính, Quyến rũ, Đẳng cấp quý ông',
            'nu' => 'Nữ tính, Quý phái, Dịu dàng, Đầy sức hút mê hoặc',
            'unisex' => 'Thanh lịch, Độc đáo, Tinh tế, Hiện đại & Khác biệt'
        ];

        $familyMap = [
            'nam' => 'Hương Gỗ Cay Nồng & Thảo Mộc Đại Ngàn (Woody Aromatic Spicy)',
            'nu' => 'Hương Hoa Cỏ & Trái Cây Quý Phái (Floral Fruity Tender)',
            'unisex' => 'Hương Gỗ Trầm & Thảo Mộc Thanh Tịnh (Woody Oriental Aromatic)'
        ];

        $familyBadgeMap = [
            'nam' => 'Gỗ & Thảo Mộc Cay',
            'nu' => 'Hoa Cỏ & Trái Cây',
            'unisex' => 'Gỗ Trầm & Hổ Phách'
        ];

        $badgeColorMap = [
            'nam' => '#1b4332',
            'nu' => '#c94d68',
            'unisex' => '#854d0e'
        ];

        return [
            'family' => $familyMap[$genderKey],
            'family_badge' => $familyBadgeMap[$genderKey],
            'badge_color' => $badgeColorMap[$genderKey],
            'top' => array_merge(['title' => 'Hương Đầu (Top Notes)', 'time' => 'Tầng 1 · 0 - 15 Phút'], $top),
            'heart' => array_merge(['title' => 'Hương Giữa (Heart Notes)', 'time' => 'Tầng 2 · 15 Phút - 4 Giờ'], $heart),
            'base' => array_merge(['title' => 'Hương Cuối (Base Notes)', 'time' => 'Tầng 3 · 4 - 12 Giờ'], $base),
            'longevity' => $longevity,
            'sillage' => $sillage,
            'season' => ['text' => $seasonTexts[$genderKey], 'percent' => 92],
            'style' => ['text' => $styleTexts[$genderKey], 'percent' => 93],
            'highlight_notes' => array_merge($top['tags'] ?? [], $heart['tags'] ?? [], $base['tags'] ?? []),
        ];
    }
}
