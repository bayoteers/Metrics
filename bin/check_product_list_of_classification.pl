#!/usr/bin/perl
use POSIX qw(strftime);

#==========================================================================
# BAM (Bugzilla Automated Metrics): check_product_list_of_classification.pl
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

# check_product_list_of_classification.pl
# Check whether the product list of choosen classification is up to date.
# Report differences.
# Optional: update config file.

$help = <<'!END!';
usage: check_product_list_of_classification.pl <config file> [<send email on success> [<recreate config file>] ]
	<config file> - config file - full path
	<send email on success> - if 'false' (default) than e-mail will be sent only when problems will be found
			if 'true' than e-mail will be sent also when not problems will be found
	<recreate config file> - if 'false' (default) then script checking if data in product config file matches to data fetched from Bugzilla.
			if 'true' then script is not checking product config file bug recreating (creating) it - USE IT CAREFULLY!

This script checks whether the product list of choosen classification is up to date (or create such a list).

!END!

if ($#ARGV < 0) {
    print "ERROR: at least one parameter is needed\n$help";
    exit -1;
}

if ($ARGV[0] eq "-h" || $ARGV[0] eq "--help") {
    print "\n$help";
    exit -1;
}

$CONFIG_FILE = $ARGV[0];
die "Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

$SENT_EMAIL_ON_SUCCESS = 0;
if ($#ARGV > 0 && $ARGV[1] eq "true") {
    $SENT_EMAIL_ON_SUCCESS = 1;
}

$UPDATE_CONFIG_FILE = 0;
if ($#ARGV > 1 && $ARGV[2] eq "true") {
    $UPDATE_CONFIG_FILE = 1;
}

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$LOG_FILE           = read_config_entry("LOG_FILE");
open(LOG, ">>", $LOG_FILE);
info("======================================================================================");
info("START");

$BUGZILLA_URL            = read_config_entry("BUGZILLA_URL");
$BUGZILLA_CLASSIFICATION = read_config_entry("BUGZILLA_CLASSIFICATION");
$PRODUCTS_CONFIG_FILE    = read_config_entry("PRODUCTS_CONFIG_FILE");
if ($UPDATE_CONFIG_FILE == 0) {
    die "File with list of products does not exists: $PRODUCTS_CONFIG_FILE - exit." unless (-e $PRODUCTS_CONFIG_FILE);
}
$ADMIN_MAIL      = read_config_entry("ADMIN_MAIL");
$STATISTICS_PATH = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS");

$TMP_DIR = "/tmp/classification_tmp_" . rand_str(20);
if (-e $TMP_DIR) {
    $TMP_DIR .= rand_str(20);
}
if (!mkdir $TMP_DIR) {
    fatal("Cannot create temporary folder '$TMP_DIR': $!");
}

info("Checking whether the products' list of classification '$BUGZILLA_CLASSIFICATION' is up to date.");

$errors                   = "";
$new_elements             = "";
$old_elements             = "";
$outdated_folders         = "";
$not_matching_definitions = "";
%PRODUCTS_OLD             = {};
%PRODUCTS_NEW             = {};
@EXISTING_FOLDERS         = {};

