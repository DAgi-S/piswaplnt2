<?php    
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'core.php';
require_once 'Num2TextEnglish.php';
$orderId = $_POST['orderId'];
// Use prepared statement for security

$sql = "SELECT order_date, client_name, client_contact, sub_total, vat, total_amount, grand_total, gstn, fsnum,
        withholding_tax_enabled, withholding_tax_amount 
        FROM orders WHERE order_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();
$orderData = $orderResult->fetch_array();

// Safely get order data with null coalescing operator
$orderDate = $orderData[0] ?? '';
$clientName = $orderData[1] ?? '';
$clientContact = $orderData[2] ?? ''; 
$subTotal = $orderData[3] ?? 0;
$vat = $orderData[4] ?? 0;
$totalAmount = $orderData[5] ?? 0; 
$grandTotal = $orderData[6] ?? 0;
$gstn = $orderData[7] ?? '';
$FSNum = $orderData[8] ?? '';
$withholding_enabled = $orderData[9] ?? 0;
$withholding_amount = $orderData[10] ?? 0;

// Initialize variables used in calculations
$payment_place = 1; // Default value if not set
$cgst = 0;
$igst = 0;

// Initialize table variable
$table = '';

function numtowords($num){ 
   $decones = array( 
               '01' => "አንድ", 
               '02' => "ሁለት", 
               '03' => "ሶስት", 
               '04' => "አራት", 
               '05' => "አምስት", 
               '06' => "ስድስት", 
               '07' => "ሰባት", 
               '08' => "ስምንት", 
               '09' => "ዘጠኝ", 
               10 => "አስር", 
               11 => "አስራ አንድ", 
               12 => "አስራ ሁለት", 
               13 => "አስራ ሶስት", 
               14 => "አስራ አራት", 
               15 => "አስራ አምስት", 
               16 => "አስራ ስድስት", 
               17 => "አስራ ሰባት", 
               18 => "አስራ ስምንት", 
               19 => "አስራ ዘጠኝ" 
               );
   $ones = array( 
               0 => "",
               1 => "አንድ",     
               2 => "ሁለት", 
               3 => "ሶስት", 
               4 => "አራት", 
               5 => "አምስት", 
               6 => "ስድስት", 
               7 => "ሰባት", 
               8 => "ስምንት", 
               9 => "ዘጠኝ", 
               10 => "አስር", 
               11 => "አስራ አንድ", 
               12 => "አስራ ሁለት", 
               13 => "አስራ ሶስት", 
               14 => "አስራ አራት", 
               15 => "አስራ አምስት", 
               16 => "አስራ ስድስት", 
               17 => "አስራ ሰባት", 
               18 => "አስራ ስምንት", 
               19 => "አስራ ዘጠኝ" 
               ); 
   $tens = array( 
               0 => "",
               2 => "ሃያ", 
               3 => "ሰላሳ", 
               4 => "አርባ", 
               5 => "ሃምሳ", 
               6 => "ስልሳ", 
               7 => "ሰባ", 
               8 => "ሰማንያ", 
               9 => "ዘጠና" 
               ); 
   $hundreds = array( 
               "መቶ", 
               "ሺህ", 
               "ሚሊዮን", 
               "ቢሊዮን", 
               "ትሪሊዮን", 
               "ኳድሪሊዮን" 
               );

   $num = number_format($num,2,".",","); 
   $num_arr = explode(".",$num); 
   $wholenum = $num_arr[0]; 
   $decnum = $num_arr[1]; 
   $whole_arr = array_reverse(explode(",",$wholenum)); 
   krsort($whole_arr); 
   $rettxt = ""; 

   foreach($whole_arr as $key => $i){ 
       // Convert string to number to handle leading zeros
       $i = (int)$i;
       
       if($i < 20){ 
           if(isset($ones[$i])) {
               $rettxt .= $ones[$i];
           }
       }
       elseif($i < 100){ 
           $firstDigit = floor($i/10);
           $secondDigit = $i % 10;
           
           if(isset($tens[$firstDigit])) {
               $rettxt .= $tens[$firstDigit];
           }
           if($secondDigit > 0 && isset($ones[$secondDigit])) {
               $rettxt .= " ".$ones[$secondDigit];
           }
       }
       else{ 
           $firstDigit = floor($i/100);
           $remainder = $i % 100;
           
           if(isset($ones[$firstDigit])) {
               $rettxt .= $ones[$firstDigit]." ".$hundreds[0];
           }
           
           if($remainder > 0) {
               if($remainder < 20) {
                   if(isset($ones[$remainder])) {
                       $rettxt .= " ".$ones[$remainder];
                   }
               } else {
                   $secondDigit = floor($remainder/10);
                   $thirdDigit = $remainder % 10;
                   
                   if(isset($tens[$secondDigit])) {
                       $rettxt .= " ".$tens[$secondDigit];
                   }
                   if($thirdDigit > 0 && isset($ones[$thirdDigit])) {
                       $rettxt .= " ".$ones[$thirdDigit];
                   }
               }
           }
       } 
       
       if($i != 0) {
           if($key > 0 && isset($hundreds[$key])){ 
               $rettxt .= " ".$hundreds[$key]." "; 
           }
       }
   } 
   
   $rettxt = trim($rettxt);
   $rettxt .= " ብር";
   
   if($decnum > 0){ 
       $rettxt .= " ከ "; 
       if($decnum < 20){ 
           $rettxt .= $decones[str_pad($decnum, 2, '0', STR_PAD_LEFT)];
       }
       elseif($decnum < 100){ 
           $firstDigit = floor($decnum/10);
           $secondDigit = $decnum % 10;
           
           if(isset($tens[$firstDigit])) {
               $rettxt .= $tens[$firstDigit];
           }
           if($secondDigit > 0 && isset($ones[$secondDigit])) {
               $rettxt .= " ".$ones[$secondDigit];
           }
       }
       $rettxt .= " ሳንቲም"; 
   } 
   return $rettxt;
} 
function engnumtowords($num){ 
   $decones = array( 
               '01' => "One", 
               '02' => "Two", 
               '03' => "Three", 
               '04' => "Four", 
               '05' => "Five", 
               '06' => "Six", 
               '07' => "Seven", 
               '08' => "Eight", 
               '09' => "Nine", 
               10 => "Ten", 
               11 => "Eleven", 
               12 => "Twelve", 
               13 => "Thirteen", 
               14 => "Fourteen", 
               15 => "Fifteen", 
               16 => "Sixteen", 
               17 => "Seventeen", 
               18 => "Eighteen", 
               19 => "Nineteen" 
               );
   $ones = array( 
               0 => "",
               1 => "One",     
               2 => "Two", 
               3 => "Three", 
               4 => "Four", 
               5 => "Five", 
               6 => "Six", 
               7 => "Seven", 
               8 => "Eight", 
               9 => "Nine", 
               10 => "Ten", 
               11 => "Eleven", 
               12 => "Twelve", 
               13 => "Thirteen", 
               14 => "Fourteen", 
               15 => "Fifteen", 
               16 => "Sixteen", 
               17 => "Seventeen", 
               18 => "Eighteen", 
               19 => "Nineteen" 
               ); 
   $tens = array( 
               0 => "",
               2 => "Twenty", 
               3 => "Thirty", 
               4 => "Forty", 
               5 => "Fifty", 
               6 => "Sixty", 
               7 => "Seventy", 
               8 => "Eighty", 
               9 => "Ninety" 
               ); 
   $hundreds = array( 
               "Hundred", 
               "Thousand", 
               "Million", 
               "Billion", 
               "Trillion", 
               "Quadrillion" 
               );

   $num = number_format($num,2,".",","); 
   $num_arr = explode(".",$num); 
   $wholenum = $num_arr[0]; 
   $decnum = $num_arr[1]; 
   $whole_arr = array_reverse(explode(",",$wholenum)); 
   krsort($whole_arr); 
   $rettxt = ""; 

   foreach($whole_arr as $key => $i){ 
       // Convert string to number to handle leading zeros
       $i = (int)$i;
       
       if($i < 20){ 
           if(isset($ones[$i])) {
               $rettxt .= $ones[$i];
           }
       }
       elseif($i < 100){ 
           $firstDigit = floor($i/10);
           $secondDigit = $i % 10;
           
           if(isset($tens[$firstDigit])) {
               $rettxt .= $tens[$firstDigit];
           }
           if($secondDigit > 0 && isset($ones[$secondDigit])) {
               $rettxt .= " ".$ones[$secondDigit];
           }
       }
       else{ 
           $firstDigit = floor($i/100);
           $remainder = $i % 100;
           
           if(isset($ones[$firstDigit])) {
               $rettxt .= $ones[$firstDigit]." ".$hundreds[0];
           }
           
           if($remainder > 0) {
               if($remainder < 20) {
                   if(isset($ones[$remainder])) {
                       $rettxt .= " and ".$ones[$remainder];
                   }
               } else {
                   $secondDigit = floor($remainder/10);
                   $thirdDigit = $remainder % 10;
                   
                   if(isset($tens[$secondDigit])) {
                       $rettxt .= " ".$tens[$secondDigit];
                   }
                   if($thirdDigit > 0 && isset($ones[$thirdDigit])) {
                       $rettxt .= " ".$ones[$thirdDigit];
                   }
               }
           }
       } 
       
       if($i != 0) {
           if($key > 0 && isset($hundreds[$key])){ 
               $rettxt .= " ".$hundreds[$key]." "; 
           }
       }
   } 
   
   $rettxt = trim($rettxt);
   $rettxt .= " Birr";
   
   if($decnum > 0){ 
       $rettxt .= " and "; 
       if($decnum < 20){ 
           $rettxt .= $decones[str_pad($decnum, 2, '0', STR_PAD_LEFT)];
       }
       elseif($decnum < 100){ 
           $firstDigit = floor($decnum/10);
           $secondDigit = $decnum % 10;
           
           if(isset($tens[$firstDigit])) {
               $rettxt .= $tens[$firstDigit];
           }
           if($secondDigit > 0 && isset($ones[$secondDigit])) {
               $rettxt .= " ".$ones[$secondDigit];
           }
       }
       $rettxt .= " cents"; 
   } 
   return $rettxt;
} 
   
   $text = numtowords($grandTotal); 
   $texteng = engnumtowords($grandTotal); 
