<?php
foreach([44=>true,45=>true,18=>false] as $category=>$multiple) {
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/ChainFilterAjaxFixture.php').' '.$category;
    $output=[];exec($command.' 2>&1',$output,$code);$html=implode("\n",$output);
    if($code||strpos($html,'Warning')!==false)throw new RuntimeException($html);
    $dom=new DOMDocument();@$dom->loadHTML($html);$selects=$dom->getElementsByTagName('select');
    if($selects->length!==2)throw new RuntimeException('Filters not rendered');
    $size=$selects->item(0);
    if($size->hasAttribute('multiple')!==$multiple||$size->getAttribute('name')!==($multiple?'attrs[2][]':'attrs[2]'))throw new RuntimeException('Category size control mismatch');
    if($selects->item(1)->hasAttribute('multiple'))throw new RuntimeException('Other groups changed');
}
echo "Chain filter AJAX: both chain categories support multiple sizes; other categories and groups unchanged OK\n";
