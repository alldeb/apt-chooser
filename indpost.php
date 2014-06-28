<?php
// (c) 2014 Slamet Badwi
include('config.php');
include('common.php');

$dists = $_repo_list;
$mirrors = $_mirror_list;

$packages = '';
$dist = 0;
$mirror = 0;

if (isset($_POST)) {

	$packages = isset($_POST['packages']) ? trim($_POST['packages']) : '';
	$dist = isset($_POST['dist']) ? intval(trim($_POST['dist'])) : 0;
	$mirror = isset($_POST['mirror']) ? intval(trim($_POST['mirror'])) : 0;

	if (($packages != '') && (isset($_repo_list[$dist]))) {

		// Get package dependencies and their URLs
		$res = apt_install($_repo_list[$dist][0], $packages);
		$res = parse_install($res);
		$tbInstalled = &$res['packages'];
        //$install = &$res['install'];

		if (isset($_mirror_list[$mirror])) {
			while (list($key, $val) = each($tbInstalled)) {
				$tbInstalled[$key][0] = convert_url($tbInstalled[$key][0], $_mirror_list[$mirror][0]);
			}
		}
	}

    while(list($key, $val) = each($dists)) {
    if($key == $dist)
	echo "Results for profile: <strong>".$val[1]."</strong><br/><br/>";
    }

    echo '<ol>';
    foreach($tbInstalled as $package) {

	echo '<li><a href="'.$package[0].'">'.$package[1].'</a></li>';

	$ukuran[] = $package[2];
    }
    echo '</ol>';

    function humanFileSize($size,$unit="") {
        if( (!$unit && $size >= 1<<30) || $unit == "GB")
        return number_format($size/(1<<30),2)." GB";
        if( (!$unit && $size >= 1<<20) || $unit == "MB")
        return number_format($size/(1<<20),2)." MB";
        if( (!$unit && $size >= 1<<10) || $unit == "KB")
        return number_format($size/(1<<10),2)." KB";
        return number_format($size)." bytes";
    }
    if($res != NULL){
        if($tbInstalled != NULL){
            $ukuran =  array_sum($ukuran);
            if($ukuran < 1024000) {
                echo "<strong>Total size:</strong> $ukuran bytes <strong>(".humanFileSize($ukuran,"KB").")</strong>";
            } else {
                echo "<strong>Total size:</strong> $ukuran bytes <strong>(".humanFileSize($ukuran,"MB").")</strong>";
            }
        }
        else
        {
            echo $packages." had been installed";
        }
    }
    else
    {
        echo "Cannot proceed your request searching for package(s) <span class=\"text-info\"><strong>".$packages."</strong></span>, it's likely you have misspelled.";
    }
}
else
{
    return false;

}
?>