if ($UPDATE_CONFIG_FILE == 0) {
    # -------------------------------
    # check config file mode

    read_existing_folders_list();
    read_old_products_list();
    read_new_products_list();
    check_currently_configured_products();
    search_for_new_products();
    search_for_outdated_folders();

    # check if errors occurred
    if ($old_elements ne "" || $new_elements ne "") {
        $message =
            "There are differences between products defined in the config file and products defined in Bugzilla:\n"
          . "- classification '$BUGZILLA_CLASSIFICATION'\n"
          . "- products config file: '$PRODUCTS_CONFIG_FILE'\n"
          . "Please update products config file!\n";
        if ($new_elements ne "") {
            $message .= "\nThere are NEW products - ADD to the products config file following lines:\n$new_elements";
        }
        if ($old_elements ne "") {
            $message .= "\nThere are OLD products - REMOVE from the products config file following lines:\n$old_elements";
        }
        if ($outdated_folders ne "") {
            $message .= "\nThere are OUTDATED FOLDERS in '$STATISTICS_PATH' - REMOVE them if they are not needed:\n$outdated_folders";
        }
        if ($not_matching_definitions ne "") {
            $message .= "\nDefinitions automatically generated don't match to defined in the config file - VERIFY them:\n$not_matching_definitions";
        }
        fatal($message);
    }
    elsif ($outdated_folders ne "" || $not_matching_definitions ne "") {
        $message =
            "There are inconsistencies found in the products config file:\n"
          . "- classification '$BUGZILLA_CLASSIFICATION'\n"
          . "- products config file: '$PRODUCTS_CONFIG_FILE'\n"
          . "Please review these issues!\n";
        if ($outdated_folders ne "") {
            $message .= "\nThere are OUTDATED FOLDERS in '$STATISTICS_PATH' - REMOVE them if they are not needed:\n$outdated_folders";
        }
        if ($not_matching_definitions ne "") {
            $message .= "\nDefinitions automatically generated don't match to defined in the config file - VERIFY them:\n$not_matching_definitions";
        }
        $subject = "$BUGZILLA_CLASSIFICATION - inconsistencies found in the products config file";
        $command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
        print "\n$message\n\n";
        execute_command($command);
    }
    else {
        info("No errors in products configuration file for the classification '$BUGZILLA_CLASSIFICATION' have been found.");
        if ($SENT_EMAIL_ON_SUCCESS == 1) {
            print "No errors in products configuration file for the classification '$BUGZILLA_CLASSIFICATION' have been found.\n\n";
            # send e-mail to admin that no errors has been found
            $message = "No errors in products configuration file for the classification '$BUGZILLA_CLASSIFICATION' have been found.\r\n";
            $subject = "$BUGZILLA_CLASSIFICATION - products configuration is up-to-date";
            $command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
            execute_command($command);
        }
    }
}
else {
    # -------------------------------
    # update config file mode

    read_new_products_list();
    update_config_file();

    if ($errors ne "") {
        $message =
            "Error occured while updating products configuration file:\n"
          . "- classification '$BUGZILLA_CLASSIFICATION'\n"
          . "- products config file: '$PRODUCTS_CONFIG_FILE'\n"
          . "Please review these issues!\n\n$errors";
        fatal($message);
    }
    else {
        info("Products configuration file for the classification '$BUGZILLA_CLASSIFICATION' has been updated.");
        if ($SENT_EMAIL_ON_SUCCESS == 1) {
            print "Products configuration file for the classification '$BUGZILLA_CLASSIFICATION' has been updated.\n\n";
            # send e-mail to admin that no errors has been found
            $message = "Products configuration file for the classification '$BUGZILLA_CLASSIFICATION' has been updated.\r\n";
            $subject = "$BUGZILLA_CLASSIFICATION - products configuration is up-to-date";
            $command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
            execute_command($command);
        }
    }
}

info("END");
close LOG;

# ==================================================
# read list of folders from $STATISTICS_PATH
# ignore "all" folder
sub read_existing_folders_list {
    @EXISTING_FOLDERS = ();
    if (opendir(DIR, "$STATISTICS_PATH")) {
        while (($f = readdir(DIR))) {
            if ($f eq "all" || $f eq "bugs_with_dependencies" || $f =~ /^\.+$/) {
                next;
            }
            push(@EXISTING_FOLDERS, $f);
        }
        closedir DIR;
    }
}

# the list of products from $PRODUCTS_CONFIG_FILE and store the list in %PRODUCTS_OLD hash table
sub read_old_products_list {
    %PRODUCTS_OLD = ();
    open(FILE1, "<", $PRODUCTS_CONFIG_FILE);
    while (chomp($a = <FILE1>)) {
        if ($a =~ /\#/ || $a eq "") {
            next;
        }
        ($product_folder, $params)  = split(";",        $a);
        ($x,              $product) = split("product=", $params);
        $PRODUCTS_OLD{$product_folder} = $a;
    }
    close FILE1;
}

