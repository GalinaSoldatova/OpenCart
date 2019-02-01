<?php

//if(!defined('IMPORTXLS')) die('access denied');

//die('Ведутся технические работы');
error_reporting(7);

$template = 1;
$catRoot = 0;
$chunkSize = 100;
$id1c = 9999;


$link = mysql_connect('localhost', 'hotelmj7_new1', 'P@ssw0rd');
mysql_select_db('hotelmj7_new1', $link);


if ($_GET['event'] == 'doBackUp') {

 echo json_encode(array('state' => true , 'text' => "tablebackUpped"));

 exit(); 
 
}


require_once($_SERVER['DOCUMENT_ROOT'].'/importXLS/Classes/PHPExcel.php');

class chunkReadFilter implements PHPExcel_Reader_IReadFilter 
{
    private $_startRow = 0; 
    private $_endRow = 0; 
    /**  Set the list of rows that we want to read  */ 
    public function setRows($startRow, $chunkSize) { 
        $this->_startRow    = $startRow; 
        $this->_endRow      = $startRow + $chunkSize; 
    } 
     public function readCell($column, $row, $worksheetName = '') { 
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow 
        if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) { 
            return true; 
        } 
        return false; 
    } 
}



function initF() {
 
     if ($_FILES[0]) {
          $extArr =  explode('.', $_FILES[0]['name'] );
          $ext = end($extArr);     
                 
          if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/xls/')) {
               if (!mkdir($_SERVER['DOCUMENT_ROOT'].'/xls/')) {
                    //echo 'fallse';
                    //return false;
               }else {
                   // echo 'mkdirOk';
               }
          }  
          
          if (($ext != 'xls' || $ext != 'xlsx' || $ext != 'ods' || $ext != 'csv' || $ext != 'txt') && $_FILES[0]['size'] > 0 && $_FILES[0]['error'] == 0)   {
               $newfileName = time().'.'.$ext;
               if (move_uploaded_file($_FILES[0]['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileName)) {

                    if ($ext == 'xls' || $ext == 'xlsx' || $ext == 'ods' ) {
                      return '{"result":"true","path":"'.$_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileName.'"}';
                    }else {
                      $objReader = PHPExcel_IOFactory::createReader('CSV');
                      $objReader->setDelimiter(";");
                      $objReader->setInputEncoding('CP1251');
                      $objPHPExcel = $objReader->load($_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileName);
                      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
                      $newfileNameCont = time().'.xls';
                      $objWriter->save($_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileNameCont);
                      return '{"result":"true","path":"'.$_SERVER['DOCUMENT_ROOT'].'/xls/'.$newfileNameCont.'"}';

                    }
                    
               }else {
                    return '{"result":"false","path":"Неверно определен путь к файлу"}';
               }
          }
          return false;
     }
}



function buildTreeCat($root) {
  if (!is_numeric($root)) return;
  $content = '';

  $sql = "SELECT c.category_id, c.parent_id, cd.name  FROM `oc_category` AS c 
  INNER JOIN `oc_category_description` AS cd ON c.category_id = cd.category_id WHERE c.parent_id={$root}";

  if ( $result = mysql_query( $sql)) {
    if (mysql_num_rows($result) > 0) {
      while($row =  mysql_fetch_assoc($result)){
        $content .= '<div class="treeElem">';
        $content .= '<span class="pickFolder" data-idcat="'.$row['category_id'].'">'.$row['name'].'</span>';
        $content .= buildTreeCat($row['category_id']);
        $content .= '</div>';
          
      }
    }
  }
  
  return $content;
}



function uploadimages() {

 if ($_FILES[0]) {
      $extArr =  explode('.', $_FILES[0]['name'] );
      $ext = end($extArr);     
        array_pop($extArr);
      $nameWithOutExt = $extArr;
        $nameWithOutExt = implode('.',$nameWithOutExt);
        
      
      if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/image/data/upl/')) {
           echo 'rr';
           if (!mkdir($_SERVER['DOCUMENT_ROOT'].'/image/data/upl/')) {
                //echo 'fallse';
                //return false;
           }else {
               //echo 'mkdirOk';
           }
      }  
      
      if (($ext != 'png' || $ext != 'jpg' || $ext != 'jpeg') && $_FILES[0]['size'] > 0 && $_FILES[0]['error'] == 0)   {
           $newfileName = time().'_'.md5($_FILES[0]['name']).'.'.$ext;
           if (move_uploaded_file($_FILES[0]['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/image/data/upl/'.$newfileName)) {
                return '{"result":"true","path":"/image/data/upl/'.$newfileName.'","realname":"'.$nameWithOutExt.'"}';
           }else {
                return '{"result":"false","path":"Неверно определен путь к файлу"}';
           }
      }
      return false;
 }
}



function getArrValuesChunk($pathToXLS , $start,  $currentList = 0  , $excludedRows = array() , $stringsCollation = array(), $pgtIndex = false , $currentStep = 1 , $stringsCollationIgnore = array() ) {
 
  $startRow = $start;
 $inputFileType = 'Excel5';
 //$chunkSize = 100;
 global $chunkSize;

 //set_time_limit(1800);
 //ini_set('memory_liit', '128M');
 $exit = false;           //флаг выхода
 $empty_value = 0;
 $objReader = PHPExcel_IOFactory::createReaderForFile($pathToXLS);
 $objReader->setReadDataOnly(true);
 $chunkFilter = new chunkReadFilter(); 
 $objReader->setReadFilter($chunkFilter); 
 $chunkFilter->setRows($startRow,$chunkSize);      //устанавливаем знаечние фильтра
 $objPHPExcel = $objReader->load($pathToXLS);       //открываем файл
 $resultShhetList = $objPHPExcel->getSheetNames();
 $objPHPExcel->setActiveSheetIndex($currentList);        //устанавливаем индекс активной страницы
 $objWorksheet = $objPHPExcel->getActiveSheet();   //делаем активной нужную страницу
 $nRow = ($objWorksheet->getHighestRow());
 $nColumn = PHPExcel_Cell::columnIndexFromString($objWorksheet->getHighestColumn());
 if ($nColumn > 85) $nColumn = 85;
 $resultArr = array();
 $itsNotEmpty = true;

 
 $strictList = false;
 if (count($stringsCollation) > 0) {
      $strictList = true;
 }

 for ($i = $startRow; $i < $startRow + $chunkSize; $i++)     //внутренний цикл по строкам
 {
      $emptyAllCols = false;

      if ($strictList && !in_array( $i,  $stringsCollation  )) { continue; } 

      if (in_array( $i,  $stringsCollationIgnore  )) {  continue; } 

      if ($pgtIndex !== false){
           $tmp = addslashes($objWorksheet->getCellByColumnAndRow($pgtIndex, $i)->getCalculatedValue());
            if ($tmp == ''){
                 continue;
           }
      }

      if (  @in_array($i-1 , $excludedRows ))  continue;

     // $value = trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(0, $i)->getValue()));      //получаем наименование  

      //Манипуляции с данными каким Вам угодно способом, в PHPExcel их превеликое множество
      
      $readerColIterator = array();
      for ($col = 0; $col < $nColumn; $col++) {
           $tmp = trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow($col, $i)->getCalculatedValue()));
           
            array_push($readerColIterator,  $tmp);
      }
     // array_push($readerColIterator,  $i);

     // print_r($readerColIterator);
      foreach ($readerColIterator AS $elem){
           if ( empty($elem) ){
                $emptyAllCols = true;
           }else {
                $emptyAllCols = false;
                break;
           }
      }

      if ( $emptyAllCols){
          // echo "emptyAllCols<br>";
           $empty_value++;   
           $itsNotEmpty = false;  
      }else {
           $itsNotEmpty = true;  
      }       //проверяем значение на пустоту 

      if ($empty_value == 20 || $i >=$nRow+$startRow)       //после 20 пустых значений, завершаем обработку файла, думая, что это конец
      // if ($empty_value == 20 )       //после 20 пустых значений, завершаем обработку файла, думая, что это конец
      {    
           $exit = true;
           unset($_SESSION['startRow']);
           break;         
      }

      //echo $empty_value;   

      if ( $itsNotEmpty ) {  
           array_push($resultArr,  $readerColIterator);
      }else {
      }

      $fulldata['data'] = $resultArr;
      $fulldata['meta']['from'] = $startRow;
      $fulldata['meta']['nColumn'] = $nColumn;
      $fulldata['meta']['highestRow'] = $i-$empty_value;
      $fulldata['meta']['currentStep'] = $nRow+$startRow;
      $fulldata['meta']['currentList'] = $currentList;
      $fulldata['meta']['allList'] = $resultShhetList;
  }
  
  $objPHPExcel->disconnectWorksheets();                  //чистим 
  unset($objPHPExcel); 
  $currentStopped =   $startRow;                         //память
  $startRow += $chunkSize;                     //переходим на следующий шаг цикла, увеличивая строку, с которой будем читать файл
  
  if ($exit || $strictList){
        $fulldata['meta']['finish'] = true; 
        //echo '{"result":"TheEnd","count":"'.$startRow.'"}';
  }else {
        //echo  '{"result":"DooTheNextPart","count":"'.$startRow.'"}';
        $_SESSION['startRow'] = $startRow;
  }

  return $fulldata;
  
}



