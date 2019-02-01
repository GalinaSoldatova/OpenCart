<html dir="ltr" lang="ru">

<head>
    <meta charset="UTF-8">  

</head>
<body> 


<?php

//die('Ведутся технические работы');


if (!isset($_POST['password'])) {
    die('Вы пытаетесь перейти по прямой ссылке. Осуществите переход через CMS.');
}

if ($_POST['password'] !== 'admin') {
    die('Вы ввели неверный пароль!');
}


//v02   
//=====================================================================================
$sm_base= '../importXLS/';
$module_url= '../importXLS/_action.php';

$link = mysql_connect('сервер', 'пользователь', 'пароль');
mysql_select_db('БД', $link);

/********CONFIG**********/
$template = 1;
$catRoot = 74;

/********CONFIG**********/


$sql= "SELECT * FROM `oc_option_description` AS od LIMIT 100";


$symmaryTV = '';
$i =2;

$symmaryTV .= '<select class="pxselectlistdefaulttv" style="display: none">';
$symmaryTV .= '<option class="" data-type="NULL"  data-tvid="NULL">Не используется</option>';
$symmaryTV .= '<option class="" data-type="tv"  data-tvid="PGT">Название (PGT)</option>';
$symmaryTV .= '<option class="" data-type="tv"  data-tvid="CONTENT">Опиcание</option>';
$symmaryTV .= '<option class="" data-type="tv"  data-tvid="ART">Артикул / модель</option>';
$symmaryTV .= '<option class="" data-type="tv"  data-tvid="QUANTITY">Количество</option>';
$symmaryTV .= '<option class="" data-type="tv"  data-tvid="PRICE">Стоимость</option>';

/*
if (mysql_num_rows($result = mysql_query($sql)) > 0) {
    echo mysql_error();
    while ($row = mysql_fetch_assoc($result)) {
        if ($i > 13) $i =0;
        $symmaryTV .= '<option class="" data-type="tv" data-tvid="'.$row['option_id'].'">'.$row['name'].'</option>';
        $i++;
    }
}
*/

$symmaryTV .='</select>';



if ($result = mysql_query('SHOW TABLES LIKE "%_site_content_redo_%"')){
    if(mysql_num_rows($result)) { 
          $dooReDo  = '<div class="dooRedo">Откатить базу на шаг</div>';
    }
}


?>




<link rel="stylesheet" type="text/css" href="<?php print $sm_base;?>_styles.css" />
<script type="text/javascript" src="//yandex.st/jquery/2.1.0/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php print $sm_base;?>_script.js?v2"></script>






<div class="darkBG">
     <div class="treeList"></div>
     <div class="modalNotice">
          <div class="titNotice">Подтвердите</div>
          <div class="oneSring">
              
          </div>
          <div class="buttonsAcc">
              <div class="accDelete">Удалить</div>
              <div class="accCancel">Отмена</div>
          </div>

     </div>
</div>

<div class="leftBL">
     <div class="processImport">Импорт</div>
     <form action="<?= $module_url ?>" enctype="multipart/form-data" method="post">
          <label class="file_upload">
               <span class="button">Выбрать</span>
               <mark>Файл не выбран</mark>
               <input type="file"  name="xlsFile" id="uploaded_file" multiple="false" accept=".xlsx,.xls*,.ods,.txt,.csv">
          </label>                              
          <div class="progress-bar orange shine">
               <span id="docprogress" style="width: 0%"></span>
          </div>    
                                           
     </form>
     

</div>


<?= $symmaryTV ?>

<div class="rightBL">
     <div id="dropZone">
          <div class="textInDrop">Перетащите фото сюда</div>
     </div>
     
     <div class="fpwrap">
          <div class="fullImageProgress orange shine"><span id="progressFillImg" style="width: 0%"></span></div>
     </div>
     
</div>





