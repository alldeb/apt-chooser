<?php
include "apt-worker.php";

if(isset($_GET['package'])){

LogAction("Package query: ".$_GET['package']." ".$_GET['repo']." ".$_GET['arch']." ".$_GET['have']);

echo '<strong>Results for <span class="text-primary">'.$_GET['package'].'</span></strong>';
echo '<br/><br/>';

       $List=GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['package'], GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['have'], array()));
       if(!$List) print "<p class=\"text-warning\">There is no such package in ".$_GET['repo']."<p>\n"; else OutputPackages($_GET['arch'], $_GET['repo'], $List);
}
else if(isset($_GET['packages'])){

$data = $_GET;
$url = 'http://apt-web.tk/apt-web/indpost.php';
$ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1); // Mengembalikan data, bukan langsung echo.
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      if(!curl_exec($ch)){
	//echo "Failure";
      return false;
      }
      curl_close($ch);
//print_r($data);
}
else if(isset($_POST['pass'])){

LogAction("Administrator access");
       if($_POST['pass']!=$AdminPass) print "Wrong password";
       else if($_POST['file'] && $_POST['repo'] && $_POST['component'] && $_POST['arch']) ParsePackagesList($_POST['file'], $_POST['repo'], $_POST['component'], $_POST['arch']);
}
else
{
print "Error occured";
}
?>