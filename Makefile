# Makefile for relman
# $Id: Makefile 43 2008-05-27 15:30:53Z izzy $

DESTDIR=
INSTALL=install
INSTALL_PROGRAM=$(INSTALL)
INSTALL_DATA=$(INSTALL) -m 644
prefix=/usr/local
exec_prefix=$(prefix)
bindir=$(exec_prefix)/bin
datarootdir=$(prefix)/share
datadir=$(datarootdir)
docdir=$(datarootdir)/doc/pvptools
mandir=$(datarootdir)/man
man8dir=$(mandir)/man8

install: installdirs
	$(INSTALL) -c *.php $(DESTDIR)$(bindir)
	$(INSTALL_DATA) -c doc/* $(DESTDIR)$(docdir)
	#gzip man/*
	#$(INSTALL_DATA) -c man/*.8* $(DESTDIR)$(man8dir)

uninstall:
	rm -f $(DESTDIR)$(bindir)/get_movie_ids.php $(DESTDIR)$(bindir)/get_name_ids.php
	rm -rf $(DESTDIR)$(docdir)
	#rm -f $(DESTDIR)$(man8dir)/get_movie_ids.* $(DESTDIR)$(man8dir)/get_name_ids.*

installdirs:
	# Generate all required target directories (due to DESTDIR, i.e. all)
	mkdir -p $(DESTDIR)$(docdir)
	if [ ! -d $(DESTDIR)$(bindir) ]; then mkdir -p $(DESTDIR)$(bindir); fi
	#if [ ! -d $(DESTDIR)$(man8dir) ]; then mkdir -p $(DESTDIR)$(man8dir); fi
