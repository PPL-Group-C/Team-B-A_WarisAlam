<?php

/*
| Demo heritage sites for the map (read by MapDemoSiteSeeder and the Dusk tests).
| Statuses are set manually because threat scoring arrives in Sprint 2.
| Registration IDs shared with SiteDetailDemoSeeder / ModerationDemoSeeder reuse the
| same value so no site is created twice. "DEMO-" IDs are placeholders.
*/

return [
    ['registration_id' => 'CB-JTG-MAGELANG-1991-001', 'name' => 'Candi Borobudur', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Jawa Tengah', 'city' => 'Magelang', 'lat' => -7.6079, 'lng' => 110.2038, 'threat_score' => 1.4],
    ['registration_id' => 'CB-DIY-SLEMAN-1991-001', 'name' => 'Kompleks Candi Prambanan', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'DI Yogyakarta', 'city' => 'Sleman', 'lat' => -7.7520, 'lng' => 110.4915, 'threat_score' => 2.1],
    ['registration_id' => 'CB-JTG-KARANGANYAR-1993-001', 'name' => 'Candi Cetho', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Jawa Tengah', 'city' => 'Karanganyar', 'lat' => -7.5953, 'lng' => 111.1574, 'threat_score' => 8.7],
    ['registration_id' => 'CB-JTG-SRAGEN-1996-001', 'name' => 'Situs Sangiran', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Jawa Tengah', 'city' => 'Sragen', 'lat' => -7.4558, 'lng' => 110.8378, 'threat_score' => 6.2],
    ['registration_id' => 'CB-JMB-MUAROJAMBI-2013-001', 'name' => 'Kawasan Percandian Muaro Jambi', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Jambi', 'city' => 'Muaro Jambi', 'lat' => -1.4767, 'lng' => 103.6667, 'threat_score' => 18.3],
    ['registration_id' => 'DEMO-SLS-ROTTERDAM', 'name' => 'Benteng Rotterdam', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Sulawesi Selatan', 'city' => 'Makassar', 'lat' => -5.1339, 'lng' => 119.4053, 'threat_score' => 0.8],
    ['registration_id' => 'DEMO-DKI-KOTATUA', 'name' => 'Kota Tua Jakarta', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'DKI Jakarta', 'city' => 'Jakarta Barat', 'lat' => -6.1352, 'lng' => 106.8133, 'threat_score' => 11.5],
    ['registration_id' => 'DEMO-BAL-JATILUWIH', 'name' => 'Subak Jatiluwih', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Bali', 'city' => 'Tabanan', 'lat' => -8.3700, 'lng' => 115.1310, 'threat_score' => 3.0],
    ['registration_id' => 'TN-NTT-KOMODO-1980-001', 'name' => 'Taman Nasional Komodo', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Nusa Tenggara Timur', 'city' => 'Manggarai Barat', 'lat' => -8.5500, 'lng' => 119.4890, 'threat_score' => 7.9],
    ['registration_id' => 'DEMO-BTN-UJUNGKULON', 'name' => 'Taman Nasional Ujung Kulon', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Banten', 'city' => 'Pandeglang', 'lat' => -6.7500, 'lng' => 105.3330, 'threat_score' => 2.6],
    ['registration_id' => 'DEMO-PPT-LORENTZ', 'name' => 'Taman Nasional Lorentz', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Papua Tengah', 'city' => 'Mimika', 'lat' => -4.7500, 'lng' => 137.8330, 'threat_score' => 0.0],
    ['registration_id' => 'DEMO-ACH-LEUSER', 'name' => 'Taman Nasional Gunung Leuser', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Aceh', 'city' => 'Aceh Tenggara', 'lat' => 3.7500, 'lng' => 97.5000, 'threat_score' => 21.4],
    ['registration_id' => 'DEMO-JMB-KERINCI', 'name' => 'Taman Nasional Kerinci Seblat', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Jambi', 'city' => 'Kerinci', 'lat' => -1.7000, 'lng' => 101.2500, 'threat_score' => 16.0],
    ['registration_id' => 'DEMO-LPG-BUKITBARISAN', 'name' => 'Taman Nasional Bukit Barisan Selatan', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Lampung', 'city' => 'Lampung Barat', 'lat' => -5.0000, 'lng' => 104.2500, 'threat_score' => 9.8],
    ['registration_id' => 'DEMO-JTM-BROMO', 'name' => 'Taman Nasional Bromo Tengger Semeru', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Jawa Timur', 'city' => 'Probolinggo', 'lat' => -7.9425, 'lng' => 112.9530, 'threat_score' => 4.1],
    ['registration_id' => 'DEMO-SLT-WAKATOBI', 'name' => 'Taman Nasional Wakatobi', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Sulawesi Tenggara', 'city' => 'Wakatobi', 'lat' => -5.5000, 'lng' => 123.7500, 'threat_score' => 1.2],
    ['registration_id' => 'DEMO-PBD-RAJAAMPAT', 'name' => 'Raja Ampat', 'type' => 'natural', 'category' => 'Kawasan Konservasi Perairan', 'province' => 'Papua Barat Daya', 'city' => 'Raja Ampat', 'lat' => -0.2346, 'lng' => 130.5079, 'threat_score' => 5.0],
    ['registration_id' => 'DEMO-KTG-TANJUNGPUTING', 'name' => 'Taman Nasional Tanjung Puting', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Kalimantan Tengah', 'city' => 'Kotawaringin Barat', 'lat' => -2.9000, 'lng' => 111.9000, 'threat_score' => 15.0],
];