if (isset($_GET['uploadfiles'])) {
  echo initF();
}


if (isset($_GET['uploadimages'])) {
  echo uploadimages();
}


if (isset($_GET['buildTreeCat'])) {
 echo buildTreeCat(0); 
}



if (isset($_GET['getXMLdata']) && $_POST['pathToXLS'] != '') {
  
  if (!is_numeric($_POST['listIndex'])) {
       $listIndex = 0;
  }else {
       $listIndex = $_POST['listIndex']; 
  }
  echo json_encode( getArrValuesChunk(addslashes($_POST['pathToXLS']) , $_POST['from'],  $listIndex   ) ); 
  
}



if (isset($_GET['dooImportData']) && $_POST['pathToXLS'] != '' ) {

  $tvCollation = json_decode($_POST["tvCollation"]);
  $filterCollation = json_decode($_POST["filterCollation"]); 
  $imageCollation = json_decode($_POST["imageCollation"]);
  $imageCollationLink = json_decode($_POST["imageCollationLink"]);
  $collationImageCol = json_decode($_POST["collationImageCol"]);
  $stringsCollation = json_decode($_POST["stringsCollation"]);
  $stringsCollationIgnore = json_decode($_POST["stringsCollationIgnore"]);
  $currentSheet = addslashes($_POST["currentSheet"]);
  $imageTVcol = addslashes($_POST["imageTVcol"]);
  $tocat = addslashes($_POST["tocat"]);
  $typeImport = addslashes($_POST["typeImport"]);
  $pathToXLS = addslashes($_POST["pathToXLS"]);
  $callationIndex = addslashes($_POST["callationIndex"]);
  $selectColCat1st_pos = json_decode($_POST["selectColCat1st_pos"]);

  $addToPGT = json_decode($_POST["addToPGT"]);

  $selectAcolLonkImg_pos = json_decode($_POST["selectAcolLonkImg_pos"]);
  $selectAcolLocalImg_pos = json_decode($_POST["selectAcolLocalImg_pos"]);

  $currentPos = addslashes($_POST["startFrom"]);
  $postv = addslashes($_POST["postv"]);
  $vendorID = addslashes($_POST["vendorID"]);

  $arrsXLS = false;


  if (file_exists($pathToXLS)){
       if (!is_numeric($currentPos)) {
            $currentPos = 0;  
       }

       if ($currentPos == 0 ) {
           disAllCats($tocat);
       }

       $pgtIndex = array_search ( "PGT" ,  $tvCollation);
       $arrsXLS  = getArrValuesChunk(addslashes($pathToXLS) , $currentPos+1, $currentSheet, false, $stringsCollation , $pgtIndex , 1 ,  $stringsCollationIgnore);
  }

  if ($typeImport == "allInSelected") {
       
       if ($result = importToOneCat($arrsXLS,$tocat,$tvCollation,  $stringsCollation , $imageCollation,$imageCollationLink,$collationImageCol,$imageTVcol , $template , $callationIndex, $postv ,$selectColCat1st_pos , $selectAcolLonkImg_pos , $filterCollation , $selectAcolLocalImg_pos , $addToPGT , $vendorID) ) {
            $imporiRes =  $result; 
       }

  }elseif($typeImport == "toChangedCat"){ 
        
       if ($result = importToOneCat($arrsXLS,$tocat,$tvCollation,  $stringsCollation , $imageCollation,$imageCollationLink,$collationImageCol,$imageTVcol , $template , $callationIndex, $postv ,$selectColCat1st_pos , $selectAcolLonkImg_pos , $filterCollation , $selectAcolLocalImg_pos , $addToPGT , $vendorID) ) {
           $imporiRes =  $result;
       }

  }

  $retRes['highestRow'] = $arrsXLS['meta']['highestRow'];
  $retRes['finished'] = $arrsXLS['meta']['finish'];
  $retRes['currentStep'] = $arrsXLS['meta']['currentStep'];
  $retRes['meta'] = $imporiRes;
  echo  json_encode( $retRes);

}



