<?php

require_once '../vendor/phpoffice/phpword/bootstrap.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
     $section = $phpWord->addSection();
     $header = array('size' => 16, 'bold' => true);
     $styleTable = array('borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80, 'width' => '100%');
     $styleFirstRow = array('borderBottomSize' => 18, 'borderBottomColor' => '0000FF', 'bgColor' => '66BBFF');
     $styleCell = array('align' => 'center');
     $fontStyle = array('bold' => true, 'align' => 'center');
     $section->addText("Карточка предприятия ".$comp["comp_name"]."", $header);
     $phpWord->addTableStyle('Table', $styleTable);
     $table = $section->addTable('Table');


		if($comp["nds"] == 1) { $nds = "с НДС"; }
		if($comp["nds"] == 2) { $nds = "без НДС"; }
		if($comp["dogovor"] == 1) { $dogovor = "Договор"; }
		if($comp["dogovor"] == 2) { $dogovor = "Счёт-договор"; }
		
		$table->addRow();
			$table->addCell(9000)->addText("Полное наименование предприятия");
			$table->addCell(9000)->addText("".$comp["comp_name"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Краткое наименование предприятия");
			$table->addCell(9000)->addText("".$comp["comp_short_name"]."");
		$table->addRow();
			$table->addCell(9000)->addText("ИНН");
			$table->addCell(9000)->addText("".$comp["inn"]."");
		$table->addRow();
			$table->addCell(9000)->addText("КПП");
			$table->addCell(9000)->addText("".$comp["kpp"]."");
		$table->addRow();
			$table->addCell(9000)->addText("ОГРН(ОГРНИП)");
			$table->addCell(9000)->addText("".$comp["ogrn"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Юридический адрес");
			$table->addCell(9000)->addText("".$comp["url_address"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Почтовый адрес");
			$table->addCell(9000)->addText("".$comp["postal_address"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Контактный телефон");
			$table->addCell(9000)->addText("".$user["telefon"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Электронная почта");
			$table->addCell(9000)->addText("".$user["email"]."");		
		$table->addRow();
			$table->addCell(9000)->addText("Банк");
			$table->addCell(9000)->addText("".$comp["bank"]."");
		$table->addRow();
			$table->addCell(9000)->addText("БИК");
			$table->addCell(9000)->addText("".$comp["bik"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Расчетный счет");
			$table->addCell(9000)->addText("".$comp["raschet"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Корреспондентский счет");
			$table->addCell(9000)->addText("".$comp["korschet"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Генеральный директор");
			$table->addCell(9000)->addText("".$comp["dir_name"]."");
		$table->addRow();
			$table->addCell(9000)->addText("Система налогообложения");
			$table->addCell(9000)->addText("".$nds."");
		$table->addRow();
			$table->addCell(9000)->addText("Условия поставки");
			$table->addCell(9000)->addText("".$dogovor."");
	
 
// (D) OR FORCE DOWNLOAD
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment;filename=\"Реквизиты ".$comp["comp_name"].".docx\"");
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "Word2007");
ob_clean();
$objWriter->save("php://output");
exit();
