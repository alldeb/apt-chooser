<?php
//file apt-config.php
        $Debug=FALSE;                                  // Determines whether PHP warnings and errors are output
        $BaseURL="http://id.archive.ubuntu.com/ubuntu/";  // The base URL for the used APT repositories
        $GPLFile="gpl.txt";                            // A valid local filename for the GNU GPL in plain text
        $LogFile="apt.log";                            // A local filename for log messages
        $DBHost="";                           // The hostname where the DBMS resides
        $DBName="";                              // The name of the database containing the Packages table
        $DBUser="";                             // A DBMS user who can access and alter the Packages table
        $DBPass="";                            // The password for the DBMS user
        $AdminPass="";                         // A password to access administrator mode
?>