function disAllCats($root){

  $sql = "SELECT category_id FROM  `oc_category` WHERE parent_id = {$root}";

  if ($result = mysql_query($sql)){
    if (mysql_num_rows($result) >0 ){
      while ($row = mysql_fetch_assoc($result)){
        disAllCats($row['category_id']);
      }
    }
  }
//echo mysql_error(); 
}




function createPathWay($nameCats, $rootCreatePath , &$cnt){
 global $template;
 global $catRoot;
 $contentFolderID = array();
 $catRootInner = $rootCreatePath;
  
  foreach($nameCats AS $nameCat) {
       
       $sql = "SELECT c.category_id, c.parent_id, cd.name  FROM `oc_category` AS c 
       INNER JOIN `oc_category_description` AS cd ON c.category_id = cd.category_id
       WHERE c.parent_id={$catRootInner} AND UPPER(sc.pagetitle) = '".(strtoupper ( $nameCat ))."' LIMIT 1";


       if ($result = mysql_query($sql)){
             if (mysql_num_rows($result) < 1 ) {
                 
                  //$alias = GenerAlias($nameCat);

                  $sql = 'INSERT INTO  `oc_category`
                    (
                          parent_id,
                          top,
                          column,
                          sort_order,
                          status,
                          date_added,
                          date_modified
                    ) VALUES (
                          '.$catRootInner.',
                          1,
                          1,
                          10,
                          1,
                          "'.date("Y-m-d H:i:s").'",
                          "'.date("Y-m-d H:i:s").'"
                    )';

                  if ($resultIN = mysql_query($sql)){
                        $cnt++;

                        $sql2= "SELECT * FROM `oc_category` ORDER BY `category_id` DESC LIMIT 1";
                        if (mysql_num_rows($result2 = mysql_query($sql2)) > 0) {
                            echo mysql_error();
                            while ($row2 = mysql_fetch_assoc($result2)) {
                              $contentID = $row2['category_id'];
                            }
                        }


                     /*   $contentFolderID[] = mysql_insert_id();
                        $catRootInner = mysql_insert_id();
                        $nextID = mysql_insert_id();*/

                        $contentFolderID[] = $contentID;
                        $catRootInner = $contentID;
                        $nextID = $contentID;
                  }


                  $sql = 'INSERT INTO  `oc_category_description`
                    (
                          category_id,
                          language_id,
                          name,
                          meta_description,
                          meta_keyword,
                          seo_title,
                          seo_h1
                    ) VALUES (
                          '.$nextID.',
                          1,
                          "'.$nameCat.'",
                          "'.$nameCat.' - товары для отелей и домов отдыха купить в магазине товаров для оснащения отелей",
                          "'.$nameCat.'",
                          "'.$nameCat.'",
                          "'.$nameCat.'"
                    )';

                    mysql_query($sql);

                    
                    $sql = 'INSERT INTO  `oc_category_path`
                    (
                          category_id,
                          path_id,
                          level
                    ) VALUES (
                          '.$nextID.',
                          '.$nextID.',
                          0
                    )';

                    mysql_query($sql);
                   
             }else {
                   //getid
                   //$contentFolderID = mysql_fetch_assoc($result)["id"]; // PHP 5.4  OR Higest
                   $tmp  = mysql_fetch_assoc($result); 
                   $contentFolderID[] = $tmp["category_id"];
                   $catRootInner = $tmp["category_id"];
             }
       }
       
       
  }

 return $contentFolderID;          
}