sub read_new_products_list {
    # fetch the products' list from Bugzilla
    $PRODUCTS_NEW_file = "$TMP_DIR/PRODUCTS_NEW.html";
    info("Get list of products of classification $BUGZILLA_CLASSIFICATION, save it in $PRODUCTS_NEW_file");
    execute_command("wget --no-check-certificate -O $PRODUCTS_NEW_file \"$BUGZILLA_URL\"");
    # check if file has been fetched
    if (!-e "$PRODUCTS_NEW_file") {
        fatal("Cannot find file '$PRODUCTS_NEW_file' - the list of products has not been fetched from Bugzilla..");

    }
    # check if file is not empty - there should be at least 1 line
    if (-z "$PRODUCTS_NEW_file") {
        execute_command("rm -f '$PRODUCTS_NEW_file'");
        fatal("products list file is empty '$PRODUCTS_NEW_file' - the list has not been fetched correctly from Bugzilla.. - removing the file");
    }

    # parse the output and store the list of products found in %PRODUCTS_NEW hash table
    $classification_found = 0;
    %PRODUCTS_NEW         = ();
    open(INFILE, "<", $PRODUCTS_NEW_file);
    while (chomp($line = <INFILE>)) {
        if ($line =~ /<optgroup label="$BUGZILLA_CLASSIFICATION">/) {
            $classification_found = 1;
            #print "classification_found - line: '$line'\n";
        }
        elsif ($classification_found == 1 && $line =~ /<\/optgroup/) {
            # end of the group - exit
            #print "end of the group - exit\n";
            last;
        }
        elsif ($classification_found == 1 && $line =~ /<option/) {
            @c = split("\"", $line);
            $product = $c[1];
            $product =~ s/ /%20/g;
            $product =~ s/!/%21/g;
            $product =~ s/\(/%28/g;
            $product =~ s/\)/%29/g;
            $product =~ s/\//%2F/g;
            $product =~ s/&amp;/%26/g;
            $product_folder = $c[1];
            $product_folder =~ s/ /_/g;
            $product_folder =~ s/!//g;
            $product_folder =~ s/\(//g;
            $product_folder =~ s/\)//g;
            $product_folder =~ s/\//and/g;
            $product_folder =~ s/&amp;/and/g;

            #print "-- '$product'\n";
            $PRODUCTS_NEW{$product_folder} = "$product_folder;product=$product";
        }
    }
}

sub check_currently_configured_products() {
    for $product (sort (keys %PRODUCTS_OLD)) {
        if (!exists $PRODUCTS_NEW{$product}) {
            $old_elements .= $PRODUCTS_OLD{$product} . "\n";
        }
    }
}

sub search_for_new_products() {
    for $product (sort(keys %PRODUCTS_NEW)) {
        if (!exists $PRODUCTS_OLD{$product}) {
            $new_elements .= $PRODUCTS_NEW{$product} . "\n";
        }
        elsif ($PRODUCTS_OLD{$product} ne $PRODUCTS_NEW{$product}) {
            $not_matching_definitions .= "$product:\n\tCONF FILE: '" . $PRODUCTS_OLD{$product} . "'\n\tGENERATED: '" . $PRODUCTS_NEW{$product} . "'\n";
        }
    }
}

sub search_for_outdated_folders() {
    foreach $folder (@EXISTING_FOLDERS) {
        if (!exists $PRODUCTS_NEW{$folder}) {
            $outdated_folders .= "$folder\n";
        }
    }
}

# ==================================================

sub update_config_file() {
    $new_elements = "";
    for $product (sort(keys %PRODUCTS_NEW)) {
        $new_elements .= $PRODUCTS_NEW{$product} . "\n";
    }

    # create a backup of old file
    if (-e "$PRODUCTS_CONFIG_FILE") {
        execute_command("cp $PRODUCTS_CONFIG_FILE $PRODUCTS_CONFIG_FILE.bak");
        die "ERROR: Cannot create backup file: $PRODUCTS_CONFIG_FILE.bak - exit." unless (-e "$PRODUCTS_CONFIG_FILE.bak");
    }
    if (open(FILE, ">", "$PRODUCTS_CONFIG_FILE")) {
        print FILE $new_elements;
        close FILE;
    }
    else {
        $errors .= "ERROR: can't open a file for writing: $PRODUCTS_CONFIG_FILE";
    }
}

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
    }
    elsif ($ret_common ne "") {
        return $ret_common;
    }
    elsif (@_[1] ne "can be empty") {
        fatal("config entry " . @_[0] . " not found or it's value is empty. Please fix config file: $CONFIG_FILE");
    }
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

sub fatal {
    info("FATAL: @_");

    # send e-mail to admin that fatal error occurred
    $message = "Fatal error occurred:\r\n\r\nMessage:\r\n@_";
    $subject = "$BUGZILLA_CLASSIFICATION - Problem with the list of products";
    $command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
    execute_command($command);

    close LOG;
    die "@_";
}

# ==================================================
sub rand_str {
    my $length_of_randomstring = shift;    # the length of the random string to generate

    my @chars = ('a' .. 'z', 'A' .. 'Z', '0' .. '9', '_');
    my $random_string;
    foreach (1 .. $length_of_randomstring) {
        # rand @chars will generate a random
        # number between 0 and scalar @chars
        $random_string .= $chars[ rand @chars ];
    }
    return $random_string;
}

# ==================================================

