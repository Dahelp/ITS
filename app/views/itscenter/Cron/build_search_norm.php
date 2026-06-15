<?php
use ishop\App;

\R::setup('mysql:host=localhost;dbname=DB','USER','PASS');
\R::freeze(true);

function norm_text($s){
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = str_replace(['х','x','*',',','.','-','/',' '], '', $s);
    return $s;
}
function norm_digits($s){
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = str_replace(['х','x','*',',','.','-','/',' '], '', $s);
    return preg_replace('/[^\d]/u', '', $s);
}

$cnt=0;

// product
foreach (\R::getAll("SELECT id,name,article FROM product") as $row){
    $id = (int)$row['id'];
    $name_norm_text   = norm_text($row['name']);
    $name_norm_digits = norm_digits($row['name']);
    $article_norm     = norm_text($row['article']);
    R::exec("UPDATE product SET name_norm_text=?, name_norm_digits=?, article_norm=? WHERE id=?",
        [$name_norm_text,$name_norm_digits,$article_norm,$id]);
    if ((++$cnt % 1000)==0) echo "product updated: $cnt\n";
}

// plagins_cross
$cnt=0;
foreach (\R::getAll("SELECT id,cross_name,cross_abbreviated_name FROM plagins_cross") as $row){
    $id = (int)$row['id'];
    $combo = (string)$row['cross_name'] . (string)$row['cross_abbreviated_name'];
    $cross_norm_text   = norm_text($combo);
    $cross_norm_digits = norm_digits($combo);
    \R::exec("UPDATE plagins_cross SET cross_norm_text=?, cross_norm_digits=? WHERE id=?",
        [$cross_norm_text,$cross_norm_digits,$id]);
    if ((++$cnt % 1000)==0) echo "cross updated: $cnt\n";
}

echo "Done.\n";