function importToOneCat($arrsXLS,$tocat,$tvCollation, $stringsCollation,$imageCollation,$imageCollationLink,$collationImageCol,$imageTVcol, $template , $callationIndex, $postv, $catPos=false , $imageLinks = false , $filterCollation , $imageLinksLocal , $addToPGT = false ,  $vendorID = false){

 global $catRoot;
 $noFindedPath = 0;

// print_r($arrsXLS);
 if (! (is_array($arrsXLS) && (is_numeric($tocat) ||  ($catPos!== false) )) ) return false;

 // echo $tocat;
 // var_dump($catPos);
 //if ($catPos!== false) {
 if (is_array($catPos) && count($catPos)) {
      if (is_numeric($tocat)) {
           $rootCreatePath = $tocat;
      }else {
           $rootCreatePath = $catRoot;
      }
      $tocat = $noFindedPath;
 }
 //echo $tocat;
 
 $countEvent['added'] = 0;
 $countEvent['updated'] = 0;
 $countEvent['createNewPath'] = 0;


 $flippedArrTv = (@array_flip($tvCollation));
 $flippedArrFLT = (@array_flip($filterCollation));


 foreach($arrsXLS['data'] AS $indexString => $strCols) {
      $contentID  = false;     

      if (is_array($catPos) && count($catPos)) {
              $namesCat = array(); // сделать проверки на массив и тд 
              foreach ($catPos AS $elem){
                  if (trim($strCols[$elem]) !='') {
                    $namesCat[] = $strCols[$elem];
                  }
              }
              if ($resT = createPathWay($namesCat , $rootCreatePath , $countEvent['createNewPath'])){
                $tocat = end($resT);
           }
      }

      if ($imageLinks!== false) {
          $tmpLnk='';
          foreach ($imageLinks as $keyL => $valueL) {
            if ($strCols[$valueL] != '') {
              if ($localLink = loadRemoteFile($strCols[$valueL])) {
                $tmpLnk .= $tmpLnk == '' ? $localLink : '||'.$localLink;
              }
            }            
          } 
          //echo $tmpLnk."<br>"; 
      }


      if ($imageLinksLocal!== false) { 
          $tmpLnkLoc='';
          foreach ($imageLinksLocal as $keyL => $valueL) {
            if ($strCols[$valueL] != '') {
                //$tmpLnkLoc .= $tmpLnkLoc == '' ? "assets/images/".$strCols[$valueL] : '||'."assets/images/".$strCols[$valueL];
                $tmpLnkLoc .= $tmpLnkLoc == '' ? $strCols[$valueL] : '||'.$strCols[$valueL];       
            }           
          } 
          //echo $tmpLnk."<br>"; 
      }


      if (is_numeric($addToPGT)) {
        $strCols[$flippedArrTv["PGT"]] .= ' '.$strCols[$addToPGT];
      }

      

      $inWHERE = " pd.name = '".$strCols[$flippedArrTv["PGT"]]."' ";
      $andParent = "AND ptc.category_id = {$tocat} ";

      //делаем уникальными только pagetitle

      $sql = "SELECT pd.product_id  FROM `oc_product_description` AS pd 
      INNER JOIN `oc_product_to_category` AS ptc ON pd.product_id = ptc.product_id 
      WHERE ".$inWHERE."  ".$andParent."  GROUP BY pd.product_id  LIMIT 100";       

      if ($result = mysql_query($sql)){

          $finded = false; 
          if (mysql_num_rows($result) > 0 ){ 
            $finded = true; 
            while ($ttrow = mysql_fetch_assoc($result)) {
                $ttid = $ttrow['product_id'];
            }

          }

          if (!$finded &&  array_search ( "PGT" ,  $tvCollation) !== false )  {

              //adddd

               // $alias = GenerAlias($strCols[$flippedArrTv["PGT"]]);
                         
                     
                $sql = "INSERT INTO  `oc_product`
                     (
                          model,
                          quantity,
                          stock_status_id,
                          manufacturer_id,
                          shipping,
                          price,
                          date_available,
                          sort_order,
                          status,
                          date_added,
                          date_modified

                     ) VALUES (
                          '".mysql_real_escape_string($strCols[$flippedArrTv["ART"]])."',
                          '".mysql_real_escape_string($strCols[$flippedArrTv["QUANTITY"]])."',
                          7,
                          0,
                          1,
                          '".mysql_real_escape_string($strCols[$flippedArrTv["PRICE"]])."',
                          '".date("Y-m-d")."',
                          1,
                          1,
                          '".date("Y-m-d H:i:s")."',
                          '".date("Y-m-d H:i:s")."'                              
                     )";

                   //  echo $sql;
                if ($result = mysql_query($sql)){

                  $sql2= "SELECT * FROM `oc_product` ORDER BY `product_id` DESC LIMIT 1";
                  if (mysql_num_rows($result2 = mysql_query($sql2)) > 0) {
                      echo mysql_error();
                      while ($row2 = mysql_fetch_assoc($result2)) {
                        $contentID = $row2['product_id'];
                      }
                  }
                  

                  $sql = "INSERT INTO  `oc_product_description`
                  (
                       product_id,
                       language_id,
                       name,
                       description,
                       meta_description,
                       meta_keyword,
                       seo_title,
                       seo_h1

                  ) VALUES (
                       '{$contentID}',
                       1,
                       '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                       '".mysql_real_escape_string($strCols[$flippedArrTv["CONTENT"]])."',
                       '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])." - интернет-магазин товаров для отелей',
                       '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                       '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                       '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."'
                  )";
                  mysql_query($sql);



                  $sql = "INSERT INTO  `oc_product_to_store`
                  (
                       product_id,
                       store_id
                  ) VALUES (
                    '{$contentID}',
                    0
                  )";

                  mysql_query($sql);




                  $sql = "INSERT INTO  `oc_product_to_category`
                  (
                       product_id,
                       category_id,
                       main_category
                  ) VALUES (
                      {$contentID},
                      {$tocat},
                      0
                  )";

                  mysql_query($sql);

                  $countEvent['added']++;

                  if (($keyImg = array_search ( explode('.',$strCols[$collationImageCol]) [0] , $imageCollation )) !== false) {
                    $imgLinkT =  $imageCollationLink[$keyImg];
                  }else {
                    $imgLinkT = false;
                  }
                     
                  if ($imgLinkT != false ||  $tmpLnk != '' ||  $tmpLnkLoc != '') {
                        processedIMG($contentID,$imageTVcol,$imgLinkT, $tmpLnk , $tmpLnkLoc);
                    } 
                    
                }

            }else { 

              //update
            
                $contentID = $ttid;
               // echo $contentID.'update';


               $sql = "UPDATE  `oc_product`  SET
                    model =  '".mysql_real_escape_string($strCols[$flippedArrTv["ART"]])."',
                    quantity =   '".mysql_real_escape_string($strCols[$flippedArrTv["QUANTITY"]])."',
                    price =  '".mysql_real_escape_string($strCols[$flippedArrTv["PRICE"]])."',
                    date_modified = '".date("Y-m-d H:i:s")."'
                    WHERE product_id = {$contentID} LIMIT 1";
                    
                mysql_query($sql);

                    
                $sql = "UPDATE  `oc_product_description`  SET
                    name =  '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                    description =   '".mysql_real_escape_string($strCols[$flippedArrTv["QUANTITY"]])."',
                    meta_description = '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])." - интернет-магазин товаров для отелей',
                    meta_keyword = '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                    seo_title = '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."',
                    seo_h1 = '".mysql_real_escape_string($strCols[$flippedArrTv["PGT"]])."'
                    WHERE product_id = {$contentID} LIMIT 1";
               
                //mysql_query($sql);


              if ($result = mysql_query($sql) ){
                   $countEvent['updated']++;

                  if (($keyImg = array_search ( explode('.',$strCols[$collationImageCol]) [0] , $imageCollation )) !== false) {
                    $imgLinkT =  $imageCollationLink[$keyImg];
                  }else {
                    $imgLinkT = false;
                  }

                  if ($imgLinkT != false ||  $tmpLnk != '' ||  $tmpLnkLoc != '') {
                      processedIMG($contentID,$imageTVcol,$imgLinkT, $tmpLnk , $tmpLnkLoc);
                  } 
              }
            }           
      } 
      //echo mysql_error();        
 }

 return $countEvent;
}





