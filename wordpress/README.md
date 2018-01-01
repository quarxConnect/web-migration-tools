# Migration-Tools for Wordpress
Detect and migrate remote wordpress-setups

## Usage
Copy `site.ini.example` to `site.ini` and edit the file to match the
desired setup. If the remote path of a wordpress-installation is not
known you may use `ftp-detect.php` to localize all wordpress-setups
at the remote site.

`ftp-migrate.php` migrates all files and the database of a remote
wordpress-setup to the local site and adjusts all required values.

## License
Copyright (C) 2010-18 Bernd Holzmüller

Licensed under the MIT License. This is free software: you are free
to change and redistribute it. There is NO WARRANTY, to the extent
permitted by law.
