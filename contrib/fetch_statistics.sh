#!/bin/bash

#==========================================================================
# BAM: example configuration file
#
# Copyright 2011, Nokia Oy
# Maintainer: Stephen Jayna <ext-stephen.jayna@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#==========================================================================


find ../conf -name "*.conf" -print0 | while read -d $'\0' file
do
  echo "../bin/fetch_statistics_from_bugzilla.pl $file"
done