<div class="controlAddsButton">
     <div class="dopBtn selectAcolsImg">Сопоставление картинок<span class="miniAddsTx">Выбрать столбец</span></div>
     <div class="dopBtn selectAunique">Уникальные значения<span class="miniAddsTx">По умолчанию PAGETITLE</span></div>
     
     <div class="dopBtn selectAcat">
          <input class="checkbox_px" type="checkbox"  disabled id="px_f_1">
          <label for="px_f_1"></label>
          Категория
          <span class="miniAddsTx">Не задана</span>
     </div>
     
     <div class="dopBtn selectAcolCat1st">
          <input class="checkbox_px" type="checkbox"  disabled id="px_f_2">
          <label for="px_f_2"></label>
          <span class="px_tesxCol">Столбцы с категориями</span>
          <span class="miniAddsTx">Не заданы</span>
     </div>


     <div class="dopBtn selectAcolLonkImg">
          <input class="checkbox_px" type="checkbox"  disabled id="px_f_3">
          <label for="px_f_3"></label>
          <span class="px_tesxCol2">Ссылки на картинки (URL)</span>
          <span class="miniAddsTx">Не заданы</span>
     </div>


     <!--div class="dopBtn selectAcolLocalImg">
          <input class="checkbox_px" type="checkbox"  disabled id="px_f_4">
          <label for="px_f_4"></label>
          <span class="px_tesxCol3">Пути к картинкам (LOC)</span>
          <span class="miniAddsTx">Не заданы</span>
     </div-->

    
    

     <?= $dooReDo ?>

     <!-- <input type="text" name="delpo" id="postav" placeholder="Введите наименование поставщика"> -->
     <!-- <div class="dopBtn delNotUpdated">Удалить позиции, отсутствующие в текущем прайсе<span class="noticeDelepeItems"></span></div> -->
     <div class="infoArea"><img src="<?php print $sm_base?>tail-spin.svg" width="30"/><span>Jdfgfd</span>

    
 
     </div>
     <div class="clr"></div>
     
     
</div>

<div class="clr"></div>



<div class="prepaireTable"></div>
<div class="sheetList"></div>





 
<?php


function initF($imgPath) {
     if (strlen($imgPath) < 1) return false;
     if ($_FILES['xlsFile']) {
          $ext = end(explode('.',$_FILES['xlsFile']['name']));
          
          if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/xls/')) {
               echo 'rr';
               if (!mkdir($_SERVER['DOCUMENT_ROOT'].'/xls/')) {
                    echo 'fallse';
               }else {
                    echo 'mkdirOk';
               }
          }  
           
          if (($ext != 'xls' || $ext != 'xlsx' || $ext != 'ods') && $_FILES['xlsFile']['size'] > 0 && $_FILES['xlsFile']['error'] == 0)   {
               $newfileName = time().'.'.$ext;
               if (move_uploaded_file($_FILES['xlsFile']['tmp_name'],'../xls/'.$newfileName)) {
                    return $_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileName;
               }
          }
     }
     return false;
}






function getArrValues($pathToXLS) {
     
     require_once($_SERVER['DOCUMENT_ROOT'].'/importXLS/Classes/PHPExcel.php');
     $objPHPExcel = PHPExcel_IOFactory::load($pathToXLS);
     $objPHPExcel->setActiveSheetIndex(0);
     $aSheet = $objPHPExcel->getActiveSheet();
     
     $nColumn = PHPExcel_Cell::columnIndexFromString($aSheet->getHighestColumn());
     $nColumn = 32;
     $nRow = ($aSheet->getHighestRow());
     
     $resultArr = array();
     $resultRowArr = array();
     
     for ($iterRow = 2; $iterRow <= $nRow; $iterRow++) {
          for ($iterCol = 0; $iterCol < $nColumn; $iterCol++) {
               $tmp = addslashes($aSheet->getCellByColumnAndRow($iterCol, $iterRow)->getCalculatedValue());
               //array_push($resultRowArr, iconv('utf-8', 'cp1251', $tmp));
               array_push($resultRowArr, $tmp);
          }
          array_push($resultArr,  $resultRowArr);
          $resultRowArr = array();
     }
     return $resultArr;
}



?>
</body> 
</html>  
