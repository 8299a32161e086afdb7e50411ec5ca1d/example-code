**Code sample**

This project contains code that has been used for the extraction of configuration and data from multiple servers that are running on OpenStack-systems. It was rewritten from a previously existing backup-script and contains only minor amounts of logic that was not written by myself (Silas).

Due to the large amount of servers, different operating systems and the disparity between available targets of export (e.g. MySQL, Tomcat, mongodb, static files) on each system; an approach has been chosen where each target machine has a custom BackupService that meets its requirements.

Please keep in mind that the included code does not represent the project in its entirety and has been heavily modified to be made suitable as a code example. As a result most names of classes and methods have been altered to meaningless values.

As this is an example that contains a partial codebase, actually running the backup script might not be recommended. It would not be possible anyway, at least not without a valid configuration in the environment file.


**Known issues:**
* The current codebase contains configuration that was specific for the setup of this project (i.e. only one OpenStack account and one storage location), therefor it is not really adaptable.
* Some BackupServices contain largely similar methods for frequently recurring tasks (e.g. extracting the NGINX or Apache configuration), these could be moved to the abstract BackupService class and only be overridden when necessary to prevent code duplication.
* rackspace/php-opencloud still depends on guzzle/guzzle which has been abandoned for some time now, ought to re-evaluate actual necessity.