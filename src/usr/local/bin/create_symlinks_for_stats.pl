#!/usr/bin/perl
use POSIX qw(strftime);

# 1/ read configuratio
# if variable 'SUBSET_OF' is defined then check if existing symlink pointing to correct folders.
# if not, then create new ones.

$help = "1 agrument needed: <config file>\n";

if ($#ARGV != 0) {
	print "ERROR: too few or too many argument(s)\n$help";
	exit -1;
}

$CONFIG_FILE=$ARGV[0];
die "Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$LOG_FILE = read_config_entry("LOG_FILE");
open (LOG, ">>", $LOG_FILE);
info("======================================================================================");
info("START");

$SUBSET_OF = read_config_entry("SUBSET_OF", "can be empty");
$PARENT_STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . $SUBSET_OF;
if ($SUBSET_OF ne "") {
	if (! -e $PARENT_STATS_FOLDER) {
		fatal("Variable 'SUBSET_OF' is defined, but I cannot find parent statistic folder: '$PARENT_STATS_FOLDER'");
	}
}

#$BUGZILLA_URL_BASE = read_config_entry("BUGZILLA_URL_BASE");
# variables needed to fetch the snapshot from BZ
#$BUGZILLA_URL_SNAPSHOT = read_config_entry("BUGZILLA_URL_SNAPSHOT");
#$BUGZILLA_URL_COMMON_PARAMS = read_config_entry("BUGZILLA_URL_COMMON_PARAMS");
# variables needed to create links in GUI
#$VARIABLES_FILE_NAME = read_config_entry("VARIABLES_FILE_NAME");
#$BUGZILLA_URL_REPORT_ACTIVE = read_config_entry("BUGZILLA_URL_REPORT_ACTIVE");
#$BUGZILLA_URL_REPORT_NOT_RELEASED = read_config_entry("BUGZILLA_URL_REPORT_NOT_RELEASED");
#$BUGZILLA_URL_REPORT_OPEN = read_config_entry("BUGZILLA_URL_REPORT_OPEN");
#$BUGZILLA_URL_REPORT_NOT_CONFIRMED = read_config_entry("BUGZILLA_URL_REPORT_NOT_CONFIRMED");
# other
$STATS_URL_BASE = read_config_entry("STATS_URL_BASE");
$STATISTICS = read_config_entry("STATISTICS");
$STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS");
#$ALL_COMPONENTS_DIR = read_config_entry("ALL_COMPONENTS_DIR");
#$RAW_DATA_DIR = read_config_entry("RAW_DATA_DIR");
#$USER = read_config_entry("USER");
#$PASS = read_config_entry("PASS");
$TMP_DIR = read_config_entry("TMP_DIR");
$COMPONENTS_CONFIG_FILE = read_config_entry("PRODUCTS_CONFIG_FILE");
die "File with list of components does not exists: $COMPONENTS_CONFIG_FILE - exit." unless (-e $COMPONENTS_CONFIG_FILE);
#$MAIL_TO = read_config_entry("MAIL_TO", "can be empty");
$ADMIN_MAIL = read_config_entry("ADMIN_MAIL");
#$EMAIL_LAST_DAY_STATISTICS = read_config_entry("EMAIL_LAST_DAY_STATISTICS");
#$EMAIL_COMPONENT_STATISTICS = read_config_entry("EMAIL_COMPONENT_STATISTICS");
#$DAILY_STATS_HISTORY_FILE_NAME = read_config_entry("DAILY_STATS_HISTORY_FILE_NAME");

if (! -e $STATS_FOLDER) {
	if (!mkdir $STATS_FOLDER) {
		fatal("Cannot create statistics' folder '$STATS_FOLDER': $!");
	}
}

if ($SUBSET_OF ne "")
{
	%COMPONENTS = {};
	read_components_list();
	$components_list = "";

	# ==================================================

	for $component ( sort (keys %COMPONENTS) )
	{
		# prepare folder for component's data
		$snapshot_output_dir = "$STATS_FOLDER/$component";

		$parent_snapshot_output_dir = "$PARENT_STATS_FOLDER/$component";
		if (! -e $parent_snapshot_output_dir) {
			print "Warning: cannot find parent statistic folder for component: '$parent_snapshot_output_dir'\n";
		}

		if (-e $snapshot_output_dir && ! -l $snapshot_output_dir) {
			fatal("Variable 'SUBSET_OF' is defined, but a component folder '$snapshot_output_dir' is not a symbolic link of parent component folder: '$parent_snapshot_output_dir':");
		}
		if ( readlink($snapshot_output_dir) ne $parent_snapshot_output_dir ) {
			info("Variable 'SUBSET_OF' is defined, but a symbolic link is not pointing to the parent component folder '$parent_snapshot_output_dir'. Current situation: '$snapshot_output_dir' -> '" . readlink($snapshot_output_dir) . "' ");
			execute_command("rm -f '$snapshot_output_dir'");
			# create a symbolic link
			if (symlink($parent_snapshot_output_dir, $snapshot_output_dir) == 1 ) {
				info("Symbolic link created: '$snapshot_output_dir' -> '$parent_snapshot_output_dir'");
			} else {
				fatal("Cannot create symbolic link of parent folder '$snapshot_output_dir' -> '$parent_snapshot_output_dir': $!");
			}
		}
	}

	info("END");
}
else
{
	# REAL FOLDER - nothing to do - exit
	print "It's not a subset. Symbolick links will not be created. Exit..";
}

close LOG;

# ==================================================
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

sub read_components_list { 
	%COMPONENTS = ();
	open(FILE1, "<", $COMPONENTS_CONFIG_FILE);
	while ( chomp($a = <FILE1>) ) {
		if ( $a =~ /\#/ || $a eq "" ) {
			next;
		}
		($component, $params) = split(";", $a);
		$COMPONENTS{$component} = $params;
	}
	close FILE1;
}

# ==================================================
sub execute_command {
	my $command = @_[0];
	info("COMMAND: $command");
	my $ret = `$command 2<&1`;
	info("RESPONSE: $ret");
	return $ret;
}

sub info {
	if (@_[0] ne "") {
		chomp($time = `date +%Y-%m-%d-%H-%M-%S`);
		print LOG "$time: @_\n";
	}
}

sub fatal
{
	info("FATAL: @_");
	
	# send e-mail to admin that fatal error occurred
	$message = "Fatal error occurred while collecting '$STATISTICS' statistics\r\n\r\nMessage:\r\n@_";
	$subject = "Fatal error";
	$command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
	execute_command($command);

	close LOG;
	die "@_";
}

# ==================================================