// $num = $grandTotal;  
// $text =  $num_to_text->convertNumber($num); 


// Debug database connection
if ($connect->connect_error) {
    error_log("Connection failed: " . $connect->connect_error);
    die("Database connection failed");
}

// Verify order ID
if (!isset($orderId) || !is_numeric($orderId)) {
    error_log("Invalid order ID: " . print_r($orderId, true));
    die("Invalid order ID provided");
}

// Test if order_items table exists
$testSql = "SHOW TABLES LIKE 'order_items'";
$result = $connect->query($testSql);
if ($result->num_rows == 0) {
    error_log("order_items table does not exist");
    die("Required database table 'order_items' is missing");
}

// Updated query to get specific fields with INNER JOIN to ensure only valid products
$orderItemSql = "SELECT oi.quantity, oi.rate, oi.total, 
                 p.name as product_name,
                 p.product_id
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.product_id 
                 WHERE oi.order_id = ? AND p.status = 'active'";

try {
    $itemStmt = $connect->prepare($orderItemSql);
    if (!$itemStmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }
    
    $itemStmt->bind_param("i", $orderId);
    if (!$itemStmt->execute()) {
        throw new Exception("Execute failed: " . $itemStmt->error);
    }
    
    $orderItemResult = $itemStmt->get_result();
    
    // Debug
    error_log("Number of items found: " . $orderItemResult->num_rows);
    
    $x = 1;
    while($row = $orderItemResult->fetch_assoc()) {
        $table .= '<tr>
            <td>' . $x . '</td>
            <td>' . htmlspecialchars($row['product_name']) . '</td>
            <td>' . number_format($row['rate'], 2) . ' ETB</td>
            <td>' . $row['quantity'] . '</td>
            <td>' . number_format($row['total'], 2) . ' ETB</td>
        </tr>';
        $x++;
    }
    
    // Add totals rows
    $table .= '
        <tr>
            <td colspan="3"></td>
            <td style="font-weight:bold">ጠቅላላ<br>Total</td>
            <td>' . number_format($subTotal, 2) . ' ETB</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td style="font-weight:bold">ቫት 15%<br>VAT 15%</td>
            <td>' . number_format($vat, 2) . ' ETB</td>
        </tr>';

    // Add withholding tax row if enabled
    if ($withholding_enabled) {
        $table .= '
        <tr>
            <td colspan="3"></td>
            <td style="font-weight:bold">ዊዝሆልዲንግ 2%<br>Withholding 2%</td>
            <td>(' . number_format($withholding_amount, 2) . ') ETB</td>
        </tr>';
    }

    $table .= '
        <tr>
            <td colspan="3"></td>
            <td style="font-weight:bold">አጠቃላይ ድምር<br>Grand Total</td>
            <td>' . number_format($grandTotal, 2) . ' ETB</td>
        </tr>
        </tbody>
    </table>';

} catch (Exception $e) {
    error_log("Error in order items query: " . $e->getMessage());
    die("An error occurred while retrieving order items. Please check the error log.");
}

