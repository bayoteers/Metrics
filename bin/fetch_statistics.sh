#!/bin/sh

#==========================================================================
# BAM: example configuration file
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

# Example statistics from meego.com
fetch_statistics_from_bugzilla.pl /usr/local/etc/BAM/EXAMPLE_MeeGo_Projects.conf
fetch_statistics_from_bugzilla.pl /usr/local/etc/BAM/EXAMPLE_MeeGo_Projects_-_subset1.conf
fetch_statistics_from_bugzilla.pl /usr/local/etc/BAM/EXAMPLE_MeeGo_Projects_-_subset2.conf
