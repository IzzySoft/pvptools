pvptools is a separately distributed package for the
[phpVideoPro](https://github.com/IzzySoft/phpVideoPro) admin. It contains
several scripts for the maintenance of the database (for details, see the
_table of contents_ on the right-hand side of this page). No explicit
installation is required for these scripts: You can simply unpack the archive
where you like, and use the scripts right away. Details on how to use them are
to be found on the corresponding pages - as well as in the text files of the
archives `doc/` directory.

## Installation
### Requirements
Of course the scripts require
[phpVideoPro](https://github.com/IzzySoft/phpVideoPro) itself to be installed -
as they make no sense without that. Next to this, you will need the
command-line version of the PHP interpreter, as these scripts are not intended
to be run from a browser.

Take care to check the text files mentioned above for the required version of
phpVideoPro - if this condition is not met, the scripts probably do no harm to
your database, but they will most likely fail to do their jobs. Best is to
always use the latest version of the tools with the latest version of
phpVideoPro. On an installation of phpVideoPro v0.9.3, for example, the scripts
of pvptools v0.1 will fail due to changed database and code structures, so you
have to use v0.2 here.

**Important:** Before you run any of the IMDB scripts, go to your _phpVideoPro_
web interface, log in as admin, go to *Admin › Configure* and check the IMDB
settings that either caching is disabled or a valid cache directory is
specified. Otherwise you may destroy parts of your installation.

### Installation
There is no special installation required. Simply unpack the downloaded archive
into a directory of your choice, and keep the script files together with the
configuration file in the same directory. Some configuration will, however, be
required - and discussed in its [article](IzzySoft/pvptools/wiki/Configuration).
