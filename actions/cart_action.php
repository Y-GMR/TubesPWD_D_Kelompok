<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = array(
        'merchant' => '',
        'items' => array()
    );
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
if ($action === '' && isset($_POST['action'])) {
    $action = trim($_POST['action']);
}

$item_id = isset($_REQUEST['item']) ? trim($_REQUEST['item']) : '';
$merchant_id = isset($_REQUEST['merchant']) ? trim($_REQUEST['merchant']) : '';
$qty = isset($_REQUEST['qty']) ? (int) $_REQUEST['qty'] : 1;

if ($action === 'add') {
    if ($item_id !== '') {
        if ($merchant_id !== '' && $_SESSION['cart']['merchant'] !== '' && $_SESSION['cart']['merchant'] !== $merchant_id) {
            $_SESSION['cart']['items'] = array();
        }
        if ($merchant_id !== '') {
            $_SESSION['cart']['merchant'] = $merchant_id;
        }

        if (isset($_SESSION['cart']['items'][$item_id])) {
            $_SESSION['cart']['items'][$item_id] += $qty;
        } else {
            $_SESSION['cart']['items'][$item_id] = $qty;
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Item berhasil ditambahkan ke keranjang',
            'cart' => $_SESSION['cart']
        ));
        exit;
    }
} elseif ($action === 'update') {
    if ($item_id !== '') {
        if ($qty > 0) {
            $_SESSION['cart']['items'][$item_id] = $qty;
        } else {
            unset($_SESSION['cart']['items'][$item_id]);
            if (empty($_SESSION['cart']['items'])) {
                $_SESSION['cart']['merchant'] = '';
            }
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Jumlah item berhasil diperbarui',
            'cart' => $_SESSION['cart']
        ));
        exit;
    }
} elseif ($action === 'remove') {
    if ($item_id !== '') {
        unset($_SESSION['cart']['items'][$item_id]);
        if (empty($_SESSION['cart']['items'])) {
            $_SESSION['cart']['merchant'] = '';
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang',
            'cart' => $_SESSION['cart']
        ));
        exit;
    }
} elseif ($action === 'clear') {
    $_SESSION['cart'] = array(
        'merchant' => '',
        'items' => array()
    );

    echo json_encode(array(
        'success' => true,
        'message' => 'Keranjang berhasil dikosongkan',
        'cart' => $_SESSION['cart']
    ));
    exit;
} elseif ($action === 'get') {
    echo json_encode(array(
        'success' => true,
        'cart' => $_SESSION['cart']
    ));
    exit;
}

echo json_encode(array(
    'success' => false,
    'message' => 'Aksi tidak valid atau parameter tidak lengkap',
    'cart' => $_SESSION['cart']
));
exit;
