<?php
namespace app\services;

final class CompanyCard
{
    public static function bytes(array $fields, array $context): string
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $doc=new \PhpOffice\PhpWord\PhpWord();
        $doc->setDefaultFontName('Arial');$doc->setDefaultFontSize(10);
        $doc->addTitleStyle(0,['name'=>'Arial','size'=>20,'bold'=>true,'color'=>'000000'],['spaceAfter'=>160]);
        $doc->addTitleStyle(1,['name'=>'Arial','size'=>12,'bold'=>true,'color'=>'000000'],['spaceBefore'=>180,'spaceAfter'=>100,'keepNext'=>true]);
        $section=$doc->addSection(['pageSizeW'=>11906,'pageSizeH'=>16838,'marginTop'=>1000,'marginBottom'=>1000,'marginLeft'=>1100,'marginRight'=>1100]);
        $section->addTitle('Карточка организации',0);
        $section->addText('Реквизиты покупателя для оформления документов и передачи в бухгалтерию.',[],['spaceAfter'=>100]);
        $caption=!empty($context['number']) ? 'Заказ № '.$context['number'].' от '.($context['date']??'') : 'Дата формирования '.($context['date']??date('d.m.Y'));
        $section->addText($caption,['size'=>10],['spaceAfter'=>180]);
        $tableStyle=['borderSize'=>4,'borderColor'=>'D9DFE5','cellMargin'=>100,'layout'=>'fixed','width'=>9706,'unit'=>'dxa'];
        $write=function($title,$rows)use($section,$tableStyle){
            $section->addTitle($title,1);$table=$section->addTable($tableStyle);
            foreach($rows as$label=>$value){$table->addRow(null,['cantSplit'=>true]);$table->addCell(3000,['bgColor'=>'F5F6F8','valign'=>'top'])->addText((string)$label,['bold'=>true],['spaceAfter'=>30]);$table->addCell(6706,['valign'=>'top'])->addText($value!==''?(string)$value:'Не указано',[],['spaceAfter'=>30]);}
        };
        $legal=[];$bank=[];
        foreach(CheckoutCompany::LABELS as$key=>$label){if(in_array($key,['bank','bik','raschet','korschet'],true))$bank[$label]=$fields[$key]??'';else$legal[$label]=$fields[$key]??'';}
        $write('Юридические реквизиты',$legal);
        $write('Банковские реквизиты',$bank);
        $write('Контактные данные',['Контактное лицо'=>(string)($context['name']??''),'Телефон'=>(string)($context['phone']??''),'Электронная почта'=>(string)($context['email']??'')]);
        if (isset(CheckoutCompany::PURPOSES[$fields['purchase_purpose']??''])) {
            $section->addTitle('Цель покупки и маркировка',1);
            $section->addText(CheckoutCompany::PURPOSES[$fields['purchase_purpose']],[],['spaceAfter'=>120]);
        }
        if(!empty($context['has_attachment']))$section->addText('К письму также приложен файл с реквизитами покупателя. Неуказанные сведения следует уточнить по этому файлу.', ['size'=>9,'color'=>'526174']);
        $tmp=tempnam(sys_get_temp_dir(),'company-card-');
        if($tmp===false)throw new \RuntimeException('Cannot create company card');
        try{\PhpOffice\PhpWord\IOFactory::createWriter($doc,'Word2007')->save($tmp);$bytes=file_get_contents($tmp);if($bytes===false)throw new \RuntimeException('Cannot read company card');return $bytes;}finally{if(is_file($tmp))unlink($tmp);}
    }

    public static function download(array $fields, array $context, string $suffix=''): void
    {
        $bytes=self::bytes($fields,$context);
        $safe=preg_replace('/[^A-Za-z0-9_-]/','',$suffix);
        $filename='Карточка организации'.($safe!==''?' '.$safe:'').'.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="company-card'.($safe!==''?'-'.$safe:'').'.docx"; filename*=UTF-8\'\''.rawurlencode($filename));
        header('Content-Length: '.strlen($bytes));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
        exit;
    }
}
