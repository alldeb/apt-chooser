<?php
//file apt-config.php
        $Debug=FALSE;                                  // Determines whether PHP warnings and errors are output
        $BaseURL="http://id.archive.ubuntu.com/ubuntu/";  // The base URL for the used APT repositories
        $GPLFile="gpl.txt";                            // A valid local filename for the GNU GPL in plain text
        $LogFile="apt.log";                            // A local filename for log messages
        $DBHost="localhost";                           // The hostname where the DBMS resides
        $DBName="";                              // The name of the database containing the Packages table
        $DBUser="";                             // A DBMS user who can access and alter the Packages table
        $DBPass="";                            // The password for the DBMS user
        $AdminPass="";                         // A password to access administrator mode
?>
<?php

// APT Chooser, a web application to select .deb packages to download
// Copyright (C) 2006  Lorenzo J. Lucchini
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// (version 2) as published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require "apt-config.php";

if(isset($Debug) && $Debug) {
        error_reporting(E_ALL ^ E_DEPRECATED);
        ini_set('display_errors', '1');
}

function DBConnect($Host, $Database, $User, $Password) {
        global $DB;
        $DB = mysql_connect($Host, $User, $Password) or die("Could not connect: ".mysql_error());
        mysql_select_db($Database) or die("Could not select database");
}

function DBEscape($String) {
        return mysql_real_escape_string($String);
}

function GetRepositories() {
        return array_reverse(DBQuery("SELECT DISTINCT Repository FROM Packages", FALSE));
}

function GetComponents() {
        return DBQuery("SELECT DISTINCT Component FROM Packages", FALSE);
}

function GetArchitectures() {
        return DBQuery("SELECT DISTINCT Arch FROM Packages", FALSE);
}

function DBQuery($Query, $Smart=TRUE) {
        global $DB;
        $Result=mysql_query($Query) or die("Query failed: ".mysql_error());
        if(is_bool($Result)) return $Result;
        $Array=array();
        while($Row=mysql_fetch_array($Result, MYSQL_ASSOC)) {
                // If there is only one column, just return an atomic variable
                if(count($Row)==1) $Row=$Row[key($Row)];
                array_push($Array, $Row);
        }
        // If there is only one row, return it without wrapping an array around it, unless told otherwise
        if($Smart && count($Array)==1) $Array=$Array[0];
        return $Array;
}


// Read packages data from an APT 'Packages' style file, and put them into the database

function ParsePackagesList($File, $Repository, $Component, $Architecture) {
        $Handle=fopen($File, "r");
        $Count=0;
        do {
                do {
                        $Line=rtrim(fgets($Handle));
                        if(feof($Handle)) return FALSE;
                } while(!ereg("^Package: ", $Line));
                $Name=ereg_replace("^Package: ", "", $Line);
                unset($Filename); $Depends=""; $Recommends=""; $Description="";
                do {
                        $Line=rtrim(fgets($Handle));
                        if(feof($Handle)) return FALSE;
                        if(ereg("^Depends: ", $Line)) $Depends=ereg_replace("^Depends: ", "", $Line);
                        if(ereg("^Recommends: ", $Line)) $Recommends=ereg_replace("^Recommends: ", "", $Line);
                        if(ereg("^Filename: ", $Line)) $Filename=ereg_replace("^Filename: ", "", $Line);
                        if(ereg("^Description: ", $Line)) $Description=ereg_replace("^Description: ", "", $Line);
			if(ereg("^Size: ", $Line)) $Size=ereg_replace("^Size: ", "", $Line);
                } while($Line);
                $Query = sprintf(
                        "INSERT INTO Packages (Package, Depends, Recommends, Filename, Description, Repository, Component, Arch, Size)
                        VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                        DBEscape($Name), DBEscape($Depends), DBEscape($Recommends), DBEscape($Filename), DBEscape($Description),
                        DBEscape($Repository), DBEscape($Component), DBEscape($Architecture), DBEscape($Size)
                );
                DBQuery($Query);
                $Count+=1;
        } while(!feof($Handle));
        print "<p>".$Count." packages added to ".$Repository." (".$Component.") for ".$Architecture."</p>\n";
}


// Return stored information about a package, including dependency list as an array

function GetPackageEntry($Package, $Repository, $Arch) {
        $Entry=DBQuery(sprintf("SELECT * FROM Packages WHERE Package='%s' AND Repository='%s' AND Arch='%s'", DBEscape($Package), DBEscape($Repository), DBEscape($Arch)));
        if(!isset($Entry['Package'])) return FALSE;
        $Entry['Depends']=split(" [^,]*(, |$)|, ", $Entry['Depends']);
        $Entry['Recommends']=split(" [^,]*(, |$)|, ", $Entry['Recommends']);
        return $Entry;
}


// Return an array containing the name of a package and of all packages (in)directly dependant on it, ignoring packages we already have

function GetPackageTree($Arch, $Repository, $Package, $Have=FALSE) {
        if(!isset($Package) || !isset($Repository)) return FALSE;
        $Entry=GetPackageEntry($Package, $Repository, $Arch);
        if(!$Entry) return FALSE;
        if(!$Have) $Have=array();
        $Have=array_merge($Have, $Tree=array($Package));
        foreach($Entry['Depends'] as $Dependency) {
                if(in_array($Dependency, $Have)) continue;
                $Subtree=GetPackageTree($Arch, $Repository, $Dependency, $Have);
                if($Subtree) {
                        $Tree=array_merge($Subtree, $Tree);
                        $Have=array_merge($Subtree, $Have);
                }
        }
        return array_unique($Tree);
}


// Print an HTML-formatted list of packages with descriptions