// Debug - print the orderId being used
error_log("Processing order ID: " . $orderId);

// Debug - print the SQL query
error_log("SQL Query: " . $orderItemSql);

// Debug - print the number of rows returned
if (isset($orderItemResult)) {
    error_log("Number of rows found: " . $orderItemResult->num_rows);
}

// Create a new variable for the HTML template
$htmlTemplate = '<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <style>
        .invoice-header { margin-bottom: 30px; }
        .company-info { text-align: right; margin-bottom: 20px; }
        .buyer-info { margin-bottom: 30px; }
        .invoice-title { 
            text-align: center;
            color: #204ba0;
            font-size: 24px;
            margin: 20px 0;
            text-decoration: underline;
        }
        .table th, .table td {
            padding: 8px;
            vertical-align: middle;
        }
        .totals-section {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .amount-in-words {
            margin-top: 20px;
            font-weight: bold;
        }
        .signature-section {
            position: fixed;
            bottom: 50px;  /* Adjusted to give more space at bottom */
            left: 0;
            width: 100%;
            text-align: center;
            background-color: white;
            padding: 20px;
            z-index: 1000;
        }
        
        .signature-section img {
            /* Removed width constraint to keep original size */
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        .signature-text {
            margin-top: 5px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Add padding to main content to prevent overlap */
        .container {
            padding-bottom: 250px;  /* Increased padding to accommodate larger signature */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row invoice-header">
            <div class="col-6">
                <img src="logo.png" alt="logo" width="200px">
            </div>
            <div class="col-6 company-info">
                <p>Date: '.$orderDate.'<br>
                Attachment Number: '.$orderId.'<br>
                <b>Lebawi Net Trading plc</b><br>
                <b>TIN:</b> 0073012029<br>
                <b>www.lebawi.net</b></p>
            </div>
        </div>

        <div class="buyer-info">
            <h5>Prepared for:</h5>
            <p>FS No: '.$FSNum.'<br>
            Date: '.$orderDate.'<br>
            Buyers Name: '.$clientName.'<br>
            Phone: '.$clientContact.'<br>
            TIN: '.$gstn.'</p>
        </div>

        <h4 class="invoice-title">Cash Sales Invoice Attachment</h4>

        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th style="width: 5%">ተቁ<br>NO</th>
                    <th style="width: 40%">ዝርዝር<br>Description</th>
                    <th style="width: 15%">ነጠላ ዋጋ<br>Unit Price</th>
                    <th style="width: 15%">ብዛት<br>Quantity</th>
                    <th style="width: 25%">መጠን<br>Amount</th>
                </tr>
            </thead>
            <tbody>';

// Insert the order items table into the template where needed
$htmlTemplate = str_replace('<tbody>', '<tbody>' . $table, $htmlTemplate);

$htmlTemplate .= '
        <div class="amount-in-words">
            <p>በፊደል: <u>'.$text.'</u></p>
            <p>In Word: <u>'.$texteng.'</u></p>
        </div>
    </div>
    
    <div class="signature-section">
        <img src="./uploads/signature.png" alt="Signature">
    </div>
</body>
</html>';

// Output the complete HTML
echo $htmlTemplate;

$connect->close();

// For debugging - let's check if the file exists
$signaturePath = "./assests/images/signature.png";
if (!file_exists($signaturePath)) {
    error_log("Signature file not found at: " . $signaturePath);
}

