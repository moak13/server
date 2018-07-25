<?php
	require('./../util/functions.php');
	$utility = new Utility();
    $q = $_GET["q"];
    $search = $utility->searchproduct($q);
    if(empty($search)){
       echo "noting found";
    }else{
        foreach($search as $result){
            $name = $result['title'];
            // echo '<a href="javascript:;" onClick="linkprice([\'$name\']);"><div id="psresult">'.$name.'</div></a><hr>';
            echo "<a href='javascript:;' id='searchresult' onclick=\"linkprice('$name')\"><div id='psresult'>$name</div></a><hr>";
        }
    }
?>