function OutputPackages($Arch, $Repository, $Packages) {
        global $BaseURL;
	$TotalSize=0;
        if(isset($_GET['json'])) ob_clean();
	else print "<small><ul>\n";
        foreach($Packages as $Package) {
                $Entry=GetPackageEntry($Package, $Repository, $Arch);
		if(isset($_GET['json'])) {
			$List[]=$BaseURL.$Entry['Filename'];
			continue;
		}
                print "<li><a href='".$BaseURL.$Entry['Filename']."'>".$Entry['Package']."</a> (".$Entry['Component'].") ".$Entry['Description']."</li>\n";
		$TotalSize+=$Entry['Size'];
        }
        if(isset($_GET['json'])) {
                print json_encode($List);
                exit;
        }
	print "<p><b>Total size: </b>".$TotalSize." bytes";
        print "</ul><p>After you have downloaded these packages, put them in a directory and transfered
                that to your Ubuntu system, you should be able to install them with ''dpkg -i *.deb'' from inside the directory.</p></small>\n";
        flush();
}


// Store a string in the log file, with timestamp and originating address

function LogAction($String) {
        global $LogFile;
        if(!isset($LogFile)) return;
        $Handle=fopen($LogFile, "a");
        fwrite($Handle, date("d/m/y H:i:s ").$_SERVER['REMOTE_ADDR']." : ".$String."\n");
        fclose($Handle);
}


// Check that configuration variables have been set

if(!isset($BaseURL)) die('$BaseURL not set');
if(!isset($DBHost)) die('$DBHost not set');
if(!isset($DBName)) die('$DBName not set');
if(!isset($DBUser)) die('$DBUser not set');
if(!isset($DBPass)) die('$DBPass not set');
if(!isset($AdminPass)) die('$AdminPass not set');
if(!isset($GPLFile) || !file_exists($GPLFile)) die('$GPLFile not set or file missing (the GNU GPL should be made available with the program)');

// Serve the source code of this file itself if so requested

if(isset($_GET['source'])) {
        LogAction("Source download");
        header('Content-type: text/x-php');
        $Handle=fopen(basename($_SERVER['PHP_SELF']), "r");
        fpassthru($Handle);
        fclose($Handle);
        die();
}

// Connect to the database

DBConnect($DBHost, $DBName, $DBUser, $DBPass);

// Start buffering, since we might need to scrap HTML output if using JSON

ob_start();

?>


<html><title>Ubuntu APT web download</title><body>

<?php if(isset($_GET['admin'])): ?>

        <?php LogAction("Administrator access"); ?>
        <?php
                if(isset($_POST['parse'])) {
                        if($_POST['pass']!=$AdminPass) print "<p>Wrong password</p>";
                        else ParsePackagesList($_POST['file'], $_POST['repo'], $_POST['component'], $_POST['arch']);
                }
        ?>
        <h1>Site administration</h1>
        <form method="POST"><p>Password <input type="TEXT" name="pass" /></p><table>
                <tr><td>What is the file that contains the packages data?</td><td><input type="TEXT" name="file" /></td></tr>
                <tr><td>What repository does it refer to?</td><td><input type="TEXT" name="repo" /></td></tr>
                <tr><td>What component does it list?</td><td><input type="TEXT" name="component" /></td></tr>
                <tr><td>What architecture are the packages for?</td><td><input type="TEXT" name="arch" /></td></tr>
        </table><input type="SUBMIT" name="parse" value="Add" /></form>

<?php else: ?>

<p style="text-align: right;"><a href="<?php print basename($_SERVER['PHP_SELF'])."?admin"; ?>">Administration</a></p>

<?php  if(isset($_GET['package'])): ?>

        <?php LogAction("Package query: ".$_GET['package']." ".$_GET['repo']." ".$_GET['arch']." ".$_GET['have']); ?>
        <h1>Results for <?php print $_GET['package']; ?></h1>
        <?php
                $List=GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['package'], GetPackageTree($_GET['arch'], $_GET['repo'], $_GET['have'], array()));
                if(!$List) print "<p>There is no such package in ".$_GET['repo']."<p>\n"; else OutputPackages($_GET['arch'], $_GET['repo'], $List);
        ?>

<?php  endif; ?>

        <h1>APT Chooser</h1>
        <form method="GET"><table>
                <tr><td>Which distribution do you have?</td><td>
                        <select name="repo">
                                <?php foreach(GetRepositories() as $Repo) print "<option value='".$Repo."'>".$Repo."</option>\n"; ?>
                        </select>
                </td><tr>
                <tr><td>What architecture is your distribution compiled for?</td><td>
                        <select name="arch">
                                <?php foreach(GetArchitectures() as $Arch) print "<option value='".$Arch."'>".$Arch."</option>\n"; ?>
                        </select>
                </td><tr>
                <tr><td>What package do you need to install?</td><td><input type="TEXT" name="package" /></td></tr>
                <tr><td>What (meta)package do you already have?</td><td><input type="TEXT" name="have" /></td></tr>
        </table><input type="SUBMIT" value="Find" /></form>
        <p><small>
                Examples of metapackages that are likely to be useful are ''ubuntu-desktop'' and ''kubuntu-desktop''.<br />
                Generally speaking, you should specify a metapackage that matches what is already installed on your system.
        </small></p>

<?php endif; ?>

        <hr>
        <p style="text-align: center;"><small>
                Copyright 2006 <a href="mailto:ljlbox@tiscali.it">Lorenzo J. Lucchini</a><br />
                The source code for this web application is available under the terms of the
                <a href="<?php print $GPLFile; ?>">GNU General Public Licence</a>.
                <form method="GET"><input type="SUBMIT" name="source" value="Download" /></form>
        </small></p>

</body></html>
