<?php

/*
| Demo heritage sites for the Sprint 1 map.
| Statuses are set manually because threat scoring arrives in Sprint 2.
| The same list can be reused by a seeder once the `sites` table exists.
*/

return [
    ['id' => 1, 'name' => 'Candi Borobudur', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Jawa Tengah', 'city' => 'Magelang', 'lat' => -7.6079, 'lng' => 110.2038, 'status' => 'safe', 'threat_score' => 1.4],
    ['id' => 2, 'name' => 'Kompleks Candi Prambanan', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'DI Yogyakarta', 'city' => 'Sleman', 'lat' => -7.7520, 'lng' => 110.4915, 'status' => 'safe', 'threat_score' => 2.1],
    ['id' => 3, 'name' => 'Candi Cetho', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Jawa Tengah', 'city' => 'Karanganyar', 'lat' => -7.5953, 'lng' => 111.1574, 'status' => 'watch', 'threat_score' => 8.7],
    ['id' => 4, 'name' => 'Situs Sangiran', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Jawa Tengah', 'city' => 'Sragen', 'lat' => -7.4558, 'lng' => 110.8378, 'status' => 'watch', 'threat_score' => 6.2],
    ['id' => 5, 'name' => 'Kawasan Percandian Muaro Jambi', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Jambi', 'city' => 'Muaro Jambi', 'lat' => -1.4767, 'lng' => 103.6667, 'status' => 'threatened', 'threat_score' => 18.3],
    ['id' => 6, 'name' => 'Benteng Rotterdam', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'Sulawesi Selatan', 'city' => 'Makassar', 'lat' => -5.1339, 'lng' => 119.4053, 'status' => 'safe', 'threat_score' => 0.8],
    ['id' => 7, 'name' => 'Kota Tua Jakarta', 'type' => 'cultural', 'category' => 'Cagar Budaya Nasional', 'province' => 'DKI Jakarta', 'city' => 'Jakarta Barat', 'lat' => -6.1352, 'lng' => 106.8133, 'status' => 'watch', 'threat_score' => 11.5],
    ['id' => 8, 'name' => 'Subak Jatiluwih', 'type' => 'cultural', 'category' => 'UNESCO World Heritage', 'province' => 'Bali', 'city' => 'Tabanan', 'lat' => -8.3700, 'lng' => 115.1310, 'status' => 'safe', 'threat_score' => 3.0],
    ['id' => 9, 'name' => 'Taman Nasional Komodo', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Nusa Tenggara Timur', 'city' => 'Manggarai Barat', 'lat' => -8.5500, 'lng' => 119.4890, 'status' => 'watch', 'threat_score' => 7.9],
    ['id' => 10, 'name' => 'Taman Nasional Ujung Kulon', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Banten', 'city' => 'Pandeglang', 'lat' => -6.7500, 'lng' => 105.3330, 'status' => 'safe', 'threat_score' => 2.6],
    ['id' => 11, 'name' => 'Taman Nasional Lorentz', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Papua Tengah', 'city' => 'Mimika', 'lat' => -4.7500, 'lng' => 137.8330, 'status' => 'safe', 'threat_score' => 0.0],
    ['id' => 12, 'name' => 'Taman Nasional Gunung Leuser', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Aceh', 'city' => 'Aceh Tenggara', 'lat' => 3.7500, 'lng' => 97.5000, 'status' => 'threatened', 'threat_score' => 21.4],
    ['id' => 13, 'name' => 'Taman Nasional Kerinci Seblat', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Jambi', 'city' => 'Kerinci', 'lat' => -1.7000, 'lng' => 101.2500, 'status' => 'threatened', 'threat_score' => 16.0],
    ['id' => 14, 'name' => 'Taman Nasional Bukit Barisan Selatan', 'type' => 'natural', 'category' => 'UNESCO World Heritage', 'province' => 'Lampung', 'city' => 'Lampung Barat', 'lat' => -5.0000, 'lng' => 104.2500, 'status' => 'watch', 'threat_score' => 9.8],
    ['id' => 15, 'name' => 'Taman Nasional Bromo Tengger Semeru', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Jawa Timur', 'city' => 'Probolinggo', 'lat' => -7.9425, 'lng' => 112.9530, 'status' => 'safe', 'threat_score' => 4.1],
    ['id' => 16, 'name' => 'Taman Nasional Wakatobi', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Sulawesi Tenggara', 'city' => 'Wakatobi', 'lat' => -5.5000, 'lng' => 123.7500, 'status' => 'safe', 'threat_score' => 1.2],
    ['id' => 17, 'name' => 'Raja Ampat', 'type' => 'natural', 'category' => 'Kawasan Konservasi Perairan', 'province' => 'Papua Barat Daya', 'city' => 'Raja Ampat', 'lat' => -0.2346, 'lng' => 130.5079, 'status' => 'watch', 'threat_score' => 5.0],
    ['id' => 18, 'name' => 'Taman Nasional Tanjung Puting', 'type' => 'natural', 'category' => 'Taman Nasional', 'province' => 'Kalimantan Tengah', 'city' => 'Kotawaringin Barat', 'lat' => -2.9000, 'lng' => 111.9000, 'status' => 'threatened', 'threat_score' => 15.0],
];
