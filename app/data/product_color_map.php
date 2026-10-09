<?php
// BẢN ĐỒ ÁNH XẠ MÀU SẮC SANG ĐÚNG ẢNH THỰC TẾ TRONG ASSETS CHO TẤT CẢ 58 SẢN PHẨM
function getAllProductColorMap(): array {
    static $map = null;
    if ($map === null) {
        $json = <<<'JSON'
{
  "1": {
    "Đỏ Burgundy": "assets/images/products/iphone-18-promax-do-burgundy.png",
    "Xanh Glacier Blue": "assets/images/products/iphone-18-promax-xanh-glacier-blue.png",
    "Đen": "assets/images/products/iphone-18-promax-den.png",
    "Bạc": "assets/images/products/iphone-18-promax-bac.png"
  },
  "2": {
    "Đỏ Burgundy": "assets/images/products/iphone-18-promax-do-burgundy.png",
    "Xanh Glacier Blue": "assets/images/products/iphone-18-promax-xanh-glacier-blue.png",
    "Đen": "assets/images/products/iphone-18-promax-den.png",
    "Bạc": "assets/images/products/iphone-18-promax-bac.png"
  },
  "3": {
    "Trắng Ánh Sao": "assets/images/products/iphone-18-duo-trang-anh-sao.png",
    "Trời Đêm": "assets/images/products/iphone-18-duo-troi-dem.png"
  },
  "4": {
    "Xanh Băng": "assets/images/products/iphone-18.png",
    "Hồng": "assets/images/products/iphone-18.png",
    "Bạc Ánh Kim": "assets/images/products/iphone-18.png",
    "Đen": "assets/images/products/iphone-18.png"
  },
  "5": {
    "Đen Phantom": "assets/images/products/samsung-tri-fold-den-phantom.png"
  },
  "6": {
    "Tím Cobalt": "assets/images/products/samsung-s26-ultra-tim-cobalt.png",
    "Đen Classic": "assets/images/products/samsung-s26-ultra-den-classic.png",
    "Xanh Sky Blue": "assets/images/products/samsung-s26-ultra-xanh-sky-blue.png",
    "Trắng Classic": "assets/images/products/samsung-s26-ultra-trang-classic.png"
  },
  "7": {
    "Đỏ Ruihong": "assets/images/products/huawei-mate-xt-do-ruihong.png"
  },
  "8": {
    "Đen Da Thuần": "assets/images/products/xiaomi-14-ultra-den.png",
    "Xanh Lam": "assets/images/products/xiaomi-14-ultra-xanh.png"
  },
  "9": {
    "Đen": "assets/images/products/oppo-find-n5.png"
  },
  "10": {
    "Titan Tự Nhiên": "assets/images/products/iphone-17-promax-titan-tu-nhien.png",
    "Titan Đen": "assets/images/products/iphone-17-promax-titan-den.png",
    "Titan Bạc": "assets/images/products/iphone-17-promax-titan-bac.png",
    "Bạc": "assets/images/products/iphone-17-promax.png"
  },
  "11": {
    "Titan Tự Nhiên": "assets/images/products/iphone-17-pro-titan-tu-nhien.png",
    "Titan Đen": "assets/images/products/iphone-17-pro-titan-den.png",
    "Titan Bạc": "assets/images/products/iphone-17-pro-titan-bac.png",
    "Bạc": "assets/images/products/iphone-17-pro.png"
  },
  "12": {
    "Bạc Ánh Kim": "assets/images/products/iphone-17-air.png",
    "Xanh Băng": "assets/images/products/iphone-17-air.png",
    "Đen": "assets/images/products/iphone-17-air.png"
  },
  "13": {
    "Xanh": "assets/images/products/iphone-17.png",
    "Hồng": "assets/images/products/iphone-17.png",
    "Trắng": "assets/images/products/iphone-17.png",
    "Đen": "assets/images/products/iphone-17.png"
  },

  "14": {
    "Titan Sa Mạc": "assets/images/products/iphone-16-promax-titan-sa-mac.png",
    "Titan Tự Nhiên": "assets/images/products/iphone-16-promax-titan-tu-nhien.png",
    "Titan Đen": "assets/images/products/iphone-16-promax-titan-den.png",
    "Titan Trắng": "assets/images/products/iphone-16-promax-titan-trang.png"
  },
  "15": {
    "Titan Sa Mạc": "assets/images/products/iphone-16-pro-titan-sa-mac.png",
    "Titan Tự Nhiên": "assets/images/products/iphone-16-pro-titan-tu-nhien.png",
    "Titan Đen": "assets/images/products/iphone-16-pro-titan-den.png",
    "Titan Trắng": "assets/images/products/iphone-16-pro-titan-trang.png"
  },
  "16": {
    "Đen": "assets/images/products/iphone-16-plus-den.png",
    "Trắng": "assets/images/products/iphone-16-plus-trang.png",
    "Hồng": "assets/images/products/iphone-16-plus-hong.png",
    "Xanh Mòng Két": "assets/images/products/iphone-16-plus-xanh-mong-ket.png",
    "Xanh Lưu Ly": "assets/images/products/iphone-16-plus-xanh-luu-ly.png"
  },
  "17": {
    "Đen": "assets/images/products/iphone-16-den.png",
    "Trắng": "assets/images/products/iphone-16-trang.png",
    "Hồng": "assets/images/products/iphone-16-hong.png",
    "Xanh Mòng Két": "assets/images/products/iphone-16-xanh-mong-ket.png",
    "Xanh Lưu Ly": "assets/images/products/iphone-16-xanh-luu-ly.png"
  },
  "18": {
    "Titan Tự Nhiên": "assets/images/products/iphone-15-promax-titan-tu-nhien.png",
    "Titan Xanh": "assets/images/products/iphone-15-promax-titan-xanh.png",
    "Titan Đen": "assets/images/products/iphone-15-promax-titan-den.png",
    "Titan Trắng": "assets/images/products/iphone-15-promax-titan-trang.png"
  },
  "19": {
    "Đen": "assets/images/products/iphone-15-den.png",
    "Xanh Lam": "assets/images/products/iphone-15-xanh-lam.png",
    "Xanh Lá": "assets/images/products/iphone-15-xanh-la.png",
    "Vàng": "assets/images/products/iphone-15-vang.png",
    "Hồng": "assets/images/products/iphone-15-hong.png"
  },
  "20": {
    "Tím Deep Purple": "assets/images/products/iphone-14-promax-tim-deep-purple.png",
    "Vàng Gold": "assets/images/products/iphone-14-promax-vang-gold.png",
    "Bạc Silver": "assets/images/products/iphone-14-promax-bac-silver.png",
    "Đen Space": "assets/images/products/iphone-14-promax-den-space.png"
  },
  "21": {
    "Xanh Dương": "assets/images/products/iphone-14-xanh-duong.jpg",
    "Tím": "assets/images/products/iphone-14-tim.jpg",
    "Vàng": "assets/images/products/iphone-14-vang.jpg",
    "Trắng Starlight": "assets/images/products/iphone-14-trang-starlight.jpg",
    "Đen Midnight": "assets/images/products/iphone-14-den-midnight.jpg",
    "Đỏ": "assets/images/products/iphone-14-do.jpg"
  },
  "22": {
    "Trắng Starlight": "assets/images/products/iphone-13-trang-starlight.png",
    "Đen Midnight": "assets/images/products/iphone-13-den-midnight.png",
    "Hồng": "assets/images/products/iphone-13-hong.png",
    "Xanh Lá": "assets/images/products/iphone-13-xanh-la.png",
    "Đỏ": "assets/images/products/iphone-13-do.png",
    "Xanh Dương": "assets/images/products/iphone-13-xanh-duong.png"
  },
  "23": {
    "Tím": "assets/images/products/iphone-12-tim.jpg",
    "Xanh Lam": "assets/images/products/iphone-12-xanh-lam.jpg",
    "Xanh Lục": "assets/images/products/iphone-12-xanh-luc.jpg",
    "Đỏ": "assets/images/products/iphone-12-do.jpg",
    "Trắng": "assets/images/products/iphone-12-trang.jpg",
    "Đen": "assets/images/products/iphone-12-den.jpg"
  },
  "24": {
    "Đen": "assets/images/products/iphone-11-den.png",
    "Trắng": "assets/images/products/iphone-11-trang.png",
    "Tím": "assets/images/products/iphone-11-tim.png",
    "Vàng": "assets/images/products/iphone-11-vang.png",
    "Xanh Lục": "assets/images/products/iphone-11-xanh-luc.png",
    "Đỏ": "assets/images/products/iphone-11-do.png"
  },
  "25": {
    "Đen Midnight": "assets/images/products/iphone-se-2022-den-midnight.jpg",
    "Trắng Starlight": "assets/images/products/iphone-se-2022-trang-starlight.jpg",
    "Đỏ Product Red": "assets/images/products/iphone-se-2022-do-product-red.jpg"
  },
  "26": {
    "Xám Titan": "assets/images/products/samsung-s24-ultra-xam-titan.png",
    "Đen Titan": "assets/images/products/samsung-s24-ultra-den-titan.png",
    "Tím Titan": "assets/images/products/samsung-s24-ultra-tim-titan.png",
    "Vàng Titan": "assets/images/products/samsung-s24-ultra-vang-titan.png"
  },
  "27": {
    "Đen Onyx": "assets/images/products/samsung-s24-plus-den-onyx.jpg",
    "Xám Marble": "assets/images/products/samsung-s24-plus-xam-marble.jpg",
    "Tím Cobalt": "assets/images/products/samsung-s24-plus-tim-cobalt.jpg",
    "Vàng Amber": "assets/images/products/samsung-s24-plus-vang-amber.jpg"
  },
  "28": {
    "Đen Onyx": "assets/images/products/samsung-s24-den-onyx.jpg",
    "Xám Marble": "assets/images/products/samsung-s24-xam-marble.jpg",
    "Tím Cobalt": "assets/images/products/samsung-s24-tim-cobalt.jpg",
    "Vàng Amber": "assets/images/products/samsung-s24-vang-amber.jpg"
  },
  "29": {
    "Xanh Topaz": "assets/images/products/samsung-s24-fe-xanh-topaz.jpg",
    "Đen Graphite": "assets/images/products/samsung-s24-fe-den-graphite.jpg",
    "Xám Opal": "assets/images/products/samsung-s24-fe-xam-opal.jpg",
    "Xanh Jade": "assets/images/products/samsung-s24-fe-xanh-jade.jpg"
  },
  "30": {
    "Xám Metal": "assets/images/products/samsung-z-fold6-xam-metal.png",
    "Hồng Rosé": "assets/images/products/samsung-z-fold6-hong-rose.png",
    "Trắng Bạc": "assets/images/products/samsung-z-fold6-trang-bac.png"
  },
  "31": {
    "Xám Metal": "assets/images/products/samsung-z-flip6-xam-metal.jpg",
    "Xanh Maya": "assets/images/products/samsung-z-flip6-xanh-maya.jpg",
    "Vàng Solar": "assets/images/products/samsung-z-flip6-vang-solar.jpg",
    "Xanh Mint": "assets/images/products/samsung-z-flip6-xanh-mint.jpg"
  },
  "32": {
    "Xanh Icy Blue": "assets/images/products/samsung-z-fold5-xanh-icy-blue.jpg",
    "Kem Ivory": "assets/images/products/samsung-z-fold5-kem-ivory.jpg",
    "Đen Phantom": "assets/images/products/samsung-z-fold5-den-phantom.jpg"
  },
  "33": {
    "Đen Phantom": "assets/images/products/samsung-s23-ultra-den-phantom.png",
    "Xanh Botanic": "assets/images/products/samsung-s23-ultra-xanh-botanic.png",
    "Kem Cotton": "assets/images/products/samsung-s23-ultra-kem-cotton.png",
    "Tím Lilac": "assets/images/products/samsung-s23-ultra-tim-lilac.png"
  },
  "34": {
    "Xanh Iceblue": "assets/images/products/samsung-a55-xanh-iceblue.png",
    "Xanh Navy": "assets/images/products/samsung-a55-xanh-navy.png",
    "Tím Lilac": "assets/images/products/samsung-a55-tim-lilac.png"
  },
  "35": {
    "Xanh Iceblue": "assets/images/products/samsung-a35-xanh-iceblue.jpg",
    "Vàng Lemon": "assets/images/products/samsung-a35-vang-lemon.jpg",
    "Xanh Navy": "assets/images/products/samsung-a35-xanh-navy.jpg"
  },
  "36": {
    "Xanh Lạc Quan": "assets/images/products/samsung-a15-xanh-lac-quan.jpg",
    "Vàng Cá Tính": "assets/images/products/samsung-a15-vang-ca-tinh.jpg",
    "Đen Bản Lĩnh": "assets/images/products/samsung-a15-den-ban-linh.jpg"
  },
  "37": {
    "Đen Đá": "assets/images/products/samsung-a05s-den-da.jpg",
    "Xanh Matcha": "assets/images/products/samsung-a05s-xanh-matcha.jpg",
    "Bạc Xỉu": "assets/images/products/samsung-a05s-bac-xiu.jpg"
  },
  "38": {
    "Đen": "assets/images/products/xiaomi-14-ultra-den.png",
    "Xanh Dragon Crystal": "assets/images/products/xiaomi-14-ultra-xanh.png"
  },
  "39": {
    "Đen": "assets/images/products/xiaomi-14-den.jpg",
    "Trắng": "assets/images/products/xiaomi-14-trang.jpg",
    "Xanh Ngọc Bích": "assets/images/products/xiaomi-14-xanh-ngoc-bich.jpg"
  },
  "40": {
    "Đen Midnight": "assets/images/products/redmi-note-13-pro-plus-den-midnight.jpg",
    "Tím Cực Quang": "assets/images/products/redmi-note-13-pro-plus-tim-cuc-quang.jpg",
    "Trắng Ánh Trăng": "assets/images/products/redmi-note-13-pro-plus-trang-anh-trang.jpg"
  },
  "41": {
    "Đen Bóng Đêm": "assets/images/products/redmi-13-den-bong-dem.jpg",
    "Xanh Đại Dương": "assets/images/products/redmi-13-xanh-dai-duong.jpg",
    "Hồng Ngọc Trai": "assets/images/products/redmi-13-hong-ngoc-trai.jpg"
  },
  "42": {
    "Đen Vũ Trụ": "assets/images/products/poco-f6-pro.jpg"
  },
  "43": {
    "Vàng POCO": "assets/images/products/poco-x6-pro-vang-poco.jpg",
    "Đen": "assets/images/products/poco-x6-pro-den.jpg",
    "Xám": "assets/images/products/poco-x6-pro-xam.jpg"
  },
  "44": {
    "Vàng Cổ Điển": "assets/images/products/oppo-find-n3-vang-co-dien.png",
    "Đen Cổ Điển": "assets/images/products/oppo-find-n3-den-co-dien.png"
  },
  "45": {
    "Vàng Thạch Anh": "assets/images/products/oppo-find-n3-flip-vang-thach-anh.jpg",
    "Đen Kính": "assets/images/products/oppo-find-n3-flip-den-kinh.jpg"
  },
  "46": {
    "Bạc Ngân Hà": "assets/images/products/oppo-reno12-pro-bac-ngan-ha.jpg",
    "Đen Không Gian": "assets/images/products/oppo-reno12-pro-den-khong-gian.jpg"
  },
  "47": {
    "Xanh Đại Dương": "assets/images/products/oppo-reno11-f-xanh-dai-duong.jpg",
    "Xanh Sơn Lâm": "assets/images/products/oppo-reno11-f-xanh-son-lam.jpg"
  },
  "48": {
    "Cam Hoàng Hôn": "assets/images/products/vivo-x100-pro-cam-hoang-hon.jpg",
    "Xanh Tinh Tú": "assets/images/products/vivo-x100-pro-xanh-tinh-tu.jpg",
    "Đen": "assets/images/products/vivo-x100-pro-den.jpg"
  },
  "49": {
    "Cam Ánh Dương": "assets/images/products/vivo-y28-cam-anh-duong.jpg",
    "Xanh Lục Bảo": "assets/images/products/vivo-y28-xanh-luc-bao.jpg"
  },
  "50": {
    "Đen Obsidian": "assets/images/products/google-pixel-9-den-obsidian.png",
    "Trắng Sứ Porcelain": "assets/images/products/google-pixel-9-trang-su-porcelain.png",
    "Xám Xanh Hazel": "assets/images/products/google-pixel-9-xam-xanh-hazel.png",
    "Hồng Rose Quartz": "assets/images/products/google-pixel-9-hong-rose-quartz.png"
  },
  "51": {
    "Đen Phantom": "assets/images/products/asus-rog-phone-8-den-phantom.jpg"
  },
  "52": {
    "Đen": "assets/images/products/sony-xperia-1-vi-den.png",
    "Bạc Bạch Kim": "assets/images/products/sony-xperia-1-vi-bac-bach-kim.png",
    "Xanh Rêu Khaki": "assets/images/products/sony-xperia-1-vi-xanh-reu-khaki.png"
  },
  "53": {
    "Titan Tự Nhiên": "assets/images/products/iphone-15-promax-titan-tu-nhien.png",
    "Titan Xanh": "assets/images/products/iphone-15-promax-titan-xanh.png",
    "Titan Đen": "assets/images/products/iphone-15-promax-titan-den.png"
  },
  "54": {
    "Tím Deep Purple": "assets/images/products/iphone-14-promax-tim-deep-purple.png",
    "Vàng Gold": "assets/images/products/iphone-14-promax-vang-gold.png",
    "Đen Space": "assets/images/products/iphone-14-promax-den-space.png"
  },
  "55": {
    "Trắng Ánh Sao": "assets/images/products/iphone-13-trang-anh-sao.png",
    "Đen Midnight": "assets/images/products/iphone-13-den-midnight.png",
    "Hồng": "assets/images/products/iphone-13-hong.png"
  },
  "56": {
    "Đen": "assets/images/products/iphone-11-den.png",
    "Trắng": "assets/images/products/iphone-11-trang.png",
    "Đỏ": "assets/images/products/iphone-11-do.png"
  },
  "57": {
    "Đen Phantom": "assets/images/products/samsung-s23-ultra-den-phantom.png",
    "Kem Cotton": "assets/images/products/samsung-s23-ultra-kem-cotton.png",
    "Xanh Botanic": "assets/images/products/samsung-s23-ultra-xanh-botanic.png"
  },
  "58": {
    "Xanh Icy Blue": "assets/images/products/samsung-z-fold5-xanh-icy-blue.jpg",
    "Đen Phantom": "assets/images/products/samsung-z-fold5-den-phantom.jpg"
  }
}
JSON;
        $map = json_decode($json, true) ?: [];
    }
    return $map;
}
