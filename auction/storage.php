<?php
/**
 * 경매 데이터 저장 및 관리 함수
 */

// 데이터 파일 경로
define('AUCTION_DATA_DIR', __DIR__ . '/../data/auction/');
define('AUCTION_DATA_FILE', AUCTION_DATA_DIR . 'auctions.json');
define('BID_DATA_FILE', AUCTION_DATA_DIR . 'bids.json');

// 데이터 디렉토리 생성
if (!is_dir(AUCTION_DATA_DIR)) {
    mkdir(AUCTION_DATA_DIR, 0755, true);
}

/**
 * 모든 경매 데이터 로드
 */
function auction_load_all() {
    if (!file_exists(AUCTION_DATA_FILE)) {
        return [];
    }
    $data = file_get_contents(AUCTION_DATA_FILE);
    return json_decode($data, true) ?: [];
}

/**
 * 경매 데이터 저장
 */
function auction_save_all($auctions) {
    file_put_contents(AUCTION_DATA_FILE, json_encode($auctions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 새 경매 생성
 */
function auction_create($data) {
    $auctions = auction_load_all();
    
    // 새 ID 생성
    $new_id = 1;
    if (!empty($auctions)) {
        $ids = array_column($auctions, 'id');
        $new_id = max($ids) + 1;
    }
    
    // 경매 데이터 구조
    $auction = [
        'id' => $new_id,
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'manufacturer' => $data['manufacturer'] ?? '',
        'model' => $data['model'] ?? '',
        'year' => $data['year'] ?? 0,
        'mileage' => $data['mileage'] ?? 0,
        'transmission' => $data['transmission'] ?? '',
        'fuel' => $data['fuel'] ?? '',
        'accident' => $data['accident'] ?? '',
        'accident_detail' => $data['accident_detail'] ?? '',
        'start_price' => $data['start_price'] ?? 0,
        'current_price' => $data['start_price'] ?? 0,
        'image' => $data['image'] ?? '',
        'created_at' => time(),
        'end_time' => $data['end_time'] ?? (time() + 7 * 86400),
        'status' => 'active', // active, ended, sold
        'bid_count' => 0
    ];
    
    $auctions[] = $auction;
    auction_save_all($auctions);
    
    return $new_id;
}

/**
 * ID로 경매 찾기
 */
function auction_find_by_id($id) {
    $auctions = auction_load_all();
    foreach ($auctions as $auction) {
        if ($auction['id'] == $id) {
            return $auction;
        }
    }
    return null;
}

/**
 * 경매 업데이트
 */
function auction_update($id, $data) {
    $auctions = auction_load_all();
    foreach ($auctions as $key => $auction) {
        if ($auction['id'] == $id) {
            $auctions[$key] = array_merge($auction, $data);
            auction_save_all($auctions);
            return true;
        }
    }
    return false;
}

/**
 * 경매 삭제
 */
function auction_delete($id) {
    $auctions = auction_load_all();
    foreach ($auctions as $key => $auction) {
        if ($auction['id'] == $id) {
            unset($auctions[$key]);
            auction_save_all(array_values($auctions));
            return true;
        }
    }
    return false;
}

/**
 * 모든 입찰 데이터 로드
 */
function bid_load_all() {
    if (!file_exists(BID_DATA_FILE)) {
        return [];
    }
    $data = file_get_contents(BID_DATA_FILE);
    return json_decode($data, true) ?: [];
}

/**
 * 입찰 데이터 저장
 */
function bid_save_all($bids) {
    file_put_contents(BID_DATA_FILE, json_encode($bids, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 새 입찰 추가
 */
function bid_create($auction_id, $user_id, $amount, $user_name = '') {
    $bids = bid_load_all();
    
    // 새 ID 생성
    $new_id = 1;
    if (!empty($bids)) {
        $ids = array_column($bids, 'id');
        $new_id = max($ids) + 1;
    }
    
    // 입찰 데이터
    $bid = [
        'id' => $new_id,
        'auction_id' => $auction_id,
        'user_id' => $user_id,
        'user_name' => $user_name,
        'amount' => $amount,
        'created_at' => time()
    ];
    
    $bids[] = $bid;
    bid_save_all($bids);
    
    // 경매 정보 업데이트
    $auction = auction_find_by_id($auction_id);
    if ($auction) {
        auction_update($auction_id, [
            'current_price' => $amount,
            'bid_count' => $auction['bid_count'] + 1
        ]);
    }
    
    return $new_id;
}

/**
 * 특정 경매의 입찰 내역 가져오기
 */
function bid_get_by_auction($auction_id) {
    $bids = bid_load_all();
    $auction_bids = [];
    
    foreach ($bids as $bid) {
        if ($bid['auction_id'] == $auction_id) {
            $auction_bids[] = $bid;
        }
    }
    
    // 최신순 정렬
    usort($auction_bids, function($a, $b) {
        return $b['created_at'] - $a['created_at'];
    });
    
    return $auction_bids;
}

/**
 * 경매 상태 업데이트 (종료된 경매 체크)
 */
function auction_update_status() {
    $auctions = auction_load_all();
    $updated = false;
    
    foreach ($auctions as $key => $auction) {
        if ($auction['status'] === 'active' && $auction['end_time'] < time()) {
            $auctions[$key]['status'] = 'ended';
            $updated = true;
        }
    }
    
    if ($updated) {
        auction_save_all($auctions);
    }
}

