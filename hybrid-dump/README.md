# hybrid-dump
Remote mysqldump using a combination of FTP and HTTP.

## Usage
~~~ {.bash}
php hybrid-dump.php ftp://{username}:{password}@{domain}{path} mysql://{username}:{password}@{host}/{database} http://{domain}{path} > dump.sql
~~~

`hybrid-dump` will upload the programme `mini-dmp.php` via FTP to the destination-host and invoke the programme via HTTP, which will create a dump of the specified database.
The `{path}` of the ftp://-Parameter must correspond to a `{path}` that may be reached via HTTP (see last http://-Parameter).

## License
Copyright (C) 2010-17 Bernd Holzmüller

Licensed under the MIT License. This is free software: you are free
to change and redistribute it. There is NO WARRANTY, to the extent
permitted by law.
