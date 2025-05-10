<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../Auth/login.php");
        exit();
    }

    if (!isset($_GET['kode'])) {
        echo "Kode penyakit tidak ditemukan!";
        exit();
    }

    $kode = $_GET['kode'];
    $stmt = $conn->prepare("DELETE FROM penyakit WHERE kode_penyakit = ?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();

    header("Location: penyakit.php");
    exit();
?>
