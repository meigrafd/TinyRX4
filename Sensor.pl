#!/usr/bin/perl -w
# Reads data from serial port and posts to PHP

# Run: sudo apt-get install libdevice-serialport-perl
# if you get "Can't locate device/SerialPort.pm in @INC (@INC includes ..."

# use lib '/usr/lib/perl5/Device'
# sudo apt-get install libwww-mechanize-perl

#To install Proc:Daemon
#perl -MCPAN -e 'install Proc::Daemon' OR sudo apt-get install libproc-daemon-perl 


#### CONFIG - START

# Secret Key
my $KEY = '23338d373027ce83b1f81b9e9563b629';

# set url to add.php
my $url = "http://127.0.0.1/Sensor/add.php?key=". $KEY ."&node=";

# set UART baudrate
my $baudrate = 9600;

my $PORT = "/dev/ttyAMA0";

#### CONFIG - END

# Declare the subroutines
sub trim($);

BEGIN {
	push @INC,"/usr/lib/perl5/";
}

use strict;
use Device::SerialPort qw( :PARAM :STAT 0.07 );
use WWW::Mechanize;
use Time::localtime;
use Scalar::Util 'looks_like_number'; 
use Proc::Daemon;

print "Serial to PHP gateway for RaspberryPi with RFM12B\r\n";

my $ob = Device::SerialPort->new($PORT);
$ob->baudrate($baudrate); 
$ob->parity("none"); 
$ob->databits(8); 
$ob->stopbits(1); 
#$ob->handshake("xoff"); 
$ob->write_settings;

open(SERIAL, "+>$PORT");

my $continue = 1;
$SIG{TERM} = sub { $continue = 0 };

while ($continue) {
	my $line = trim(<SERIAL>);
	print $line; print "\r\n";
	my @values = split(' ', $line);
	if(looks_like_number($values[0]) && $values[0] >=1) {
		post2php($values[0],$values[1]);
		sleep(2);
	}
}

sub post2php {
	my $ua = WWW::Mechanize->new();
	my $URL = $url . $_[0] ."&" . $_[1];
	#my $url = "http://127.0.0.1/Sensor/add.php?key=23338d373027ce83b1f81b9e9563b629&node=" . $_[0] ."&" . $_[1];
	#print $url; print "\r\n";
	my $response = $ua->get($URL);
	if ($response->is_success) {
		#print "Success!\n";
		my $c = $ua->content;
		print ("$c");
	} else {
		print "Failed to open url!";
		#die $response->status_line;
	}
}


# Perl trim function to remove whitespace from the start and end of the string
sub trim($) {
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}
#
