<?

function ExportToExcel($con, $sql, $filename)
{
    header('Content-Type: application/x-msexcel; charset=utf-8');
    header('Content-Language:Russian');
    header("Cache-control: private");
    header("Content-Disposition: filename=" . $filename . ".xls");
    echo "
<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">
<head>
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Export</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
</head>
<body>
<table border=0>
<tr><td>$filename</td><td></td></tr>
<tr><td>Date:</td><td>" . date("r") . "</td></tr>
<tr><td>User:</td><td>" . $_SESSION['ad_login'] . "</td></tr>
</table><br>
";
    $q = $con->exec($sql);
    echo "<table border=1>\n";
    echo "<tr>";
    for ($i = 0; $i < $q->num_fields(); $i++) {
        echo "<td><b>" . $q->field_name($i) . "</b></td>";
    };
    echo "</tr>\n";
    while ($r = $q->fetch()) {
        echo "<tr><td>" . implode("</td><td>", $r) . "</td></tr>";
    };
    echo "</table>\n";
    echo "</body></html>";
}
