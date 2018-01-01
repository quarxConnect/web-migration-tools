# Tools for migration of websites
This repository contains a set of tools used by our support-team to
migrate websites and applications from foreign webhosters to our own
infrastructure.

## Index
`hybrid-dump` allows to dump a MySQL-Database via HTTP by uploading a
small dump-script via FTP. Other tools like phpMyAdmin are not
required, it is all stand-alone.

`ftp` contains `ftp-mirror`, a standalone-tool to mirror a remote
ftp-directory to a local directory.

`wordpress` contains a tool to search an ftp-directory for wordpress-
installations and another tool to migrate an entire wordpress-setup
to the local server.

## License
Copyright (C) 2010-18 Bernd Holzmüller

Licensed under the MIT License. This is free software: you are free
to change and redistribute it. There is NO WARRANTY, to the extent
permitted by law.
