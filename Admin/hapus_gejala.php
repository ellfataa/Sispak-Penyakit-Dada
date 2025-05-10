<?php
    session_start();
    include '../Auth/connect.php';

    if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../Auth/login.php");
        exit();
    }

    if (!isset($_GET['kode'])) {
        echo "Kode gejala tidak ditemukan!";
        exit();
    }

    $kode = $_GET['kode'];

    $stmt = $conn->prepare("DELETE FROM gejala WHERE kode_gejala = ?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();

    header("Location: gejala.php");
    exit();
?>
