#!/usr/bin/perl
use POSIX qw(strftime);

#==========================================================================
# BAM (Bugzilla Automated Metrics): update_all_products_statistics.pl
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

#
# Generate summary statistics for all products on the basis of statistics calculated for each product
#

$help = <<'!END!';
Generate summary statistics for all products on the basis of statistics calculated for each product.

usage: update_all_products_statistics.pl -d|-w <config file> <changes_outfile>
	-d - daily statistics
	-w - weekly statistics
	<config file> - config file - full path
	<changes_outfile> - day for which whe should calculate summary statistics - it's a third element from 'daily_stats'|'weekly_stats' file.
!END!


die "ERROR: expected 3 arguments - you have provided " . ($#ARGV+1) . " arguments..\n\n$help" unless ($#ARGV == 2);
die "ERROR: Found '".$ARGV[0]."' when expected '-d' or '-w' - exit." unless ($ARGV[0] eq "-d" or $ARGV[0] eq "-w");

if ($ARGV[0] eq "-h" || $ARGV[0] eq "--help") {
	print "\n$help";
	exit -1;
}

$DAILY_STATS = 1;
if ($ARGV[0] eq "-w") {$DAILY_STATS = 0;}
$CONFIG_FILE = $ARGV[1];
$CHANGES_OUTFILE = $ARGV[2];
$WEEK_DAY = "";
$SNAPSHOT_TAKEN_TIME = "";

die "Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS");

# 'Static' variables - it's better to not touch them
$ALL_PRODUCTS_DIR = "all";
$STATS_OUTFILE = "weekly_stats";
if ($DAILY_STATS == 1) {
	$STATS_OUTFILE = "daily_stats";
}

@stats_all  = (0,0,0,0,0,0, 0,0, 0,0, 0,0);

if (! -e "$STATS_FOLDER/$ALL_PRODUCTS_DIR") {
	mkdir "$STATS_FOLDER/$ALL_PRODUCTS_DIR" or die "Cannot create folder '$STATS_FOLDER/$ALL_PRODUCTS_DIR': $!";
}
if (-e "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$CHANGES_OUTFILE") {
	execute_command("rm $STATS_FOLDER/$ALL_PRODUCTS_DIR/$CHANGES_OUTFILE");
}

opendir(C_DIR, $STATS_FOLDER) || die "can't open folder: $STATS_FOLDER: $!";
foreach my $product (sort (readdir C_DIR ) )
{
	#if ($product eq "." || $product eq ".." || $product eq $ALL_PRODUCTS_DIR || $product =~ /_-_/) {
	if ($product eq "." || $product eq ".." || $product eq $ALL_PRODUCTS_DIR) {
		next;
	}
	if (! -d "$STATS_FOLDER/$product") {
		next;
	}

	if (-e "$STATS_FOLDER/$product/$STATS_OUTFILE_DAILY") {
		open(STATSFILE, "<", "$STATS_FOLDER/$product/$STATS_OUTFILE") || die "can't open a file for reading: $STATS_FOLDER/$product/$STATS_OUTFILE";
		while ( $a = <STATSFILE> )
		{
			if ($a  =~ /$CHANGES_OUTFILE/) {
				my @columns = split(";;;", $a);

				$WEEK_DAY = $columns[0];
				$SNAPSHOT_TAKEN_TIME = $columns[1];
				
				$stats_all[0] += $columns[3];
				$stats_all[1] += $columns[4];
				$stats_all[2] += $columns[5];
				$stats_all[3] += $columns[6];
				$stats_all[4] += $columns[7];
				$stats_all[5] += $columns[8];
				$stats_all[6] += $columns[9];
				$stats_all[7] += $columns[10];
				$stats_all[8] += $columns[11];
				$stats_all[9] += $columns[12];
				$stats_all[10] += $columns[13];
				$stats_all[11] += $columns[14];

				execute_command("cat $STATS_FOLDER/$product/$CHANGES_OUTFILE >> $STATS_FOLDER/$ALL_PRODUCTS_DIR/$CHANGES_OUTFILE");
			}
		}
		close STATSFILE;
	}
}
closedir C_DIR;

