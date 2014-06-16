APT Chooser
===========

This project is originally created by Lorenzo J. Lucchini.
It is essentially generates the download links of an Ubuntu package along with its dependencies.
The user provides a package name for the query.

The original source code is the `index1.php`.

Deployment
----------
This project demands a hosting with minimum requirements, such as:
 1. PHP 5+
 2. MySQL

Database
--------
The database for this project is the files in `/var/lib/apt/lists/` from an Ubuntu
system. Currently it supports only the main repository database.

Example
-------
The example site is  <http://apt-web.tk>