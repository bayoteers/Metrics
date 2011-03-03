#!/usr/bin/perl
use POSIX qw(strftime);

#==========================================================================
# BAM (Bugzilla Automated Metrics): compare_snapshots.pl
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

$help = <<'!END!';
usage: compare_snapshots.pl -d|-w <config file> <product> <snapshot file 1>|none <snapshot file 2> [<not_verifiable_bugs>]
	-d - daily statistics
	-w - weekly statistics
	<config file> - config file - full path
	<product> = product name (subfolder name)
	<snapshot file 1>, <snapshot file 2> - CSV shapshot files from bugzilla (raw data) - name without path
	<not_verifiable_bugs_file_name> - full path to the file with list of resolved bug IDs, which cannot be currently verified becasue of open dependencies

This script compares <snapshot file 1> with <snapshot file 2> and calculate the changes in bugs' statuses between these files.

In case when there is only one file, use "none" as a <snapshot file 1> - in such case only current status (number of active and resolved bugs) will be calculated; changes in bugs statuses (inflow, outflow, etc.) will be set 0.

!END!

die "ERROR: expected 6 arguments - you have provided " . ($#ARGV+1) . " arguments..\n$help" unless ($#ARGV > 4);
die "ERROR: Found '".$ARGV[0]."' when expected '-d' or '-w' - exit." unless ($ARGV[0] eq "-d" or $ARGV[0] eq "-w");

if ($ARGV[0] eq "-h" || $ARGV[0] eq "--help") {
	print "\n$help";
	exit -1;
}

$DAILY_STATS = 1;
if ($ARGV[0] eq "-w") {$DAILY_STATS = 0; }
$CONFIG_FILE = $ARGV[1];
$PRODUCT = $ARGV[2];
$SNAPSHOT_FILE_1 = $ARGV[3];
$SNAPSHOT_FILE_2 = $ARGV[4];
$BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME = $ARGV[5];
$BUGS_WHICH_CANNOT_BE_VERIFIED = "";
open(DEPFILE, "<", "$BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME") || fatal("can't open a file for reading: $BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME");
while ( $a = <DEPFILE> )
{
	$BUGS_WHICH_CANNOT_BE_VERIFIED .= $a;
}
close DEPFILE;

die "ERROR: Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

# 'Static' variables - it's better to not touch them
$RAW_DATA_DIR = "raw_data";
$ALL_PRODUCTS_DIR = "all";
$STATS_OUTFILE = "weekly_stats";
if ($DAILY_STATS == 1) {
	$STATS_OUTFILE = "daily_stats";
}

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$OUTPUT_PATH = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS") . "/" . $PRODUCT;
$SNAPSHOTS_PATH = $OUTPUT_PATH . "/" . $RAW_DATA_DIR;

$BUGZILLA_SNAPSHOT_COLUMN_LIST = ",bug_severity,priority,short_desc,";
$SUBGROUPS_COLUMN_NAME = read_config_entry("SUBGROUPS_COLUMN_NAME", "can be empty");
if ($SUBGROUPS_COLUMN_NAME ne "") {
	$BUGZILLA_SNAPSHOT_COLUMN_LIST .= "$SUBGROUPS_COLUMN_NAME,";
}

# variables needed to create links in GUI
@BUGS_OPEN = read_config_rule(read_config_entry("BUGS_OPEN"), 1);
@BUGS_FIXED = read_config_rule(read_config_entry("BUGS_FIXED"), 1);
@BUGS_RELEASED = read_config_rule(read_config_entry("BUGS_RELEASED"), 1);
@BUGS_CLOSED = read_config_rule(read_config_entry("BUGS_CLOSED"), 1);
@BUGS_RELEASEABLE = read_config_rule(read_config_entry("BUGS_RELEASEABLE"), 1);
@BUGS_NOT_CONFIRMED = read_config_rule(read_config_entry("BUGS_NOT_CONFIRMED"), 1);
@BUGS_DEPENDS_ON_DEPENDENCIES = read_config_rule(read_config_entry("BUGS_DEPENDS_ON_DEPENDENCIES"), 1);

$SUBGROUPS_FILE_NAME = "";
if ($SUBGROUPS_COLUMN_NAME ne "") {
	$SUBGROUPS_FILE_NAME = "$OUTPUT_PATH/subgroups";
}



die "ERROR: Shapshot file 1 does not exists: $SNAPSHOT_FILE_1 - exit." unless ($SNAPSHOT_FILE_1 eq "none" or -e "$SNAPSHOTS_PATH/$SNAPSHOT_FILE_1");
die "ERROR: Shapshot file 1 is empty: $SNAPSHOT_FILE_1 - exit." if (-z "$SNAPSHOTS_PATH/$SNAPSHOT_FILE_1");
die "ERROR: Shapshot file 2 does not exists: $SNAPSHOT_FILE_2 - exit." unless (-e "$SNAPSHOTS_PATH/$SNAPSHOT_FILE_2");
die "ERROR: Shapshot file 2 is empty: $SNAPSHOT_FILE_2 - exit." if (-z "$SNAPSHOTS_PATH/$SNAPSHOT_FILE_2");

# Calculating date variables
@days_of_weeks = ("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
if ($SNAPSHOT_FILE_1 eq "none")
{
	# NOTE: this "if" will fail in case when statistics generation starts in first day of the year - funny data will be generated (eg. "week 0")
	($year_n, $month_n, $day_n, $hour_n, $minute_n) = ($SNAPSHOT_FILE_2 =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
	$week_of_year = strftime "%V", (0,  $minute_n, $hour_n, $day_n, $month_n-1, $year_n-1900);
	$day_of_week_no = strftime "%u", (0,  $minute_n, $hour_n, $day_n, $month_n-1, $year_n-1900);
	$year = $year_n;
	$day_of_week_no--;
	if ($day_of_week_no == 0) {
		# if it's monday then we assume that snapshot contains data from Fri to Sun of previous week..
		$day_of_week_no = 5;
		$day_of_week = $days_of_weeks[4] . "-" . $days_of_weeks[6];
		$week_of_year--;
		if ($week_of_year < 10) {
			$week_of_year = "0" . $week_of_year;
		}
		# when $week_of_year == 0, we should change $year to $year-1 and $week_of_year to 53?
	} else {
		# .. in other case we assume that snapshot contains data from last day only
		$day_of_week = $days_of_weeks[ $day_of_week_no-1 ];
	}
	if ($week_of_year > 50 && $month_n == 1) {
		$year--;
	}
}
else
{
	($year, $month, $day, $hour, $minute) = ($SNAPSHOT_FILE_1 =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
	$week_of_year = strftime "%V", (0, $minute, $hour, $day, $month-1, $year-1900);
	$day_of_week_no = strftime "%u", (0, $minute, $hour, $day, $month-1, $year-1900);
	$day_of_week = $days_of_weeks[ $day_of_week_no-1 ];
	($year_n, $month_n, $day_n, $hour_n, $minute_n) = ($SNAPSHOT_FILE_2 =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
	$day_of_week_no_new = strftime "%u", (0, $minute_n, $hour_n, $day_n, $month_n-1, $year_n-1900);
	if ($day_of_week_no_new == 1) {
		$day_of_week_no_new += 7;
	}
	$day_of_week_no_new--;
	if ($day_of_week_no_new != $day_of_week_no) {
		$day_of_week .= "-" . $days_of_weeks[ $day_of_week_no_new-1 ];
	}
	if ($week_of_year > 50 && $month == 1) {
		$year--;
	}
}
$SNAPSHOT_TAKEN_TIME = strftime "%a, %e %b %Y, %R", (0, $minute_n, $hour_n, $day_n, $month_n-1, $year_n-1900);
$CHANGES_OUTFILE = $year . "_" . $week_of_year;
$DAY = "Week $week_of_year";
if ($DAILY_STATS == 1) {
	$CHANGES_OUTFILE .= "_" . $day_of_week_no;
	$DAY .= ", $day_of_week";
}

$STATUS_ACTIVE = "active";
$STATUS_OPEN = "open";
$STATUS_FIXED = "fixed";
$STATUS_RELEASED = "released";
$STATUS_CLOSED = "closed";
$STATUS_UNKNOWN = "unknown";

$STATUS_VERIFIABLE = "verifiable";
$STATUS_NOT_RELEASED = "not_released";
$STATUS_NOT_CONFIRMED = "unconfirmed";
$STATUS_RELEASEABLE = "releaseable";
$STATUS_DEPENDS_ON_DEPENDENCIES = "depends_on_dependencies";

$STATUS_NEW = "new";
$STATUS_REOPENED = "reopened";
$STATUS_RESOLVED = "resolved";
$STATUS_MOVED_OUT = "moved_out";


# ======================================================================
%snapshot_prev = ();
%snapshot_new  = ();
%results       = ();
%columns       = ();

my @columns_list_tmp = split(",", $BUGZILLA_SNAPSHOT_COLUMN_LIST);
my @columns_list = ();
for $a (@columns_list_tmp) {
	if ($a ne "") {
		push(@columns_list, $a);
	}
}

prepare_results_array("all_bugs");

# READ RAW DATA
if ($SNAPSHOT_FILE_1 ne "none") {
	%snapshot_prev = read_snapshot_file("$SNAPSHOTS_PATH/$SNAPSHOT_FILE_1");
}
%snapshot_new  = read_snapshot_file("$SNAPSHOTS_PATH/$SNAPSHOT_FILE_2");

# COMPARE SNAPSHOTS
compare_snapshots();


# SAVE RESULTS IN STATS FILE
save_results();

# RETURN REQUIRED DATA
print "$DAY;$CHANGES_OUTFILE";
exit 0;



# ======================================================================
# Read input file (bugs' snapshot) and return list of bugs in the array.
# @param 1: input file
# return bugs list in hash.
sub read_snapshot_file
{
	my $input_file = @_[0];
	my %output_array  = {};
	%columns = ();
	my $line_no = 0;
	my $col_no = 0;
	my $bug_id_col_no = -1;

	foreach my $column_name (@columns_list) {
		$columns{$column_name} = -1;
	}


	open(INFILE, "<", $input_file);
	while ( $line = <INFILE> )
	{
		$line =~ s/^\s+//;
		$line =~ s/\s+$//;
		if ($line eq "") {
			next;
		}
		
		$line_no += 1;
		#print "$line\n";
		$line =~ s/(.*)\s$/$1/g;
		$line =~ s/bug_id,/bug_id",/g;
		$line =~ s/,bug_id/,"bug_id/g;
		$line =~ s/^(\d+),/$1",/g;
		$line =~ s/,(\d+)/,"$1/g;
		$line =~ s/",,"/","","/g;
		$line =~ s/",-,"/","-","/g;
		$line =~ s/"$//g;
		$line =~ s/",$/","/g;

		my @elements = split("\",\"", $line);

		if ($line_no == 1)
		{
			$col_no = 0;
			foreach my $element (@elements)
			{
				$element =~ s/"//g;
				if ($element eq "bug_id") {
					$bug_id_col_no = $col_no;
				} else {
					foreach my $column_name (@columns_list) {
						if ($element eq $column_name) {
							$columns{$column_name} = $col_no;
						}
					}
				}
				$col_no += 1;
			}
			
			foreach my $column_name (@columns_list) {
				if ($columns{$column_name} == -1) {
					die "ERROR: Wrong input file: '$input_file' - cannot find column '$column_name'";
				}
			}
			if ($columns{"bug_id"} == -1) {
				die "ERROR: Wrong input file: '$input_file' - cannot find column 'bug_id'";
			}
		}
		else
		{
			$bug_id = $elements[$bug_id_col_no];
			
			if ($bug_id eq "bug_id") {
				next;
			}

			$elements[$columns{"short_desc"}] =~ s/^(.*)"$/$1/g;
			
			# status (open | resolved | released | closed | unknown == error)
			# not_confirmed ( 0 | 1 )
			# releaseable ( 0 | 1 )
			# verifiable ( 0 | 1 )
			# subgroup_name ("string")
			# details ("string")
			$output_array{$bug_id}{"not_confirmed"} = 0;
			$output_array{$bug_id}{"releaseable"} = 0;
			$output_array{$bug_id}{"verifiable"} = 1;
			if ( $SUBGROUPS_COLUMN_NAME ne "") {
				$output_array{$bug_id}{"subgroup_name"} = $elements[$columns{$SUBGROUPS_COLUMN_NAME}];
			} else {
				$output_array{$bug_id}{"subgroup_name"} = "";
			}
			$output_array{$bug_id}{"details"} = ";;;$bug_id;;;" . $elements[$columns{"bug_severity"}] . ";;;" . $elements[$columns{"priority"}] . ";;;" . $elements[$columns{"short_desc"}]  . "\n";

			if ( match_to_the_rule($STATUS_OPEN, @elements) == 1 )
			{
				$output_array{$bug_id}{"status"} = $STATUS_OPEN;
				if ( match_to_the_rule($STATUS_NOT_CONFIRMED, @elements) == 1 ) {
					$output_array{$bug_id}{"not_confirmed"} = 1;
				}
			}
			elsif ( match_to_the_rule($STATUS_FIXED, @elements) == 1 )
			{
				$output_array{$bug_id}{"status"} = $STATUS_FIXED;
			}
			elsif ( match_to_the_rule($STATUS_RELEASED, @elements) == 1 )
			{
				$output_array{$bug_id}{"status"} = $STATUS_RELEASED;
			}
			elsif ( match_to_the_rule($STATUS_CLOSED, @elements) == 1 )
			{
				$output_array{$bug_id}{"status"} = $STATUS_CLOSED;
			}
			else
			{
				$output_array{$bug_id}{"status"} = $STATUS_UNKNOWN;
			}
			
			if ( match_to_the_rule($STATUS_RELEASEABLE, @elements) == 1 ) {
				$output_array{$bug_id}{"releaseable"} = 1;
			}
			
			if ( match_to_the_rule($STATUS_DEPENDS_ON_DEPENDENCIES, @elements) == 1 && index($BUGS_WHICH_CANNOT_BE_VERIFIED, ",$bug_id,") > -1) {
				$output_array{$bug_id}{"verifiable"} = 0;
			}

			#print $bug_id . ": " . $output_array{$bug_id}{"status"}
			#	 . " c" . $output_array{$bug_id}{"not_confirmed"}
			#	 . " r" . $output_array{$bug_id}{"releaseable"}
			#	 . " v" . $output_array{$bug_id}{"verifiable"}
			#	 . " (" . $output_array{$bug_id}{"subgroup_name"}
			#	 . ") " . $output_array{$bug_id}{"details"};
		}
	}
	close INFILE;

	if ($line_no == 0) {
		die "ERROR: Wrong input file: '$input_file' - file is empty";
	}

	return %output_array;
}

sub match_to_the_rule
{
	my ($rule_type, @bug) = @_;
	my @rule;
	
	if ($rule_type eq $STATUS_OPEN) {
		@rule = @BUGS_OPEN;
	} elsif ($rule_type eq $STATUS_FIXED) {
		@rule = @BUGS_FIXED;
	} elsif ($rule_type eq $STATUS_RELEASED) {
		@rule = @BUGS_RELEASED;
	} elsif ($rule_type eq $STATUS_CLOSED) {
		@rule = @BUGS_CLOSED;
	} elsif ($rule_type eq $STATUS_RELEASEABLE) {
		@rule = @BUGS_RELEASEABLE;
	} elsif ($rule_type eq $STATUS_NOT_CONFIRMED) {
		@rule = @BUGS_NOT_CONFIRMED;
	} elsif ($rule_type eq $STATUS_DEPENDS_ON_DEPENDENCIES) {
		@rule = @BUGS_DEPENDS_ON_DEPENDENCIES;
	}

	for $i ( 0 .. $#rule ) {
		$matches_and = 1;
		for my $param_name (keys  %{$rule[$i]}) {
			my $param_values = $rule[$i]{$param_name};
			#print "-- $param_name:  $param_values\n===" . $bug[$columns{$param_name}] . "\n";
			my $matches_or = 0;
			my @params = split(/,/, $param_values);

			foreach my $param (@params) {
				if ( $param eq $bug[$columns{$param_name}] ) {
					#print "$param - " . $bug[$columns{$param_name}] . " == maching\n";
					$matches_or = 1;
					last;
				} else {
					#print "$param - " . $bug[$columns{$param_name}] . " == NOT maching\n";
				}
			}
			if ($matches_or == 0) {
				$matches_and = 0;
				last;
			}
		}
		if ($matches_and == 1) {
			return 1;
		}
	}
	return 0;
}

# ======================================================================
# check differences in bugs' statuses between previous and new snapshots (%snapshot_prev and %snapshot_new hashes)
# save the result (numbers) to the $STATS_OUTFILE file
# save the list of changes in bugs' statuses to the $CHANGES_OUTFILE in format:
#   <status>;;;<bug_id>;;;<bug_severity>;;;<priority>;;;<short_dest>
#   where <status> == active|verifiable|not_released|open|unconfirmed|new|reopened|resolved|moved_out|released|closed
# This file is used by web page ot display details of changes
sub compare_snapshots
{
	# check differences between old and new snapshots
	foreach $bug_id ( sort (keys %snapshot_new) )
	{
		if ( ! exists $snapshot_new{$bug_id} || ! exists $snapshot_new{$bug_id}{"status"} ) {
			next;
		}
		
		my $bug_details = $snapshot_new{$bug_id}{"details"};
		my $bug_status = $snapshot_new{$bug_id}{"status"};
		my $bug_not_confirmed = $snapshot_new{$bug_id}{"not_confirmed"};
		my $bug_releaseable = $snapshot_new{$bug_id}{"releaseable"};
		my $bug_verifiable = $snapshot_new{$bug_id}{"verifiable"};
		my $bug_prev_status = "";
		my $bug_prev_releaseable = 0;
		
		#print "$bug_id, $bug_status, $bug_not_confirmed, $bug_releaseable, $bug_verifiable | $bug_prev_status ($bug_details";
		
		if ( $SNAPSHOT_FILE_1 ne "none" && exists $snapshot_prev{$bug_id} ) {
			$bug_prev_status = $snapshot_prev{$bug_id}{"status"};
			$bug_prev_releaseable = $snapshot_prev{$bug_id}{"releaseable"};
		}

		my $subgroup_name = "";
		if ( $SUBGROUPS_COLUMN_NAME ne "" && ! exists $output{$snapshot_new{$bug_id}{"subgroup_name"}} ) {
			$subgroup_name = $snapshot_new{$bug_id}{"subgroup_name"};
		}

		if ($bug_status eq $STATUS_OPEN)
		{
			add_to_results($STATUS_ACTIVE, $bug_details, $subgroup_name);
			add_to_results($STATUS_OPEN, $bug_details, $subgroup_name);

			if ($bug_not_confirmed == 1) {
				add_to_results($STATUS_NOT_CONFIRMED, $bug_details, $subgroup_name);
			}
			
			if ($SNAPSHOT_FILE_1 ne "none" )
			{
				if ( $bug_prev_status eq "" )
				{
					# bug is new
					add_to_results($STATUS_NEW, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_OPEN)
				{
					# no changes, bug still not resolved
				}
				elsif ($bug_prev_status eq $STATUS_FIXED || $bug_prev_status eq $STATUS_RELEASED || $bug_prev_status eq $STATUS_CLOSED)
				{
					# bug has been reopened
					add_to_results($STATUS_REOPENED, $bug_details, $subgroup_name);
				}
				else
				{
					print "Error: not recognized previous status of the bug ".$bug_id.": '".$bug_prev_status."'\n";
				}
			}
		}

		elsif ($bug_status eq $STATUS_FIXED)
		{
			add_to_results($STATUS_ACTIVE, $bug_details, $subgroup_name);

			if ($bug_releaseable == 1) {
				add_to_results($STATUS_NOT_RELEASED, $bug_details, $subgroup_name);
			} elsif ($bug_verifiable == 1) {
				add_to_results($STATUS_VERIFIABLE, $bug_details, $subgroup_name);
			}

			if ($SNAPSHOT_FILE_1 ne "none")
			{
				if ($bug_prev_status eq "")
				{
					# bug is new and resolved
					add_to_results($STATUS_NEW, $bug_details, $subgroup_name);
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_OPEN)
				{
					# bug has been resolved
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_FIXED)
				{
					# no changes, bug is still resolved
				}
				elsif ($bug_prev_status eq $STATUS_RELEASED || $bug_prev_status eq $STATUS_CLOSED)
				{
					# bug has been reopened and resolved
					add_to_results($STATUS_REOPENED, $bug_details, $subgroup_name);
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
				}
				else
				{
					print "Error: not recognized previous status of the bug ".$bug_id.": '".$bug_prev_status."'\n";
				}
			}
		}
		
		elsif ($bug_status eq $STATUS_RELEASED)
		{
			add_to_results($STATUS_ACTIVE, $bug_details, $subgroup_name);

			if ($bug_verifiable == 1) {
				add_to_results($STATUS_VERIFIABLE, $bug_details, $subgroup_name);
			}

			if ($SNAPSHOT_FILE_1 ne "none")
			{
				if ($bug_prev_status eq "")
				{
					# bug is new, resolved and released
					add_to_results($STATUS_NEW, $bug_details, $subgroup_name);
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
					add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_OPEN)
				{
					# bug has been resolved and released
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
					add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_FIXED)
				{
					# bug has been released
					add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_RELEASED)
				{
					# no changes, bug is still released
				}
				elsif ($bug_prev_status eq $STATUS_CLOSED)
				{
					# bug has been reopened, resolved and released
					add_to_results($STATUS_REOPENED, $bug_details, $subgroup_name);
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
					add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
				}
				else
				{
					print "Error: not recognized previous status of the bug ".$bug_id.": '".$bug_prev_status."'\n";
				}
			}
		}
		
		elsif ($bug_status eq $STATUS_CLOSED)
		{
			if ($SNAPSHOT_FILE_1 ne "none")
			{
				if ($bug_prev_status eq "")
				{
					# bug is new, resolved, released (if it's releaseable) and verified/closed - IGNORE IT
					#add_to_results($STATUS_NEW, $bug_details, $subgroup_name);
					#add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
					#add_to_results($STATUS_CLOSED, $bug_details, $subgroup_name);
					#if ($bug_prev_releaseable == 1) {
					#	add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
					#}
				}
				elsif ($bug_prev_status eq $STATUS_OPEN)
				{
					# bug has been resolved and released (if it's releaseable) and verified/closed
					add_to_results($STATUS_RESOLVED, $bug_details, $subgroup_name);
					add_to_results($STATUS_CLOSED, $bug_details, $subgroup_name);
					if ($bug_prev_releaseable == 1) {
						add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
					}
				}
				elsif ($bug_prev_status eq $STATUS_FIXED)
				{
					# bug has been released (if it's releaseable) and verified/closed
					add_to_results($STATUS_CLOSED, $bug_details, $subgroup_name);
					if ($bug_prev_releaseable == 1) {
						add_to_results($STATUS_RELEASED, $bug_details, $subgroup_name);
					}
				}
				elsif ($bug_prev_status eq $STATUS_RELEASED)
				{
					# bug has been verified/closed
					add_to_results($STATUS_CLOSED, $bug_details, $subgroup_name);
				}
				elsif ($bug_prev_status eq $STATUS_CLOSED)
				{
					# no changes, bug is still verified/closed
				}
				else
				{
					print "Error: not recognized previous status of the bug ".$bug_id.": '".$bug_prev_status."'\n";
				}
			}
		}

		else
		{
			print "Error: not recognized current status of the bug ".$bug_id.": '".$bug_status."'\n";
		}

		delete $snapshot_prev{$bug_id};

	}

	# bug moved out - not exists in new snapshot
	if ($SNAPSHOT_FILE_1 ne "none")
	{
		foreach $bug_id ( sort (keys %snapshot_prev) )
		{
			if ( exists $snapshot_prev{$bug_id} ) {
				$bug_prev_status = $snapshot_prev{$bug_id}{"status"};
				if ($bug_prev_status ne "" && $bug_prev_status ne $STATUS_CLOSED && $bug_prev_status ne $STATUS_UNKNOWN)
				{
					# bug moved out to another product | specific keyword / flag has been removed
					my $subgroup_name = "";
					if ( $SUBGROUPS_COLUMN_NAME ne "" && ! exists $output{$snapshot_prev{$bug_id}{"subgroup_name"}} ) {
						$subgroup_name = $snapshot_prev{$bug_id}{"subgroup_name"};
					}
					add_to_results($STATUS_MOVED_OUT, $snapshot_prev{$bug_id}{"details"}, $subgroup_name);
				}
			}
		}
	}
}

# ======================================================================
sub add_to_results() {
	my $type = @_[0];
	my $bug_details = @_[1];
	my $subgroup_name = @_[2];

	$results{"all_bugs"}{$type}{'count'}++;
	$results{"all_bugs"}{$type}{'list'} .= "$type$bug_details";
	
	if ( $PRODUCT ne $ALL_PRODUCTS_DIR && $subgroup_name ne "" ) {
		$subgroup_name =~ s/ /_/g;
		$subgroup_name =~ s/!//g;
		$subgroup_name =~ s/\(/_/g;
		$subgroup_name =~ s/\)/_/g;
		$subgroup_name =~ s/\[/_/g;
		$subgroup_name =~ s/\]/_/g;
		$subgroup_name =~ s/\//and/g;
		$subgroup_name =~ s/&/and/g;
		$subgroup_name =~ s/&amp;/and/g;
		prepare_results_array($subgroup_name);
		$results{$subgroup_name}{$type}{'count'}++;
		$results{$subgroup_name}{$type}{'list'} .= "$type$bug_details";
	}
}

sub prepare_results_array() {
	my $group_name = @_[0];

	if ( ! exists $results{$group_name} ) {
		$results{$group_name}{$STATUS_ACTIVE}{'count'} = 0;
		$results{$group_name}{$STATUS_ACTIVE}{'list'} = "";
		$results{$group_name}{$STATUS_VERIFIABLE}{'count'} = 0;
		$results{$group_name}{$STATUS_VERIFIABLE}{'list'} = "";
		$results{$group_name}{$STATUS_NOT_RELEASED}{'count'} = 0;
		$results{$group_name}{$STATUS_NOT_RELEASED}{'list'} = "";
		$results{$group_name}{$STATUS_OPEN}{'count'} = 0;
		$results{$group_name}{$STATUS_OPEN}{'list'} = "";
		$results{$group_name}{$STATUS_NOT_CONFIRMED}{'count'} = 0;
		$results{$group_name}{$STATUS_NOT_CONFIRMED}{'list'} = "";
		$results{$group_name}{$STATUS_NEW}{'count'} = 0;
		$results{$group_name}{$STATUS_NEW}{'list'} = "";
		$results{$group_name}{$STATUS_REOPENED}{'count'} = 0;
		$results{$group_name}{$STATUS_REOPENED}{'list'} = "";
		$results{$group_name}{$STATUS_RESOLVED}{'count'} = 0;
		$results{$group_name}{$STATUS_RESOLVED}{'list'} = "";
		$results{$group_name}{$STATUS_MOVED_OUT}{'count'} = 0;
		$results{$group_name}{$STATUS_MOVED_OUT}{'list'} = "";
		$results{$group_name}{$STATUS_RELEASED}{'count'} = 0;
		$results{$group_name}{$STATUS_RELEASED}{'list'} = "";
		$results{$group_name}{$STATUS_CLOSED}{'count'} = 0;
		$results{$group_name}{$STATUS_CLOSED}{'list'} = "";
	}
}

# ======================================================================

# save statistics to stats file $STATS_OUTFILE (daily or weekly)
# in case when entry with the same first column already exists, replace it with the new one
# params:
#  param 0: file name
sub save_results
{
	my @subgroups_list = ();
	
	foreach $group_name (keys %results)
	{
		my $stats_file =  "$OUTPUT_PATH/$STATS_OUTFILE" . ($group_name eq "all_bugs" ? "" : "_$group_name");
		my $changes_file =  "$OUTPUT_PATH/$CHANGES_OUTFILE" . ($group_name eq "all_bugs" ? "" : "_$group_name");
		my $stats_data =  @_[0];
		my $changes_data =  @_[0];
		
		if ($group_name ne "all_bugs") {
			push(@subgroups_list, $group_name);
		}

		# create a backup of old file
		if (-e "$stats_file") {
			execute_command("cp $stats_file $stats_file.bak");
			die "ERROR: Cannot create backup file: $stats_file.bak - exit." unless (-e "$stats_file.bak");
		}

		open(STATSFILE, ">", "$stats_file") || die "ERROR: can't open a file for writing: $stats_file";
		
		# check if entry with the same first column already exists
		if (-e "$stats_file.bak") {
			open(STATSFILE_PREV, "<", "$stats_file.bak") || die "ERROR: can't open a backup file for reading: $stats_file.bak";
			while ( $a = <STATSFILE_PREV> )
			{
				if ( $a =~ /$CHANGES_OUTFILE/ || $a eq "") {
					# skip the line
				} else {
					print STATSFILE $a;
				}
			}
			close STATSFILE_PREV;
		}

		print STATSFILE "$DAY;;;$SNAPSHOT_TAKEN_TIME;;;$CHANGES_OUTFILE" . ($group_name eq "all_bugs" ? "" : "_$group_name")
			. ";;;" . $results{$group_name}{'active'}{'count'}
			. ";;;" . ( $results{$group_name}{'active'}{'count'} - $results{$group_name}{'open'}{'count'} )
			. ";;;" . $results{$group_name}{'verifiable'}{'count'} . ";;;" . $results{$group_name}{'not_released'}{'count'}
			. ";;;" . $results{$group_name}{'open'}{'count'} . ";;;" . $results{$group_name}{'unconfirmed'}{'count'}
			. ";;;" . $results{$group_name}{'new'}{'count'} . ";;;" . $results{$group_name}{'reopened'}{'count'}
			. ";;;" . $results{$group_name}{'resolved'}{'count'} . ";;;" . $results{$group_name}{'moved_out'}{'count'}
			. ";;;" . $results{$group_name}{'released'}{'count'} . ";;;" . $results{$group_name}{'closed'}{'count'} . "\n";
		close STATSFILE;

		open(OUTFILE, ">", "$changes_file");
		print OUTFILE $results{$group_name}{'active'}{'list'}
			. $results{$group_name}{'verifiable'}{'list'} . $results{$group_name}{'not_released'}{'list'}
			. $results{$group_name}{'open'}{'list'} . $results{$group_name}{'unconfirmed'}{'list'}
			. $results{$group_name}{'new'}{'list'} . $results{$group_name}{'reopened'}{'list'}
			. $results{$group_name}{'resolved'}{'list'} . $results{$group_name}{'moved_out'}{'list'}
			. $results{$group_name}{'released'}{'list'} . $results{$group_name}{'closed'}{'list'};
		close OUTFILE;
	}

	if ($SUBGROUPS_COLUMN_NAME ne "") {
		# file: "$SUBGROUPS_FILE_NAME" 
		# product: $PRODUCT
		# format: $subgroup1;$subgroup2;$subgroup3;..

		if (-e "$SUBGROUPS_FILE_NAME") {
			execute_command("cp $SUBGROUPS_FILE_NAME $SUBGROUPS_FILE_NAME.bak");
			die "ERROR: Cannot create backup file: $SUBGROUPS_FILE_NAME.bak - exit." unless (-e "$SUBGROUPS_FILE_NAME.bak");
		}

		open(SUBGROUPSFILE, ">", "$SUBGROUPS_FILE_NAME") || die "ERROR: can't open a file for writing: $SUBGROUPS_FILE_NAME";
		
		$subgroups_list = "";

		if (-e "$SUBGROUPS_FILE_NAME.bak") {
			open(SUBGROUPSFILE_PREV, "<", "$SUBGROUPS_FILE_NAME.bak") || die "ERROR: can't open a backup file for reading: $SUBGROUPS_FILE_NAME.bak";
			while ( $a = <SUBGROUPSFILE_PREV> )
			{
				$a =~ s/^\s+//;
				$a =~ s/\s+$//;
				$subgroups_list .= $a;
			}
			close SUBGROUPSFILE_PREV;
		}
		
		if ($subgroups_list eq "") {
			$subgroups_list = ";";
		}
		
		foreach $group_name (@subgroups_list) {
			if ( ! ($subgroups_list =~ /;$group_name;/) )
			{
				$subgroups_list .= "$group_name;";
			}
		}
		print SUBGROUPSFILE "$subgroups_list\n";

		close SUBGROUPSFILE;
	}

}

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
	return "";
}

sub read_config_rule() {
	my @ret = ();
	my $i_or = 0;
	my @elements_or = split(/\|\|/, $_[0]);
	foreach my $element_or (@elements_or) {
		my @elements_and = split("&", $element_or);
		foreach my $element_and (@elements_and) {
			my ($param_name, $param_values) = split("=", $element_and);
			$ret[$i_or]{$param_name} = $param_values;
			#my @params = split(/,/, $param_values);
			#$ret[$i_or]{$param_name} = @params;
			if (@_[1] == 1) {
				if ($BUGZILLA_SNAPSHOT_COLUMN_LIST =~ /,$param_name,/) {
				} else {
					$BUGZILLA_SNAPSHOT_COLUMN_LIST .= "$param_name,";
				}
			}
		}
		$i_or++;
	}
	return @ret;
}


# execute shell command
sub execute_command {
	my $command = @_[0];
	my $ret = `$command 2<&1`;
	return $ret;
}