die "Cannot find entries with '$CHANGES_OUTFILE' in products' stats files - exit." if ($WEEK_DAY eq "" || $SNAPSHOT_TAKEN_TIME eq "");


if (-e "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE") {
	execute_command("cp $STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE $STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak");
	die "ERROR: Cannot create backup file: $STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak - exit." unless (-e "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak");
}

open(STATSFILE, ">", "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE") || die "can't open a file for writing: $STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE";
$added = 0;

# check if entry with the same first column already exists
if (-e "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak") {
	open(STATSFILE_PREV, "<", "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak") || die "ERROR: can't open a backup file for reading: $STATS_FOLDER/$ALL_PRODUCTS_DIR/$STATS_OUTFILE.bak";
	while ( $a = <STATSFILE_PREV> )
	{
		if ( $a =~ /$CHANGES_OUTFILE/ ) {
			# replace this line with new entry
			print STATSFILE "$WEEK_DAY;;;$SNAPSHOT_TAKEN_TIME;;;$CHANGES_OUTFILE;;;";
			print STATSFILE $stats_all[0] . ";;;";
			print STATSFILE $stats_all[1] . ";;;";
			print STATSFILE $stats_all[2] . ";;;";
			print STATSFILE $stats_all[3] . ";;;";
			print STATSFILE $stats_all[4] . ";;;";
			print STATSFILE $stats_all[5] . ";;;";
			print STATSFILE $stats_all[6] . ";;;";
			print STATSFILE $stats_all[7] . ";;;";
			print STATSFILE $stats_all[8] . ";;;";
			print STATSFILE $stats_all[9] . ";;;";
			print STATSFILE $stats_all[10] . ";;;";
			print STATSFILE $stats_all[11] . "\n";
			$added = 1;
		} elsif ( $a eq "") {
			# skip empty line
		} else {
			# keep this line
			print STATSFILE $a;
		}
	}
	close STATSFILE_PREV;
}

if ($added != 1)
{
	print STATSFILE "$WEEK_DAY;;;$SNAPSHOT_TAKEN_TIME;;;$CHANGES_OUTFILE;;;";
	print STATSFILE $stats_all[0] . ";;;";
	print STATSFILE $stats_all[1] . ";;;";
	print STATSFILE $stats_all[2] . ";;;";
	print STATSFILE $stats_all[3] . ";;;";
	print STATSFILE $stats_all[4] . ";;;";
	print STATSFILE $stats_all[5] . ";;;";
	print STATSFILE $stats_all[6] . ";;;";
	print STATSFILE $stats_all[7] . ";;;";
	print STATSFILE $stats_all[8] . ";;;";
	print STATSFILE $stats_all[9] . ";;;";
	print STATSFILE $stats_all[10] . ";;;";
	print STATSFILE $stats_all[11] . "\n";
}

close STATSFILE;


# ======================================================================
# read parameter from config file:
# - first try to read it from $COMMON_PARAMS_FILE
# - then try to read it from $CONFIG_FILE
# if parameter does not exist in any file and it cannot been empty (the second parameter provided to this method) then report failure
# if parameter exists in both config files, return the one from $CONFIG_FILE
# 
sub read_config_entry {
	if ($COMMON_PARAMS_FILE ne "") {
		chomp($ret_common = execute_command("grep \"^" . @_[0] . " =\" $COMMON_PARAMS_FILE | awk 'BEGIN{FS=\" = \"} {print \$2}'"));
	}
	chomp($ret = execute_command("grep \"^" . @_[0] . " =\" $CONFIG_FILE | awk 'BEGIN{FS=\" = \"} {print \$2}'"));
	if ($ret ne "") {
		return $ret;
	} elsif ($ret_common ne "") {
		return $ret_common;
	} elsif (@_[1] ne "can be empty") {
		fatal("config entry " . @_[0] . " not found or it's value is empty. Please fix config file: $CONFIG_FILE");
	}
}


# execute shell command
sub execute_command {
	my $command = @_[0];
	#print "COMMMMMMMAND:  $command\n";
	my $ret = `$command 2<&1`;
	return $ret;
}


