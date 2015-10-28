# rc-rank
A tool to calculate ranks in RC laps using RCM software with results published to MyRCM.
This system is not intended to be publicly available on the internet.
You should run it on your internal server and copy the HTML for the ranking list to your website.

Username and password the MyRCM soap API is required to get events.

The system is made to be international and to support multiple federations. All text shown to the user is passed through gettext so they easily can be translated.

##Setup##
The first you need to do is to create a config_[yor federation].php
Use config_sample.php and populate it with the following information:
* Database information (server, database name and credentials)
* When your outdoor season start
* The license ISO code for your country (currently not in use because of too few drivers filling it correctly)
* Username and passord for the MyRCM SOAP api (You get this from Felix Romer, the developer of RCM)
* The different names the clubs might use for laps in the different championships
* Which championships to calculate points for
* Words in the the section names which indicates that they not should be counted

When the config is created you need to run *setup.php* to create the required table structure in the MySQL database.
Now you are ready to use the system.

##Using the system##
First you need to run *load_events.php* to load events and sections from MyRCM. The information is written to the tables *events* and *sections*.
After that you need to run *section_mapping.php* to connect the events and sections to the right championship. This information is written to the table *championships*.
When the events and sections are correctly connected you can run *calculate_ponts.php* to load the results from MyRCM and calculate points for each driver and lap. This information is written to the table *points*.
If the above steps are correctly done you should be able to produce a correct ranking list using *championship_results.php*.