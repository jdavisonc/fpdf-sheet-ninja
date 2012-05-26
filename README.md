fpdf-sheet-ninja
================

PHP Library that let you create a simple Sheet (or Table) as PDF to later print it.

This library is a merge of code of http://www.fpdf.org/en/script/script14.php and http://www.fpdf.org/en/script/script3.php to get MultiCells features in the Sheet/Table.
All thanks goes to its Authors.

Features
-----------

* Let you create printable sheet in PDF
* Can add a 'Logo' image
* Enable to set a Page Title
* Enable to set a Footer
* Enable to personalize Font, Size and Style for Title, Header and Footer
* Create the Table with an array of data

Usage
-----------

```php
require_once('FPDF_Sheet.php');

$listAllOrdered = $this->someMethodGetResults();
$list = array();
foreach ($listAllOrdered as $boy) {
	$res = array();
	$res['full_name'] = $boy->getFullName();
	$res['phone'] = $boy->getPhone();
	$list[] = $res;
}

$pdf = new FPDF_Sheet();
$pdf->SetLogo('some/url/logo.jpg');
$pdf->SetTitle('Phones Sheet');

$pdf->AddPage();
$pdf->AddCol('full_name','50%','Full Name');
$pdf->AddCol('phone','50%','Phones', 'C');

// Let you create a custom page footer text, replacing defined variables with their value.
$pdf->SetFooterText('Page {page_num}/{total_pages}');

$pdf->Table($list, array('padding' => 2, 'vert_padding' => 6));
$pdf->Output();
```