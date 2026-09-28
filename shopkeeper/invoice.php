<?php

session_start();

/* =========================================================
   SHOPKEEPER AUTHENTICATION
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'shopkeeper'
) {
    header("Location: ../login.php");
    exit();
}


/* =========================================================
   DATABASE AND FPDF
========================================================= */

include '../config/database.php';

require_once '../vendor/autoload.php';


/* =========================================================
   VALIDATE PAYMENT ID
========================================================= */

if (
    !isset($_GET['payment_id']) ||
    !ctype_digit($_GET['payment_id'])
) {
    die("Invalid payment ID.");
}

$payment_id = (int) $_GET['payment_id'];
$shopkeeper_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET PAYMENT DETAILS
========================================================= */

$payment_sql = "
    SELECT
        payment_id,
        shopkeeper_id,
        amount,
        payment_method,
        payment_status,
        transaction_id,
        payment_date

    FROM payments

    WHERE
        payment_id = ?
        AND shopkeeper_id = ?

    LIMIT 1
";

$payment_stmt = $conn->prepare($payment_sql);

if (!$payment_stmt) {
    die("Payment query error: " . $conn->error);
}

$payment_stmt->bind_param(
    "ii",
    $payment_id,
    $shopkeeper_id
);

$payment_stmt->execute();

$payment_result = $payment_stmt->get_result();

if ($payment_result->num_rows === 0) {
    die("Transaction not found.");
}

$payment = $payment_result->fetch_assoc();


/* =========================================================
   GET PURCHASED ITEMS
========================================================= */

$items_sql = "
    SELECT
        products.product_name,
        products.price AS unit_price,
        orders.quantity,
        orders.total_amount

    FROM orders

    INNER JOIN products
        ON orders.product_id = products.product_id

    WHERE
        orders.payment_id = ?
        AND orders.shopkeeper_id = ?

    ORDER BY
        orders.order_id ASC
";

$items_stmt = $conn->prepare($items_sql);

if (!$items_stmt) {
    die("Items query error: " . $conn->error);
}

$items_stmt->bind_param(
    "ii",
    $payment_id,
    $shopkeeper_id
);

$items_stmt->execute();

$items_result = $items_stmt->get_result();


/* =========================================================
   PREPARE INVOICE VALUES
========================================================= */

$invoice_year = date(
    'Y',
    strtotime($payment['payment_date'])
);

$invoice_number =
    'INV-' .
    $invoice_year .
    '-' .
    str_pad(
        (string) $payment_id,
        6,
        '0',
        STR_PAD_LEFT
    );

$transaction_id = $payment['transaction_id'];

$payment_method = $payment['payment_method'];

$payment_status = $payment['payment_status'];

$payment_date = date(
    'd M Y, h:i A',
    strtotime($payment['payment_date'])
);

$grand_total = (float) $payment['amount'];

$shopkeeper_name =
    $_SESSION['name'] ?? 'Shopkeeper';


/* =========================================================
   CUSTOM PDF CLASS
========================================================= */

class InvoicePDF extends FPDF
{
    public function Header()
    {
        $this->SetFont(
            'Arial',
            'B',
            18
        );

        $this->Cell(
            0,
            10,
            'INVENTORY MANAGEMENT SYSTEM',
            0,
            1,
            'C'
        );

        $this->SetFont(
            'Arial',
            'B',
            13
        );

        $this->Cell(
            0,
            8,
            'SALES INVOICE',
            0,
            1,
            'C'
        );

        $this->Ln(4);

        $this->SetDrawColor(
            80,
            80,
            80
        );

        $this->Line(
            10,
            $this->GetY(),
            200,
            $this->GetY()
        );

        $this->Ln(7);
    }


    public function Footer()
    {
        $this->SetY(-22);

        $this->SetDrawColor(
            190,
            190,
            190
        );

        $this->Line(
            10,
            $this->GetY(),
            200,
            $this->GetY()
        );

        $this->Ln(5);

        $this->SetFont(
            'Arial',
            'I',
            9
        );

        $this->SetTextColor(
            90,
            90,
            90
        );

        $this->Cell(
            0,
            5,
            'Thank you for your purchase.',
            0,
            1,
            'C'
        );

        $this->Cell(
            0,
            5,
            'Page ' . $this->PageNo(),
            0,
            0,
            'C'
        );
    }
}


/* =========================================================
   CREATE PDF
========================================================= */

$pdf = new InvoicePDF(
    'P',
    'mm',
    'A4'
);

$pdf->SetMargins(
    10,
    15,
    10
);

$pdf->SetAutoPageBreak(
    true,
    28
);

$pdf->AddPage();


/* =========================================================
   INVOICE AND TRANSACTION INFORMATION
========================================================= */

$pdf->SetTextColor(
    0,
    0,
    0
);

