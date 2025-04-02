<?php
$phpword = new \PhpOffice\PhpWord\PhpWord();
$paper = new \PhpOffice\PhpWord\Style\Paper();
$paper->setSize('Letter'); 

$phpword->setDefaultFontName('Times New Roman');
$phpword->setDefaultFontSize(14);

$properties = $phpword->getDocInfo();

$properties->setCreator('EKKA');
$properties->setCompany('Guangdong Yaotai Filter Technology Co.,Ltd.');
$properties->setTitle('Catalog EKKA');
$properties->setDescription('My description');
$properties->setCategory('My category');
$properties->setLastModifiedBy('My name');
$properties->setCreated(mktime(0, 0, 0, 3, 12, 2014));
$properties->setModified(mktime(0, 0, 0, 3, 14, 2014));
$properties->setSubject('My subject');
$properties->setKeywords('my, key, word');

$sectionStyle = array(
					'pageSizeW' => $paper->getWidth(), 
					'pageSizeH' => $paper->getHeight(),
					'orientation' => 'landscape',
					'marginTop' => \PhpOffice\PhpWord\Shared\Converter::pixelToTwip(10),
					'marginLeft' => 600,
				    'marginRight' => 600,
				    'colsNum' => 1,
				    'pageNumberingStart' => 1
				    
				
					);
$sectionStyle2 = array(
					'pageSizeW' => $paper->getWidth(), 
					'pageSizeH' => $paper->getHeight(),
					'orientation' => 'landscape',
					'marginTop' => \PhpOffice\PhpWord\Shared\Converter::pixelToTwip(30),
					'marginLeft' => 600,
				    'marginRight' => 600,
				    'colsNum' => 1,
				    'pageNumberingStart' => 1
				    
					
					);
$section = $phpword->addSection($sectionStyle);
$section->addImage('images/yaotai.jpg');
$section->addText('广东耀泰过滤器科技有限公司');
$section->addText('Guangdong Yaotai Filter Technology Co.,Ltd.');
$section->addTextBreak(1); // перенос строки
//$fontStyle = array('name' => 'Times New Roman', 'size' => 20,'color' => '075776');
//$phpword->addTitleStyle(6,$fontStyle);
//$section->addTitle($cat['name'],6);
//$section->addText(2);

$products = \R::getAll("SELECT * FROM product WHERE model IN ('EK-1019', 'EK-1020', 'EK-1039', 'EK-1044', 'EK-1081', 'EK-1084', 'EK-1098', 'EK-1105', 'EK-1126', 'EK-1812', 'EK-2031', 'EK-1009', 'EK-4836', 'EK-4864', 'EK-4874', 'EK-4888', 'EK-6025', 'EK-4049', 'EK-4080', 'EK-4610', 'EK-3015AB', 'EK-3024AB', 'EK-3044AB', 'EK-3046AB', 'EK-3052AB', 'EK-3091AB', 'EK-3546AB', 'EK-3547AB', 'EK-3700AB', 'EK-3802AB', 'EK-3803AB', 'EK-3804AB', 'EK-5011', 'EK-5020', 'EK-5020', 'EK-3027A')");

foreach($products as $product){
	
	
$styleTable = array('borderSize' => 6, 'borderColor' => '999999');
      $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
      $cellRowContinue = array('vMerge' => 'continue');
      $cellColSpan2 = array('gridSpan' => 2, 'valign' => 'center');
      $cellColSpan3 = array('gridSpan' => 3, 'valign' => 'center');
       
      $cellHCentered = array('align' => 'center');
      $cellVCentered = array('valign' => 'center');
 
$section2 = $phpword->addSection($sectionStyle2);

    $table = $section2->addTable('Colspan Rowspan');	  
    $table->addRow();
	$call1 = $table->addCell(6000)->addTable('Colspan Rowspan');
	$call2 = $table->addCell(4000)->addImage('images/product/baseimg/'.$product['img'].'');
  
	// группа аттрибутов товаров
$attribute_group = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id WHERE product_attribute.product_id = ? GROUP BY attribute.attribute_group_id", [$product["id"]]);
foreach($attribute_group as $group){
		
	$call1->addRow(null, array('tblHeader' => true));
    $call1->addCell(2000, $cellColSpan2)->addText(''.$group["attribute_name"].'', array('bold' => true), $cellHCentered);
	$attributs = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_id = attribute.id WHERE product_attribute.product_id = ? ORDER BY attribute_position", [$product["id"]]);
	
	$call1->addRow();
		$call1->addCell(6000, $cellVCentered)->addText('Model', null, $cellHCentered);
		$call1->addCell(2000, $cellVCentered)->addText(''.$product['model'].'', null, $cellHCentered);
	
	foreach($attributs as $att) {
		// аттрибуты товаров		
		
		$call1->addRow();
		$call1->addCell(6000, $cellVCentered)->addText(''.$att["attribute_name"].'', null, $cellHCentered);
		$call1->addCell(2000, $cellVCentered)->addText(''.$att["attribute_text"].'', null, $cellHCentered);
		
	}
	
}
	  
}     
$footer = $section->addFooter();
$footer->addPreserveText('广 东 耀 泰 过 滤 器 科 技 有 限 公 司 Guangdong Yaotai Filter Technology Co.,Ltd.', null, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpword,'Word2007');
$objWriter->save('doc.docx');	