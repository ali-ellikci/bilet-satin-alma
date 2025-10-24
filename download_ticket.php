<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    die("Erişim Yetkiniz Yok!");
}

$ticket_id = $_GET['ticket_id'] ?? null;
if (!$ticket_id) {
    die("Geçersiz bilet ID'si.");
}

$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt_ticket = $db->prepare("
    SELECT tickets.*, trips.departure_city, trips.destination_city, trips.departure_time, trips.arrival_time,
           bus_company.name AS company_name
    FROM Tickets tickets
    JOIN Trips trips ON tickets.trip_id = trips.id
    JOIN Bus_Company bus_company ON trips.company_id = bus_company.id
    WHERE tickets.id = :ticket_id AND tickets.user_id = :user_id
");
$stmt_ticket->execute([
    ':ticket_id' => $ticket_id,
    ':user_id' => $_SESSION['user_id']
]);
$ticket = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı veya yetkiniz yok.");
}

$stmt_seats = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = :ticket_id");
$stmt_seats->execute([':ticket_id' => $ticket_id]);
$seats = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);

require('fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

$pdf->Cell(0,10,'Bilet Detaylari',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',12);
$pdf->Cell(50,8,'Firma:',0,0);
$pdf->Cell(0,8,$ticket['company_name'],0,1);

$pdf->Cell(50,8,'Guzergah:',0,0);
$pdf->Cell(0,8,$ticket['departure_city'].' -> '.$ticket['destination_city'],0,1);

$pdf->Cell(50,8,'Kalkis:',0,0);
$pdf->Cell(0,8,date('d F Y, H:i', strtotime($ticket['departure_time'])),0,1);

$pdf->Cell(50,8,'Varis:',0,0);
$pdf->Cell(0,8,date('d F Y, H:i', strtotime($ticket['arrival_time'])),0,1);

$pdf->Cell(50,8,'Koltuklar:',0,0);
$pdf->Cell(0,8,implode(', ', $seats),0,1);

$pdf->Cell(50,8,'Toplam Tutar:',0,0);
$pdf->Cell(0,8,number_format($ticket['total_price'],2).' ₺',0,1);

$pdf->Cell(50,8,'Durum:',0,0);
$pdf->Cell(0,8,ucfirst($ticket['status']),0,1);

$pdf->Output('I', 'Bilet_'.$ticket_id.'.pdf');