$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    40,
    8,
    'Invoice Number:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $invoice_number,
    0,
    1
);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    40,
    8,
    'Transaction ID:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $transaction_id,
    0,
    1
);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    40,
    8,
    'Payment Date:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $payment_date,
    0,
    1
);

$pdf->Ln(5);


/* =========================================================
   CUSTOMER INFORMATION
========================================================= */

$pdf->SetFillColor(
    235,
    240,
    247
);

$pdf->SetFont(
    'Arial',
    'B',
    12
);

$pdf->Cell(
    0,
    9,
    'CUSTOMER INFORMATION',
    0,
    1,
    'L',
    true
);

$pdf->Ln(3);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    40,
    8,
    'Customer Name:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $shopkeeper_name,
    0,
    1
);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    40,
    8,
    'Customer ID:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    '#' . $shopkeeper_id,
    0,
    1
);

$pdf->Ln(6);


/* =========================================================
   ITEMS SECTION TITLE
========================================================= */

$pdf->SetFillColor(
    235,
    240,
    247
);

$pdf->SetFont(
    'Arial',
    'B',
    12
);

$pdf->Cell(
    0,
    9,
    'PURCHASED ITEMS',
    0,
    1,
    'L',
    true
);

$pdf->Ln(4);


/* =========================================================
   ITEMS TABLE HEADER
========================================================= */

$pdf->SetFillColor(
    45,
    65,
    90
);

$pdf->SetTextColor(
    255,
    255,
    255
);

$pdf->SetFont(
    'Arial',
    'B',
    10
);

$pdf->Cell(
    82,
    10,
    'Product',
    1,
    0,
    'L',
    true
);

$pdf->Cell(
    25,
    10,
    'Quantity',
    1,
    0,
    'C',
    true
);

$pdf->Cell(
    38,
    10,
    'Unit Price',
    1,
    0,
    'R',
    true
);

$pdf->Cell(
    45,
    10,
    'Total',
    1,
    1,
    'R',
    true
);


/* =========================================================
   ITEMS TABLE DATA
========================================================= */

$pdf->SetTextColor(
    0,
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    10
);

if ($items_result->num_rows > 0) {

    while ($item = $items_result->fetch_assoc()) {

        $product_name = $item['product_name'];

        /*
         * Prevent very long product names from
         * overflowing outside the table.
         */
        if (strlen($product_name) > 37) {
            $product_name =
                substr(
                    $product_name,
                    0,
                    34
                ) . '...';
        }

        $quantity =
            (int) $item['quantity'];

        $unit_price =
            (float) $item['unit_price'];

        $item_total =
            (float) $item['total_amount'];


        $pdf->Cell(
            82,
            10,
            $product_name,
            1,
            0,
            'L'
        );

        $pdf->Cell(
            25,
            10,
            $quantity,
            1,
            0,
            'C'
        );

        $pdf->Cell(
            38,
            10,
            'Rs. ' . number_format(
                $unit_price,
                2
            ),
            1,
            0,
            'R'
        );

        $pdf->Cell(
            45,
            10,
            'Rs. ' . number_format(
                $item_total,
                2
            ),
            1,
            1,
            'R'
        );
    }

} else {

    $pdf->Cell(
        190,
        12,
        'No purchased items found.',
        1,
        1,
        'C'
    );
}


/* =========================================================
   GRAND TOTAL
========================================================= */

$pdf->Ln(6);

$pdf->SetFont(
    'Arial',
    'B',
    12
);

$pdf->Cell(
    145,
    10,
    'Grand Total',
    1,
    0,
    'R'
);

$pdf->SetFillColor(
    235,
    240,
    247
);

$pdf->Cell(
    45,
    10,
    'Rs. ' . number_format(
        $grand_total,
        2
    ),
    1,
    1,
    'R',
    true
);

$pdf->Ln(7);


/* =========================================================
   PAYMENT INFORMATION
========================================================= */

$pdf->SetFillColor(
    235,
    240,
    247
);

$pdf->SetFont(
    'Arial',
    'B',
    12
);

$pdf->Cell(
    0,
    9,
    'PAYMENT INFORMATION',
    0,
    1,
    'L',
    true
);

$pdf->Ln(3);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    45,
    8,
    'Payment Method:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $payment_method,
    0,
    1
);


$pdf->SetFont(
    'Arial',
    'B',
    11
);

$pdf->Cell(
    45,
    8,
    'Payment Status:',
    0,
    0
);

$pdf->SetFont(
    'Arial',
    '',
    11
);

$pdf->Cell(
    0,
    8,
    $payment_status,
    0,
    1
);


/* =========================================================
   DOWNLOAD PDF
========================================================= */

$file_name =
    $invoice_number . '.pdf';

$pdf->Output(
    'D',
    $file_name
);

exit();

?>