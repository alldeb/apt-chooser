<?php
include "apt-worker.php";

if(isset($_GET['package'])): 

LogAction("Package query: ".$_GET['package']." ".$_GET['repo']." ".$_GET['arch']." ".$_GET['have']); 
?>

<strong>Results for <span class="text-primary"><?php print $_GET['package']; ?></span></strong>
<br/><br/>

<?php
       $List=GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['package'], GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['have'], array()));
       if(!$List) print "<p class=\"text-warning\">There is no such package in ".$_GET['repo']."<p>\n"; else OutputPackages($_GET['arch'], $_GET['repo'], $List);
?>

<?php elseif(isset($_POST['pass'])): ?>

<?php
LogAction("Administrator access");
       if($_POST['pass']!=$AdminPass) print "Wrong password";
       elseif($_POST['file'] && $_POST['repo'] && $_POST['component'] && $_POST['arch']) ParsePackagesList($_POST['file'], $_POST['repo'], $_POST['component'], $_POST['arch']);
?>

<?php else: ?>

<?php print "Error occured"; ?>

<?php  endif; ?>
