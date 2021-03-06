# rc-rank
A tool to calculate ranks in RC laps using RCM software with results published to MyRCM and produce ranking lists.

Username and password the MyRCM soap API is required to get events.

The system is made to be international and to support multiple federations. All text shown to the user is passed through gettext so they easily can be translated.

##System requirements##
* [PHP 5.6](http://php.net/) (Older versions may work, but is not tested)
* The following PHP modules:
  - [SOAP](http://php.net/manual/en/book.soap.php)
  - [PDO](http://php.net/manual/en/book.pdo.php) with [MySQL](http://php.net/manual/en/ref.pdo-mysql.php) driver.
* [MariaDB 10](https://mariadb.org/) or [MySQL](http://www.mysql.com/products/community/) (Other databases might work with the correct pdo driver, but is not tested)
* [Apache](https://httpd.apache.org/) web server with [PHP module](http://php.net/manual/en/install.unix.apache2.php) (Any web server supporting PHP should work)

##Setup##
The first you need to do is to create a config\_[your federation].php
Use *config_sample.php* and populate it with the following information:

* Database information (server, database name and credentials)
* When your outdoor season starts and ends
* The license ISO code for your country (currently not in use because of too few drivers filling it correctly)
* Username and password for the MyRCM SOAP (Simple Object Access Protocol) API (You get this from Felix Romer, the developer of RCM)
* The different names the clubs might use for laps in the different championships
* Which championships to calculate points for
* Words in the section names which indicates that they not should be counted

When the config is created you need to run *php setup.php [your federation]* on the command line to create the required table structure in the database.
*setup.php* is the only script running on the command line, the other scripts are made to run in a web browser.

##Using the system##
First you need to run *load\_events.php* to load events and sections from MyRCM. (Related SQL tables: *events* and *sections*).

Then you need to run *section\_mapping.php* to connect the events and sections to the right championship. This information is written to the table *championships*.

When the events and sections are correctly connected you can run *calculate\_points.php* to load the results from MyRCM and calculate points for each driver and lap. This information is written to the table *points*.

If the above steps are correctly done you should be able to produce a correct ranking list using *championship_results.php*
##Calculation##
The drivers are assigned points for each lap based on their final finishing position using the [EFRA](http://news.efra.ws/fileadmin/documents/2011/03/Handb11General.pdf) GP2 table.
Then their ranking is calculated by summing the points for their laps, excluding their worst lap. If they have run three or fewer laps the points for all of their laps are summed.

This is based on the Norwegian (NMF) rules, but if others got different rules, please open an issue so I can implement that too.

##Weaknesses##
The data available from MyRCM gives this system some weaknesses:

* Due to the possibility to register for laps on MyRCM without creating an account there is no unique user id. This means that drivers in different laps are connected only by their full name, so it is impossible to distinguish between two drivers with identical names.
* Different clubs use different naming of the laps which makes it difficult to identify which laps to count as what


##Other##
This system is not intended to be publicly available on the internet.
You should run it on your internal server and copy the HTML for the ranking list to your website.

The data fetching and calculations could be made as background jobs, but with the current data quality you would still need a manual job with connecting events before a correct ranking list could be produced.

The WSDL for the MyRCM SOAP interface got a syntax error, so a fixed version is included in this repository. 

##Support##
For support requests please use the GitHub issue system.

##Contributing##
The project is licensed under GNU General Public License v2.0 and you are welcome to [fork](https://help.github.com/articles/fork-a-repo/) the repository and send me a pull request with your changes.