function  loadRemoteFile ($link){

  /*
  $pattern = '/(https?|ftp):\/\//iu';

  if  ( preg_match ( $pattern ,  $subject)  == 0)  {
    $link = 'http://'.$link;
  }
  */

  //echo $link.'-----------';



  $confPath = 'image/data/upl/';
  $acceptedExtension = array('jpg' , 'jpeg' , 'png' , 'gif' );
  $ext = @end(explode('.' , $link));
  $localImgName = md5($link).'.'.mb_strtolower($ext , "UTF-8");

  if (!in_array($ext , $acceptedExtension)) return false;

  //if ($path == 'good') {
  $dir = substr($localImgName , 0 , 2).'/';
  if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$confPath.$dir)) {
    @mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$confPath.$dir , 0777 , true);
  }

  $localPath = $confPath.$dir.$localImgName;

  $newfile = $_SERVER['DOCUMENT_ROOT'].'/'.$localPath;

  if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$localPath)) {
     $localPath = stristr($localPath, 'd');
     return $localPath;
  }else {
       /*
      $ch = curl_init($link); 
      $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/'.$localPath, "w");
     // echo $_SERVER['DOCUMENT_ROOT'].'/'.$localPath;
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
      return $localPath;
      */

      copy($link, $newfile);
      $localPath = stristr($localPath, 'd');
      return $localPath;
  }

}




