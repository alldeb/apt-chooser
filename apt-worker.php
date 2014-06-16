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

function DBConnect($Host, $User, $Password, $Database) {
        global $DB;
        $DB = mysql_connect($Host, $User, $Password) or die("Could not connect: ".mysql_error());
        mysql_select_db($Database) or die("Could not select database");
}

function DBEscape($String) {
        return mysql_real_escape_string($String);
}

function GetRepositories() {
        return array_reverse(DBQuery("SELECT DISTINCT Repository FROM Paket", FALSE));
}

function GetComponents() {
        return DBQuery("SELECT DISTINCT Component FROM Paket", FALSE);
}

function GetArchitectures() {
        return DBQuery("SELECT DISTINCT Arch FROM Paket", FALSE);
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
        
        if($Handle=fopen($File, "r"))
        {
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
                        "INSERT INTO Paket (Package, Depends, Recommends, Filename, Description, Repository, Component, Arch, Size)
                        VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                        DBEscape($Name), DBEscape($Depends), DBEscape($Recommends), DBEscape($Filename), DBEscape($Description),
                        DBEscape($Repository), DBEscape($Component), DBEscape($Architecture), DBEscape($Size)
                );
                DBQuery($Query);
                $Count+=1;
        } while(!feof($Handle));
            print "<p class=\"alert alert-success\">".$Count." packages added to ".$Repository." (".$Component.") for ".$Architecture."</p>\n";
        }
        else
        {
            print "Cannot open file";
        }
}


// Return stored information about a package, including dependency list as an array

function GetPackageEntry($Package, $Repository, $Arch) {
        $Entry=DBQuery(sprintf("SELECT * FROM Paket WHERE Package='%s' AND Repository='%s' AND Arch='%s'", DBEscape($Package), DBEscape($Repository), DBEscape($Arch)));
        if(!isset($Entry['Package'])) return FALSE;
        $Entry['Depends']=preg_split("/ [^,]*(, |$)|, /", $Entry['Depends']);
        $Entry['Recommends']=preg_split("/ [^,]*(, |$)|, /", $Entry['Recommends']);
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
        else print "<small><ol>\n";
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
        print "</ol><p><b>Total size: </b>".$TotalSize." bytes <b>(".humanFileSize($TotalSize,'MB').")</b>";
        print "<p>After you have downloaded these packages, put them in a directory and transfered
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

// Set the file size format in the human readable one

function humanFileSize($size,$unit="") {
  if( (!$unit && $size >= 1<<30) || $unit == "GB")
    return number_format($size/(1<<30),2)."GB";
  if( (!$unit && $size >= 1<<20) || $unit == "MB")
    return number_format($size/(1<<20),2)."MB";
  if( (!$unit && $size >= 1<<10) || $unit == "KB")
    return number_format($size/(1<<10),2)."KB";
  return number_format($size)." bytes";
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
        $Handle=fopen("index1.php", "r");
        fpassthru($Handle);
        fclose($Handle);
        die();
}

// Connect to the database

DBConnect($DBHost, $DBUser, $DBPass, $DBName);

// Start buffering, since we might need to scrap HTML output if using JSON

ob_start();
?>