function processedIMG($contentID,$imageTVcol,$imageCollation=false,$dopImage = false , $dopImageLoc = false) {


if ($imageCollation != false && $dopImage != false ) {
  $imageCollation = $imageCollation.'||'.$dopImage;
}elseif ($imageCollation == false && $dopImage != false ) {
   $imageCollation = $dopImage;
}

if ($imageCollation != false && $dopImageLoc != false ) {
  $imageCollation = $imageCollation.'||'.$dopImageLoc;
}elseif ($imageCollation == false && $dopImageLoc != false ) {
   $imageCollation = $dopImageLoc;
}

 $sql = "SELECT product_image_id FROM `oc_product_image` WHERE product_id = {$contentID} LIMIT 1";
 if ($result = mysql_query($sql)){
      if (mysql_num_rows($result) > 0 ){
           //$tvID = mysql_fetch_assoc($result)['id'];// PHP 5.4  OR Higest
               $tmp  = mysql_fetch_assoc($result);
                $tvID = $tmp["product_image_id"];
           $sql = "UPDATE `oc_product_image` SET `image` = '".$imageCollation."' WHERE product_image_id = {$tvID} ";
           mysql_query($sql);                 
      }else {
           $sql = "INSERT INTO `oc_product_image` (product_id,image,sort_order) VALUES ({$contentID} , '".$imageCollation."',0)";
           mysql_query($sql);
      }
 }     

 $sql = "UPDATE `oc_product` SET `image` = '".$imageCollation."' WHERE product_id = {$contentID} ";
 mysql_query($sql); 

}



function pre($data){
  echo '<pre>';
  print_r($data);
  echo '</pre>';
}

